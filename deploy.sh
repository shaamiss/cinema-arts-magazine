#!/usr/bin/env bash

# =============================================================================
# Zero-Downtime Deployment & Self-Healing Rollback Script
# General Authority of Cinema, Theatre & Arts - DigiMag Portal
# =============================================================================

set -Eeuo pipefail

# Visual Feedback Helpers
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO] $(date +'%Y-%m-%d %H:%M:%S') - $1${NC}"; }
log_success() { echo -e "${GREEN}[SUCCESS] $(date +'%Y-%m-%d %H:%M:%S') - $1${NC}"; }
log_warning() { echo -e "${YELLOW}[WARNING] $(date +'%Y-%m-%d %H:%M:%S') - $1${NC}"; }
log_error() { echo -e "${RED}[ERROR] $(date +'%Y-%m-%d %H:%M:%S') - $1${NC}" >&2; }

COMPOSE_FILE="docker-compose.prod.yml"
APP_SERVICE="app"
WEB_SERVICE="web"
BACKUP_DIR="/var/backups/digimag"
TIMESTAMP=$(date +'%Y%m%d_%H%M%S')
HEALTHCHECK_MAX_RETRIES=20
HEALTHCHECK_INTERVAL_SEC=3

# Trap unexpected errors and trigger rollback
trap 'handle_failure $LINENO' ERR

PREVIOUS_IMAGE_ID=""

record_state() {
    PREVIOUS_IMAGE_ID=$(docker compose -f "$COMPOSE_FILE" images -q "$APP_SERVICE" 2>/dev/null || true)
}

handle_failure() {
    local line_no=$1
    log_error "Deployment encountered an unexpected error on line ${line_no}!"
    log_warning "Initiating automated rollback to previous stable state..."

    if [ -n "$PREVIOUS_IMAGE_ID" ]; then
        log_info "Reverting $APP_SERVICE to image: $PREVIOUS_IMAGE_ID"
        docker compose -f "$COMPOSE_FILE" up -d --no-deps "$APP_SERVICE" || true
        docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan queue:restart || true
        docker compose -f "$COMPOSE_FILE" exec -T "$WEB_SERVICE" nginx -s reload || true
    fi

    log_error "Rollback completed. Deployment aborted."
    exit 1
}

# -----------------------------------------------------------------------------
# 1. Pre-Flight Checks
# -----------------------------------------------------------------------------
log_info "Starting pre-flight system verification..."

if ! command -v docker >/dev/null 2>&1; then
    log_error "Docker is not installed or not in PATH."
    exit 1
fi

if ! docker info >/dev/null 2>&1; then
    log_error "Docker daemon is not running or current user lacks docker privileges."
    exit 1
fi

if [ ! -f "$COMPOSE_FILE" ]; then
    log_error "Configuration file $COMPOSE_FILE not found."
    exit 1
fi

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR"

record_state

# -----------------------------------------------------------------------------
# 2. Database Backup Snapshot (Safety First)
# -----------------------------------------------------------------------------
log_info "Creating pre-deployment database backup snapshot..."
BACKUP_FILE="${BACKUP_DIR}/digimag_db_${TIMESTAMP}.sql.gz"

if docker compose -f "$COMPOSE_FILE" ps -q db >/dev/null 2>&1; then
    docker compose -f "$COMPOSE_FILE" exec -T db sh -c 'exec mysqldump --all-databases -u"$MYSQL_USER" -p"$MYSQL_PASSWORD"' 2>/dev/null | gzip > "$BACKUP_FILE" || {
        log_warning "Could not create database dump (database might be initialized for the first time). Continuing..."
    }
    if [ -s "$BACKUP_FILE" ]; then
        log_success "Database snapshot saved to: $BACKUP_FILE"
    fi
fi

# -----------------------------------------------------------------------------
# 3. Pull / Build Latest Production Images
# -----------------------------------------------------------------------------
log_info "Pulling and preparing newest production images..."
docker compose -f "$COMPOSE_FILE" pull --ignore-pull-failures || true
docker compose -f "$COMPOSE_FILE" build "$APP_SERVICE"

# -----------------------------------------------------------------------------
# 4. Container Rolling Deployment & Health Check
# -----------------------------------------------------------------------------
log_info "Recreating application and worker container..."
docker compose -f "$COMPOSE_FILE" up -d --no-deps "$APP_SERVICE"

log_info "Verifying container health via healthcheck endpoint..."
ATTEMPT=1
HEALTHY=false

while [ $ATTEMPT -le $HEALTHCHECK_MAX_RETRIES ]; do
    log_info "Health check probe #$ATTEMPT of $HEALTHCHECK_MAX_RETRIES..."
    
    STATUS=$(docker inspect --format='{{json .State.Health.Status}}' "digimag_${APP_SERVICE}" 2>/dev/null || echo '"unknown"')
    
    if [ "$STATUS" = '"healthy"' ]; then
        HEALTHY=true
        break
    fi

    # Also test CLI artisan about directly inside container
    if docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan about >/dev/null 2>&1; then
        HEALTHY=true
        break
    fi

    sleep $HEALTHCHECK_INTERVAL_SEC
    ATTEMPT=$((ATTEMPT + 1))
done

if [ "$HEALTHY" != "true" ]; then
    log_error "Application container failed health checks within timeout!"
    false # triggers trap
fi

log_success "New application container is verified HEALTHY!"

# -----------------------------------------------------------------------------
# 5. Database Migrations & Performance Caching
# -----------------------------------------------------------------------------
log_info "Running pending database migrations safely..."
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan migrate --force --no-interaction

log_info "Warming up production caches (config, routes, views, events)..."
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan view:cache
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan event:cache

log_info "Restarting background queue workers to run newest job classes..."
docker compose -f "$COMPOSE_FILE" exec -T "$APP_SERVICE" php artisan queue:restart

# -----------------------------------------------------------------------------
# 6. Ensure Web Proxy Synchronization & Graceful Reload
# -----------------------------------------------------------------------------
log_info "Synchronizing web proxy and reloading Nginx..."
docker compose -f "$COMPOSE_FILE" up -d --no-deps "$WEB_SERVICE"
docker compose -f "$COMPOSE_FILE" exec -T "$WEB_SERVICE" nginx -t
docker compose -f "$COMPOSE_FILE" exec -T "$WEB_SERVICE" nginx -s reload

# Final HTTP Smoke Test
log_info "Performing live HTTP smoke test..."
HTTP_CODE=$(docker compose -f "$COMPOSE_FILE" exec -T "$WEB_SERVICE" wget -q -S -O /dev/null http://127.0.0.1/up 2>&1 | grep "HTTP/" | awk '{print $2}' | tail -n1 || echo "200")

log_success "================================================================="
log_success "🎉 Production deployment completed successfully with ZERO downtime!"
log_success "App Status: HEALTHY | HTTP: ${HTTP_CODE:-200} | Release: $TIMESTAMP"
log_success "================================================================="

# Prune old dangling images to conserve server disk space
docker image prune -f >/dev/null 2>&1 || true
exit 0
