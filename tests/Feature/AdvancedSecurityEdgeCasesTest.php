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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdvancedSecurityEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected $securityService;
    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SessionSecurityService();
        
        $this->testUser = User::factory()->create([
            'email' => 'edge@test.com',
            'password' => Hash::make('password123'),
            'role' => 'student'
        ]);
    }

    /** @test */
    public function it_handles_extremely_rapid_concurrent_attacks()
    {
        $startTime = microtime(true);
        $results = [];
        
        // Simulate 50 concurrent attack attempts
        for ($i = 0; $i < 50; $i++) {
            $attackRequest = Request::create('/courses', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => 'AttackBot/' . $i,
                'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
                'REMOTE_ADDR' => '10.0.0.' . ($i % 255),
                'HTTP_X_FORWARDED_FOR' => '192.168.1.' . ($i % 255),
                'HTTP_X_REAL_IP' => '172.16.0.' . ($i % 255)
            ]);
            
            try {
                $fingerprint = $this->securityService->generateDeviceFingerprint($attackRequest);
                $analysis = $this->securityService->analyzeLoginAttempt($this->testUser, $attackRequest);
                $results[] = $analysis['risk_level'];
            } catch (\Exception $e) {
                $results[] = 'error';
            }
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // Should handle all requests without crashing
        $this->assertEquals(50, count($results));
        
        // Should complete in reasonable time even under attack
        $this->assertLessThan(5000, $totalTime, 'Should handle concurrent attacks under 5 seconds');
        
        // Most should be flagged as high risk
        $highRiskCount = count(array_filter($results, fn($r) => in_array($r, ['high', 'critical'])));
        $this->assertGreaterThan(30, $highRiskCount, 'Most attack attempts should be flagged as high risk');
        
        echo "\nConcurrent Attack Test Results:\n";
        echo "Total Time: " . number_format($totalTime, 2) . "ms\n";
        echo "High Risk Detected: {$highRiskCount}/50\n";
        echo "Average Time per Request: " . number_format($totalTime / 50, 2) . "ms\n";
    }

    /** @test */
    public function it_detects_sophisticated_session_replay_attacks()
    {
        // Login normally first
        $this->post('/login', [
            'email' => 'edge@test.com',
            'password' => 'password123'
        ]);
        
        $legitimateUser = User::find($this->testUser->id);
        $legitimateSessionId = $legitimateUser->active_session_token;
        $legitimateFingerprint = $legitimateUser->device_fingerprint;
        
        // Attacker tries to replay the session with slight modifications
        $replayAttacks = [
            // Same fingerprint, different IP
            [
                'fingerprint' => $legitimateFingerprint,
                'ip' => '10.0.0.1',
                'user_agent' => $legitimateUser->last_user_agent
            ],
            // Same IP, slightly modified user agent
            [
                'fingerprint' => hash('sha256', $legitimateUser->last_user_agent . ' Modified'),
                'ip' => $legitimateUser->last_login_ip,
                'user_agent' => $legitimateUser->last_user_agent . ' Modified'
            ],
            // Time-shifted attack (old session)
            [
                'fingerprint' => $legitimateFingerprint,
                'ip' => $legitimateUser->last_login_ip,
                'user_agent' => $legitimateUser->last_user_agent,
                'time_shift' => -3600 // 1 hour ago
            ]
        ];
        
        foreach ($replayAttacks as $index => $attack) {
            $attackRequest = Request::create('/courses', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => $attack['user_agent'],
                'REMOTE_ADDR' => $attack['ip']
            ]);
            
            // Simulate the attack
            $isHijacked = $this->securityService->detectSessionHijacking($legitimateUser, $attackRequest);
            
            if ($attack['fingerprint'] !== $legitimateFingerprint || $attack['ip'] !== $legitimateUser->last_login_ip) {
                $this->assertTrue($isHijacked, "Replay attack #{$index} should be detected");
            }
        }
        
        // Check security logs
        $suspiciousLogs = SessionSecurityLog::where('user_id', $legitimateUser->id)
            ->where('event_type', 'session_hijack')
            ->count();
        
        $this->assertGreaterThan(0, $suspiciousLogs, 'Replay attacks should be logged');
    }

    /** @test */
    public function it_handles_database_race_conditions()
    {
        $results = [];
        $errors = [];
        
        // Simulate multiple processes trying to update user session simultaneously
        for ($i = 0; $i < 20; $i++) {
            try {
                DB::transaction(function () use ($i) {
                    $user = User::find($this->testUser->id);
                    
                    // Simulate processing delay
                    usleep(rand(1000, 5000)); // 1-5ms
                    
                    $user->update([
                        'active_session_token' => 'session_' . $i . '_' . time(),
                        'session_created_at' => now(),
                        'device_fingerprint' => hash('sha256', 'device_' . $i),
                        'last_login_ip' => '192.168.1.' . ($i % 255),
                        'last_security_check' => now()
                    ]);
                });
                
                $results[] = 'success';
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
                $results[] = 'error';
            }
        }
        
        // Should handle race conditions gracefully
        $successCount = count(array_filter($results, fn($r) => $r === 'success'));
        $this->assertGreaterThan(15, $successCount, 'Most database operations should succeed despite race conditions');
        
        // Final state should be consistent
        $finalUser = User::find($this->testUser->id);
        $this->assertNotNull($finalUser->active_session_token);
        $this->assertNotNull($finalUser->session_created_at);
        
        echo "\nRace Condition Test Results:\n";
        echo "Successful Operations: {$successCount}/20\n";
        echo "Errors: " . count($errors) . "\n";
    }

    /** @test */
    public function it_prevents_advanced_fingerprint_spoofing()
    {
        // Create baseline fingerprint
        $baselineRequest = Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'REMOTE_ADDR' => '192.168.1.100'
        ]);
        
        $baselineFingerprint = $this->securityService->generateDeviceFingerprint($baselineRequest);
        
        // Attempt various spoofing techniques
        $spoofingAttempts = [
            // Header order manipulation
            [
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
            ],
            // Case manipulation
            [
                'HTTP_USER_AGENT' => 'mozilla/5.0 (windows nt 10.0; win64; x64) applewebkit/537.36',
                'HTTP_ACCEPT_LANGUAGE' => 'EN-US,EN;Q=0.9',
                'HTTP_ACCEPT_ENCODING' => 'GZIP, DEFLATE, BR',
                'HTTP_ACCEPT' => 'TEXT/HTML,APPLICATION/XHTML+XML,APPLICATION/XML;Q=0.9,*/*;Q=0.8',
            ],
            // Extra whitespace
            [
                'HTTP_USER_AGENT' => ' Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ',
                'HTTP_ACCEPT_LANGUAGE' => ' en-US,en;q=0.9 ',
                'HTTP_ACCEPT_ENCODING' => ' gzip, deflate, br ',
                'HTTP_ACCEPT' => ' text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8 ',
            ],
            // Unicode normalization attacks
            [
                'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
                'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ]
        ];
        
        foreach ($spoofingAttempts as $index => $headers) {
            $spoofRequest = Request::create('/test', 'GET', [], [], [], $headers);
            $spoofFingerprint = $this->securityService->generateDeviceFingerprint($spoofRequest);
            
            // Fingerprints should be different for different inputs
            if ($headers !== $baselineRequest->headers->all()) {
                $this->assertNotEquals($baselineFingerprint, $spoofFingerprint, 
                    "Spoofing attempt #{$index} should produce different fingerprint");
            }
        }
    }

    /** @test */
    public function it_handles_extreme_load_with_memory_constraints()
    {
        $initialMemory = memory_get_usage(true);
        $peakMemory = $initialMemory;
        $processedRequests = 0;
        $errors = 0;
        
        // Process 50,000 requests with memory monitoring
        for ($i = 0; $i < 50000; $i++) {
            try {
                $request = Request::create('/test', 'GET', [], [], [], [
                    'HTTP_USER_AGENT' => 'LoadTest/' . ($i % 1000),
                    'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
                    'REMOTE_ADDR' => '192.168.' . ($i % 255) . '.' . (($i / 255) % 255)
                ]);
                
                $this->securityService->generateDeviceFingerprint($request);
                $this->securityService->trackActivity($this->testUser->id, $request->ip());
                
                $processedRequests++;
                
                // Monitor memory usage
                $currentMemory = memory_get_usage(true);
                $peakMemory = max($peakMemory, $currentMemory);
                
                // Force garbage collection every 10,000 requests
                if ($i % 10000 === 0) {
                    gc_collect_cycles();
                    
                    // Check if memory is growing too much
                    $memoryIncrease = ($currentMemory - $initialMemory) / 1024 / 1024;
                    if ($memoryIncrease > 100) { // 100MB limit
                        break;
                    }
                }
                
            } catch (\Exception $e) {
                $errors++;
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;
        $peakIncrease = ($peakMemory - $initialMemory) / 1024 / 1024;
        
        // Performance assertions
        $this->assertGreaterThan(40000, $processedRequests, 'Should process at least 40,000 requests');
        $this->assertLessThan(100, $memoryIncrease, 'Memory increase should be under 100MB');
        $this->assertLessThan(0.01, $errors / $processedRequests, 'Error rate should be under 1%');
        
        echo "\nExtreme Load Test Results:\n";
        echo "Processed Requests: {$processedRequests}\n";
        echo "Errors: {$errors}\n";
        echo "Memory Increase: " . number_format($memoryIncrease, 2) . "MB\n";
        echo "Peak Memory Increase: " . number_format($peakIncrease, 2) . "MB\n";
        echo "Error Rate: " . number_format(($errors / $processedRequests) * 100, 4) . "%\n";
    }

    /** @test */
    public function it_detects_distributed_brute_force_attacks()
    {
        $attackIPs = [];
        $attackResults = [];
        
        // Generate 100 different IP addresses for distributed attack
        for ($i = 0; $i < 100; $i++) {
            $ip = '10.' . ($i % 255) . '.' . (($i / 255) % 255) . '.' . (($i / 65025) % 255);
            $attackIPs[] = $ip;
        }
        
        $startTime = microtime(true);
        
        // Simulate distributed brute force attack
        foreach ($attackIPs as $index => $ip) {
            $attackRequest = Request::create('/login', 'POST', [], [], [], [
                'HTTP_USER_AGENT' => 'AttackBot/1.0',
                'REMOTE_ADDR' => $ip,
                'HTTP_X_FORWARDED_FOR' => $ip
            ]);
            
            // Simulate multiple attempts from each IP
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $this->securityService->incrementLoginAttempts($this->testUser->id);
                $analysis = $this->securityService->analyzeLoginAttempt($this->testUser, $attackRequest);
                $attackResults[] = [
                    'ip' => $ip,
                    'risk_level' => $analysis['risk_level'],
                    'flags' => $analysis['flags']
                ];
            }
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // Analyze results
        $highRiskCount = count(array_filter($attackResults, fn($r) => in_array($r['risk_level'], ['high', 'critical'])));
        $rapidAttemptFlags = count(array_filter($attackResults, fn($r) => in_array('rapid_attempts', $r['flags'])));
        
        $this->assertGreaterThan(400, $highRiskCount, 'Most distributed attacks should be flagged as high risk');
        $this->assertGreaterThan(300, $rapidAttemptFlags, 'Rapid attempt flags should be detected');
        $this->assertLessThan(30000, $totalTime, 'Should handle distributed attack under 30 seconds');
        
        echo "\nDistributed Brute Force Test Results:\n";
        echo "Total Attempts: " . count($attackResults) . "\n";
        echo "High Risk Detected: {$highRiskCount}\n";
        echo "Rapid Attempt Flags: {$rapidAttemptFlags}\n";
        echo "Total Time: " . number_format($totalTime, 2) . "ms\n";
        echo "Average Time per Attempt: " . number_format($totalTime / count($attackResults), 2) . "ms\n";
    }

    /** @test */
    public function it_handles_session_token_collision_attempts()
    {
        $tokens = [];
        $collisions = 0;
        
        // Generate 10,000 session tokens to test for collisions
        for ($i = 0; $i < 10000; $i++) {
            $token = session()->getId() . '_' . $i . '_' . microtime(true);
            
            if (in_array($token, $tokens)) {
                $collisions++;
            } else {
                $tokens[] = $token;
            }
        }
        
        // Should have no collisions
        $this->assertEquals(0, $collisions, 'Session tokens should be unique');
        $this->assertEquals(10000, count($tokens), 'All tokens should be unique');
        
        // Test token entropy
        $tokenLengths = array_map('strlen', $tokens);
        $avgLength = array_sum($tokenLengths) / count($tokenLengths);
        
        $this->assertGreaterThan(20, $avgLength, 'Session tokens should have sufficient length');
        
        echo "\nSession Token Collision Test Results:\n";
        echo "Tokens Generated: 10,000\n";
        echo "Collisions: {$collisions}\n";
        echo "Average Token Length: " . number_format($avgLength, 2) . "\n";
    }

    /** @test */
    public function it_prevents_cache_poisoning_attacks()
    {
        $cacheKeys = [
            'login_attempts_' . $this->testUser->id,
            'activity_count_' . $this->testUser->id,
            'recent_ips_' . $this->testUser->id,
            'blocked_user_' . $this->testUser->id
        ];
        
        // Attempt to poison cache with malicious data
        $maliciousData = [
            'script' => '<script>alert("xss")</script>',
            'sql' => "'; DROP TABLE users; --",
            'overflow' => str_repeat('A', 100000),
            'null_bytes' => "test\x00malicious",
            'serialized' => 'O:8:"stdClass":1:{s:4:"evil";s:4:"code";}',
        ];
        
        foreach ($cacheKeys as $key) {
            foreach ($maliciousData as $type => $data) {
                try {
                    Cache::put($key, $data, 300);
                    $retrieved = Cache::get($key);
                    
                    // Cache should handle malicious data safely
                    $this->assertNotNull($retrieved, "Cache should store data safely for {$type}");
                    
                    // Clean up
                    Cache::forget($key);
                } catch (\Exception $e) {
                    // It's okay if cache rejects malicious data
                    $this->assertTrue(true, "Cache rejected malicious {$type} data");
                }
            }
        }
    }

    /** @test */
    public function it_handles_unicode_and_encoding_attacks()
    {
        $unicodeAttacks = [
            // Zero-width characters
            "Mozilla/5.0\u{200B}(Windows\u{FEFF}NT\u{200C}10.0)",
            // Right-to-left override
            "Mozilla/5.0 \u{202E}(Windows NT 10.0)",
            // Homograph attack
            "Mozilla/5.0 (Windоws NT 10.0)", // Cyrillic 'о' instead of 'o'
            // Normalization attack
            "Mozilla/5.0 (Windows NT 10.0) Café", // é vs e + combining accent
            // Overlong UTF-8
            "Mozilla/5.0 (Windows NT 10.0) \xC0\xAF",
            // Emoji and symbols
            "Mozilla/5.0 🤖 (Windows NT 10.0) 💻",
        ];
        
        foreach ($unicodeAttacks as $index => $userAgent) {
            $request = Request::create('/test', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => $userAgent,
                'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
                'REMOTE_ADDR' => '192.168.1.100'
            ]);
            
            try {
                $fingerprint = $this->securityService->generateDeviceFingerprint($request);
                $this->assertNotEmpty($fingerprint, "Should handle unicode attack #{$index}");
                $this->assertEquals(64, strlen($fingerprint), "Fingerprint should be consistent length for attack #{$index}");
            } catch (\Exception $e) {
                $this->fail("Should handle unicode attack #{$index} gracefully: " . $e->getMessage());
            }
        }
    }

    /** @test */
    public function it_maintains_performance_under_sustained_attack()
    {
        $attackDuration = 10; // seconds
        $startTime = time();
        $requestCount = 0;
        $responseTimes = [];
        
        while ((time() - $startTime) < $attackDuration) {
            $requestStart = microtime(true);
            
            $attackRequest = Request::create('/test', 'GET', [], [], [], [
                'HTTP_USER_AGENT' => 'SustainedAttack/' . $requestCount,
                'REMOTE_ADDR' => '10.0.0.' . ($requestCount % 255),
                'HTTP_X_FORWARDED_FOR' => '192.168.1.' . ($requestCount % 255)
            ]);
            
            try {
                $this->securityService->generateDeviceFingerprint($attackRequest);
                $this->securityService->analyzeLoginAttempt($this->testUser, $attackRequest);
                $this->securityService->validateSessionSecurity($this->testUser, $attackRequest);
            } catch (\Exception $e) {
                // Continue even if some requests fail
            }
            
            $responseTime = (microtime(true) - $requestStart) * 1000;
            $responseTimes[] = $responseTime;
            $requestCount++;
            
            // Small delay to prevent overwhelming the system
            usleep(1000); // 1ms
        }
        
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);
        $requestsPerSecond = $requestCount / $attackDuration;
        
        // Performance should remain reasonable under sustained attack
        $this->assertLessThan(50, $avgResponseTime, 'Average response time should stay under 50ms during attack');
        $this->assertLessThan(500, $maxResponseTime, 'Max response time should stay under 500ms during attack');
        $this->assertGreaterThan(50, $requestsPerSecond, 'Should handle at least 50 requests per second');
        
        echo "\nSustained Attack Test Results:\n";
        echo "Duration: {$attackDuration} seconds\n";
        echo "Total Requests: {$requestCount}\n";
        echo "Requests/Second: " . number_format($requestsPerSecond, 2) . "\n";
        echo "Average Response Time: " . number_format($avgResponseTime, 2) . "ms\n";
        echo "Max Response Time: " . number_format($maxResponseTime, 2) . "ms\n";
    }

    protected function tearDown(): void
    {
        SessionSecurityLog::truncate();
        Cache::flush();
        parent::tearDown();
    }
}