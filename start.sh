
#!/bin/sh

set -e

echo "Starting Lumora deployment..."

echo "Running database migrations..."
php artisan migrate --force

echo "Creating admin account..."
php artisan db:seed --class=AdminUserSeeder --force

echo "Creating storage link..."
php artisan storage:link || true

echo "Starting Apache..."
exec apache2-foreground
