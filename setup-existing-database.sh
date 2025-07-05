#!/bin/bash

# MedCapsule - Setup with Existing Database
echo "🗄️ Setting up MedCapsule with your existing 'medcapsule' database..."

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
    print_info ".env file exists, updating configuration..."
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

# Configure for existing database
print_info "Configuring for your existing 'medcapsule' database..."

# Get database connection details
echo ""
echo "📊 Please provide your database connection details:"
echo "   (Press Enter to use default values in brackets)"
echo ""

read -p "🏠 Database Host [127.0.0.1]: " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}

read -p "🔌 Database Port [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

read -p "👤 Database Username [root]: " DB_USERNAME
DB_USERNAME=${DB_USERNAME:-root}

read -s -p "🔒 Database Password [leave empty]: " DB_PASSWORD
echo ""

read -p "🗄️ Database Name [medcapsule]: " DB_NAME
DB_NAME=${DB_NAME:-medcapsule}

print_info "Updating .env configuration..."

# Update .env for existing database
sed -i.bak "s|APP_ENV=.*|APP_ENV=local|g" .env
sed -i.bak "s|APP_DEBUG=.*|APP_DEBUG=true|g" .env
sed -i.bak "s|APP_URL=.*|APP_URL=http://localhost:8000|g" .env

# Database configuration
sed -i.bak "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|g" .env
sed -i.bak "s|DB_HOST=.*|DB_HOST=$DB_HOST|g" .env
sed -i.bak "s|DB_PORT=.*|DB_PORT=$DB_PORT|g" .env
sed -i.bak "s|DB_DATABASE=.*|DB_DATABASE=$DB_NAME|g" .env
sed -i.bak "s|DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|g" .env
sed -i.bak "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|g" .env

# Environment flags
sed -i.bak "s|FORCE_HTTPS=.*|FORCE_HTTPS=false|g" .env
sed -i.bak "s|SECURE_COOKIES=.*|SECURE_COOKIES=false|g" .env
sed -i.bak "s|IS_PRODUCTION=.*|IS_PRODUCTION=false|g" .env

print_info "Testing database connection..."

# Test database connection
php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo 'Database connection: SUCCESS\n';
} catch (Exception \$e) {
    echo 'Database connection: FAILED\n';
    echo 'Error: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

if [ $? -ne 0 ]; then
    print_error "Database connection failed!"
    print_info "Please check your database credentials and try again."
    print_info "Make sure your MySQL/MariaDB server is running."
    exit 1
fi

print_info "Checking database tables..."

# Check if tables exist
TABLE_COUNT=$(php artisan tinker --execute="
try {
    \$count = DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = \"$DB_NAME\"')[0]->count;
    echo \$count;
} catch (Exception \$e) {
    echo '0';
}
")

print_info "Found $TABLE_COUNT tables in your database"

if [ "$TABLE_COUNT" -gt 0 ]; then
    print_status "Your database has existing tables!"
    
    # Check for specific MedCapsule tables
    print_info "Checking for MedCapsule core tables..."
    
    CORE_TABLES=("users" "courses" "topics" "questions" "quiz_attempts")
    MISSING_TABLES=()
    
    for table in "${CORE_TABLES[@]}"; do
        EXISTS=$(php artisan tinker --execute="
        try {
            DB::select('SELECT 1 FROM $table LIMIT 1');
            echo '1';
        } catch (Exception \$e) {
            echo '0';
        }
        ")
        
        if [ "$EXISTS" = "1" ]; then
            print_status "$table table exists"
        else
            print_warning "$table table missing"
            MISSING_TABLES+=("$table")
        fi
    done
    
    if [ ${#MISSING_TABLES[@]} -gt 0 ]; then
        echo ""
        print_warning "Some core tables are missing. Would you like to run migrations?"
        read -p "   Run migrations? (y/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_info "Running migrations..."
            php artisan migrate --force
        fi
    fi
else
    print_warning "Database is empty. Running migrations to create tables..."
    php artisan migrate --force
fi

# Clear caches
print_info "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Kill any existing server on port 8000
PORT=8000
if lsof -i :$PORT | grep LISTEN; then
    print_warning "Killing existing server on port $PORT..."
    fuser -k $PORT/tcp 2>/dev/null || kill $(lsof -t -i:$PORT) 2>/dev/null
    sleep 1
fi

print_status "Setup complete!"
print_info "Your MedCapsule is now connected to your 'medcapsule' database"
print_info "Start the server with: php artisan serve"
print_info "Check status with: php artisan env:status"

echo ""
read -p "🤔 Do you want to start the server now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_info "Starting server on http://localhost:8000..."
    php artisan serve
fi