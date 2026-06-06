#!/bin/sh
set -e

# Ensure the SQLite database file exists in the persistent volume
if [ ! -f /data/database.sqlite ]; then
    echo "Creating production database.sqlite..."
    touch /data/database.sqlite
    chown www-data:www-data /data/database.sqlite
    chmod 664 /data/database.sqlite
fi

# Run migrations and seeders on first boot
echo "Running database migrations..."
php artisan migrate --force

# Seed the database if it is empty (no users present)
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -n 1)
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "Database is empty. Running seeders..."
    php artisan db:seed --force
fi

# Clear and optimize Laravel caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start supervisor to run Nginx and PHP-FPM
echo "Starting Nginx and PHP-FPM..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
