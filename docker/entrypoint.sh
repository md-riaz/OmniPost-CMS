#!/bin/sh

# Exit on fail
set -e

# Wait for database to be ready (rudimentary check or use wait-for-it in production)
echo "Deploying application..."

# Enter app directory
cd /var/www

# Run migrations (careful in production auto-deploy, maybe make optional via env var)
# php artisan migrate --force

# Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

# Start FPM
exec php-fpm
