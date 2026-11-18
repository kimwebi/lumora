
#!/bin/sh

set -e

echo "Starting Lumora..."

echo "Running database migrations..."
php artisan migrate --force

echo "Creating admin account..."
php artisan db:seed --class=AdminUserSeeder --force

echo "Creating storage link..."
php artisan storage:link || true

echo "Starting Laravel queue worker..."

php artisan queue:work \
    --sleep=3 \
    --tries=3 \
    --timeout=120 \
    --max-time=3600 &

echo "Starting Apache..."
exec apache2-foreground
