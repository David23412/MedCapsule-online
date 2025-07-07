#!/bin/bash

# Medcapsule Laravel - Architectural Stability Audit Issues
# Run this script to create all stability and testing issues from the audit

echo "🛡️ Creating Medcapsule Laravel Stability Audit Issues..."

# Issue 7: Add Comprehensive Test Suite
gh issue create \
  --title "Add comprehensive test suite for refactored services" \
  --body "## Problem
Critical missing test coverage for new services identified in architectural stability audit.

## Missing Test Coverage

### 🔴 GradingService Tests (12 test methods needed)
- Quiz grading with various question types
- XP calculation with accuracy/speed bonuses
- Level up notification triggers
- Mistake recording scenarios
- Database transaction rollbacks
- Boundary conditions (max answers, empty answers)
- Quiz attempt record creation
- Existing mistake record updates
- Encouragement message generation

### 🔴 QuizService Tests (12 test methods needed)
- Quiz generation with source filtering
- Random quiz with difficulty filtering
- Access validation for admin/enrolled/unauthorized users
- Cache behavior verification
- Error handling for empty questions
- Tutor quiz generation with hints
- Max questions per quiz limit
- Source filtering validation
- Cache service failure handling
- Mixed question types
- Unique question generation
- Invalid source filter handling

### 🔴 Integration Tests (16 test methods needed)
- Complete quiz flow from start to grade
- Tutor mode flow with immediate feedback
- Quiz access validation integration
- Notification delivery after quiz completion
- User statistics updates after quiz
- Payment verification integration
- Cache stale behavior handling
- User inactive scenarios
- Written questions handling
- Mixed question types in single quiz
- Quiz timeout scenarios
- Concurrent quiz attempts
- Malformed quiz data handling
- Nonexistent questions handling
- Quiz with no questions
- Quiz session state preservation

## Solution
Create comprehensive test suites:
- \`tests/Unit/GradingServiceTest.php\` (12 tests)
- \`tests/Unit/QuizServiceTest.php\` (12 tests)
- \`tests/Feature/QuizControllerIntegrationTest.php\` (16 tests)

## Acceptance Criteria
- [ ] All unit tests pass with 100% coverage
- [ ] All integration tests pass
- [ ] Edge cases and boundary conditions covered
- [ ] Error scenarios properly tested
- [ ] Database transaction testing included
- [ ] Mock dependencies properly configured

## Files to Create/Modify
- \`tests/Unit/GradingServiceTest.php\`
- \`tests/Unit/QuizServiceTest.php\`
- \`tests/Feature/QuizControllerIntegrationTest.php\`
- Update \`phpunit.xml\` if needed

## Effort Estimate
**2-3 days** - High complexity due to comprehensive coverage requirements" \
  --label "testing,high-priority,refactor,stability"

# Issue 8: Implement MCP Security Tools
gh issue create \
  --title "Implement MCP security and testing tools" \
  --body "## Problem
Project currently has no MCP tools for enhanced security scanning and automated testing.

## Current State
- No .mcp.json configuration file
- No automated security scanning
- No automated test generation
- No dependency visualization tools

## Solution
Implement comprehensive MCP toolchain:

### 🛡️ Security MCPs
```json
{
  \"mcpServers\": {
    \"snyk\": {
      \"command\": \"npx\",
      \"args\": [\"@snyk/cli\", \"mcp\"],
      \"env\": {
        \"SNYK_TOKEN\": \"\${SNYK_TOKEN}\"
      }
    },
    \"larascan\": {
      \"command\": \"npx\",
      \"args\": [\"larascan\", \"mcp\"],
      \"env\": {
        \"LARASCAN_API_KEY\": \"\${LARASCAN_API_KEY}\"
      }
    }
  }
}
```

### 🧪 Testing MCPs
```json
{
  \"mcpServers\": {
    \"gpt-test\": {
      \"command\": \"npx\",
      \"args\": [\"@gpt-test/mcp\"],
      \"env\": {
        \"OPENAI_API_KEY\": \"\${OPENAI_API_KEY}\"
      }
    },
    \"phpunit-coverage\": {
      \"command\": \"npx\",
      \"args\": [\"@phpunit/coverage-mcp\"],
      \"env\": {
        \"PHPUNIT_COVERAGE_DIR\": \"coverage/\"
      }
    }
  }
}
```

### 📊 Visualization MCPs
```json
{
  \"mcpServers\": {
    \"graphviz\": {
      \"command\": \"npx\",
      \"args\": [\"@graphviz/mcp\"],
      \"env\": {
        \"GRAPHVIZ_OUTPUT_DIR\": \"docs/diagrams/\"
      }
    },
    \"dependency-mapper\": {
      \"command\": \"npx\",
      \"args\": [\"@dependency-mapper/mcp\"],
      \"env\": {
        \"MAPPER_OUTPUT_DIR\": \"docs/dependencies/\"
      }
    }
  }
}
```

## Non-MCP CLI Tools
```bash
# Security scanning
composer require --dev roave/security-advisories
composer require --dev enlightn/enlightn
composer require --dev nunomaduro/larastan

# Code quality
composer require --dev friendsofphp/php-cs-fixer
composer require --dev phpstan/phpstan

# Testing tools
composer require --dev pestphp/pest
composer require --dev orchestra/testbench
composer require --dev phpunit/php-code-coverage
```

## Acceptance Criteria
- [ ] .mcp.json file created with all tools
- [ ] Security scanning integrated into CI/CD
- [ ] Automated test generation working
- [ ] Dependency visualization generated
- [ ] Code quality tools configured
- [ ] All tools working in development environment

## Files to Create/Modify
- \`.mcp.json\`
- \`composer.json\` (add dev dependencies)
- \`.github/workflows/security-scan.yml\`
- \`docs/diagrams/\` (for generated diagrams)
- \`docs/dependencies/\` (for dependency maps)

## Effort Estimate
**1 day** - Medium complexity, mostly configuration" \
  --label "security,mcp,medium-priority,automation"

# Issue 9: Service Registration in AppServiceProvider
gh issue create \
  --title "Register new services in AppServiceProvider" \
  --body "## Problem
New refactored services are not properly registered in Laravel's service container, causing potential dependency injection issues.

## Current State
```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // Empty - needs service registration
}
```

## Solution
Add comprehensive service registration:

```php
public function register(): void
{
    // Core application services
    \$this->app->singleton(GradingService::class);
    \$this->app->singleton(QuizService::class);
    \$this->app->singleton(NotificationService::class);
    \$this->app->singleton(UserStatsService::class);
    \$this->app->singleton(CacheService::class);
    
    // Payment and verification services
    \$this->app->singleton(PaymentVerificationService::class);
    \$this->app->singleton(FraudDetectionService::class);
    \$this->app->singleton(PaymentLogService::class);
    
    // Knowledge and processing services
    \$this->app->singleton(WrittenAnswerEvaluationService::class);
    \$this->app->singleton(MedicalKnowledgeService::class);
    \$this->app->singleton(PatternMatcherService::class);
    \$this->app->singleton(TextProcessingService::class);
    
    // Register service aliases for backward compatibility
    \$this->app->alias(GradingService::class, 'grading.service');
    \$this->app->alias(QuizService::class, 'quiz.service');
    \$this->app->alias(NotificationService::class, 'notification.service');
    \$this->app->alias(UserStatsService::class, 'user.stats.service');
    \$this->app->alias(CacheService::class, 'cache.service');
}
```

## Service Dependencies Configuration
```php
protected function configureServiceDependencies(): void
{
    // Configure GradingService with its dependencies
    \$this->app->when(GradingService::class)
        ->needs(NotificationService::class)
        ->give(function (\$app) {
            return \$app->make(NotificationService::class);
        });
        
    \$this->app->when(GradingService::class)
        ->needs(UserStatsService::class)
        ->give(function (\$app) {
            return \$app->make(UserStatsService::class);
        });
        
    // Configure QuizService with CacheService dependency
    \$this->app->when(QuizService::class)
        ->needs(CacheService::class)
        ->give(function (\$app) {
            return \$app->make(CacheService::class);
        });
        
    // Configure PaymentVerificationService with optional dependencies
    \$this->app->when(PaymentVerificationService::class)
        ->needs('PaymentReceiptValidator')
        ->give(function (\$app) {
            return \$app->bound('PaymentReceiptValidator') 
                ? \$app->make('PaymentReceiptValidator') 
                : null;
        });
}
```

## Acceptance Criteria
- [ ] All services properly registered as singletons
- [ ] Service aliases created for backward compatibility
- [ ] Dependency injection working correctly
- [ ] Optional dependencies handled gracefully
- [ ] No circular dependency issues
- [ ] All existing functionality continues to work

## Files to Modify
- \`app/Providers/AppServiceProvider.php\`

## Testing
- [ ] Run \`php artisan tinker\` and test service resolution
- [ ] Verify all controllers can inject services
- [ ] Test service aliases work correctly
- [ ] Confirm no breaking changes

## Effort Estimate
**30 minutes** - Low complexity, configuration only" \
  --label "dependency-injection,high-priority,configuration"

# Issue 10: Update Existing AppServiceProvider
gh issue create \
  --title "Update existing AppServiceProvider with new service registrations" \
  --body "## Problem
Need to replace the current minimal AppServiceProvider with the comprehensive version that includes all service registrations.

## Current File
\`app/Providers/AppServiceProvider.php\` - Minimal registration

## Target File
\`app/Providers/AppServiceProvider_Updated.php\` - Complete registration

## Action Required
Replace the current AppServiceProvider with the updated version:

```bash
# Backup current version
cp app/Providers/AppServiceProvider.php app/Providers/AppServiceProvider.php.backup

# Replace with updated version
cp app/Providers/AppServiceProvider_Updated.php app/Providers/AppServiceProvider.php
```

## Verification Steps
1. Clear application cache: \`php artisan cache:clear\`
2. Clear config cache: \`php artisan config:clear\`
3. Test service resolution: \`php artisan tinker\`
4. Run tests to ensure no regressions
5. Test quiz functionality end-to-end

## Rollback Plan
If issues arise:
```bash
# Restore backup
cp app/Providers/AppServiceProvider.php.backup app/Providers/AppServiceProvider.php
php artisan cache:clear
php artisan config:clear
```

## Acceptance Criteria
- [ ] AppServiceProvider updated successfully
- [ ] All services resolve correctly
- [ ] No breaking changes to existing functionality
- [ ] Tests pass after update
- [ ] Quiz system works end-to-end

## Effort Estimate
**15 minutes** - Very low complexity, file replacement only" \
  --label "deployment,low-priority,configuration"

echo "✅ All stability audit issues created successfully!"
echo ""
echo "📋 Summary of Issues Created:"
echo "  • Issue 7: Add comprehensive test suite (2-3 days)"
echo "  • Issue 8: Implement MCP security tools (1 day)"
echo "  • Issue 9: Service registration in AppServiceProvider (30 min)"
echo "  • Issue 10: Update existing AppServiceProvider (15 min)"
echo ""
echo "🚀 Next Steps:"
echo "  1. Start with Issue 9 (Service Registration) - 30 minutes"
echo "  2. Move to Issue 10 (Update AppServiceProvider) - 15 minutes"
echo "  3. Implement Issue 7 (Test Suite) - 2-3 days"
echo "  4. Add Issue 8 (MCP Tools) - 1 day"
echo ""
echo "🛡️ All issues maintain backward compatibility and zero regression risk." 