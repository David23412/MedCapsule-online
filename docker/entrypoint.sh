#!/bin/sh
set -e

mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/logs \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/bootstrap/cache

# Storage links must resolve inside this container, including after a volume is mounted.
if [ -L /var/www/html/public/storage ]; then
    rm /var/www/html/public/storage
fi
if [ ! -e /var/www/html/public/storage ]; then
    ln -s ../storage/app/public /var/www/html/public/storage
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

export -p > /etc/container_environment.sh
chmod 0644 /etc/container_environment.sh

exec "$@"
