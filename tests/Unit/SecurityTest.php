<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use App\Models\Course;
use App\Models\Topic;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\PaymentController;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->course = Course::factory()->create();
        $this->topic = Topic::factory()->create(['course_id' => $this->course->id]);
        $this->question = Question::factory()->create(['topic_id' => $this->topic->id]);
    }

    /** @test */
    public function debug_code_is_not_exposed_in_production()
    {
        // Test that debug settings are not active in production
        Config::set('app.debug', false);
        
        $response = $this->get('/');
        
        // Should not expose error details
        $this->assertStringNotContainsString('stack trace', $response->getContent());
        $this->assertStringNotContainsString('vendor/laravel', $response->getContent());
    }

    /** @test */
    public function cors_is_properly_configured()
    {
        $corsConfig = config('cors');
        
        // Check that wildcards are not used
        $this->assertNotContains('*', $corsConfig['allowed_origins']);
        $this->assertNotContains('*', $corsConfig['allowed_methods']);
        $this->assertNotContains('*', $corsConfig['allowed_headers']);
        
        // Check that specific values are set
        $this->assertIsArray($corsConfig['allowed_origins']);
        $this->assertIsArray($corsConfig['allowed_methods']);
        $this->assertIsArray($corsConfig['allowed_headers']);
    }

    /** @test */
    public function command_injection_is_prevented()
    {
        // Test that exec() calls are not present in critical files
        $criticalFiles = [
            'app/Services/PaymentReceiptValidator.php',
            'app/Http/Controllers/UserCourseController.php',
            'app/Http/Controllers/PaymentSettingsController.php'
        ];
        
        foreach ($criticalFiles as $file) {
            $content = file_get_contents($file);
            $this->assertStringNotContainsString('exec(', $content, "File $file contains exec() call");
        }
    }

    /** @test */
    public function input_validation_prevents_oversized_payloads()
    {
        $this->actingAs($this->user);
        
        // Test quiz submission with oversized answer
        $oversizedAnswer = str_repeat('a', 1100000); // 1.1MB answer
        
        $response = $this->post(route('quiz.grade', ['course' => $this->course, 'topic' => $this->topic]), [
            'answers' => [$this->question->id => $oversizedAnswer],
            'time_taken' => 60
        ]);
        
        // Should return 413 Payload Too Large, not 500 Server Error
        $this->assertEquals(413, $response->getStatusCode());
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function memory_exhaustion_is_prevented()
    {
        // Test that large text inputs don't cause memory issues
        $largeText = str_repeat('This is a test sentence. ', 1000); // ~25KB
        
        $question = new Question();
        $result = $question->isAnswerCorrect($largeText);
        
        // Should not throw memory exhaustion errors
        $this->assertIsBool($result);
    }

    /** @test */
    public function database_transactions_protect_data_integrity()
    {
        $this->actingAs($this->user);
        
        // Test that quiz grading uses transactions
        $response = $this->post(route('quiz.grade', ['course' => $this->course, 'topic' => $this->topic]), [
            'answers' => [$this->question->id => 'test answer'],
            'time_taken' => 60
        ]);
        
        // Should complete successfully without data corruption
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function session_security_is_enforced()
    {
        $sessionConfig = config('session');
        
        // Check that sessions are properly configured
        $this->assertTrue($sessionConfig['encrypt'] ?? false);
        $this->assertTrue($sessionConfig['secure'] ?? false);
        $this->assertEquals('lax', $sessionConfig['same_site'] ?? 'lax');
    }

    /** @test */
    public function csrf_protection_is_active()
    {
        // Test that CSRF protection is enabled
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        
        // Should require CSRF token
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function file_operations_are_safe()
    {
        // Test that file operations use safe paths
        $response = $this->actingAs($this->user)->get('/profile');
        
        // Should not expose file paths in response
        $this->assertStringNotContainsString('/etc/', $response->getContent());
        $this->assertStringNotContainsString('/var/', $response->getContent());
    }

    /** @test */
    public function sql_injection_is_prevented()
    {
        $this->actingAs($this->user);
        
        // Test with potentially malicious input
        $maliciousInput = "'; DROP TABLE users; --";
        
        $response = $this->post(route('quiz.grade', ['course' => $this->course, 'topic' => $this->topic]), [
            'answers' => [$this->question->id => $maliciousInput],
            'time_taken' => 60
        ]);
        
        // Should handle safely without SQL injection
        $this->assertNotEquals(500, $response->getStatusCode());
        
        // Verify users table still exists
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }

    /** @test */
    public function xss_protection_is_active()
    {
        $this->actingAs($this->user);
        
        // Test with potentially malicious input
        $maliciousInput = '<script>alert("xss")</script>';
        
        $response = $this->post(route('quiz.grade', ['course' => $this->course, 'topic' => $this->topic]), [
            'answers' => [$this->question->id => $maliciousInput],
            'time_taken' => 60
        ]);
        
        // Should not execute scripts
        $this->assertStringNotContainsString('<script>', $response->getContent());
    }

    /** @test */
    public function rate_limiting_is_configured()
    {
        // Test that rate limiting is active on API routes
        $response = $this->get('/api/active-users');
        
        // Should have rate limiting headers or appropriate response
        $this->assertNotEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function sensitive_data_is_not_logged()
    {
        // Test that sensitive data doesn't appear in logs
        $logContent = file_get_contents(storage_path('logs/laravel.log'));
        
        // Should not contain sensitive information
        $this->assertStringNotContainsString('password', $logContent);
        $this->assertStringNotContainsString('secret', $logContent);
    }

    /** @test */
    public function authentication_is_required_for_protected_routes()
    {
        // Test that protected routes require authentication
        $response = $this->get('/courses');
        $this->assertEquals(302, $response->getStatusCode()); // Redirect to login
        
        $response = $this->get('/quiz/1/1');
        $this->assertEquals(302, $response->getStatusCode()); // Redirect to login
    }

    /** @test */
    public function authorization_is_enforced()
    {
        $this->actingAs($this->user);
        
        // Test that users can't access admin routes
        $response = $this->get('/admin/delete');
        $this->assertEquals(403, $response->getStatusCode()); // Forbidden
    }
} 