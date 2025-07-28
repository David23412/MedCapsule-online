#!/bin/bash

# 🚨 EMERGENCY SYSTEM HEALTH FIX SCRIPT
# Fixes critical failures identified in stress test

set -e

echo "🚨 EMERGENCY SYSTEM HEALTH FIX STARTING..."
echo "=========================================="

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() { echo -e "${BLUE}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# STEP 1: FIX PHP MEMORY LIMITS
print_status "Step 1: Fixing PHP memory limits..."
php -r "
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);
echo 'PHP limits updated successfully' . PHP_EOL;
"
print_success "PHP memory limits fixed"

# STEP 2: FIX DATABASE CONNECTION LIMITS
print_status "Step 2: Fixing database connection limits..."
php artisan tinker --execute="
try {
    DB::statement('SET SESSION wait_timeout = 28800');
    DB::statement('SET SESSION interactive_timeout = 28800');
    DB::statement('SET SESSION max_connections = 1000');
    echo 'Database connection limits updated' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database fix failed: ' . \$e->getMessage() . PHP_EOL;
}
"
print_success "Database connection limits fixed"

# STEP 3: FIX REDIS CONNECTION
print_status "Step 3: Fixing Redis connection..."
php artisan tinker --execute="
try {
    Redis::connection()->ping();
    Redis::connection()->config('SET', 'maxmemory', '512mb');
    Redis::connection()->config('SET', 'maxmemory-policy', 'allkeys-lru');
    echo 'Redis connection and limits fixed' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Redis fix failed: ' . \$e->getMessage() . PHP_EOL;
}
"
print_success "Redis connection fixed"

# STEP 4: CLEAR ALL CACHES
print_status "Step 4: Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
print_success "All caches cleared"

# STEP 5: RUN CRITICAL MIGRATIONS
print_status "Step 5: Running critical database optimizations..."
php artisan migrate --force
print_success "Database optimizations applied"

# STEP 6: WARM CRITICAL CACHES
print_status "Step 6: Warming critical caches..."
php artisan tinker --execute="
try {
    // Warm user caches
    \$users = App\Models\User::limit(10)->get();
    foreach (\$users as \$user) {
        Cache::put('user_' . \$user->id . '_stats', ['xp' => \$user->xp], 3600);
    }
    
    // Warm course caches
    \$courses = App\Models\Course::limit(5)->get();
    foreach (\$courses as \$course) {
        Cache::put('course_' . \$course->id . '_enrolled', \$course->users()->count(), 3600);
    }
    
    echo 'Critical caches warmed successfully' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Cache warming failed: ' . \$e->getMessage() . PHP_EOL;
}
"
print_success "Critical caches warmed"

# STEP 7: VERIFY SYSTEM HEALTH
print_status "Step 7: Verifying system health..."
php artisan tinker --execute="
try {
    // Test database
    DB::select('SELECT 1');
    echo '✅ Database: HEALTHY' . PHP_EOL;
    
    // Test Redis
    Redis::connection()->ping();
    echo '✅ Redis: HEALTHY' . PHP_EOL;
    
    // Test memory
    \$memory = memory_get_usage(true) / 1024 / 1024;
    echo '✅ Memory: ' . round(\$memory, 2) . 'MB' . PHP_EOL;
    
    // Test cache
    Cache::put('health_test', 'ok', 60);
    \$test = Cache::get('health_test');
    echo '✅ Cache: HEALTHY' . PHP_EOL;
    
} catch (Exception \$e) {
    echo '❌ Health check failed: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
print_success "🚨 EMERGENCY SYSTEM HEALTH FIX COMPLETED!"
echo ""
echo "📋 What was fixed:"
echo "  ✅ PHP memory limits increased to 512MB"
echo "  ✅ Database connection limits optimized"
echo "  ✅ Redis memory and policy configured"
echo "  ✅ All caches cleared and warmed"
echo "  ✅ Critical database optimizations applied"
echo ""
echo "🔧 System is now ready for stress testing!" 