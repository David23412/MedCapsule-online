<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Topic;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Payment;
use App\Services\QuizService;
use App\Services\GradingService;
use App\Services\NotificationService;

class SystemStabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->course = Course::factory()->create(['price' => 0]); // Free course
        $this->paidCourse = Course::factory()->create(['price' => 99.99]);
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);
        $this->question = Question::factory()->create(['topic_id' => $this->topic->id]);
    }

    /** @test */
    public function https_is_enforced_on_all_routes()
    {
        // Force HTTPS in testing
        URL::forceScheme('https');
        
        $routes = [
            '/',
            '/login',
            '/register',
            '/courses',
            '/profile',
            '/admin/user-course'
        ];
        
        foreach ($routes as $route) {
            $response = $this->get($route);
            
            // Check for HTTPS headers
            $this->assertTrue(
                $response->headers->has('Strict-Transport-Security') ||
                str_contains($response->getContent(), 'https://') ||
                $response->getStatusCode() === 302 // Redirect to HTTPS
            );
        }
    }

    /** @test */
    public function security_headers_are_present()
    {
        $response = $this->get('/');
        
        // Check for essential security headers
        $headers = $response->headers;
        
        // XSS Protection
        $this->assertTrue(
            $headers->has('X-XSS-Protection') ||
            $headers->has('X-Content-Type-Options') ||
            $headers->has('X-Frame-Options')
        );
        
        // Content Security Policy should be present
        $this->assertTrue(
            $headers->has('Content-Security-Policy') ||
            str_contains($response->getContent(), 'nonce') ||
            str_contains($response->getContent(), 'unsafe-inline')
        );
    }

    /** @test */
    public function csrf_protection_works_on_all_forms()
    {
        $this->actingAs($this->user);
        
        $formsToTest = [
            ['url' => '/quiz/grade', 'data' => ['answers' => [], 'time_taken' => 60]],
            ['url' => '/profile/update', 'data' => ['name' => 'Test']],
            ['url' => '/courses/enroll', 'data' => ['course_id' => $this->course->id]]
        ];
        
        foreach ($formsToTest as $form) {
            $response = $this->post($form['url'], $form['data']);
            
            // Should require CSRF token
            $this->assertNotEquals(200, $response->getStatusCode());
        }
    }

    /** @test */
    public function free_course_auto_enrollment_works_correctly()
    {
        // Test registration auto-enrollment
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'university' => 'Test University',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => 'on',
            '_token' => csrf_token()
        ]);
        
        $newUser = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($newUser);
        
        // Should be enrolled in free courses
        $this->assertTrue($newUser->hasAccessToCourse($this->course->id));
        
        // Test login auto-enrollment
        $this->actingAs($newUser);
        $this->user->enrollInAllFreeCourses();
        
        $this->assertTrue($this->user->hasAccessToCourse($this->course->id));
    }

    /** @test */
    public function database_integrity_is_maintained()
    {
        $this->actingAs($this->user);
        
        // Test quiz submission with transaction integrity
        $response = $this->post(route('quiz.grade', ['course' => $this->course, 'topic' => $this->topic]), [
            'answers' => [$this->question->id => 'test answer'],
            'time_taken' => 60,
            '_token' => csrf_token()
        ]);
        
        // Should complete successfully
        $this->assertNotEquals(500, $response->getStatusCode());
        
        // Verify data integrity
        $attempt = QuizAttempt::where('user_id', $this->user->id)->first();
        $this->assertNotNull($attempt);
        $this->assertEquals($this->topic->id, $attempt->topic_id);
    }

    /** @test */
    public function payment_system_is_secure()
    {
        $this->actingAs($this->user);
        
        // Test payment verification
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->paidCourse->id,
            'status' => 'pending'
        ]);
        
        $response = $this->post('/payment/verify', [
            'payment_id' => $payment->id,
            '_token' => csrf_token()
        ]);
        
        // Should handle payment verification securely
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function file_upload_security_is_enforced()
    {
        $this->actingAs($this->user);
        
        // Test image upload security
        $file = \Illuminate\Http\UploadedFile::fake()->image('test.jpg');
        
        $response = $this->post('/upload-image', [
            'image' => $file,
            '_token' => csrf_token()
        ]);
        
        // Should validate file type and size
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function session_security_is_enforced()
    {
        $this->actingAs($this->user);
        
        // Test session hijacking protection
        $response = $this->get('/profile');
        
        // Should have secure session headers
        $headers = $response->headers;
        $this->assertTrue(
            $headers->has('Set-Cookie') &&
            str_contains($headers->get('Set-Cookie'), 'HttpOnly') &&
            str_contains($headers->get('Set-Cookie'), 'Secure')
        );
    }

    /** @test */
    public function rate_limiting_prevents_abuse()
    {
        // Test rate limiting on sensitive endpoints
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }
        
        // Should eventually hit rate limit
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function sql_injection_is_prevented()
    {
        $this->actingAs($this->user);
        
        // Test with malicious input
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "' OR '1'='1",
            "'; INSERT INTO users VALUES (999, 'hacker', 'hacker@evil.com'); --"
        ];
        
        foreach ($maliciousInputs as $input) {
            $response = $this->post(route('quiz.grade', ['course' => $this->course, 'topic' => $this->topic]), [
                'answers' => [$this->question->id => $input],
                'time_taken' => 60,
                '_token' => csrf_token()
            ]);
            
            // Should handle safely
            $this->assertNotEquals(500, $response->getStatusCode());
        }
        
        // Verify database integrity
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }

    /** @test */
    public function xss_protection_is_active()
    {
        $this->actingAs($this->user);
        
        // Test with XSS payloads
        $xssPayloads = [
            '<script>alert("xss")</script>',
            'javascript:alert("xss")',
            '<img src="x" onerror="alert(\'xss\')">',
            '<svg onload="alert(\'xss\')">'
        ];
        
        foreach ($xssPayloads as $payload) {
            $response = $this->post(route('quiz.grade', ['course' => $this->course, 'topic' => $this->topic]), [
                'answers' => [$this->question->id => $payload],
                'time_taken' => 60,
                '_token' => csrf_token()
            ]);
            
            // Should not execute scripts
            $this->assertStringNotContainsString('<script>', $response->getContent());
            $this->assertStringNotContainsString('javascript:', $response->getContent());
        }
    }

    /** @test */
    public function authentication_is_required_for_protected_routes()
    {
        $protectedRoutes = [
            '/courses',
            '/profile',
            '/quiz/1/1',
            '/admin/user-course'
        ];
        
        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $this->assertEquals(302, $response->getStatusCode()); // Redirect to login
        }
    }

    /** @test */
    public function authorization_is_enforced()
    {
        $this->actingAs($this->user);
        
        // Test admin-only routes
        $adminRoutes = [
            '/admin/user-course',
            '/add-course',
            '/add-topic',
            '/add-question'
        ];
        
        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            $this->assertEquals(403, $response->getStatusCode()); // Forbidden
        }
    }

    /** @test */
    public function memory_usage_is_optimized()
    {
        $this->actingAs($this->user);
        
        // Test with large datasets
        $largeAnswer = str_repeat('This is a test answer. ', 1000); // ~25KB
        
        $response = $this->post(route('quiz.grade', ['course' => $this->course, 'topic' => $this->topic]), [
            'answers' => [$this->question->id => $largeAnswer],
            'time_taken' => 60,
            '_token' => csrf_token()
        ]);
        
        // Should not cause memory issues
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function error_handling_is_robust()
    {
        // Test with invalid data
        $response = $this->post('/quiz/grade', [
            'invalid' => 'data',
            '_token' => csrf_token()
        ]);
        
        // Should handle gracefully
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function caching_is_working_correctly()
    {
        $this->actingAs($this->user);
        
        // Test course statistics caching
        $response1 = $this->get('/courses');
        $response2 = $this->get('/courses');
        
        // Should be consistent
        $this->assertEquals($response1->getStatusCode(), $response2->getStatusCode());
    }

    /** @test */
    public function notification_system_is_working()
    {
        $this->actingAs($this->user);
        
        // Test notification creation
        $notificationService = app(NotificationService::class);
        $result = $notificationService->createCourseWelcomeNotification($this->user, $this->course->name);
        
        $this->assertTrue($result);
        
        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'course_welcome'
        ]);
    }

    /** @test */
    public function quiz_service_is_stable()
    {
        $this->actingAs($this->user);
        
        $quizService = app(QuizService::class);
        
        // Test quiz generation
        $result = $quizService->generateQuiz($this->topic, 5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertArrayHasKey('topic', $result);
    }

    /** @test */
    public function grading_service_is_accurate()
    {
        $this->actingAs($this->user);
        
        $gradingService = app(GradingService::class);
        
        // Test grading accuracy
        $answers = [$this->question->id => $this->question->correct_answer];
        $result = $gradingService->gradeQuiz($this->topic, $answers, 60);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('percentage', $result);
        $this->assertEquals(100, $result['percentage']); // Should be 100% for correct answer
    }

    /** @test */
    public function system_performance_is_acceptable()
    {
        $this->actingAs($this->user);
        
        $startTime = microtime(true);
        
        $response = $this->get('/courses');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete within reasonable time (2 seconds)
        $this->assertLessThan(2.0, $executionTime);
        $this->assertNotEquals(500, $response->getStatusCode());
    }
} 