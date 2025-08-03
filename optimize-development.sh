#!/bin/bash

# Development Performance Optimization Script
# This script applies development-specific optimizations

echo "🚀 Applying development performance optimizations..."

# Enable debug mode but optimize for development
echo "🔧 Optimizing for development..."
php artisan config:cache
php artisan route:cache

# Warm up cache with development data
echo "🔥 Warming up development cache..."
php artisan cache:warm

# Install development dependencies
echo "📦 Installing development dependencies..."
composer install

echo "🏆 Development optimizations complete!"