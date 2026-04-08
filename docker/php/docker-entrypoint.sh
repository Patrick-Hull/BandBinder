#!/bin/bash
set -e

cd /var/www/html

# Install dependencies if missing or out of date
if [ ! -d "vendor" ] || [ composer.lock -nt vendor ]; then
    echo "Installing/updating composer dependencies..."
    composer install --no-interaction --prefer-dist
else
    echo "Composer dependencies already up to date."
fi

# Ensure uploads directory exists and is writable by www-data
# (must run after the volume mount, so Dockerfile alone cannot do this)
mkdir -p /var/www/html/public/uploads/charts
chown -R www-data:www-data /var/www/html/public/uploads

# Wait for MySQL to be ready and the app user/database to exist before running migrations
echo "Waiting for database to be ready..."
until mysql -h "${DB_HOST:-db}" -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e "SELECT 1" >/dev/null 2>&1; do
    echo "  Database not ready yet, retrying in 2s..."
    sleep 2
done
echo "Database is ready."

# Run database migrations
echo "Running database migrations..."
php /var/www/html/db/migrate.php
echo "Migrations complete."

# Finally pass control to Apache/whatever CMD image has
exec "$@"