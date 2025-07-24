# Design Document

## Overview

The Flexible Course System redesigns the rigid medical course structure to support both traditional medical courses and custom tutor-created courses. The system provides dynamic categorization, streamlined assignment flows, and bulletproof performance while maintaining backward compatibility. Key innovations include bypassing source selection for assignments, real-time countdown timers, and post-assignment topic access for continued learning.

## Architecture

### System Components
- **Course Type Management**: Dynamic categorization based on course type (medical vs custom)
- **Assignment Flow Engine**: Streamlined assignment execution with timer integration
- **Dynamic Categorization System**: Client-side filtering with real-time pill generation
- **Source Management**: Intelligent source selection and post-assignment access
- **Performance Layer**: Optimized database queries, caching, and real-time updates

### Data Flow
1. Course type determination → Dynamic categorization generation → Client-side filtering
2. Assignment creation → Source specification → Timer setup → Direct quiz access
3. Assignment completion → Score calculation → Post-assignment topic access
4. Topic locking → Access control → Visual indicators → Student notifications

## Components and Interfaces

### Frontend Architecture

#### Dynamic Categorization System
```javascript
class DynamicCategorization {
    constructor(courseType, topics) {
        this.courseType = courseType;
        this.topics = topics;
        this.activePills = new Set();
    }
    
    generatePills() {
        if (this.courseType === 'medical') {
            return this.generateMedicalPills();
        } else {
            return this.generateCustomPills();
        }
    }
    
    generateMedicalPills() {
        const medicalCategories = ['Quiz', 'Cases', 'Practical'];
        return medicalCategories.filter(category => 
            this.topics.some(topic => topic.categorization === category)
        );
    }
    
    generateCustomPills() {
        const uniqueCategories = [...new Set(
            this.topics
                .map(topic => topic.categorization)
                .filter(cat => cat !== null && cat !== '')
        )];
        return uniqueCategories.length > 0 ? uniqueCategories : [];
    }
    
    filterTopics(selectedPill) {
        if (!selectedPill || selectedPill === 'all') {
            return this.topics;
        }
        return this.topics.filter(topic => topic.categorization === selectedPill);
    }
    
    updateURL(selectedPill) {
        const url = new URL(window.location);
        if (selectedPill && selectedPill !== 'all') {
            url.searchParams.set('category', selectedPill);
        } else {
            url.searchParams.delete('category');
        }
        window.history.pushState({}, '', url);
    }
}
```

#### Assignment Flow Controller
```javascript
class AssignmentFlowController {
    constructor(topicId, assignmentData) {
        this.topicId = topicId;
        this.assignmentData = assignmentData;
        this.timer = null;
    }
    
    startAssignment() {
        // Bypass source selection completely
        if (this.assignmentData.mode === 'source_specific') {
            this.startSourceSpecificAssignment();
        } else {
            this.startCompleteTopicAssignment();
        }
    }
    
    startSourceSpecificAssignment() {
        const assignedSource = this.assignmentData.assigned_source;
        this.redirectToQuiz({
            source: assignedSource,
            mode: 'assignment',
            timeLimit: this.assignmentData.time_limit
        });
    }
    
    startCompleteTopicAssignment() {
        this.redirectToQuiz({
            mode: 'complete_topic_assignment',
            timeLimit: this.assignmentData.time_limit,
            allSources: true
        });
    }
    
    redirectToQuiz(params) {
        // Start timer immediately
        if (params.timeLimit) {
            this.startCountdownTimer(params.timeLimit);
        }
        
        // Redirect to quiz with assignment context
        window.location.href = `/quiz/${this.topicId}?${new URLSearchParams(params)}`;
    }
    
    startCountdownTimer(timeLimit) {
        this.timer = new AssignmentCountdown(this.topicId, timeLimit);
        this.timer.start();
    }
}
```

#### Real-time Assignment Timer
```javascript
class AssignmentCountdown {
    constructor(assignmentId, timeLimit) {
        this.assignmentId = assignmentId;
        this.timeLimit = timeLimit; // in minutes
        this.startTime = Date.now();
        this.endTime = this.startTime + (timeLimit * 60 * 1000);
        this.interval = null;
        this.urgencyThreshold = timeLimit * 0.1; // 10% remaining
    }
    
    start() {
        this.updateDisplay();
        this.interval = setInterval(() => {
            this.updateDisplay();
        }, 1000);
    }
    
    updateDisplay() {
        const now = Date.now();
        const remaining = Math.max(0, this.endTime - now);
        
        if (remaining === 0) {
            this.handleExpiration();
            return;
        }
        
        const minutes = Math.floor(remaining / 60000);
        const seconds = Math.floor((remaining % 60000) / 1000);
        
        const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        this.renderTimer(display, remaining);
        
        // Apply urgency styling
        if (remaining <= this.urgencyThreshold * 60 * 1000) {
            this.applyUrgencyStyles();
        }
    }
    
    renderTimer(display, remaining) {
        const timerElements = document.querySelectorAll('.assignment-timer');
        timerElements.forEach(element => {
            element.textContent = display;
            element.setAttribute('data-remaining', remaining);
        });
    }
    
    applyUrgencyStyles() {
        const timerElements = document.querySelectorAll('.assignment-timer');
        timerElements.forEach(element => {
            element.classList.add('timer-urgent');
        });
    }
    
    handleExpiration() {
        clearInterval(this.interval);
        this.autoSubmitAssignment();
    }
    
    autoSubmitAssignment() {
        // Auto-submit current progress
        fetch(`/assignments/${this.assignmentId}/auto-submit`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(() => {
            window.location.href = `/assignments/${this.assignmentId}/expired`;
        });
    }
}
```

### Backend Architecture

#### Course Type Service
```php
class CourseTypeService
{
    public function determineCourseType(Course $course): string
    {
        // Return explicit type if set
        if ($course->type) {
            return $course->type;
        }
        
        // Default to medical for backward compatibility
        return 'medical';
    }
    
    public function getCategorizations(Course $course): array
    {
        $courseType = $this->determineCourseType($course);
        
        if ($courseType === 'medical') {
            return $this->getMedicalCategorizations($course);
        }
        
        return $this->getCustomCategorizations($course);
    }
    
    private function getMedicalCategorizations(Course $course): array
    {
        $predefinedCategories = ['Quiz', 'Cases', 'Practical'];
        $existingCategories = $course->topics()
            ->whereIn('categorization', $predefinedCategories)
            ->distinct('categorization')
            ->pluck('categorization')
            ->toArray();
            
        return array_intersect($predefinedCategories, $existingCategories);
    }
    
    private function getCustomCategorizations(Course $course): array
    {
        return $course->topics()
            ->whereNotNull('categorization')
            ->where('categorization', '!=', '')
            ->distinct('categorization')
            ->pluck('categorization')
            ->toArray();
    }
}
```

#### Enhanced Assignment Service
```php
class AssignmentService
{
    public function createAssignment(array $data): Assignment
    {
        DB::beginTransaction();
        
        try {
            $assignment = Assignment::create([
                'tutor_id' => $data['tutor_id'],
                'student_id' => $data['student_id'],
                'topic_id' => $data['topic_id'],
                'course_id' => $data['course_id'],
                'deadline' => $data['deadline'],
                'time_limit' => $data['time_limit'] ?? null,
                'assignment_mode' => $data['assignment_mode'], // 'source_specific' or 'complete_topic'
                'assigned_source' => $data['assigned_source'] ?? null,
                'status' => 'active'
            ]);
            
            // Send notification
            $this->notificationService->sendAssignmentNotification($assignment);
            
            DB::commit();
            return $assignment;
            
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    
    public function calculateAssignmentScore(Assignment $assignment, array $answers): float
    {
        if ($assignment->assignment_mode === 'source_specific') {
            return $this->calculateSourceSpecificScore($assignment, $answers);
        }
        
        return $this->calculateCompleteTopicScore($assignment, $answers);
    }
    
    private function calculateSourceSpecificScore(Assignment $assignment, array $answers): float
    {
        $sourceQuestions = Question::where('topic_id', $assignment->topic_id)
            ->where('source', $assignment->assigned_source)
            ->pluck('id')
            ->toArray();
            
        $correctAnswers = 0;
        $totalQuestions = count($sourceQuestions);
        
        foreach ($answers as $questionId => $answer) {
            if (in_array($questionId, $sourceQuestions)) {
                $question = Question::find($questionId);
                if ($question && $question->correct_answer === $answer) {
                    $correctAnswers++;
                }
            }
        }
        
        return $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
    }
    
    public function handleAssignmentExpiry(Assignment $assignment): void
    {
        $assignment->update([
            'status' => 'expired',
            'expired_at' => now()
        ]);
        
        // Topic remains accessible for practice mode
        // No topic locking occurs
    }
}
```

#### Topic Access Controller
```php
class TopicAccessController
{
    public function checkAccess(Topic $topic, User $user): array
    {
        $access = [
            'can_access' => true,
            'mode' => 'practice',
            'assignment' => null,
            'locked' => false,
            'lock_reason' => null
        ];
        
        // Check if topic is locked
        if ($topic->is_locked) {
            $access['can_access'] = false;
            $access['locked'] = true;
            $access['lock_reason'] = $topic->lock_reason;
            return $access;
        }
        
        // Check for active assignment
        $activeAssignment = Assignment::where('topic_id', $topic->id)
            ->where('student_id', $user->id)
            ->where('status', 'active')
            ->first();
            
        if ($activeAssignment) {
            $access['mode'] = 'assignment';
            $access['assignment'] = $activeAssignment;
        }
        
        return $access;
    }
    
    public function getTopicSources(Topic $topic, User $user): array
    {
        $accessInfo = $this->checkAccess($topic, $user);
        
        if (!$accessInfo['can_access']) {
            return [];
        }
        
        if ($accessInfo['mode'] === 'assignment') {
            $assignment = $accessInfo['assignment'];
            
            if ($assignment->assignment_mode === 'source_specific') {
                return [$assignment->assigned_source];
            }
            
            // Complete topic mode - return all sources
            return $topic->getAvailableSources();
        }
        
        // Practice mode - return all sources
        return $topic->getAvailableSources();
    }
}
```

## Data Models

### Course Model Extensions
```php
class Course extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type', // 'medical' or 'custom'
        'year',
        'created_by'
    ];
    
    protected $casts = [
        'type' => 'string'
    ];
    
    public function getTypeAttribute($value)
    {
        return $value ?? 'medical'; // Default to medical for backward compatibility
    }
    
    public function topics()
    {
        return $this->hasMany(Topic::class);
    }
    
    public function getCategorizations(): array
    {
        return app(CourseTypeService::class)->getCategorizations($this);
    }
}
```

### Assignment Model Enhancements
```php
class Assignment extends Model
{
    protected $fillable = [
        'tutor_id',
        'student_id',
        'topic_id',
        'course_id',
        'deadline',
        'time_limit', // in minutes
        'assignment_mode', // 'source_specific' or 'complete_topic'
        'assigned_source',
        'status',
        'score',
        'completed_at',
        'expired_at'
    ];
    
    protected $casts = [
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'expired_at' => 'datetime',
        'time_limit' => 'integer',
        'score' => 'float'
    ];
    
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->deadline > now() && 
               !$this->completed_at;
    }
    
    public function isExpired(): bool
    {
        return $this->status === 'expired' || 
               $this->deadline <= now();
    }
    
    public function getTimeRemainingAttribute(): ?int
    {
        if (!$this->isActive() || !$this->time_limit) {
            return null;
        }
        
        $elapsed = now()->diffInMinutes($this->created_at);
        return max(0, $this->time_limit - $elapsed);
    }
    
    public function shouldShowTimer(): bool
    {
        return $this->isActive() && 
               $this->time_limit && 
               $this->time_remaining > 0;
    }
}
```

### Topic Model Extensions
```php
class Topic extends Model
{
    protected $fillable = [
        'name',
        'course_id',
        'categorization',
        'is_locked',
        'locked_by_tutor_id',
        'lock_reason',
        'locked_at'
    ];
    
    protected $casts = [
        'is_locked' => 'boolean',
        'locked_at' => 'datetime'
    ];
    
    public function getAvailableSources(): array
    {
        return $this->questions()
            ->whereNotNull('source')
            ->distinct('source')
            ->pluck('source')
            ->toArray();
    }
    
    public function getQuestionsForSource(string $source): Collection
    {
        return $this->questions()->where('source', $source)->get();
    }
    
    public function getAllQuestions(): Collection
    {
        return $this->questions()->get();
    }
    
    public function isAccessibleBy(User $user): bool
    {
        if ($this->is_locked) {
            return false;
        }
        
        // Additional access logic can be added here
        return true;
    }
}
```

## Database Schema Changes

### Courses Table Modifications
```sql
ALTER TABLE courses 
ADD COLUMN type ENUM('medical', 'custom') DEFAULT 'medical' AFTER description,
ADD INDEX idx_courses_type (type);
```

### Assignments Table Enhancements
```sql
ALTER TABLE assignments 
ADD COLUMN time_limit INT UNSIGNED NULL AFTER deadline,
ADD COLUMN assignment_mode ENUM('source_specific', 'complete_topic') DEFAULT 'source_specific' AFTER time_limit,
ADD COLUMN assigned_source VARCHAR(255) NULL AFTER assignment_mode,
ADD COLUMN expired_at TIMESTAMP NULL AFTER completed_at,
ADD INDEX idx_assignments_mode (assignment_mode),
ADD INDEX idx_assignments_source (assigned_source),
ADD INDEX idx_assignments_status_deadline (status, deadline);
```

### Topics Table Enhancements
```sql
ALTER TABLE topics 
ADD COLUMN is_locked BOOLEAN DEFAULT FALSE AFTER categorization,
ADD COLUMN locked_by_tutor_id BIGINT UNSIGNED NULL AFTER is_locked,
ADD COLUMN lock_reason TEXT NULL AFTER locked_by_tutor_id,
ADD COLUMN locked_at TIMESTAMP NULL AFTER lock_reason,
ADD INDEX idx_topics_locked (is_locked),
ADD INDEX idx_topics_categorization (categorization),
ADD FOREIGN KEY (locked_by_tutor_id) REFERENCES users(id) ON DELETE SET NULL;
```

## Performance Optimizations

### Database Indexing Strategy
```sql
-- Optimize course topic queries
CREATE INDEX idx_topics_course_categorization ON topics(course_id, categorization);

-- Optimize assignment queries
CREATE INDEX idx_assignments_student_status ON assignments(student_id, status);
CREATE INDEX idx_assignments_tutor_course ON assignments(tutor_id, course_id);

-- Optimize question source queries
CREATE INDEX idx_questions_topic_source ON questions(topic_id, source);
```

### Caching Strategy
```php
class CourseCategorizationCache
{
    public function getCategorizations(Course $course): array
    {
        return Cache::remember(
            "course_categorizations_{$course->id}",
            3600, // 1 hour
            fn() => app(CourseTypeService::class)->getCategorizations($course)
        );
    }
    
    public function invalidate(Course $course): void
    {
        Cache::forget("course_categorizations_{$course->id}");
    }
}
```

### Frontend Performance
```javascript
// Debounced filtering for smooth performance
class OptimizedTopicFilter {
    constructor() {
        this.filterDebounce = this.debounce(this.performFilter.bind(this), 100);
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    performFilter(category) {
        requestAnimationFrame(() => {
            const topics = document.querySelectorAll('.topic-card');
            topics.forEach(topic => {
                const shouldShow = !category || 
                    topic.dataset.categorization === category;
                topic.style.display = shouldShow ? 'block' : 'none';
            });
        });
    }
}
```

## Security Considerations

### Assignment Access Control
```php
class AssignmentSecurityMiddleware
{
    public function handle($request, Closure $next)
    {
        $assignment = $request->route('assignment');
        $user = $request->user();
        
        // Verify tutor-student relationship
        if (!TutorStudentRelationship::hasAccess($assignment->tutor_id, $user->id)) {
            abort(403, 'Unauthorized access to assignment');
        }
        
        // Verify assignment is still active
        if (!$assignment->isActive()) {
            return redirect()->route('assignment.expired', $assignment);
        }
        
        return $next($request);
    }
}
```

### Source Access Validation
```php
class SourceAccessValidator
{
    public function validateSourceAccess(Topic $topic, string $source, User $user): bool
    {
        $accessInfo = app(TopicAccessController::class)->checkAccess($topic, $user);
        
        if (!$accessInfo['can_access']) {
            return false;
        }
        
        if ($accessInfo['mode'] === 'assignment') {
            $assignment = $accessInfo['assignment'];
            
            if ($assignment->assignment_mode === 'source_specific') {
                return $source === $assignment->assigned_source;
            }
        }
        
        return in_array($source, $topic->getAvailableSources());
    }
}
```

## Error Handling

### Graceful Degradation
```javascript
class RobustAssignmentSystem {
    constructor() {
        this.fallbackMode = false;
        this.retryAttempts = 3;
    }
    
    async startAssignment(assignmentId) {
        try {
            await this.initializeAssignment(assignmentId);
        } catch (error) {
            console.error('Assignment initialization failed:', error);
            this.enableFallbackMode();
        }
    }
    
    enableFallbackMode() {
        this.fallbackMode = true;
        this.showUserMessage('System is running in offline mode. Your progress will be saved when connection is restored.');
    }
    
    async retryOperation(operation, maxRetries = this.retryAttempts) {
        for (let i = 0; i < maxRetries; i++) {
            try {
                return await operation();
            } catch (error) {
                if (i === maxRetries - 1) throw error;
                await this.delay(1000 * Math.pow(2, i)); // Exponential backoff
            }
        }
    }
    
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
```

## Testing Strategy

### Performance Testing
```php
class CourseSystemPerformanceTest extends TestCase
{
    public function test_course_topics_load_within_200ms()
    {
        $course = Course::factory()->create();
        Topic::factory()->count(100)->create(['course_id' => $course->id]);
        
        $startTime = microtime(true);
        $response = $this->get("/courses/{$course->id}/topics");
        $endTime = microtime(true);
        
        $loadTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $this->assertLessThan(200, $loadTime);
        $response->assertStatus(200);
    }
    
    public function test_categorization_filtering_is_instant()
    {
        $course = Course::factory()->create(['type' => 'medical']);
        Topic::factory()->count(50)->create([
            'course_id' => $course->id,
            'categorization' => 'Quiz'
        ]);
        
        $startTime = microtime(true);
        $categorizations = app(CourseTypeService::class)->getCategorizations($course);
        $endTime = microtime(true);
        
        $queryTime = ($endTime - $startTime) * 1000;
        
        $this->assertLessThan(50, $queryTime); // Should be under 50ms
        $this->assertContains('Quiz', $categorizations);
    }
}
```

### Security Testing
```php
class AssignmentSecurityTest extends TestCase
{
    public function test_students_cannot_access_unauthorized_sources()
    {
        $assignment = Assignment::factory()->create([
            'assignment_mode' => 'source_specific',
            'assigned_source' => 'Kaplan'
        ]);
        
        $response = $this->actingAs($assignment->student)
            ->get("/topics/{$assignment->topic_id}/quiz?source=Practice Sheet");
            
        $response->assertStatus(403);
    }
    
    public function test_assignment_timer_cannot_be_tampered()
    {
        $assignment = Assignment::factory()->create([
            'time_limit' => 60 // 60 minutes
        ]);
        
        // Attempt to manipulate timer via API
        $response = $this->actingAs($assignment->student)
            ->post("/assignments/{$assignment->id}/extend-time", [
                'additional_time' => 30
            ]);
            
        $response->assertStatus(403);
    }
}
```

This design ensures the system will be lightning-fast, bulletproof secure, and provide an exceptional user experience while maintaining complete backward compatibility.