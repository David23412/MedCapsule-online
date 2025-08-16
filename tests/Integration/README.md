# 🧪 Integration Test Suite

This directory contains comprehensive integration tests that verify performance, prevent N+1 query regressions, and ensure backward compatibility.

## 🚀 Running Tests

### Run All Integration Tests
```bash
php artisan test --filter=Integration
```

### Run Specific Test Classes
```bash
# Performance tests only
php artisan test tests/Integration/PerformanceIntegrationTest.php

# Backward compatibility tests only
php artisan test tests/Integration/BackwardCompatibilityTest.php
```

### Run Individual Tests
```bash
# Test specific endpoint
php artisan test --filter=courses_index_meets_query_threshold

# Test backward compatibility
php artisan test --filter=backward_compatibility_maintained
```

## 📊 Query Count Thresholds

The following query limits are enforced to prevent N+1 regressions:

| Endpoint | Max Queries | Purpose |
|----------|-------------|---------|
| `courses.index` | ≤ 10 | Course listing with enrollment data |
| `dashboard.home` | ≤ 8 | User dashboard with enrolled courses |
| `course.show` | ≤ 6 | Single course with topics |
| `topic.show` | ≤ 4 | Single topic with questions |
| `api.courses` | ≤ 15 | API courses with statistics |

### Configuring Thresholds

Thresholds are defined in `config/performance.php`:

```php
'query_thresholds' => [
    'courses.index' => 10,
    'dashboard.home' => 8,
    // ... etc
],
```

## 🔧 Test Configuration

### Environment Variables

```bash
# Enable detailed query logging in tests
PERFORMANCE_LOG_QUERY_DETAILS=true

# Enable performance monitoring
PERFORMANCE_MONITORING=true

# Set slow query threshold (milliseconds)
SLOW_QUERY_THRESHOLD=1000
```

### Database Setup

Tests use `RefreshDatabase` trait, so they:
- Create a fresh database for each test
- Automatically rollback changes
- Don't affect your development database

## 🚨 Failure Messages

When tests fail, you'll see clear error messages:

### N+1 Query Detection
```
❌ N+1 DETECTED in courses.index — 15 queries > 10 limit. Fix eager loading!

Queries executed:
1. SELECT * FROM courses
2. SELECT * FROM course_user WHERE course_id = 1
3. SELECT * FROM course_user WHERE course_id = 2
...
```

### Performance Regression
```
❌ SLOW RESPONSE in dashboard — 1500ms > 1000ms limit
```

### Exponential Scaling
```
❌ EXPONENTIAL SCALING DETECTED — Query count should not scale with data size.
2 courses: 5 queries, 10 courses: 25 queries.
This indicates N+1 queries are still present!
```

## 🛠️ Debugging Failed Tests

### 1. Enable Query Logging
```bash
PERFORMANCE_LOG_QUERY_DETAILS=true php artisan test --filter=courses_index
```

### 2. Check Eager Loading
Look for missing `with()` clauses in controllers:
```php
// ❌ Missing eager loading
Course::all();

// ✅ Proper eager loading
Course::with(['topics', 'users'])->get();
```

### 3. Verify Explicit Methods
Ensure controllers use explicit methods instead of removed accessors:
```php
// ❌ Removed accessor (causes errors)
$course->completion_percentage

// ✅ Explicit method
$course->getCompletionPercentage($userId)
```

## 🔄 Rollback Procedures

If tests fail after deployment:

### 1. Immediate Rollback
```bash
# Revert to previous commit
git revert HEAD

# Or reset to specific commit
git reset --hard <previous-commit-hash>
```

### 2. Database Rollback
```bash
# If migrations were run
php artisan migrate:rollback

# Or rollback specific migration
php artisan migrate:rollback --step=1
```

### 3. Cache Clearing
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## 📈 Performance Monitoring

### Continuous Integration

Add to your CI/CD pipeline:
```yaml
- name: Run Integration Tests
  run: php artisan test --filter=Integration --stop-on-failure
```

### Local Development

Run before committing:
```bash
# Quick performance check
php artisan test --filter=PerformanceIntegrationTest

# Full compatibility check
php artisan test --filter=Integration
```

## 🎯 Test Coverage

### Performance Tests
- ✅ Query count thresholds for all major endpoints
- ✅ Linear scaling verification (no exponential growth)
- ✅ Response time monitoring
- ✅ Load simulation with realistic data

### Backward Compatibility Tests
- ✅ View structure verification
- ✅ API JSON structure validation
- ✅ Blade template variable accessibility
- ✅ Error response consistency
- ✅ Pagination structure maintenance

### End-to-End Coverage
- ✅ Real HTTP requests (not method calls)
- ✅ Database interactions
- ✅ Authentication flows
- ✅ Multi-user scenarios

## 🚀 Best Practices

### Writing New Tests
1. Use realistic test data (multiple users, courses, topics)
2. Test with both authenticated and unauthenticated users
3. Verify both performance AND functionality
4. Include clear failure messages
5. Test edge cases (empty data, large datasets)

### Maintaining Tests
1. Update thresholds when legitimate optimizations are made
2. Add new tests for new endpoints
3. Keep test data realistic but minimal
4. Document any special setup requirements

## 📞 Support

If tests fail unexpectedly:
1. Check the failure message for specific guidance
2. Verify your local environment matches test requirements
3. Ensure database migrations are up to date
4. Check for any recent changes to query patterns

For persistent issues, review the query logs and compare against the expected patterns documented in this README.