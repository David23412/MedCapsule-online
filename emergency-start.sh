#!/bin/bash

echo "🚨 EMERGENCY START - MedCapsule"
echo "==============================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() { echo -e "${GREEN}✅${NC} $1"; }
print_warning() { echo -e "${YELLOW}⚠️${NC} $1"; }
print_error() { echo -e "${RED}❌${NC} $1"; }
print_info() { echo -e "${BLUE}ℹ️${NC} $1"; }

# 1. Fix cache driver issues immediately
print_info "Fixing cache configuration..."
if ! grep -qE '^CACHE_DRIVER=(database|file)' .env; then
    sed -i.bak 's/^CACHE_DRIVER=.*/CACHE_DRIVER=file/' .env || echo 'CACHE_DRIVER=file' >> .env
    print_status "Set CACHE_DRIVER=file"
fi

if ! grep -q "'default' => env('CACHE_DRIVER', 'file')" config/cache.php; then
    sed -i.bak "s/'default' => env('CACHE_STORE', 'redis')/'default' => env('CACHE_DRIVER', 'file')/" config/cache.php
    print_status "Fixed config/cache.php"
fi

# 2. Generate app key if needed
if grep -q "YOURAppKeyWillBeGeneratedByArtisan" .env; then
    print_info "Setting up app key..."
    APP_KEY="base64:$(openssl rand -base64 32)"
    sed -i.bak "s|APP_KEY=.*|APP_KEY=$APP_KEY|" .env
    print_status "App key set"
fi

# 3. Test database connection
print_info "Testing database connection..."
if php artisan migrate:status >/dev/null 2>&1; then
    print_status "Database connection successful"
else
    print_warning "Database connection failed - switching to SQLite"
    sed -i.bak 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
    sed -i.bak 's/DB_DATABASE=medcapsule/DB_DATABASE=database\/database.sqlite/' .env
    touch database/database.sqlite
    print_status "Switched to SQLite"
fi

# 4. Clear caches
print_info "Clearing caches..."
php artisan cache:clear >/dev/null 2>&1
php artisan config:clear >/dev/null 2>&1
php artisan route:clear >/dev/null 2>&1
php artisan view:clear >/dev/null 2>&1

# 5. Check for required tables
REQUIRED_TABLES=(users topics courses questions)
MISSING_TABLES=()
for table in "${REQUIRED_TABLES[@]}"; do
    if ! php artisan tinker --execute="DB::table('$table')->count();" >/dev/null 2>&1; then
        MISSING_TABLES+=("$table")
    fi
done

if [ ${#MISSING_TABLES[@]} -gt 0 ]; then
    print_warning "Missing tables: ${MISSING_TABLES[*]} - running migrations..."
    php artisan migrate --force
fi

# 6. Kill any existing server on port 8000
PORT=8000
if lsof -i :$PORT | grep LISTEN; then
    print_warning "Killing existing server on port $PORT..."
    fuser -k $PORT/tcp 2>/dev/null || kill $(lsof -t -i:$PORT) 2>/dev/null
    sleep 1
fi

# 7. Set permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null
print_status "Permissions set"

echo ""
echo "🚀 STARTING SERVER..."
echo "Visit: http://localhost:8000"
echo "Press Ctrl+C to stop"
echo ""

# Start server
php artisan serve --host=0.0.0.0 --port=8000