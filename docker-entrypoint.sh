#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────────────
# MedCapsule Docker Entrypoint
# Runs one-time startup tasks before handing off to Apache.
# ─────────────────────────────────────────────────────────────────────────────

echo "==> Checking APP_KEY..."
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY environment variable is not set."
    echo "       Generate one with: php -r \"echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;\""
    echo "       Then set it as an environment variable on your hosting platform."
    exit 1
fi

echo "==> Running database migrations..."
php artisan migrate --force

echo "==> Caching configuration, routes and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Linking storage..."
php artisan storage:link --force 2>/dev/null || true

echo "==> Startup complete. Starting Apache..."
exec "$@"
