# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: install PHP deps + build frontend assets
#
# The Vite Wayfinder plugin shells out to `php artisan wayfinder:generate`
# during `npm run build`, so PHP + vendor/ must be in place before the
# frontend build runs — hence one combined build stage.
# ---------------------------------------------------------------------------
FROM php:8.4-cli-alpine AS builder

RUN apk add --no-cache nodejs npm git unzip libzip-dev \
    && docker-php-ext-install zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Wayfinder's route/controller reflection just needs the app to boot, not a
# real database — a throwaway key/env is enough for the asset build step.
RUN cp .env.example .env \
    && php artisan key:generate --force \
    && npm run build \
    && rm .env

# ---------------------------------------------------------------------------
# Stage 2: runtime image (php-fpm + nginx + supervisor)
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS production

RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    oniguruma \
    && apk add --no-cache --virtual .build-deps \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache \
    && apk del .build-deps

WORKDIR /var/www/html

# php-fpm's default pool (www.conf) runs workers as the image's built-in
# www-data user — keep storage/bootstrap/cache writable by that same user
# rather than introducing a second UID that php-fpm doesn't actually run as.
COPY --from=builder /app /var/www/html

RUN mkdir -p storage/framework/{cache,sessions,testing,views} \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf", "-n"]
