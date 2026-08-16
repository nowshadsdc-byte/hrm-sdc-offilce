#!/bin/sh
set -e

# Only the php-fpm (php) container does setup; queue/scheduler run the same
# image with a different command and just need to exec straight into it.
if [ "$1" = "php-fpm" ]; then
    # Bind-mounted project may come from a fresh checkout with no vendor/.
    if [ ! -d vendor ]; then
        composer install --no-interaction --prefer-dist
    fi

    mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache

    php artisan config:clear
fi

exec "$@"
