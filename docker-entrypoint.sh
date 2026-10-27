#!/bin/sh
set -e

# Render provides the public port via $PORT. Point Apache at it.
PORT="${PORT:-80}"

sed -i "s/^Listen .*/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf
sed -i "s/<VirtualHost _default_:[0-9]*>/<VirtualHost _default_:$PORT>/" /etc/apache2/sites-available/000-default.conf

# Auto-seed database if DB_HOST is configured in environment
if [ -n "$DB_HOST" ]; then
    echo "Attempting database auto-seed..."
    php /var/www/html/db/seed.php || echo "Database auto-seed skipped or failed, continuing container startup."
fi

exec apache2-foreground