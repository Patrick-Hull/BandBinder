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

# Finally pass control to Apache/whatever CMD image has
exec "$@"