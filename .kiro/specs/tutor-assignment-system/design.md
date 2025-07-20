# Design Document

## Overview

The Tutor Assignment System extends the existing MedCapsule platform with homework-style assignments, topic locking capabilities, and enhanced learn mistakes functionality. The system leverages existing models (User, Course, Topic, Question) and adds new Assignment and SkippedQuestion models with comprehensive web interfaces.

## Architecture

### System Components
- **Backend Services**: AssignmentService, TopicLockingService, LearnMistakesService
- **Web Controllers**: AssignmentController, StudentAssignmentController, TopicLockingController
- **Frontend Components**: Assignment counters, countdown timers, skip buttons
- **Integration Points**: Quiz system, notification system, existing dashboard

### Data Flow
1. Tutor creates assignments → AssignmentService validates → Database storage → Student notifications
2. Students view assignments → Assignment categorization in topics → Quiz completion → Assignment completion
3. Topic locking → TopicLockingService → Access control in quiz system
4. Learn mistakes skip → SkippedQuestion storage → Visual indicators in mistakes review

## Components and Interfaces

### Web Controllers

#### AssignmentController
```php
class AssignmentController extends Controller
{
    public function index(Request $request)           // List assignments with filters
    public function create()                         // Show assignment creation form
    public function store(AssignmentRequest $request) // Create new assignment
    public function show(Assignment $assignment)     // Show assignment details
    public function edit(Assignment $assignment)     // Show edit form
    public function update(AssignmentRequest $request, Assignment $assignment) // Update assignment
    public function destroy(Assignment $assignment)  // Delete assignment
    public function bulkCreate()                     // Bulk assignment creation form
    public function bulkStore(BulkAssignmentRequest $request) // Process bulk creation
    public function analytics()                      // Assignment analytics dashboard
    public function extendDeadline(Assignment $assignment, Request $request) // Extend deadline
}
```

#### StudentAssignmentController
```php
class StudentAssignmentController extends Controller
{
    public function index()                          // Student assignment dashboard
    public function show(Assignment $assignment)     // Assignment details for student
    public function complete(Assignment $assignment, Request $request) // Mark as completed
    public function history()                        // Assignment completion history
}
```

#### TopicLockingController
```php
class TopicLockingController extends Controller
{
    public function index()                          // Topic locking management
    public function lock(Topic $topic, Request $request) // Lock topic
    public function unlock(Topic $topic)            // Unlock topic
    public function bulkLock(Request $request)       // Bulk lock topics
    public function bulkUnlock(Request $request)     // Bulk unlock topics
    public function courseTopics(Course $course)    // Course topic management
}
```

### Frontend Components

#### Assignment Counter Component
```javascript
class AssignmentCounter {
    constructor(containerId, refreshInterval = 30000)
    async fetchCount()                               // Get assignment count via AJAX
    updateDisplay(count)                             // Update counter display
    startAutoRefresh()                               // Auto-refresh every 30 seconds
    applyUrgencyStyles(count)                        // Apply attention-grabbing styles
}
```

#### Assignment Countdown Timer
```javascript
class AssignmentCountdown {
    constructor(assignmentId, deadline)
    startCountdown()                                 // Start real-time countdown
    updateDisplay()                                  // Update countdown display
    handleExpiration()                               // Handle deadline expiration
    applyUrgencyStyles(timeRemaining)                // Apply urgency styling
}
```

### Service Layer Enhancements

#### LearnMistakesService
```php
class LearnMistakesService
{
    public function skipQuestion(int $userId, int $questionId, string $sessionType)
    public function getSkippedQuestions(int $userId, string $sessionType = null)
    public function removeSkip(int $userId, int $questionId, string $sessionType)
    public function getQuestionsForSession(int $userId, int $topicId, array $excludeSkipped = true)
    public function getSkipStatistics(int $userId)
}
```

## Data Models

### Assignment Model (Already Implemented)
- Relationships: tutor, student, topic, course
- Business Logic: isOverdue(), canBeCompleted(), markAsCompleted()
- Query Scopes: active(), overdue(), forTutor(), forStudent()

### SkippedQuestion Model (Already Implemented)
- Static Methods: markAsSkipped(), isSkipped(), getSkippedQuestions()
- Analytics: getSkipStatistics(), getMostSkippedTopics()

### Topic Model Extensions (Already Implemented)
- Locking Fields: is_locked, locked_by_tutor_id, lock_reason, locked_at
- Relationship: lockedByTutor()

## Error Handling

### Validation Rules
- Assignment deadlines must be in the future
- Tutor-student relationships must exist for assignment creation
- Topic access validation before quiz attempts
- Duplicate assignment prevention

### Error Responses
- 422 Unprocessable Entity for validation errors
- 403 Forbidden for unauthorized access
- 404 Not Found for missing resources
- 409 Conflict for duplicate assignments

### Exception Handling
```php
try {
    $assignment = $this->assignmentService->createAssignment($data);
} catch (InvalidArgumentException $e) {
    return response()->json(['error' => $e->getMessage()], 422);
} catch (UnauthorizedAccessException $e) {
    return response()->json(['error' => 'Unauthorized'], 403);
}
```

## Testing Strategy

### Unit Tests (Already Implemented)
- Assignment model: 12+ test methods
- SkippedQuestion model: 12+ test methods  
- AssignmentService: 15+ test methods
- TopicLockingService: 15+ test methods

### Integration Tests (To Implement)
- Controller tests with request validation
- Feature tests for complete workflows
- API endpoint testing
- Frontend component testing

### Test Coverage Requirements
- Minimum 90% code coverage
- All business logic paths tested
- Edge cases and error conditions covered
- Performance testing for bulk operations

## Security Considerations

### Authorization
- Tutor-student relationship validation
- Assignment access control
- Topic locking permissions
- CSRF protection on all forms

### Data Validation
- Input sanitization
- SQL injection prevention
- XSS protection in views
- Rate limiting on API endpoints

### Privacy
- Student data protection
- Assignment confidentiality
- Audit logging for sensitive operations