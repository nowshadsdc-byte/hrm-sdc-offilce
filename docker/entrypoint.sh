#!/bin/bash
set -e

cd /var/www/html

if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    for i in $(seq 1 30); do
        (php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null) && break
        sleep 2
    done
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || true
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
