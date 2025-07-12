#!/bin/bash

# MedCapsule - Ngrok/HTTPS Setup Script
echo "🌐 Setting up MedCapsule for NGROK/HTTPS deployment..."

# Check if ngrok is installed
if ! command -v ngrok &> /dev/null; then
    echo "❌ ngrok is not installed. Please install ngrok first:"
    echo "   Visit: https://ngrok.com/download"
    exit 1
fi

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "📄 Creating .env file from .env.example..."
    cp .env.example .env
else
    echo "📄 .env file exists, updating configuration..."
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔐 Generating application key..."
    php artisan key:generate
fi

# Get ngrok URL from user
echo "🔧 Configuring environment variables..."
read -p "🌐 Enter your ngrok URL (e.g., https://abc123.ngrok-free.app): " NGROK_URL

if [ -z "$NGROK_URL" ]; then
    echo "❌ Ngrok URL is required. Exiting..."
    exit 1
fi

# Validate URL format - Updated to accept both .ngrok.io and .ngrok-free.app
if [[ ! $NGROK_URL =~ ^https://.*\.ngrok(-free)?\.app$ ]] && [[ ! $NGROK_URL =~ ^https://.*\.ngrok\.io$ ]]; then
    echo "⚠️  Warning: URL doesn't look like a standard ngrok URL"
    echo "    Expected formats: https://abc123.ngrok-free.app or https://abc123.ngrok.io"
    read -p "🤔 Continue anyway? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Update .env for ngrok/HTTPS - Fixed to use local environment for ngrok
echo "🔧 Updating environment configuration..."
sed -i.bak "s|APP_ENV=.*|APP_ENV=local|g" .env
sed -i.bak "s|APP_DEBUG=.*|APP_DEBUG=true|g" .env
sed -i.bak "s|APP_URL=.*|APP_URL=$NGROK_URL|g" .env
sed -i.bak "s|ASSET_URL=.*|ASSET_URL=$NGROK_URL|g" .env
sed -i.bak "s|FORCE_HTTPS=.*|FORCE_HTTPS=false|g" .env
sed -i.bak "s|SESSION_SECURE=.*|SESSION_SECURE=false|g" .env
sed -i.bak "s|SESSION_SAME_SITE=.*|SESSION_SAME_SITE=none|g" .env
sed -i.bak "s|SESSION_DOMAIN=.*|SESSION_DOMAIN=|g" .env
sed -i.bak "s|SESSION_DRIVER=.*|SESSION_DRIVER=database|g" .env
sed -i.bak "s|IS_PRODUCTION=.*|IS_PRODUCTION=false|g" .env

# Add ngrok-specific configurations if not present
if ! grep -q "NGROK_URL=" .env; then
    echo "NGROK_URL=$NGROK_URL" >> .env
fi

if ! grep -q "TRUSTED_PROXIES=" .env; then
    echo "TRUSTED_PROXIES=*" >> .env
fi

if ! grep -q "SESSION_HTTP_ONLY=" .env; then
    echo "SESSION_HTTP_ONLY=false" >> .env
fi

# Set up database
echo "🗄️ Setting up database..."
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    echo "📊 Created SQLite database file"
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
echo "🚀 Running migrations..."
php artisan migrate --force

# Start the local server
echo "✅ Ngrok/HTTPS environment configured!"
echo "🌐 Application URL: $NGROK_URL"
echo "📝 Your ngrok tunnel should be pointing to: localhost:8000"

# Display ngrok command
echo ""
echo "💡 If you haven't started ngrok yet, run this command in another terminal:"
echo "   ngrok http 8000"
echo ""
echo "🔧 Make sure to update your ngrok URL in the .env file if it changes!"

# Optional: Start the server automatically
read -p "🤔 Do you want to start the local server now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🚀 Starting server on localhost:8000..."
    echo "📱 Access via: $NGROK_URL"
    php artisan serve
fi