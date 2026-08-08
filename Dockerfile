# syntax=docker/dockerfile:1

# ---------- Stage 1: shared PHP base ----------
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        bash git unzip curl \
        libpng-dev libzip-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
        icu-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip exif pcntl bcmath gd intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---------- Stage 2: build (composer + npm + vite) ----------
# Needs PHP *and* Node in the same filesystem because the Wayfinder Vite
# plugin shells out to `php artisan wayfinder:generate` during `vite build`.
FROM base AS build

RUN apk add --no-cache nodejs npm

COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Wayfinder's artisan boot needs a valid APP_KEY; this .env never leaves this stage.
RUN cp .env.example .env \
    && php artisan key:generate --ansi

RUN npm install --ignore-scripts \
    && npm run build

# ---------- Stage 3: runtime (php-fpm) ----------
FROM base AS runtime

COPY . .
COPY --from=build /var/www/html/vendor ./vendor
COPY --from=build /var/www/html/public/build ./public/build

RUN ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ---------- Stage 4: nginx ----------
FROM nginx:stable-alpine AS nginx

COPY docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=runtime /var/www/html/public /var/www/html/public

# ---------- Stage 5: dev (php-fpm over a live-mounted source tree) ----------
# No app code is copied here — docker-compose bind-mounts the project into
# /var/www/html so edits and `composer`/`artisan` commands hit real files.
FROM base AS dev

ARG WWWUSER=1000
ARG WWWGROUP=1000

# Re-map www-data to the host UID/GID (from .env WWWUSER/WWWGROUP) so files
# the container writes into the bind-mounted project stay owned by the host user.
RUN deluser www-data 2>/dev/null || true \
    && delgroup www-data 2>/dev/null || true \
    && addgroup -g "${WWWGROUP}" www-data \
    && adduser -D -G www-data -u "${WWWUSER}" -s /bin/sh www-data

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/entrypoint.dev.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ---------- Stage 6: node-dev (Vite dev server) ----------
# Needs PHP alongside Node because the Wayfinder Vite plugin shells out to
# `php artisan wayfinder:generate` on every dev-server start/rebuild.
FROM base AS node-dev

RUN apk add --no-cache nodejs npm
