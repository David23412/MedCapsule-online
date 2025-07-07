#!/bin/bash

# Medcapsule Laravel Audit - Bulk GitHub Issues Creator
# Run this script to create all refactor issues from the audit

echo "🚀 Creating Medcapsule Laravel Audit Issues..."

# Issue 1: Refactor monolithic QuizController
gh issue create \
  --title "Refactor monolithic QuizController (884 lines)" \
  --body "## Problem
QuizController handles quiz logic, grading, XP calculation, and notifications in a single 884-line file.

## Current Issues
- 884 lines of mixed responsibilities
- Complex quiz logic mixed with grading
- XP calculation embedded in controller
- Notification logic scattered throughout

## Solution
Split into focused services:
- **QuizService**: Quiz generation and management
- **GradingService**: Answer validation and scoring
- **XPCalculationService**: Experience point calculations
- **QuizNotificationService**: Quiz-related notifications

## Files Affected
- \`app/Http/Controllers/QuizController.php\`

## Effort Estimate
2-3 days

## Acceptance Criteria
- [ ] QuizController reduced to <200 lines
- [ ] All business logic moved to services
- [ ] Proper dependency injection implemented
- [ ] Unit tests for new services
- [ ] No breaking changes to existing functionality" \
  --label "refactor,backend,high-priority"

# Issue 2: Consolidate payment verification services
gh issue create \
  --title "Consolidate payment verification services" \
  --body "## Problem
Multiple overlapping payment verification services exist with similar functionality.

## Current Services
- PaymentVerificationService.php
- IndependentVerificationPipeline.php  
- FraudDetectionService.php
- TransactionPatternMatcher.php

## Solution
Create unified PaymentVerificationOrchestrator that coordinates all verification methods.

## Files Affected
- \`app/Services/PaymentVerificationService.php\`
- \`app/Services/IndependentVerificationPipeline.php\`
- \`app/Services/FraudDetectionService.php\`
- \`app/Services/TransactionPatternMatcher.php\`

## Effort Estimate
1-2 days

## Acceptance Criteria
- [ ] Single orchestration service created
- [ ] All verification methods properly coordinated
- [ ] Fallback mechanisms preserved
- [ ] No breaking changes to payment flow" \
  --label "refactor,payment,high-priority"

# Issue 3: Split NotificationService
gh issue create \
  --title "Split NotificationService into domain-specific services" \
  --body "## Problem
NotificationService (838 lines) handles multiple notification types in a single class.

## Current Issues
- 838 lines handling all notification types
- Complex caching logic mixed with notification creation
- Payment, user, and system notifications all in one place

## Solution
Split into focused services:
- **UserNotificationService**: User achievement and progress notifications
- **PaymentNotificationService**: Payment-related notifications
- **NotificationCacheService**: Caching and retrieval logic

## Files Affected
- \`app/Services/NotificationService.php\`
- \`app/Http/Controllers/NotificationController.php\`

## Effort Estimate
1 day

## Acceptance Criteria
- [ ] NotificationService reduced to <300 lines
- [ ] Domain-specific services created
- [ ] Caching logic properly separated
- [ ] All existing functionality preserved" \
  --label "refactor,notifications,medium-priority"

# Issue 4: Remove backup and test files
gh issue create \
  --title "Remove backup and test files from root directory" \
  --body "## Problem
Root directory cluttered with backup files and test scripts.

## Files to Remove
- \`backup-payment/\` directory
- \`backups/\` directory  
- \`test_*.php\` files in root
- \`fixed_quiz_script.js\` (1 byte file)

## Effort Estimate
30 minutes

## Acceptance Criteria
- [ ] All backup directories removed
- [ ] Test files moved to appropriate locations or removed
- [ ] No broken references in codebase
- [ ] Clean root directory structure" \
  --label "cleanup,low-priority"

# Issue 5: Separate admin and public controllers
gh issue create \
  --title "Separate admin and public controllers" \
  --body "## Problem
CourseController handles both public and admin functionality in a single controller.

## Solution
Create separate controllers:
- **PublicCourseController**: Public course listing and enrollment
- **AdminCourseController**: Admin course management

## Files Affected
- \`app/Http/Controllers/CourseController.php\`

## Effort Estimate
4 hours

## Acceptance Criteria
- [ ] Public and admin functionality separated
- [ ] Proper middleware applied to each controller
- [ ] Routes updated accordingly
- [ ] No functionality regression" \
  --label "refactor,controllers,medium-priority"

# Issue 6: Extract frontend JavaScript into modules
gh issue create \
  --title "Extract frontend JavaScript into modules" \
  --body "## Problem
Large JavaScript files in public/js/ should be modularized and moved to resources/js/.

## Files to Refactor
- \`public/js/payment-form.js\` (252 lines)
- \`public/js/notification-bell.js\` (197 lines)

## Solution
- Move to \`resources/js/\` directory
- Split into focused modules
- Use proper ES6 modules
- Implement proper build process

## Effort Estimate
1 day

## Acceptance Criteria
- [ ] JavaScript files moved to resources/js/
- [ ] Proper module structure implemented
- [ ] Build process updated
- [ ] No functionality regression" \
  --label "refactor,frontend,low-priority"

echo "✅ All audit issues created successfully!"
echo "📋 Review and adjust labels/descriptions as needed" 