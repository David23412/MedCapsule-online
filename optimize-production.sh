#!/bin/bash

# Production Performance Optimization Script
# This script applies production-specific optimizations

echo "🚀 Applying production performance optimizations..."

# Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize Laravel
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Warm up cache
echo "🔥 Warming up cache..."
php artisan cache:warm

# Optimize Composer autoloader
echo "📦 Optimizing Composer autoloader..."
composer install --optimize-autoloader --no-dev

# Set proper file permissions
echo "🔒 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Enable gzip compression (if not already enabled)
echo "📦 Checking gzip compression..."
if command -v nginx >/dev/null 2>&1; then
    echo "✅ Nginx detected - ensure gzip is enabled in configuration"
elif command -v apache2 >/dev/null 2>&1; then
    echo "✅ Apache detected - ensure mod_deflate is enabled"
fi

echo "🏆 Production optimizations complete!"