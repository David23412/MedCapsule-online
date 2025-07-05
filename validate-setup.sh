#!/bin/bash

echo "🔍 MedCapsule Setup Validation"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check functions
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✅${NC} $1 exists"
    else
        echo -e "${RED}❌${NC} $1 missing"
        return 1
    fi
}

check_executable() {
    if [ -x "$1" ]; then
        echo -e "${GREEN}✅${NC} $1 is executable"
    else
        echo -e "${YELLOW}⚠️${NC} $1 is not executable (run: chmod +x $1)"
        return 1
    fi
}

check_php_class() {
    if php -r "class_exists('$1') ? exit(0) : exit(1);" 2>/dev/null; then
        echo -e "${GREEN}✅${NC} $1 class loaded"
    else
        echo -e "${RED}❌${NC} $1 class not found"
        return 1
    fi
}

echo ""
echo "📄 File Structure Check:"

# Check required files
check_file ".env.example"
check_file "setup-local.sh"
check_file "setup-ngrok.sh" 
check_file "COMPREHENSIVE_STABILITY_REPORT.md"
check_file "QUICK_START.md"

echo ""
echo "🔐 Script Permissions:"

# Check script permissions
check_executable "setup-local.sh"
check_executable "setup-ngrok.sh"

echo ""
echo "🏗️ Laravel Structure:"

# Check Laravel structure
check_file "app/Http/Middleware/HttpsRedirectMiddleware.php"
check_file "app/Services/EnvironmentService.php"
check_file "app/Providers/EnvironmentServiceProvider.php"
check_file "app/Console/Commands/EnvironmentStatus.php"

echo ""
echo "🗄️ Database Check:"

if [ -f "database/database.sqlite" ]; then
    echo -e "${GREEN}✅${NC} SQLite database exists"
else
    echo -e "${YELLOW}⚠️${NC} SQLite database missing (will be created on first run)"
fi

echo ""
echo "⚙️ Laravel Configuration:"

# Check if .env exists
if [ -f ".env" ]; then
    echo -e "${GREEN}✅${NC} .env file exists"
    
    # Check for key variables
    if grep -q "APP_KEY=" .env; then
        echo -e "${GREEN}✅${NC} APP_KEY is set"
    else
        echo -e "${YELLOW}⚠️${NC} APP_KEY not set (run: php artisan key:generate)"
    fi
else
    echo -e "${YELLOW}⚠️${NC} .env file missing (copy from .env.example)"
fi

echo ""
echo "🧪 PHP Syntax Check:"

# Check PHP syntax
php -l app/Http/Middleware/HttpsRedirectMiddleware.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅${NC} HttpsRedirectMiddleware syntax valid"
else
    echo -e "${RED}❌${NC} HttpsRedirectMiddleware syntax error"
fi

php -l app/Services/EnvironmentService.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅${NC} EnvironmentService syntax valid"
else
    echo -e "${RED}❌${NC} EnvironmentService syntax error"
fi

echo ""
echo "📋 Next Steps:"
echo "1. Run: ./setup-local.sh (for local development)"
echo "2. Run: ./setup-ngrok.sh (for ngrok/HTTPS testing)"
echo "3. Run: php artisan env:status (to check environment)"

echo ""
echo "🎉 Validation complete!"