#!/bin/bash

# 🚀 FIX DUPLICATE REFERENCES SCRIPT
# This script fixes all the duplicate UltraConsolidated references

set -e

echo "🚀 FIXING DUPLICATE REFERENCES!"
echo "==============================="
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ This script must be run from the Laravel project root directory"
    exit 1
fi

print_info "Starting duplicate reference fixes..."

# Fix all the duplicate UltraConsolidated references
print_info "Fixing duplicate UltraConsolidated references..."

# Fix UltraConsolidatedUltraConsolidatedCacheService -> UltraConsolidatedCacheService
find app -name "*.php" -type f -exec sed -i '' 's/UltraConsolidatedUltraConsolidatedCacheService/UltraConsolidatedCacheService/g' {} \;
find tests -name "*.php" -type f -exec sed -i '' 's/UltraConsolidatedUltraConsolidatedCacheService/UltraConsolidatedCacheService/g' {} \;
find database -name "*.php" -type f -exec sed -i '' 's/UltraConsolidatedUltraConsolidatedCacheService/UltraConsolidatedCacheService/g' {} \;

# Fix QuizUltraConsolidatedUltraConsolidatedCacheService -> UltraConsolidatedCacheService
find app -name "*.php" -type f -exec sed -i '' 's/QuizUltraConsolidatedUltraConsolidatedCacheService/UltraConsolidatedCacheService/g' {} \;
find tests -name "*.php" -type f -exec sed -i '' 's/QuizUltraConsolidatedUltraConsolidatedCacheService/UltraConsolidatedCacheService/g' {} \;
find database -name "*.php" -type f -exec sed -i '' 's/QuizUltraConsolidatedUltraConsolidatedCacheService/UltraConsolidatedCacheService/g' {} \;

print_status "Duplicate references fixed"

# Clear caches
print_info "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
print_status "Caches cleared"

# Update composer autoloader
print_info "Updating composer autoloader..."
composer dump-autoload
print_status "Composer autoloader updated"

# Generate optimized files
print_info "Generating optimized files..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_status "Optimized files generated"

print_status "Duplicate reference fixes completed successfully!"
echo ""
echo "🎯 All duplicate UltraConsolidated references have been fixed!"
echo "⚡ Your application is now using the correct UltraConsolidatedCacheService!"

exit 0
