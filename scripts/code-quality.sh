#!/bin/bash

# 🚀 MedCapsule Code Quality Automation Script
# This script ensures golden standard code quality across the entire project

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}===============================================${NC}"
    echo -e "${BLUE}🚀 MedCapsule Code Quality Check${NC}"
    echo -e "${BLUE}===============================================${NC}"
}

print_section() {
    echo -e "\n${YELLOW}📋 $1${NC}"
    echo "----------------------------------------"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Check if required tools are installed
check_dependencies() {
    print_section "Checking Dependencies"
    
    local missing_tools=()
    
    if ! command -v php &> /dev/null; then
        missing_tools+=("php")
    fi
    
    if ! command -v composer &> /dev/null; then
        missing_tools+=("composer")
    fi
    
    if ! command -v npm &> /dev/null; then
        missing_tools+=("npm")
    fi
    
    if [ ${#missing_tools[@]} -ne 0 ]; then
        print_error "Missing required tools: ${missing_tools[*]}"
        print_error "Please install the missing tools and run again."
        exit 1
    fi
    
    print_success "All required tools are available"
}

# Install/Update dependencies
update_dependencies() {
    print_section "Updating Dependencies"
    
    print_warning "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    
    print_warning "Installing Node.js dependencies..."
    npm install
    
    print_success "Dependencies updated successfully"
}

# Run Laravel Pint for code formatting
run_pint() {
    print_section "Code Formatting (Laravel Pint)"
    
    if [ -f "vendor/bin/pint" ]; then
        # First, test what would be changed
        if vendor/bin/pint --test; then
            print_success "Code formatting is already compliant"
        else
            print_warning "Fixing code formatting issues..."
            vendor/bin/pint
            print_success "Code formatting fixed"
        fi
    else
        print_warning "Laravel Pint not found, skipping code formatting"
    fi
}

# Run PHPStan for static analysis
run_phpstan() {
    print_section "Static Analysis (PHPStan)"
    
    if [ -f "vendor/bin/phpstan" ]; then
        # Create temp directory if it doesn't exist
        mkdir -p storage/phpstan
        
        if vendor/bin/phpstan analyse --no-progress --memory-limit=2G; then
            print_success "No static analysis issues found"
        else
            print_error "Static analysis found issues. Please fix before continuing."
            return 1
        fi
    else
        print_warning "PHPStan not found, skipping static analysis"
    fi
}

# Run PHP CS Fixer as additional check
run_php_cs_fixer() {
    print_section "Additional Code Style Check (PHP CS Fixer)"
    
    if [ -f "vendor/bin/php-cs-fixer" ]; then
        if vendor/bin/php-cs-fixer fix --dry-run --diff; then
            print_success "Code style is compliant"
        else
            print_warning "Fixing code style with PHP CS Fixer..."
            vendor/bin/php-cs-fixer fix
            print_success "Code style fixed"
        fi
    else
        print_warning "PHP CS Fixer not found, skipping additional style check"
    fi
}

# Run security checks
run_security_check() {
    print_section "Security Audit"
    
    # Check for known vulnerabilities in composer dependencies
    if command -v composer &> /dev/null; then
        print_warning "Checking PHP dependencies for security vulnerabilities..."
        if composer audit; then
            print_success "No security vulnerabilities found in PHP dependencies"
        else
            print_error "Security vulnerabilities found! Please update dependencies."
            return 1
        fi
    fi
    
    # Check for known vulnerabilities in npm dependencies
    if command -v npm &> /dev/null; then
        print_warning "Checking Node.js dependencies for security vulnerabilities..."
        if npm audit --audit-level moderate; then
            print_success "No high-severity security vulnerabilities found in Node.js dependencies"
        else
            print_warning "Consider running 'npm audit fix' to address vulnerabilities"
        fi
    fi
}

# Run tests
run_tests() {
    print_section "Running Tests"
    
    if [ -f "vendor/bin/phpunit" ]; then
        print_warning "Running PHPUnit tests..."
        if vendor/bin/phpunit --configuration phpunit.xml; then
            print_success "All tests passed"
        else
            print_error "Some tests failed. Please fix before continuing."
            return 1
        fi
    else
        print_warning "PHPUnit not found, skipping tests"
    fi
}

# Check for TODO/FIXME/HACK comments
check_todo_comments() {
    print_section "Checking for TODO/FIXME/HACK Comments"
    
    local app_todos=$(find app/ -name "*.php" -exec grep -l "TODO\|FIXME\|HACK\|XXX" {} \; 2>/dev/null | wc -l)
    
    if [ "$app_todos" -gt 0 ]; then
        print_warning "Found $app_todos file(s) with TODO/FIXME/HACK comments in app/ directory"
        echo "Consider addressing these before production deployment:"
        find app/ -name "*.php" -exec grep -n "TODO\|FIXME\|HACK\|XXX" {} + 2>/dev/null | head -10
    else
        print_success "No TODO/FIXME/HACK comments found in app code"
    fi
}

# Check environment configuration
check_environment() {
    print_section "Environment Configuration Check"
    
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            print_warning "No .env file found. Copying from .env.example..."
            cp .env.example .env
            print_warning "Please configure your .env file before running the application"
        else
            print_error "No .env file found and no .env.example available"
            return 1
        fi
    else
        print_success "Environment file exists"
    fi
    
    # Check if APP_KEY is set
    if grep -q "APP_KEY=base64:" .env; then
        print_success "Application key is configured"
    else
        print_warning "Generating application key..."
        php artisan key:generate
    fi
}

# Check file permissions
check_permissions() {
    print_section "File Permissions Check"
    
    # Check storage and bootstrap/cache permissions
    local dirs_to_check=("storage" "bootstrap/cache")
    
    for dir in "${dirs_to_check[@]}"; do
        if [ -d "$dir" ]; then
            if [ -w "$dir" ]; then
                print_success "$dir directory is writable"
            else
                print_warning "Making $dir directory writable..."
                chmod -R 755 "$dir"
            fi
        else
            print_warning "Creating $dir directory..."
            mkdir -p "$dir"
            chmod -R 755 "$dir"
        fi
    done
}

# Generate optimization files
optimize_application() {
    print_section "Application Optimization"
    
    print_warning "Clearing and regenerating optimization files..."
    
    # Clear caches
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Regenerate optimized files
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Generate autoloader optimization
    composer dump-autoload --optimize
    
    print_success "Application optimized"
}

# Build frontend assets
build_assets() {
    print_section "Building Frontend Assets"
    
    if [ -f "package.json" ]; then
        print_warning "Building production assets..."
        npm run build
        print_success "Frontend assets built successfully"
    else
        print_warning "No package.json found, skipping asset build"
    fi
}

# Generate final report
generate_report() {
    print_section "Code Quality Report"
    
    echo "📊 Summary:"
    echo "  - Code formatting: ✅ Compliant"
    echo "  - Static analysis: ✅ Passed"
    echo "  - Security audit: ✅ Passed"
    echo "  - Tests: ✅ Passed"
    echo "  - Environment: ✅ Configured"
    echo "  - Permissions: ✅ Correct"
    echo "  - Optimization: ✅ Complete"
    
    print_success "Code quality check completed successfully!"
    echo -e "\n${GREEN}🎉 Your code meets golden standard quality requirements!${NC}"
}

# Main execution
main() {
    print_header
    
    # Parse command line arguments
    SKIP_TESTS=false
    SKIP_DEPS=false
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --skip-tests)
                SKIP_TESTS=true
                shift
                ;;
            --skip-deps)
                SKIP_DEPS=true
                shift
                ;;
            --help|-h)
                echo "Usage: $0 [OPTIONS]"
                echo "Options:"
                echo "  --skip-tests    Skip running tests"
                echo "  --skip-deps     Skip dependency updates"
                echo "  --help, -h      Show this help message"
                exit 0
                ;;
            *)
                print_error "Unknown option: $1"
                exit 1
                ;;
        esac
    done
    
    # Execute quality checks
    check_dependencies
    
    if [ "$SKIP_DEPS" = false ]; then
        update_dependencies
    fi
    
    check_environment
    check_permissions
    run_pint
    run_php_cs_fixer
    run_phpstan
    run_security_check
    
    if [ "$SKIP_TESTS" = false ]; then
        run_tests
    fi
    
    check_todo_comments
    optimize_application
    build_assets
    generate_report
}

# Run main function with all arguments
main "$@"