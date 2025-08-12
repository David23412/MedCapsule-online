#!/bin/bash

# 🚀 UPDATE CACHE SERVICE REFERENCES SCRIPT
# This script updates all references from CacheService to UltraConsolidatedCacheService

set -e

echo "🚀 UPDATING CACHE SERVICE REFERENCES!"
echo "====================================="
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

print_info "Starting cache service reference updates..."

# Update all files that reference CacheService
print_info "Updating service references..."

# Update use statements
find app -name "*.php" -type f -exec sed -i '' 's/use App\\Services\\CacheService;/use App\\Services\\Core\\UltraConsolidatedCacheService;/g' {} \;
find tests -name "*.php" -type f -exec sed -i '' 's/use App\\Services\\CacheService;/use App\\Services\\Core\\UltraConsolidatedCacheService;/g' {} \;
find database -name "*.php" -type f -exec sed -i '' 's/use App\\Services\\CacheService;/use App\\Services\\Core\\UltraConsolidatedCacheService;/g' {} \;

# Update class references
find app -name "*.php" -type f -exec sed -i '' 's/CacheService::/UltraConsolidatedCacheService::/g' {} \;
find tests -name "*.php" -type f -exec sed -i '' 's/CacheService::/UltraConsolidatedCacheService::/g' {} \;
find database -name "*.php" -type f -exec sed -i '' 's/CacheService::/UltraConsolidatedCacheService::/g' {} \;

# Update type hints
find app -name "*.php" -type f -exec sed -i '' 's/CacheService \$/UltraConsolidatedCacheService \$/g' {} \;
find tests -name "*.php" -type f -exec sed -i '' 's/CacheService \$/UltraConsolidatedCacheService \$/g' {} \;
find database -name "*.php" -type f -exec sed -i '' 's/CacheService \$/UltraConsolidatedCacheService \$/g' {} \;

# Update app() calls
find app -name "*.php" -type f -exec sed -i '' 's/app(CacheService::class)/app(UltraConsolidatedCacheService::class)/g' {} \;
find tests -name "*.php" -type f -exec sed -i '' 's/app(CacheService::class)/app(UltraConsolidatedCacheService::class)/g' {} \;
find database -name "*.php" -type f -exec sed -i '' 's/app(CacheService::class)/app(UltraConsolidatedCacheService::class)/g' {} \;

# Update constructor parameters
find app -name "*.php" -type f -exec sed -i '' 's/CacheService \$cacheService/UltraConsolidatedCacheService \$cacheService/g' {} \;
find tests -name "*.php" -type f -exec sed -i '' 's/CacheService \$cacheService/UltraConsolidatedCacheService \$cacheService/g' {} \;
find database -name "*.php" -type f -exec sed -i '' 's/CacheService \$cacheService/UltraConsolidatedCacheService \$cacheService/g' {} \;

# Update property declarations
find app -name "*.php" -type f -exec sed -i '' 's/private CacheService \$/private UltraConsolidatedCacheService \$/g' {} \;
find app -name "*.php" -type f -exec sed -i '' 's/protected CacheService \$/protected UltraConsolidatedCacheService \$/g' {} \;
find app -name "*.php" -type f -exec sed -i '' 's/public CacheService \$/public UltraConsolidatedCacheService \$/g' {} \;

find tests -name "*.php" -type f -exec sed -i '' 's/private CacheService \$/private UltraConsolidatedCacheService \$/g' {} \;
find tests -name "*.php" -type f -exec sed -i '' 's/protected CacheService \$/protected UltraConsolidatedCacheService \$/g' {} \;
find tests -name "*.php" -type f -exec sed -i '' 's/public CacheService \$/public UltraConsolidatedCacheService \$/g' {} \;

find database -name "*.php" -type f -exec sed -i '' 's/private CacheService \$/private UltraConsolidatedCacheService \$/g' {} \;
find database -name "*.php" -type f -exec sed -i '' 's/protected CacheService \$/protected UltraConsolidatedCacheService \$/g' {} \;
find database -name "*.php" -type f -exec sed -i '' 's/public CacheService \$/public UltraConsolidatedCacheService \$/g' {} \;

print_status "Cache service references updated"

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

print_status "Cache service reference updates completed successfully!"
echo ""
echo "🎯 All CacheService references have been updated to UltraConsolidatedCacheService!"
echo "⚡ Your application is now using the ULTRA CONSOLIDATED CACHE SERVICE!"

exit 0
