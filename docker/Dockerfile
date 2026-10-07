# =============================================================================
# Stage 1: Frontend Build (Node.js 20 Alpine)
# Builds Vite, Vue 3, Inertia.js, and Tailwind CSS assets
# =============================================================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy dependency manifests for efficient Docker layer caching
COPY package.json package-lock.json ./

RUN npm ci --prefer-offline --no-audit

# Copy application source needed for frontend compilation
COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

# Compile production bundle
RUN npm run build


# =============================================================================
# Stage 2: PHP Vendor Dependencies (Composer 2)
# Installs optimized production PHP dependencies
# =============================================================================
FROM composer:2 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts


# =============================================================================
# Stage 3: Production Runtime (PHP 8.3-FPM Alpine)
# Security hardened, optimized PHP-FPM environment with Supervisor
# =============================================================================
FROM php:8.3-fpm-alpine AS production

# Install system utilities, Ghostscript and Poppler for PDF magazine processing
RUN apk add --no-cache \
    supervisor \
    curl \
    bash \
    shadow \
    ghostscript \
    poppler-utils \
    imagemagick

# Install PHP extensions using official docker-php-extension-installer
COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    redis \
    gd \
    imagick \
    exif \
    zip \
    opcache \
    pcntl \
    bcmath \
    intl

# Set working directory
WORKDIR /var/www/html

# Copy production PHP and Supervisor configurations
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy application source code
COPY . /var/www/html

# Copy pre-built production dependencies and assets from previous stages
COPY --from=composer-builder /app/vendor /var/www/html/vendor
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

# Setup storage, cache, and logging directories with www-data permissions
RUN mkdir -p /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/app/public \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
             /var/log/supervisor \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/log/supervisor \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose PHP-FPM FastCGI port
EXPOSE 9000

# Healthcheck to verify PHP-FPM engine responsiveness
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD cgi-fcgi -bind -connect 127.0.0.1:9000 || php-fpm -t || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord"]
