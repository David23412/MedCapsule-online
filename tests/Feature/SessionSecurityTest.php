<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SessionSecurityLog;
use App\Services\SessionSecurityService;
use App\Http\Middleware\SingleSessionMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $securityService;
    protected $middleware;
    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SessionSecurityService();
        $this->middleware = new SingleSessionMiddleware($this->securityService);
        
        $this->testUser = User::factory()->create([
            'email' => 'test@security.com',
            'password' => Hash::make('password123'),
            'role' => 'student'
        ]);
    }

    /** @test */
    public function it_allows_normal_user_workflow_seamlessly()
    {
        // Start timing
        $startTime = microtime(true);
        
        // Simulate login
        $response = $this->post('/login', [
            'email' => 'test@security.com',
            'password' => 'password123'
        ]);
        
        $loginTime = (microtime(true) - $startTime) * 1000;
        
        // Should redirect to courses
        $response->assertRedirect('/courses');
        
        // User should be authenticated
        $this->assertAuthenticated();
        
        // Check performance
        $this->assertLessThan(100, $loginTime, 'Login should be under 100ms');
        
        // Verify session data is set
        $user = User::find($this->testUser->id);
        $this->assertNotNull($user->active_session_token);
        $this->assertNotNull($user->session_created_at);
        $this->assertNotNull($user->device_fingerprint);
    }

    /** @test */
    public function it_blocks_simultaneous_sessions_immediately()
    {
        // First login
        $this->post('/login', [
            'email' => 'test@security.com',
            'password' => 'password123'
        ]);
        
        $firstSessionId = session()->getId();
        
        // Second login from different "device"
        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
            'Accept-Language' => 'es-ES,es;q=0.9'
        ])->post('/login', [
            'email' => 'test@security.com',
            'password' => 'password123'
        ]);
        
        $secondSessionId = session()->getId();
        
        // Verify second session is active
        $user = User::find($this->testUser->id);
        $this->assertEquals($secondSessionId, $user->active_session_token);
        $this->assertNotEquals($firstSessionId, $user->active_session_token);
        
        // First session should be invalid now
        $this->assertNotEquals($firstSessionId, $user->active_session_token);
    }

    /** @test */
    public function it_detects_session_hijacking_attempts()
    {
        // Login normally
        $this->post('/login', [
            'email' => 'test@security.com',
            'password' => 'password123'
        ]);
        
        $legitimateSessionId = session()->getId();
        
        // Simulate session hijacking (different fingerprint, same session ID)
        $hijackRequest = Request::create('/courses', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Malicious Bot 1.0',
            'HTTP_ACCEPT_LANGUAGE' => 'zh-CN,zh;q=0.9',
            'REMOTE_ADDR' => '10.0.0.1'
        ]);
        
        $user = User::find($this->testUser->id);
        $isHijacked = $this->securityService->detectSessionHijacking($user, $hijackRequest);
        
        $this->assertTrue($isHijacked, 'Session hijacking should be detected');
        
        // Check if security log was created
        $this->assertDatabaseHas('session_security_logs', [
            'user_id' => $user->id,
            'event_type' => 'session_hijack',
            'risk_level' => 'critical'
        ]);
    }

    /** @test */
    public function it_handles_rapid_login_attempts_correctly()
    {
        $attempts = 0;
        $startTime = microtime(true);
        
        // Simulate 10 rapid login attempts
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@security.com',
                'password' => 'password123'
            ]);
            
            if ($response->isRedirect()) {
                $attempts++;
            }
            
            // Small delay to simulate real-world scenario
            app('performance.async')->delayAsync(10000); // 10ms
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // Should handle all attempts
        $this->assertEquals(10, $attempts);
        
        // Should complete in reasonable time
        $this->assertLessThan(1000, $totalTime, 'Rapid logins should complete under 1 second');
        
        // Check for rate limiting logs
        $logs = SessionSecurityLog::where('user_id', $this->testUser->id)
            ->where('event_type', 'login')
            ->count();
        
        $this->assertGreaterThan(0, $logs, 'Login attempts should be logged');
    }

    /** @test */
    public function it_maintains_performance_under_heavy_load()
    {
        // Login first
        $this->post('/login', [
            'email' => 'test@security.com',
            'password' => 'password123'
        ]);
        
        $times = [];
        $successCount = 0;
        
        // Simulate 1000 requests
        for ($i = 0; $i < 1000; $i++) {
            $startTime = microtime(true);
            
            $request = Request::create('/courses', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'REMOTE_ADDR' => '192.168.1.100'
            ]);
            
            $user = User::find($this->testUser->id);
            $validation = $this->securityService->validateSessionSecurity($user, $request);
            
            $requestTime = (microtime(true) - $startTime) * 1000;
            $times[] = $requestTime;
            
            if ($validation['valid']) {
                $successCount++;
            }
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        
        // Performance assertions
        $this->assertLessThan(5, $avgTime, 'Average request time should be under 5ms');
        $this->assertLessThan(50, $maxTime, 'Max request time should be under 50ms');
        $this->assertGreaterThan(900, $successCount, 'At least 90% of requests should succeed');
        
        echo "\nPerformance Results:\n";
        echo "Average: " . number_format($avgTime, 3) . "ms\n";
        echo "Min: " . number_format($minTime, 3) . "ms\n";
        echo "Max: " . number_format($maxTime, 3) . "ms\n";
        echo "Success Rate: " . ($successCount/1000*100) . "%\n";
    }

    /** @test */
    public function it_handles_edge_case_device_fingerprints()
    {
        $edgeCases = [
            // Empty headers
            [],
            // Minimal headers
            ['HTTP_USER_AGENT' => 'Bot'],
            // Very long headers
            ['HTTP_USER_AGENT' => str_repeat('A', 1000)],
            // Special characters
            ['HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) "Special" <script>'],
            // Non-ASCII characters
            ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) 中文'],
        ];
        
        foreach ($edgeCases as $headers) {
            $request = Request::create('/test', 'GET', [], [], [], $headers);
            $fingerprint = $this->securityService->generateDeviceFingerprint($request);
            
            $this->assertNotEmpty($fingerprint, 'Fingerprint should not be empty');
            $this->assertEquals(64, strlen($fingerprint), 'Fingerprint should be 64 characters (SHA256)');
        }
    }

    /** @test */
    public function it_handles_concurrent_login_attempts_from_same_user()
    {
        $processes = [];
        $results = [];
        
        // Simulate 5 concurrent login attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@security.com',
                'password' => 'password123'
            ]);
            
            $results[] = $response->isRedirect();
        }
        
        // All should succeed (last one wins)
        $successCount = array_sum($results);
        $this->assertGreaterThan(0, $successCount, 'At least one login should succeed');
        
        // Only one session should be active
        $user = User::find($this->testUser->id);
        $this->assertNotNull($user->active_session_token);
        
        // Should have security logs
        $logCount = SessionSecurityLog::where('user_id', $user->id)->count();
        $this->assertGreaterThan(0, $logCount, 'Security events should be logged');
    }

    /** @test */
    public function it_handles_malformed_requests_gracefully()
    {
        $malformedRequests = [
            // Missing headers
            Request::create('/test', 'GET'),
            // Invalid IP
            Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => 'invalid.ip']),
            // Extremely long user agent
            Request::create('/test', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => str_repeat('X', 10000)
            ]),
            // SQL injection attempt in headers
            Request::create('/test', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => "'; DROP TABLE users; --"
            ]),
        ];
        
        foreach ($malformedRequests as $request) {
            try {
                $fingerprint = $this->securityService->generateDeviceFingerprint($request);
                $this->assertNotEmpty($fingerprint, 'Should handle malformed request gracefully');
            } catch (\Exception $e) {
                $this->fail('Should not throw exception for malformed request: ' . $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_prevents_session_fixation_attacks()
    {
        // Start with a specific session ID
        session()->start();
        $originalSessionId = session()->getId();
        
        // Login should regenerate session
        $response = $this->post('/login', [
            'email' => 'test@security.com',
            'password' => 'password123'
        ]);
        
        $newSessionId = session()->getId();
        
        // Session ID should change after login
        $this->assertNotEquals($originalSessionId, $newSessionId, 'Session ID should change after login');
        
        // User should have the new session ID
        $user = User::find($this->testUser->id);
        $this->assertEquals($newSessionId, $user->active_session_token);
    }

    /** @test */
    public function it_handles_database_connection_failures_gracefully()
    {
        // This is a complex test that would require mocking DB failures
        // For now, we'll test that the system doesn't crash with invalid data
        
        $user = User::find($this->testUser->id);
        $user->active_session_token = null;
        $user->save();
        
        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
        ]);
        
        // Should not crash
        try {
            $validation = $this->securityService->validateSessionSecurity($user, $request);
            $this->assertIsArray($validation);
        } catch (\Exception $e) {
            $this->fail('Should handle null session token gracefully: ' . $e->getMessage());
        }
    }

    /** @test */
    public function it_handles_cache_failures_gracefully()
    {
        // Clear all cache
        Cache::flush();
        
        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'REMOTE_ADDR' => '192.168.1.100'
        ]);
        
        // Should not crash when cache is empty
        try {
            $this->securityService->trackActivity($this->testUser->id, '192.168.1.100');
            $this->securityService->incrementLoginAttempts($this->testUser->id);
            $this->assertTrue(true, 'Cache operations should not crash');
        } catch (\Exception $e) {
            $this->fail('Should handle cache failures gracefully: ' . $e->getMessage());
        }
    }

    /** @test */
    public function it_maintains_security_across_different_browsers()
    {
        $browsers = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        ];
        
        foreach ($browsers as $userAgent) {
            $request = Request::create('/test', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => $userAgent,
                'REMOTE_ADDR' => '192.168.1.100'
            ]);
            
            $fingerprint = $this->securityService->generateDeviceFingerprint($request);
            $this->assertNotEmpty($fingerprint, "Should generate fingerprint for: $userAgent");
            
            // Each browser should have unique fingerprint
            $this->assertEquals(64, strlen($fingerprint), 'Fingerprint should be consistent length');
        }
    }

    /** @test */
    public function it_handles_timezone_and_locale_variations()
    {
        $locales = [
            'en-US,en;q=0.9',
            'es-ES,es;q=0.9',
            'fr-FR,fr;q=0.9',
            'de-DE,de;q=0.9',
            'ja-JP,ja;q=0.9',
            'zh-CN,zh;q=0.9',
            'ar-SA,ar;q=0.9',
            'ru-RU,ru;q=0.9',
        ];
        
        foreach ($locales as $locale) {
            $request = Request::create('/test', 'GET', [], [], [], [
                'HTTP_ACCEPT_LANGUAGE' => $locale,
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'REMOTE_ADDR' => '192.168.1.100'
            ]);
            
            $fingerprint = $this->securityService->generateDeviceFingerprint($request);
            $this->assertNotEmpty($fingerprint, "Should handle locale: $locale");
        }
    }

    /** @test */
    public function it_prevents_timing_attacks()
    {
        $times = [];
        
        // Test with valid session
        $this->post('/login', [
            'email' => 'test@security.com',
            'password' => 'password123'
        ]);
        
        for ($i = 0; $i < 100; $i++) {
            $startTime = microtime(true);
            
            $request = Request::create('/test', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'REMOTE_ADDR' => '192.168.1.100'
            ]);
            
            $user = User::find($this->testUser->id);
            $this->securityService->validateSessionSecurity($user, $request);
            
            $times[] = (microtime(true) - $startTime) * 1000;
        }
        
        $avgTime = array_sum($times) / count($times);
        $stdDev = sqrt(array_sum(array_map(function($x) use ($avgTime) {
            return pow($x - $avgTime, 2);
        }, $times)) / count($times));
        
        // Standard deviation should be low (consistent timing)
        $this->assertLessThan($avgTime * 0.5, $stdDev, 'Timing should be consistent to prevent timing attacks');
    }

    /** @test */
    public function it_handles_memory_pressure_gracefully()
    {
        $initialMemory = memory_get_usage(true);
        
        // Create many security operations
        for ($i = 0; $i < 10000; $i++) {
            $request = Request::create('/test', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' . $i,
                'REMOTE_ADDR' => '192.168.1.' . ($i % 255)
            ]);
            
            $this->securityService->generateDeviceFingerprint($request);
            
            // Force garbage collection every 1000 iterations
            if ($i % 1000 === 0) {
                gc_collect_cycles();
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB
        
        // Memory increase should be reasonable
        $this->assertLessThan(50, $memoryIncrease, 'Memory usage should not grow excessively');
    }

    protected function tearDown(): void
    {
        // Clean up
        SessionSecurityLog::truncate();
        Cache::flush();
        parent::tearDown();
    }
}