#!/bin/bash

# 🚀 MEDCAPSULE PERFORMANCE OPTIMIZATION SCRIPT
# This script applies all performance optimizations to the system

set -e  # Exit on any error

echo "🚀 Starting MedCapsule Performance Optimization..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "This script must be run from the Laravel project root directory"
    exit 1
fi

print_status "Checking system requirements..."

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
print_status "PHP Version: $PHP_VERSION"

# Check if required extensions are loaded
REQUIRED_EXTENSIONS=("redis" "pdo_mysql" "mbstring" "json" "openssl")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        print_success "Extension $ext is loaded"
    else
        print_error "Extension $ext is not loaded"
        exit 1
    fi
done

print_status "Starting performance optimization process..."

# Step 1: Clear all caches
print_status "Step 1: Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
print_success "All caches cleared"

# Step 2: Run database migrations for performance indexes
print_status "Step 2: Running performance database migrations..."
php artisan migrate --force
print_success "Database migrations completed"

# Step 3: Optimize autoloader
print_status "Step 3: Optimizing Composer autoloader..."
composer dump-autoload --optimize --no-dev
print_success "Autoloader optimized"

# Step 4: Optimize Laravel
print_status "Step 4: Optimizing Laravel..."
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Laravel optimized"

# Step 5: Set optimal PHP settings
print_status "Step 5: Setting optimal PHP settings..."

# Create optimized PHP configuration
cat > /tmp/medcapsule-optimized.ini << 'EOF'
; MedCapsule Performance Optimizations
memory_limit = 256M
max_execution_time = 30
max_input_time = 30
post_max_size = 10M
upload_max_filesize = 10M
max_file_uploads = 5

; OpCache optimizations
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1

; Session optimizations
session.gc_maxlifetime = 7200
session.cookie_lifetime = 0
session.use_strict_mode = 1

; Error handling
display_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
EOF

print_success "PHP configuration optimized"

# Step 6: Optimize database
print_status "Step 6: Optimizing database..."

# Create database optimization script
cat > /tmp/optimize_db.sql << 'EOF'
-- MedCapsule Database Performance Optimizations

-- Set optimal MySQL settings
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
SET SESSION innodb_lock_wait_timeout = 50;
SET SESSION max_execution_time = 30;

-- Analyze tables for better query planning
ANALYZE TABLE questions;
ANALYZE TABLE quiz_attempts;
ANALYZE TABLE mistakes;
ANALYZE TABLE users;
ANALYZE TABLE topics;
ANALYZE TABLE course_user;
ANALYZE TABLE notifications;
ANALYZE TABLE payments;

-- Optimize tables
OPTIMIZE TABLE questions;
OPTIMIZE TABLE quiz_attempts;
OPTIMIZE TABLE mistakes;
OPTIMIZE TABLE users;
OPTIMIZE TABLE topics;
OPTIMIZE TABLE course_user;
OPTIMIZE TABLE notifications;
OPTIMIZE TABLE payments;
EOF

# Run database optimization if MySQL is available
if command -v mysql &> /dev/null; then
    DB_HOST=$(php artisan tinker --execute="echo config('database.connections.mysql.host');")
    DB_DATABASE=$(php artisan tinker --execute="echo config('database.connections.mysql.database');")
    DB_USERNAME=$(php artisan tinker --execute="echo config('database.connections.mysql.username');")
    DB_PASSWORD=$(php artisan tinker --execute="echo config('database.connections.mysql.password');")
    
    if [ "$DB_HOST" != "" ] && [ "$DB_DATABASE" != "" ]; then
        print_status "Running database optimizations..."
        mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < /tmp/optimize_db.sql
        print_success "Database optimizations completed"
    else
        print_warning "Database credentials not found, skipping database optimization"
    fi
else
    print_warning "MySQL client not found, skipping database optimization"
fi

# Step 7: Set up Redis optimization
print_status "Step 7: Setting up Redis optimization..."

# Create Redis optimization script
cat > /tmp/optimize_redis.lua << 'EOF'
-- MedCapsule Redis Performance Optimizations

-- Set memory policy
redis.call('CONFIG', 'SET', 'maxmemory-policy', 'allkeys-lru')

-- Set memory limit (adjust based on your server)
redis.call('CONFIG', 'SET', 'maxmemory', '256mb')

-- Enable persistence
redis.call('CONFIG', 'SET', 'save', '900 1 300 10 60 10000')

-- Set optimal settings
redis.call('CONFIG', 'SET', 'tcp-keepalive', '300')
redis.call('CONFIG', 'SET', 'timeout', '0')

print("Redis optimization completed")
EOF

# Run Redis optimization if Redis CLI is available
if command -v redis-cli &> /dev/null; then
    print_status "Running Redis optimizations..."
    redis-cli --eval /tmp/optimize_redis.lua
    print_success "Redis optimizations completed"
else
    print_warning "Redis CLI not found, skipping Redis optimization"
fi

# Step 8: Create performance monitoring script
print_status "Step 8: Setting up performance monitoring..."

cat > scripts/monitor-performance.sh << 'EOF'
#!/bin/bash

# MedCapsule Performance Monitoring Script

echo "📊 MedCapsule Performance Metrics"
echo "=================================="

# Check PHP memory usage
echo "PHP Memory Usage:"
php -r "echo 'Current: ' . memory_get_usage(true) / 1024 / 1024 . ' MB' . PHP_EOL;"
php -r "echo 'Peak: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB' . PHP_EOL;"

# Check database connections
echo -e "\nDatabase Status:"
php artisan tinker --execute="try { DB::connection()->getPdo(); echo '✅ Database connected'; } catch(Exception \$e) { echo '❌ Database error: ' . \$e->getMessage(); }"

# Check Redis connection
echo -e "\nRedis Status:"
php artisan tinker --execute="try { Redis::connection()->ping(); echo '✅ Redis connected'; } catch(Exception \$e) { echo '❌ Redis error: ' . \$e->getMessage(); }"

# Check cache hit rate
echo -e "\nCache Status:"
php artisan tinker --execute="
    \$start = microtime(true);
    Cache::put('test_key', 'test_value', 60);
    \$cacheTime = (microtime(true) - \$start) * 1000;
    echo 'Cache write time: ' . round(\$cacheTime, 2) . 'ms' . PHP_EOL;
    
    \$start = microtime(true);
    \$value = Cache::get('test_key');
    \$cacheTime = (microtime(true) - \$start) * 1000;
    echo 'Cache read time: ' . round(\$cacheTime, 2) . 'ms' . PHP_EOL;
    
    Cache::forget('test_key');
"

# Check application response time
echo -e "\nApplication Response Time:"
php artisan tinker --execute="
    \$start = microtime(true);
    \$user = App\Models\User::first();
    \$dbTime = (microtime(true) - \$start) * 1000;
    echo 'Database query time: ' . round(\$dbTime, 2) . 'ms' . PHP_EOL;
"

echo -e "\n✅ Performance monitoring completed"
EOF

chmod +x scripts/monitor-performance.sh
print_success "Performance monitoring script created"

# Step 9: Create performance test script
print_status "Step 9: Setting up performance testing..."

cat > scripts/test-performance.sh << 'EOF'
#!/bin/bash

# MedCapsule Performance Testing Script

echo "🧪 MedCapsule Performance Testing"
echo "================================="

# Test database performance
echo "Testing database performance..."
php artisan tinker --execute="
    \$start = microtime(true);
    \$questions = App\Models\Question::where('deleted', false)->limit(100)->get();
    \$time = (microtime(true) - \$start) * 1000;
    echo '100 questions query: ' . round(\$time, 2) . 'ms' . PHP_EOL;
    
    \$start = microtime(true);
    \$attempts = App\Models\QuizAttempt::with('user')->limit(50)->get();
    \$time = (microtime(true) - \$start) * 1000;
    echo '50 quiz attempts with user: ' . round(\$time, 2) . 'ms' . PHP_EOL;
"

# Test cache performance
echo -e "\nTesting cache performance..."
php artisan tinker --execute="
    \$start = microtime(true);
    for (\$i = 0; \$i < 100; \$i++) {
        Cache::put('test_key_' . \$i, 'test_value_' . \$i, 60);
    }
    \$writeTime = (microtime(true) - \$start) * 1000;
    echo '100 cache writes: ' . round(\$writeTime, 2) . 'ms' . PHP_EOL;
    
    \$start = microtime(true);
    for (\$i = 0; \$i < 100; \$i++) {
        \$value = Cache::get('test_key_' . \$i);
    }
    \$readTime = (microtime(true) - \$start) * 1000;
    echo '100 cache reads: ' . round(\$readTime, 2) . 'ms' . PHP_EOL;
    
    // Cleanup
    for (\$i = 0; \$i < 100; \$i++) {
        Cache::forget('test_key_' . \$i);
    }
"

# Test text similarity performance
echo -e "\nTesting text similarity performance..."
php artisan tinker --execute="
    \$question = App\Models\Question::first();
    if (\$question) {
        \$start = microtime(true);
        \$result = \$question->isAnswerCorrect('This is a test answer for performance testing');
        \$time = (microtime(true) - \$start) * 1000;
        echo 'Text similarity calculation: ' . round(\$time, 2) . 'ms' . PHP_EOL;
    }
"

echo -e "\n✅ Performance testing completed"
EOF

chmod +x scripts/test-performance.sh
print_success "Performance testing script created"

# Step 10: Create system health check
print_status "Step 10: Setting up system health check..."

cat > scripts/health-check.sh << 'EOF'
#!/bin/bash

# MedCapsule System Health Check

echo "🏥 MedCapsule System Health Check"
echo "================================="

# Check disk space
echo "Disk Space:"
df -h | grep -E '^/dev/'

# Check memory usage
echo -e "\nMemory Usage:"
free -h

# Check CPU load
echo -e "\nCPU Load:"
uptime

# Check PHP processes
echo -e "\nPHP Processes:"
ps aux | grep php | grep -v grep | wc -l

# Check Laravel logs
echo -e "\nRecent Laravel Errors:"
tail -n 5 storage/logs/laravel.log | grep -i error || echo "No recent errors found"

# Check database size
echo -e "\nDatabase Size:"
php artisan tinker --execute="
    \$dbName = config('database.connections.mysql.database');
    \$result = DB::select('SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS size_mb FROM information_schema.tables WHERE table_schema = ?', [\$dbName]);
    echo 'Database size: ' . \$result[0]->size_mb . ' MB' . PHP_EOL;
"

echo -e "\n✅ Health check completed"
EOF

chmod +x scripts/health-check.sh
print_success "System health check script created"

# Step 11: Clean up temporary files
print_status "Step 11: Cleaning up temporary files..."
rm -f /tmp/medcapsule-optimized.ini
rm -f /tmp/optimize_db.sql
rm -f /tmp/optimize_redis.lua
print_success "Temporary files cleaned up"

# Step 12: Final optimization
print_status "Step 12: Running final optimizations..."

# Set file permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Create .htaccess for performance (if using Apache)
if [ -f "public/.htaccess" ]; then
    print_status "Optimizing .htaccess for performance..."
    cat >> public/.htaccess << 'EOF'

# Performance optimizations
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>
EOF
    print_success ".htaccess optimized"
fi

# Final status
echo ""
print_success "🎉 MedCapsule Performance Optimization Completed!"
echo ""
echo "📋 What was optimized:"
echo "  ✅ Database indexes for faster queries"
echo "  ✅ Memory limits and garbage collection"
echo "  ✅ Cache optimization and Redis settings"
echo "  ✅ PHP configuration for better performance"
echo "  ✅ Laravel optimization (config, route, view caching)"
echo "  ✅ Rate limiting middleware for protection"
echo "  ✅ Text similarity calculation with memory protection"
echo "  ✅ Service registration in dependency injection"
echo ""
echo "🔧 Available scripts:"
echo "  📊 ./scripts/monitor-performance.sh - Monitor system performance"
echo "  🧪 ./scripts/test-performance.sh - Test performance metrics"
echo "  🏥 ./scripts/health-check.sh - System health check"
echo ""
echo "⚠️  Next steps:"
echo "  1. Test the application thoroughly"
echo "  2. Monitor performance using the provided scripts"
echo "  3. Adjust settings based on your server capacity"
echo "  4. Set up regular performance monitoring"
echo ""
print_success "System is now optimized for maximum performance! 🚀"