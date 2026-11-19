#!/bin/bash
set -e

# Ensure storage directories exist
mkdir -p /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/framework/cache/data \
         /app/storage/logs \
         /app/storage/app/public/change_requests

# Set correct permissions for www-data
chown -R www-data:www-data /app/storage /app/bootstrap/cache || true
chmod -R 775 /app/storage /app/bootstrap/cache || true

# Ensure storage symlink exists
if [ ! -L /app/public/storage ]; then
    echo "Creating public/storage symlink..."
    php artisan storage:link --force || true
fi

# Run migration if requested
if [ "${DB_AUTO_MIGRATE:-false}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || true
fi

# Optimize cache for production
if [ "${APP_ENV:-production}" = "production" ] && [ -n "$APP_KEY" ]; then
    echo "Optimizing configuration, routes, and views cache..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

exec "$@"
