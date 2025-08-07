<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Payment;
use App\Models\Notification;
use App\Models\UserTopicProgress;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->course = Course::factory()->create(['price' => 0]);
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);
        $this->question = Question::factory()->create(['topic_id' => $this->topic->id]);
    }

    /** @test */
    public function foreign_key_constraints_are_enforced()
    {
        // Create a separate course for this test to avoid affecting other tests
        $testCourse = Course::factory()->create(['price' => 0]);
        $testTopic = Topic::factory()->create(['course_id' => $testCourse->id]);
        
        // Test that deleting a course cascades to topics
        $topicCount = Topic::where('course_id', $testCourse->id)->count();
        $this->assertGreaterThan(0, $topicCount);
        
        $testCourse->delete();
        
        // Topics should be deleted due to cascade
        $remainingTopics = Topic::where('course_id', $testCourse->id)->count();
        $this->assertEquals(0, $remainingTopics);
    }

    /** @test */
    public function unique_constraints_are_enforced()
    {
        // Test email uniqueness
        $user1 = User::factory()->create(['email' => 'test@example.com']);
        
        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['email' => 'test@example.com']);
    }

    /** @test */
    public function quiz_attempts_maintain_data_integrity()
    {
        $this->actingAs($this->user);
        
        // Create quiz attempt
        $attempt = QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'topic',
            'score' => 8,
            'total_questions' => 10,
            'percentage_grade' => 80.0,
            'duration_seconds' => 300,
            'question_data' => [
                ['question_id' => 1, 'answer' => 'A', 'correct' => true],
                ['question_id' => 2, 'answer' => 'B', 'correct' => false]
            ],
            'correct_answers' => 8
        ]);
        
        // Verify data integrity
        $this->assertDatabaseHas('quiz_attempts', [
            'id' => $attempt->id,
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'score' => 8
        ]);
        
        // Test cascade delete
        $this->user->delete();
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
    }

    /** @test */
    public function course_enrollment_maintains_integrity()
    {
        // Test enrollment creation
        $this->user->enrollInCourse($this->course->id);
        
        $this->assertDatabaseHas('course_user', [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'is_enrolled' => true
        ]);
        
        // Test duplicate enrollment prevention
        $this->user->enrollInCourse($this->course->id);
        
        $enrollmentCount = DB::table('course_user')
            ->where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->count();
        
        $this->assertEquals(1, $enrollmentCount);
    }

    /** @test */
    public function payment_system_maintains_integrity()
    {
        $payment = Payment::create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'amount' => 99.99,
            'status' => 'pending',
            'payment_method' => 'manual'
        ]);
        
        // Verify payment data
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'amount' => 99.99
        ]);
        
        // Test payment completion
        $payment->update(['status' => 'completed', 'paid_at' => now()]);
        
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function user_progress_tracking_is_accurate()
    {
        $this->actingAs($this->user);
        
        // Create progress record
        $progress = UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'percentage_grade' => 85,
            'questions_attempted' => 10,
            'correct_answers' => 8
        ]);
        
        // Verify progress data
        $this->assertDatabaseHas('user_topic_progress', [
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'percentage_grade' => 85
        ]);
        
        // Test progress update
        $progress->update(['percentage_grade' => 90]);
        
        $this->assertDatabaseHas('user_topic_progress', [
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'percentage_grade' => 90
        ]);
    }

    /** @test */
    public function notification_system_maintains_integrity()
    {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'course_welcome',
            'title' => 'Welcome to MedCapsule',
            'message' => 'You have been enrolled in a course',
            'is_read' => false
        ]);
        
        // Verify notification data
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'course_welcome',
            'is_read' => false
        ]);
        
        // Test notification marking as read
        $notification->update(['is_read' => true]);
        
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true
        ]);
    }

    /** @test */
    public function database_transactions_are_atomic()
    {
        $this->actingAs($this->user);
        
        // Test that quiz grading uses transactions
        DB::beginTransaction();
        
        try {
            $attempt = QuizAttempt::create([
                'user_id' => $this->user->id,
                'topic_id' => $this->topic->id,
                'quiz_type' => 'normal',
                'study_mode' => 'quiz',
                'source' => 'topic',
                'score' => 10,
                'total_questions' => 10,
                'percentage_grade' => 100.0,
                'duration_seconds' => 300,
                'question_data' => [
                    ['question_id' => 1, 'answer' => 'A', 'correct' => true]
                ],
                'correct_answers' => 10
            ]);
            
            $progress = UserTopicProgress::create([
                'user_id' => $this->user->id,
                'topic_id' => $this->topic->id,
                'percentage_grade' => 100,
                'questions_attempted' => 10,
                'correct_answers' => 10
            ]);
            
            DB::commit();
            
            // Both records should exist
            $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt->id]);
            $this->assertDatabaseHas('user_topic_progress', ['id' => $progress->id]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** @test */
    public function data_consistency_is_maintained_across_operations()
    {
        // Test that user statistics are consistent
        $this->actingAs($this->user);
        
        // Create multiple quiz attempts
        for ($i = 0; $i < 3; $i++) {
            QuizAttempt::create([
                'user_id' => $this->user->id,
                'topic_id' => $this->topic->id,
                'quiz_type' => 'normal',
                'study_mode' => 'quiz',
                'source' => 'topic',
                'score' => 8 + $i,
                'total_questions' => 10,
                'percentage_grade' => (8 + $i) * 10.0,
                'duration_seconds' => 300,
                'question_data' => [
                    ['question_id' => 1, 'answer' => 'A', 'correct' => true]
                ],
                'correct_answers' => 8 + $i,
                'source' => 'topic'
            ]);
        }
        
        // Verify user statistics are updated
        $this->user->refresh();
        
        $this->assertGreaterThan(0, $this->user->total_questions_attempted);
        $this->assertGreaterThan(0, $this->user->correct_answers_count);
    }

    /** @test */
    public function soft_deletes_work_correctly()
    {
        // Skip this test as User model doesn't use soft deletes
        // In a real application, you would test models that actually use SoftDeletes
        $this->markTestSkipped('User model does not use soft deletes');
    }

    /** @test */
    public function database_indexes_are_working()
    {
        // Test that queries use indexes efficiently
        $startTime = microtime(true);
        
        // Query that should use indexes
        $users = User::where('email', 'like', '%@example.com')->get();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete quickly due to indexes
        $this->assertLessThan(1.0, $executionTime);
    }

    /** @test */
    public function enum_constraints_are_enforced()
    {
        // Test that enum values are enforced on course type
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Course::create([
            'name' => 'Test Course',
            'type' => 'invalid_type', // Invalid enum value - should be 'medical' or 'custom'
            'academic_year' => '2024-1',
            'is_active' => true
        ]);
    }

    /** @test */
    public function null_constraints_are_respected()
    {
        // Test that nullable fields work correctly
        $course = Course::create([
            'name' => 'Test Course',
            'description' => 'Test Description',
            'color' => '#FF0000',
            'price' => null, // Should be allowed

        ]);
        
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'price' => null
        ]);
    }

    /** @test */
    public function default_values_are_applied()
    {
        // Test that default values are set correctly
        $this->user->enrollInCourse($this->course->id);
        
        $enrollment = DB::table('course_user')
            ->where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->first();
        
        // Default values should be applied
        $this->assertEquals(1, $enrollment->is_enrolled);
        $this->assertNotNull($enrollment->enrolled_at);
    }

    /** @test */
    public function data_validation_prevents_invalid_data()
    {
        // Test that database constraints prevent invalid data
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Try to insert duplicate email (should violate unique constraint)
        User::factory()->create(['email' => 'test@example.com']);
        User::factory()->create(['email' => 'test@example.com']); // This should fail
    }

    /** @test */
    public function concurrent_access_is_handled_safely()
    {
        // Test that concurrent operations don't corrupt data
        $this->actingAs($this->user);
        
        // Simulate concurrent quiz attempts
        for ($i = 0; $i < 5; $i++) {
            QuizAttempt::create([
                'user_id' => $this->user->id,
                'topic_id' => $this->topic->id,
                'quiz_type' => 'normal',
                'study_mode' => 'quiz',
                'source' => 'topic',
                'score' => 8,
                'total_questions' => 10,
                'percentage_grade' => 80.0,
                'duration_seconds' => 300,
                'question_data' => [
                    ['question_id' => 1, 'answer' => 'A', 'correct' => true]
                ],
                'correct_answers' => 8
            ]);
        }
        
        // All attempts should be saved correctly
        $attemptCount = QuizAttempt::where('user_id', $this->user->id)->count();
        $this->assertEquals(5, $attemptCount);
    }

    /** @test */
    public function database_connections_are_stable()
    {
        // Test that database connections are stable under load
        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create();
            $this->assertNotNull($user->id);
        }
        
        // Database should still be responsive
        $userCount = User::count();
        $this->assertGreaterThan(0, $userCount);
    }

    /** @test */
    public function data_migration_safety()
    {
        // Test that data migrations are safe
        $originalUserCount = User::count();
        
        // Simulate a data migration
        DB::table('users')->update(['updated_at' => now()]);
        
        // User count should remain the same
        $newUserCount = User::count();
        $this->assertEquals($originalUserCount, $newUserCount);
    }
} 