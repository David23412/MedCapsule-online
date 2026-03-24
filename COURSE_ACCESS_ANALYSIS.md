# 🔍 COURSE ACCESS LOGIC ANALYSIS

## 📊 CURRENT STATE ASSESSMENT

### ✅ STRENGTHS

1. **Dual Access System Working**
   - `access_type` column (free/preview/paid) ✅
   - Legacy `price` column (0 = free, >0 = paid) ✅
   - Both systems coexist properly

2. **Robust Access Control Chain**
   ```
   User → hasAccessToCourse() → hasAccessToCourses() → getCourseAccessLevels()
   ```
   - Single source of truth: `getCourseAccessLevels()`
   - Proper caching with 300s TTL
   - Bulk operations for performance

3. **Role-Based Access**
   - Admin: Full access to everything ✅
   - Tutor: Full access to assigned courses ✅
   - Student: Based on enrollment + payment/survey ✅

4. **Preview System**
   - Preview courses limit to first 5 topics ✅
   - Survey unlock required for free courses ✅
   - `hasFullAccessToCourse()` vs `hasAccessToCourse()` distinction ✅

### ⚠️ IDENTIFIED ISSUES

#### 🔴 CRITICAL ISSUE #1: Inconsistent Access Type Logic

**Location:** `Course::getAccessTypeForUser()` vs `User::getCourseAccessLevels()`

**Problem:** Two different systems determining access:

**Course Model (NEW system):**
```php
public function getAccessTypeForUser(User $user): string
{
    if ($user->isAdmin() || $user->isTutor()) return 'full';
    if ($this->isFreeAccess()) return 'full';  // ❌ WRONG!
    if ($this->isPreviewAccess()) {
        return $user->hasUnlockedFreeCourses() ? 'preview' : 'locked';
    }
    if ($this->isPaidAccess()) {
        return $this->hasUserPaid($user->id) ? 'full' : 'locked';
    }
    return 'locked';
}
```

**User Model (EXISTING system):**
```php
public function getCourseAccessLevels(array $courseIds): array
{
    // Uses price column, not access_type
    // Free courses → PREVIEW (if survey unlocked)
    // Paid courses → FULL (if paid)
}
```

**THE CONFLICT:**
- `Course::getAccessTypeForUser()` uses `access_type` column
- `User::getCourseAccessLevels()` uses `price` column
- They return DIFFERENT results for the same course!

#### 🔴 CRITICAL ISSUE #2: Free Course Logic Mismatch

**Current behavior in `User::getCourseAccessLevels()`:**
```php
if ($isFreeCourse && !$hasFreeSurveyUnlock) {
    $accessMap[$courseId] = null;  // NO ACCESS
    continue;
}
```

**Expected behavior:**
- Free courses should give PREVIEW access after survey
- Free courses should give FULL access to admins/tutors
- Currently: Free courses with no survey = NO ACCESS ❌

#### 🟡 ISSUE #3: Missing Access Type in User Logic

**Problem:** `User::getCourseAccessLevels()` doesn't check `access_type` column at all!

It only checks:
- `price` (legacy)
- `is_enrolled`
- `access_level` (pivot table)

But ignores:
- `access_type` (new column) ❌

#### 🟡 ISSUE #4: Tutor Access Inconsistency

**In TopicController:**
```php
$hasFullCourseAccess = $user->hasFullAccessToCourse($course);
// Returns TRUE for tutors
```

**In User::getCourseAccessLevels():**
```php
if ($this->isTutor()) {
    $accessMap[$courseId] = self::COURSE_ACCESS_FULL;
    continue;
}
```

**But tutors must be ENROLLED first!**
- If tutor not enrolled → `$isEnrolled = false` → returns `null` ❌
- Tutor check happens AFTER enrollment check ❌

### 🔧 REQUIRED FIXES

#### FIX #1: Unify Access Logic

**Decision:** Use `access_type` as source of truth, deprecate `price`-based logic

**Update `User::getCourseAccessLevels()`:**
```php
$rows = DB::table('courses')
    ->leftJoin('course_user', ...)
    ->whereIn('courses.id', $normalizedCourseIds)
    ->select(
        'courses.id',
        'courses.access_type',  // ✅ ADD THIS
        'courses.price',        // Keep for backward compat
        'course_user.is_enrolled',
        'course_user.access_level'
    )
    ->get();

foreach ($rows as $row) {
    $courseId = (int) $row->id;
    $accessType = $row->access_type ?? 'paid'; // Default to paid
    
    // Admins/Tutors always get full access (even without enrollment)
    if ($this->isAdmin() || $this->isTutor()) {
        $accessMap[$courseId] = self::COURSE_ACCESS_FULL;
        continue;
    }
    
    // Must be enrolled for any access
    if (!$isEnrolled) {
        $accessMap[$courseId] = null;
        continue;
    }
    
    // Free access courses → FULL
    if ($accessType === 'free') {
        $accessMap[$courseId] = self::COURSE_ACCESS_FULL;
        continue;
    }
    
    // Preview access courses → PREVIEW (if survey unlocked)
    if ($accessType === 'preview') {
        $accessMap[$courseId] = $hasFreeSurveyUnlock 
            ? self::COURSE_ACCESS_PREVIEW 
            : null;
        continue;
    }
    
    // Paid courses → FULL (if paid)
    if ($accessType === 'paid') {
        $hasPaid = DB::table('payments')
            ->where('user_id', $this->id)
            ->where('course_id', $courseId)
            ->where('status', 'completed')
            ->exists();
            
        $accessMap[$courseId] = $hasPaid 
            ? self::COURSE_ACCESS_FULL 
            : null;
        continue;
    }
}
```

#### FIX #2: Remove Duplicate Method

**Delete:** `Course::getAccessTypeForUser()` - it's redundant and inconsistent

**Use:** `User::getCourseAccessLevel()` everywhere

#### FIX #3: Fix Tutor Access

**Move tutor check BEFORE enrollment check:**
```php
// Admins/Tutors bypass all checks
if ($this->isAdmin() || $this->isTutor()) {
    return array_fill_keys($courseIds, self::COURSE_ACCESS_FULL);
}
```

#### FIX #4: Add Access Type Migration

**Ensure all courses have access_type set:**
```php
// Set default access_type based on price
DB::table('courses')
    ->whereNull('access_type')
    ->update([
        'access_type' => DB::raw("CASE 
            WHEN price IS NULL OR price = 0 THEN 'preview'
            ELSE 'paid'
        END")
    ]);
```

### 🧪 TEST SCENARIOS

#### Scenario 1: Free Access Course
- access_type = 'free'
- Admin → FULL ✅
- Tutor → FULL ✅
- Student (enrolled) → FULL ✅
- Student (not enrolled) → NO ACCESS ✅

#### Scenario 2: Preview Access Course
- access_type = 'preview'
- Admin → FULL ✅
- Tutor → FULL ✅
- Student (enrolled + survey) → PREVIEW (5 topics) ✅
- Student (enrolled + no survey) → NO ACCESS ✅
- Student (not enrolled) → NO ACCESS ✅

#### Scenario 3: Paid Access Course
- access_type = 'paid'
- Admin → FULL ✅
- Tutor → FULL ✅
- Student (enrolled + paid) → FULL ✅
- Student (enrolled + not paid) → NO ACCESS ✅
- Student (not enrolled) → NO ACCESS ✅

### 📝 IMPLEMENTATION PRIORITY

1. **HIGH PRIORITY** - Fix `User::getCourseAccessLevels()` to use `access_type`
2. **HIGH PRIORITY** - Move tutor check before enrollment check
3. **MEDIUM PRIORITY** - Remove `Course::getAccessTypeForUser()` 
4. **LOW PRIORITY** - Add data migration for access_type defaults

### 🎯 EXPECTED OUTCOME

After fixes:
- Single source of truth for access control ✅
- Consistent behavior across all controllers ✅
- Tutors always have access (even without enrollment) ✅
- Preview system works correctly ✅
- No more dual logic confusion ✅
