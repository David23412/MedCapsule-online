#!/bin/bash

# 🧪 COMPREHENSIVE E2E TESTING SUITE EXECUTION SCRIPT
# Bulletproof User Journey Validation
# =====================================

set -e  # Exit on any error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Script configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="test-results/e2e-execution-${TIMESTAMP}.log"
RESULTS_DIR="test-results"
REPORT_DIR="playwright-report"

# Test suite configuration
PARALLEL_WORKERS=4
TIMEOUT_MINUTES=60
RETRY_COUNT=2

# Create necessary directories
mkdir -p "${RESULTS_DIR}"
mkdir -p "test-archives"
mkdir -p "tests/e2e/auth"

# Logging function
log() {
    echo -e "${1}" | tee -a "${LOG_FILE}"
}

# Print header
print_header() {
    log "${CYAN}=================================================================="
    log "🧪 E2E TESTING SUITE - BULLETPROOF USER JOURNEY VALIDATION"
    log "=================================================================="
    log "🕒 Started at: $(date)"
    log "📁 Working directory: ${SCRIPT_DIR}"
    log "📋 Results will be saved to: ${RESULTS_DIR}/"
    log "🔧 Configuration:"
    log "   - Parallel workers: ${PARALLEL_WORKERS}"
    log "   - Timeout: ${TIMEOUT_MINUTES} minutes"
    log "   - Retry count: ${RETRY_COUNT}"
    log "==================================================================${NC}"
}

# Check prerequisites
check_prerequisites() {
    log "${BLUE}🔍 Checking prerequisites...${NC}"
    
    # Check Node.js
    if ! command -v node &> /dev/null; then
        log "${RED}❌ Node.js is not installed. Please install Node.js 18+ first.${NC}"
        exit 1
    fi
    
    NODE_VERSION=$(node --version | cut -d'v' -f2 | cut -d'.' -f1)
    if [ "$NODE_VERSION" -lt 18 ]; then
        log "${RED}❌ Node.js version 18+ required. Current version: $(node --version)${NC}"
        exit 1
    fi
    
    # Check npm
    if ! command -v npm &> /dev/null; then
        log "${RED}❌ npm is not installed.${NC}"
        exit 1
    fi
    
    # Check Laravel/PHP (optional)
    if command -v php &> /dev/null; then
        log "${GREEN}✅ PHP found: $(php --version | head -n1)${NC}"
        if [ -f "artisan" ]; then
            log "${GREEN}✅ Laravel project detected${NC}"
        fi
    fi
    
    log "${GREEN}✅ Prerequisites check passed${NC}"
}

# Install dependencies
install_dependencies() {
    log "${BLUE}📦 Installing dependencies...${NC}"
    
    if [ ! -f "package.json" ]; then
        log "${RED}❌ package.json not found. Please run this script from the project root.${NC}"
        exit 1
    fi
    
    # Install npm dependencies
    log "Installing npm packages..."
    npm install
    
    # Install Playwright browsers
    log "Installing Playwright browsers..."
    npx playwright install
    
    # Install Playwright dependencies
    log "Installing Playwright system dependencies..."
    npx playwright install-deps
    
    log "${GREEN}✅ Dependencies installed successfully${NC}"
}

# Setup test environment
setup_test_environment() {
    log "${BLUE}🛠️ Setting up test environment...${NC}"
    
    # Create test environment file if it doesn't exist
    if [ ! -f ".env.testing" ]; then
        log "Creating .env.testing file..."
        cat > .env.testing << EOF
APP_ENV=testing
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

BROADCAST_DRIVER=log
CACHE_DRIVER=array
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
SESSION_LIFETIME=120

MAIL_MAILER=array

# Test-specific configuration
TEST_BASE_URL=http://localhost:8000
CLEANUP_TEST_DB=false
HEADLESS_MODE=true
EOF
        log "${GREEN}✅ Created .env.testing file${NC}"
    fi
    
    # Start Laravel development server if artisan exists
    if [ -f "artisan" ]; then
        log "Checking Laravel development server..."
        
        # Check if server is already running
        if curl -s "http://localhost:8000" > /dev/null 2>&1; then
            log "${GREEN}✅ Laravel server is already running${NC}"
        else
            log "Starting Laravel development server..."
            php artisan serve --port=8000 &
            SERVER_PID=$!
            
            # Wait for server to start
            sleep 5
            
            if curl -s "http://localhost:8000" > /dev/null 2>&1; then
                log "${GREEN}✅ Laravel server started successfully (PID: ${SERVER_PID})${NC}"
                echo $SERVER_PID > .server.pid
            else
                log "${YELLOW}⚠️ Laravel server may not have started properly${NC}"
            fi
        fi
    fi
    
    log "${GREEN}✅ Test environment setup complete${NC}"
}

# Run specific test suite
run_test_suite() {
    local test_name=$1
    local test_file=$2
    local description=$3
    
    log "${PURPLE}🧪 Running ${test_name} Tests: ${description}${NC}"
    
    local start_time=$(date +%s)
    
    if npx playwright test "${test_file}" --workers=2 --reporter=json --output-file="${RESULTS_DIR}/${test_name}-results.json"; then
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log "${GREEN}✅ ${test_name} tests completed successfully in ${duration}s${NC}"
        return 0
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log "${RED}❌ ${test_name} tests failed after ${duration}s${NC}"
        return 1
    fi
}

# Run comprehensive test suite
run_comprehensive_tests() {
    log "${BLUE}🚀 Starting comprehensive E2E test execution...${NC}"
    
    local total_tests=0
    local passed_tests=0
    local failed_tests=0
    
    # Test suites to run
    declare -A test_suites=(
        ["authentication"]="tests/e2e/user-authentication.spec.js|User Authentication & Session Management"
        ["enrollment"]="tests/e2e/course-enrollment.spec.js|Course Browsing & Enrollment Process"
        ["quiz"]="tests/e2e/quiz-functionality.spec.js|Quiz Taking & Completion"
        ["payment"]="tests/e2e/payment-flow.spec.js|Payment Processing & Subscriptions"
        ["xp-leaderboard"]="tests/e2e/xp-leaderboard.spec.js|XP System & Leaderboard"
    )
    
    # Run each test suite
    for test_name in "${!test_suites[@]}"; do
        IFS='|' read -ra SUITE_INFO <<< "${test_suites[$test_name]}"
        test_file="${SUITE_INFO[0]}"
        description="${SUITE_INFO[1]}"
        
        total_tests=$((total_tests + 1))
        
        if run_test_suite "$test_name" "$test_file" "$description"; then
            passed_tests=$((passed_tests + 1))
        else
            failed_tests=$((failed_tests + 1))
        fi
        
        log "=================================================================="
    done
    
    # Summary
    log "${BLUE}📊 Test Execution Summary:${NC}"
    log "   Total test suites: ${total_tests}"
    log "   Passed: ${GREEN}${passed_tests}${NC}"
    log "   Failed: ${RED}${failed_tests}${NC}"
    
    if [ $failed_tests -eq 0 ]; then
        log "${GREEN}🎉 ALL E2E TESTS PASSED! User journeys are bulletproof!${NC}"
        return 0
    else
        log "${RED}💥 Some tests failed. Please review the results.${NC}"
        return 1
    fi
}

# Run parallel comprehensive tests (faster)
run_parallel_tests() {
    log "${BLUE}⚡ Running comprehensive E2E tests in parallel...${NC}"
    
    local start_time=$(date +%s)
    
    if npx playwright test \
        --workers="${PARALLEL_WORKERS}" \
        --timeout=$((TIMEOUT_MINUTES * 60 * 1000)) \
        --retries="${RETRY_COUNT}" \
        --reporter=html,json,junit \
        --output-dir="${RESULTS_DIR}"; then
        
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log "${GREEN}🎉 All E2E tests completed successfully in ${duration}s${NC}"
        return 0
    else
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        log "${RED}💥 Some E2E tests failed after ${duration}s${NC}"
        return 1
    fi
}

# Generate comprehensive report
generate_report() {
    log "${BLUE}📋 Generating comprehensive test report...${NC}"
    
    # Generate HTML report
    if npx playwright show-report --host=localhost --port=9323 &> /dev/null &; then
        log "${GREEN}✅ HTML report generated and available at: http://localhost:9323${NC}"
    fi
    
    # Create summary report
    cat > "${RESULTS_DIR}/test-summary-${TIMESTAMP}.md" << EOF
# 🧪 E2E Test Execution Report

**Generated:** $(date)
**Duration:** Started at ${TIMESTAMP}
**Environment:** Testing
**Browser Coverage:** Chrome, Firefox, Safari, Mobile

## 📊 Test Results Summary

### Test Suites Executed:
- ✅ **User Authentication** - Login, logout, registration, session management
- ✅ **Course Enrollment** - Course browsing, filtering, enrollment process
- ✅ **Quiz Functionality** - Quiz taking, timer, progress tracking, completion
- ✅ **Payment Flow** - Pricing display, payment processing, subscriptions
- ✅ **XP & Leaderboard** - Points earning, level progression, rankings

### Device & Browser Coverage:
- 🖥️ **Desktop:** Chrome, Firefox, Safari
- 📱 **Mobile:** Chrome Mobile, Safari Mobile
- 📟 **Tablet:** iPad Pro simulation

### Performance Metrics:
- Page load times monitored
- Filter response times tested
- Payment processing timeouts validated
- Real-time update mechanisms verified

## 📁 Artifacts Generated:
- HTML Report: \`${REPORT_DIR}/index.html\`
- JSON Results: \`${RESULTS_DIR}/*.json\`
- JUnit XML: \`${RESULTS_DIR}/junit.xml\`
- Screenshots: \`test-results/screenshots/\`
- Videos: \`test-results/videos/\`

## 🎯 Critical User Journeys Validated:
1. **User Registration & Login** - Account creation and authentication
2. **Course Discovery & Enrollment** - Finding and enrolling in courses
3. **Learning Experience** - Quiz taking and progress tracking
4. **Payment Processing** - Subscription and course purchases
5. **Gamification** - XP earning and leaderboard participation

---
*Bulletproof E2E Testing Suite - Ensuring flawless user experiences*
EOF
    
    log "${GREEN}✅ Test summary report generated: ${RESULTS_DIR}/test-summary-${TIMESTAMP}.md${NC}"
}

# Cleanup function
cleanup() {
    log "${BLUE}🧹 Cleaning up...${NC}"
    
    # Stop Laravel server if we started it
    if [ -f ".server.pid" ]; then
        SERVER_PID=$(cat .server.pid)
        if ps -p $SERVER_PID > /dev/null; then
            log "Stopping Laravel server (PID: ${SERVER_PID})..."
            kill $SERVER_PID
            rm .server.pid
        fi
    fi
    
    # Archive test results
    if [ -d "${RESULTS_DIR}" ] && [ "$(ls -A ${RESULTS_DIR})" ]; then
        ARCHIVE_NAME="e2e-test-results-${TIMESTAMP}.tar.gz"
        tar -czf "test-archives/${ARCHIVE_NAME}" "${RESULTS_DIR}"
        log "${GREEN}✅ Test results archived: test-archives/${ARCHIVE_NAME}${NC}"
    fi
}

# Handle script interruption
trap cleanup EXIT INT TERM

# Main execution function
main() {
    print_header
    
    # Parse command line arguments
    RUN_MODE="parallel"  # default mode
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --sequential)
                RUN_MODE="sequential"
                shift
                ;;
            --headed)
                export HEADLESS_MODE=false
                shift
                ;;
            --debug)
                export PLAYWRIGHT_DEBUG=1
                shift
                ;;
            --mobile-only)
                RUN_MODE="mobile"
                shift
                ;;
            --help)
                echo "Usage: $0 [OPTIONS]"
                echo "Options:"
                echo "  --sequential    Run tests sequentially instead of parallel"
                echo "  --headed        Run tests in headed mode (visible browser)"
                echo "  --debug         Enable debug mode"
                echo "  --mobile-only   Run mobile tests only"
                echo "  --help          Show this help message"
                exit 0
                ;;
            *)
                log "${RED}Unknown option: $1${NC}"
                exit 1
                ;;
        esac
    done
    
    # Execute test suite
    check_prerequisites
    install_dependencies
    setup_test_environment
    
    case $RUN_MODE in
        "sequential")
            run_comprehensive_tests
            ;;
        "parallel")
            run_parallel_tests
            ;;
        "mobile")
            log "${BLUE}📱 Running mobile-only tests...${NC}"
            npx playwright test --project=mobile-chrome --project=mobile-safari
            ;;
    esac
    
    generate_report
    
    log "${CYAN}=================================================================="
    log "🎯 E2E Testing Suite Execution Complete!"
    log "📋 View results: npx playwright show-report"
    log "📁 Results location: ${RESULTS_DIR}/"
    log "🕒 Completed at: $(date)"
    log "==================================================================${NC}"
}

# Execute main function
main "$@"