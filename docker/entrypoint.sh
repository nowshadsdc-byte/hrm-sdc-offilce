#!/bin/sh
set -e

# Only the php-fpm (app) container owns migrations and cache warmup.
# The queue/scheduler containers run the same image with a different CMD
# and just need to exec straight into it.
if [ "$1" = "php-fpm" ]; then
    php artisan config:clear

    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        php artisan migrate --force
    fi

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Readiness marker for the compose healthcheck, so queue/scheduler/nginx
    # wait until migrations + cache warmup actually finish, not just process start.
    touch storage/framework/ready
fi

exec "$@"
