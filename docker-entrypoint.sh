#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────────────
# MedCapsule Docker Entrypoint
# Runs one-time startup tasks before handing off to Apache.
# ─────────────────────────────────────────────────────────────────────────────

echo "==> Checking APP_KEY..."
if [ -z "$APP_KEY" ]; then
    echo "    Generating new APP_KEY..."
    php artisan key:generate --force
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
