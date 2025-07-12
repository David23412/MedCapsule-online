#!/bin/bash

# MedCapsule - Ngrok URL Update Script
echo "🌐 Updating MedCapsule ngrok URL configuration..."

# Check if URL is provided
if [ -z "$1" ]; then
    echo "❌ Please provide the new ngrok URL as an argument"
    echo "   Usage: ./update-ngrok-url.sh https://your-new-url.ngrok-free.app"
    exit 1
fi

NEW_URL="$1"

# Validate URL format
if [[ ! $NEW_URL =~ ^https://.*\.ngrok-free\.app$ ]]; then
    echo "⚠️  Warning: URL doesn't look like a standard ngrok-free.app URL"
    echo "    Expected format: https://abc123.ngrok-free.app"
    read -p "🤔 Continue anyway? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Backup current .env
echo "📄 Creating backup of current .env..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update APP_URL
echo "🔧 Updating APP_URL..."
sed -i.bak "s|APP_URL=.*|APP_URL=$NEW_URL|g" .env

# Update ASSET_URL
echo "🔧 Updating ASSET_URL..."
sed -i.bak "s|ASSET_URL=.*|ASSET_URL=$NEW_URL|g" .env

# Clear caches (if PHP is available)
if command -v php &> /dev/null; then
    echo "🧹 Clearing Laravel caches..."
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
else
    echo "⚠️  PHP not available - please clear caches manually:"
    echo "   php artisan config:clear"
    echo "   php artisan cache:clear"
fi

echo "✅ Ngrok URL updated successfully!"
echo "🌐 New URL: $NEW_URL"
echo "📝 Backup created: .env.backup.*"
echo ""
echo "🔧 Test your application at: $NEW_URL"