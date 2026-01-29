#!/bin/sh

# Exit on fail
set -e

# Wait for database to be ready (rudimentary check or use wait-for-it in production)
echo "Deploying application..."

# Enter app directory
cd /var/www

# Run migrations (careful in production auto-deploy, maybe make optional via env var)
# php artisan migrate --force

# Clear and optimize application (avoiding config/route caching due to closures)
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Essential caches that support closures (if any)
php artisan event:cache

# Start FPM
exec php-fpm
