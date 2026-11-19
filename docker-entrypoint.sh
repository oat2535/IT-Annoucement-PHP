#!/bin/bash
set -e

# Ensure storage directories exist
mkdir -p /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/framework/cache/data \
         /app/storage/logs \
         /app/storage/app/public/change_requests

# Sync seed files into mounted media directory if missing
if [ -d /app/storage_seed/change_requests ]; then
    echo "Syncing initial change_requests files to /app/storage/app/public..."
    cp -rn /app/storage_seed/change_requests/* /app/storage/app/public/change_requests/ 2>/dev/null || true
fi

# Set correct permissions for www-data and current user
chown -R www-data:www-data /app/storage /app/bootstrap/cache || true
chmod -R 775 /app/storage /app/bootstrap/cache || true

# Ensure storage symlink exists
if [ ! -L /app/public/storage ]; then
    echo "Creating public/storage symlink..."
    php artisan storage:link --force || true
fi

# Clear stale build-time or previous container caches to respect runtime ENV
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run migration if requested
if [ "${DB_AUTO_MIGRATE:-false}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || true
fi

# Optimize cache if explicitly enabled
if [ "${APP_OPTIMIZE:-false}" = "true" ] && [ -n "$APP_KEY" ]; then
    echo "Optimizing configuration, routes, and views cache..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

exec "$@"
