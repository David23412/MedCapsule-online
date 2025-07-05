#!/bin/bash

# MedCapsule - Local Development Setup Script
echo "🏠 Setting up MedCapsule for LOCAL development..."

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

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    print_info "Creating .env file from .env.example..."
    cp .env.example .env
else
    print_info ".env file already exists, updating configuration..."
fi

# Fix cache driver issues immediately
print_info "Fixing cache configuration..."
if ! grep -qE '^CACHE_DRIVER=(database|file)' .env; then
    sed -i.bak 's/^CACHE_DRIVER=.*/CACHE_DRIVER=file/' .env || echo 'CACHE_DRIVER=file' >> .env
    print_status "Set CACHE_DRIVER=file"
fi

if ! grep -q "'default' => env('CACHE_DRIVER', 'file')" config/cache.php; then
    sed -i.bak "s/'default' => env('CACHE_STORE', 'redis')/'default' => env('CACHE_DRIVER', 'file')/" config/cache.php
    print_status "Fixed config/cache.php"
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    print_info "Generating application key..."
    php artisan key:generate
fi

# Update environment variables for local development
print_info "Configuring environment variables..."

# Update .env for local development
sed -i.bak "s|APP_ENV=.*|APP_ENV=local|g" .env
sed -i.bak "s|APP_DEBUG=.*|APP_DEBUG=true|g" .env
sed -i.bak "s|APP_URL=.*|APP_URL=http://localhost:8000|g" .env
sed -i.bak "s|FORCE_HTTPS=.*|FORCE_HTTPS=false|g" .env
sed -i.bak "s|SECURE_COOKIES=.*|SECURE_COOKIES=false|g" .env
sed -i.bak "s|SESSION_SECURE=.*|SESSION_SECURE=false|g" .env
sed -i.bak "s|IS_PRODUCTION=.*|IS_PRODUCTION=false|g" .env

# Test database connection
print_info "Testing database connection..."
if php artisan migrate:status >/dev/null 2>&1; then
    print_status "Database connection successful"
else
    print_warning "Database connection failed - setting up SQLite..."
# Set up database
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
        print_status "Created SQLite database file"
    fi
    
    # Switch to SQLite
    sed -i.bak 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
    sed -i.bak 's/DB_DATABASE=medcapsule/DB_DATABASE=database\/database.sqlite/' .env
    print_status "Switched to SQLite"
fi

# Clear caches
print_info "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check for required tables
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
else
    print_status "All required tables exist"
fi

# Kill any existing server on port 8000
PORT=8000
if lsof -i :$PORT | grep LISTEN; then
    print_warning "Killing existing server on port $PORT..."
    fuser -k $PORT/tcp 2>/dev/null || kill $(lsof -t -i:$PORT) 2>/dev/null
    sleep 1
fi

print_status "Local development environment configured!"
print_info "Starting local server on http://localhost:8000"

# Optional: Start the server automatically
read -p "🤔 Do you want to start the server now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan serve
fi