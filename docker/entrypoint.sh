#!/bin/sh
set -e

# =============================================================================
# Production Container Entrypoint - DigiMag Cultural Portal
# =============================================================================

echo "[ENTRYPOINT] Initializing container runtime environment..."

# 1. Ensure required storage and cache directories exist with strict ownership
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/app/public \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache \
         /var/log/supervisor

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/log/supervisor
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 2. Ensure public storage symlink exists
if [ ! -L /var/www/html/public/storage ]; then
    echo "[ENTRYPOINT] Creating storage symlink..."
    php /var/www/html/artisan storage:link --no-interaction || true
fi

# 3. If running default CMD, launch supervisord
if [ "$1" = "supervisord" ] || [ -z "$1" ]; then
    echo "[ENTRYPOINT] Launching Supervisor process manager..."
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi

# 4. Otherwise execute custom command passed to docker run
exec "$@"
