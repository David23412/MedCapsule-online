#!/bin/bash

echo "🚨 QUICK FIX - Getting MedCapsule running NOW!"
echo "=============================================="

# 1. Create .env file
if [ ! -f .env ]; then
    echo "📄 Creating .env file..."
    cp .env.example .env
    echo "✅ .env file created"
else
    echo "📄 .env file already exists"
fi

# 2. Generate app key
echo "🔐 Setting up application key..."
php artisan key:generate --force

# 3. Quick database setup for medcapsule database
echo "🗄️ Configuring for 'medcapsule' database..."

# Update .env with common medcapsule database settings
cat > .env << EOL
APP_NAME=MedCapsule
APP_ENV=local
APP_KEY=$(php artisan --no-ansi tinker --execute="echo config('app.key');")
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration - UPDATE THESE IF DIFFERENT
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medcapsule
DB_USERNAME=root
DB_PASSWORD=

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Cache Configuration
CACHE_DRIVER=database
CACHE_PREFIX=medcapsule_cache

# Environment Configuration
FORCE_HTTPS=false
SECURE_COOKIES=false
IS_PRODUCTION=false

# Other settings
BROADCAST_DRIVER=log
QUEUE_CONNECTION=sync
EOL

echo "✅ .env configured for medcapsule database"

# 4. Test database connection
echo "🧪 Testing database connection..."
php artisan migrate:status 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✅ Database connection successful!"
else
    echo "❌ Database connection failed!"
    echo ""
    echo "🔧 Please update these settings in your .env file:"
    echo "   DB_HOST=127.0.0.1      (your MySQL host)"
    echo "   DB_PORT=3306           (your MySQL port)" 
    echo "   DB_DATABASE=medcapsule (your database name)"
    echo "   DB_USERNAME=root       (your MySQL username)"
    echo "   DB_PASSWORD=           (your MySQL password)"
    echo ""
    echo "Then run: php artisan migrate:status"
    exit 1
fi

# 5. Clear caches
echo "🧹 Clearing caches..."
php artisan cache:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1

echo ""
echo "🎉 QUICK FIX COMPLETE!"
echo "======================"
echo "✅ .env file created and configured"
echo "✅ Application key generated"
echo "✅ Database connection configured"
echo "✅ Caches cleared"
echo ""
echo "🚀 NOW RUN: php artisan serve"
echo "📱 THEN VISIT: http://localhost:8000"
echo ""
echo "💡 If database connection fails, edit the DB_* settings in .env file"