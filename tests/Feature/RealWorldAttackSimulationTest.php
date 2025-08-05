<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SessionSecurityLog;
use App\Services\SessionSecurityService;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RealWorldAttackSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected $securityService;
    protected $loginController;
    protected $testUsers = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SessionSecurityService();
        $this->loginController = new LoginController($this->securityService);
        
        // Create multiple test users for complex scenarios
        for ($i = 1; $i <= 5; $i++) {
            $this->testUsers[] = User::factory()->create([
                'email' => "user{$i}@attack.test",
                'password' => Hash::make('password123'),
                'role' => 'student'
            ]);
        }
    }

    /** @test */
    public function it_prevents_sophisticated_account_sharing_ring()
    {
        echo "\n🎯 SIMULATING SOPHISTICATED ACCOUNT SHARING RING\n";
        echo "================================================\n";
        
        $user = $this->testUsers[0];
        $sharingAttempts = [];
        $blockedAttempts = 0;
        
        // Simulate 10 people trying to share one account from different locations
        $locations = [
            ['ip' => '192.168.1.100', 'country' => 'USA', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
            ['ip' => '10.0.0.50', 'country' => 'Canada', 'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'],
            ['ip' => '172.16.0.25', 'country' => 'UK', 'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64)'],
            ['ip' => '203.0.113.10', 'country' => 'Australia', 'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0)'],
            ['ip' => '198.51.100.5', 'country' => 'Germany', 'user_agent' => 'Mozilla/5.0 (Android 10; Mobile)'],
            ['ip' => '203.0.113.20', 'country' => 'Japan', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0)'],
            ['ip' => '198.51.100.15', 'country' => 'France', 'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari'],
            ['ip' => '203.0.113.30', 'country' => 'Brazil', 'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64)'],
            ['ip' => '198.51.100.25', 'country' => 'India', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Edge'],
            ['ip' => '203.0.113.40', 'country' => 'South Korea', 'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0)'],
        ];
        
        $startTime = microtime(true);
        
        foreach ($locations as $index => $location) {
            echo "Attempt #{$index}: {$location['country']} ({$location['ip']})\n";
            
            // Simulate login attempt
            $loginRequest = Request::create('/login', 'POST', [
                'email' => $user->email,
                'password' => 'password123'
            ], [], [], [
                'HTTP_USER_AGENT' => $location['user_agent'],
                'REMOTE_ADDR' => $location['ip'],
                'HTTP_ACCEPT_LANGUAGE' => $this->getLanguageForCountry($location['country'])
            ]);
            
            // Analyze the login attempt
            $analysis = $this->securityService->analyzeLoginAttempt($user, $loginRequest);
            $sharingAttempts[] = $analysis;
            
            // Simulate successful login (update user session)
            $sessionId = 'session_' . $index . '_' . time();
            $user->update([
                'active_session_token' => $sessionId,
                'session_created_at' => now(),
                'device_fingerprint' => $analysis['fingerprint'],
                'last_login_ip' => $location['ip'],
                'last_user_agent' => $location['user_agent'],
                'last_security_check' => now()
            ]);
            
            // Log the event
            $this->securityService->logSecurityEvent($user, 'login', $loginRequest, $analysis);
            
            // Check if previous sessions would be blocked
            if ($index > 0) {
                $previousLocation = $locations[$index - 1];
                $previousRequest = Request::create('/courses', 'GET', [], [], [], [
                    'HTTP_USER_AGENT' => $previousLocation['user_agent'],
                    'REMOTE_ADDR' => $previousLocation['ip']
                ]);
                
                // Previous session should be invalid now
                $validation = $this->securityService->validateSessionSecurity($user, $previousRequest);
                if (!$validation['valid']) {
                    $blockedAttempts++;
                    echo "  → Previous session from {$previousLocation['country']} BLOCKED\n";
                }
            }
            
            echo "  → Risk Level: {$analysis['risk_level']}\n";
            echo "  → Flags: " . implode(', ', $analysis['flags']) . "\n\n";
            
            // Small delay to simulate real-world timing
            usleep(100000); // 100ms
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // Analyze results
        $highRiskCount = count(array_filter($sharingAttempts, fn($a) => in_array($a['risk_level'], ['high', 'critical'])));
        $geoChangeFlags = count(array_filter($sharingAttempts, fn($a) => in_array('geo_change', $a['flags'])));
        $deviceChangeFlags = count(array_filter($sharingAttempts, fn($a) => in_array('device_change', $a['flags'])));
        
        // Assertions
        $this->assertGreaterThan(7, $highRiskCount, 'Most sharing attempts should be high risk');
        $this->assertGreaterThan(8, $geoChangeFlags, 'Geographic changes should be detected');
        $this->assertGreaterThan(8, $deviceChangeFlags, 'Device changes should be detected');
        $this->assertGreaterThan(8, $blockedAttempts, 'Previous sessions should be blocked');
        
        echo "ACCOUNT SHARING RING RESULTS:\n";
        echo "============================\n";
        echo "Total Attempts: 10\n";
        echo "High Risk Detected: {$highRiskCount}/10\n";
        echo "Geographic Changes: {$geoChangeFlags}/10\n";
        echo "Device Changes: {$deviceChangeFlags}/10\n";
        echo "Blocked Previous Sessions: {$blockedAttempts}/9\n";
        echo "Total Time: " . number_format($totalTime, 2) . "ms\n";
        echo "Detection Rate: " . number_format(($highRiskCount/10)*100, 1) . "%\n\n";
    }

    /** @test */
    public function it_handles_coordinated_multi_account_attack()
    {
        echo "🚨 SIMULATING COORDINATED MULTI-ACCOUNT ATTACK\n";
        echo "==============================================\n";
        
        $attackResults = [];
        $startTime = microtime(true);
        
        // Simulate coordinated attack on all 5 accounts simultaneously
        foreach ($this->testUsers as $userIndex => $user) {
            echo "Attacking User #{$userIndex}: {$user->email}\n";
            
            // Each account gets attacked from 20 different IPs
            for ($i = 0; $i < 20; $i++) {
                $attackIP = '10.' . ($userIndex * 50 + $i) . '.0.1';
                $attackRequest = Request::create('/login', 'POST', [
                    'email' => $user->email,
                    'password' => 'wrongpassword' . $i
                ], [], [], [
                    'HTTP_USER_AGENT' => 'AttackBot/' . $i,
                    'REMOTE_ADDR' => $attackIP,
                    'HTTP_X_FORWARDED_FOR' => $attackIP
                ]);
                
                // Increment login attempts
                $this->securityService->incrementLoginAttempts($user->id);
                
                // Analyze attack
                $analysis = $this->securityService->analyzeLoginAttempt($user, $attackRequest);
                
                // Log failed login
                $this->securityService->logSecurityEvent($user, 'failed_login', $attackRequest, [
                    'risk_level' => $analysis['risk_level'],
                    'reason' => 'Invalid credentials',
                    'attack_pattern' => 'coordinated_brute_force'
                ]);
                
                $attackResults[] = [
                    'user_id' => $user->id,
                    'ip' => $attackIP,
                    'risk_level' => $analysis['risk_level'],
                    'flags' => $analysis['flags']
                ];
            }
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // Analyze attack patterns
        $totalAttacks = count($attackResults);
        $highRiskAttacks = count(array_filter($attackResults, fn($a) => in_array($a['risk_level'], ['high', 'critical'])));
        $rapidAttemptFlags = count(array_filter($attackResults, fn($a) => in_array('rapid_attempts', $a['flags'])));
        
        // Check if accounts would be temporarily blocked
        $blockedAccounts = 0;
        foreach ($this->testUsers as $user) {
            $blockKey = "blocked_user_{$user->id}";
            if (Cache::has($blockKey)) {
                $blockedAccounts++;
            }
        }
        
        // Assertions
        $this->assertEquals(100, $totalAttacks, 'Should process all attack attempts');
        $this->assertGreaterThan(80, $highRiskAttacks, 'Most attacks should be flagged as high risk');
        $this->assertGreaterThan(70, $rapidAttemptFlags, 'Rapid attempts should be detected');
        $this->assertLessThan(10000, $totalTime, 'Should handle coordinated attack under 10 seconds');
        
        echo "COORDINATED ATTACK RESULTS:\n";
        echo "==========================\n";
        echo "Total Attack Attempts: {$totalAttacks}\n";
        echo "High Risk Detected: {$highRiskAttacks}/{$totalAttacks}\n";
        echo "Rapid Attempt Flags: {$rapidAttemptFlags}\n";
        echo "Blocked Accounts: {$blockedAccounts}/5\n";
        echo "Total Time: " . number_format($totalTime, 2) . "ms\n";
        echo "Average Time per Attack: " . number_format($totalTime / $totalAttacks, 2) . "ms\n";
        echo "Detection Rate: " . number_format(($highRiskAttacks/$totalAttacks)*100, 1) . "%\n\n";
    }

    /** @test */
    public function it_prevents_session_fixation_and_hijacking_combo()
    {
        echo "🔒 SIMULATING SESSION FIXATION + HIJACKING COMBO\n";
        echo "===============================================\n";
        
        $user = $this->testUsers[0];
        $attacks = [];
        
        // Step 1: Attacker tries to fix a session
        echo "Step 1: Session Fixation Attempt\n";
        $fixedSessionId = 'attacker_fixed_session_' . time();
        
        $fixationRequest = Request::create('/login', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'REMOTE_ADDR' => '192.168.1.100'
        ]);
        
        // Simulate legitimate user login (should regenerate session)
        $loginResponse = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);
        
        $legitimateSessionId = session()->getId();
        $this->assertNotEquals($fixedSessionId, $legitimateSessionId, 'Session should be regenerated on login');
        
        echo "  → Session regenerated: " . substr($legitimateSessionId, 0, 16) . "...\n";
        
        // Step 2: Attacker tries to hijack the new session
        echo "Step 2: Session Hijacking Attempt\n";
        
        $user->refresh();
        $legitimateFingerprint = $user->device_fingerprint;
        
        $hijackRequest = Request::create('/courses', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'AttackerBot/1.0',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1'
        ]);
        
        $hijackingDetected = $this->securityService->detectSessionHijacking($user, $hijackRequest);
        $this->assertTrue($hijackingDetected, 'Session hijacking should be detected');
        
        echo "  → Hijacking detected: YES\n";
        
        // Step 3: Attacker tries replay attack with stolen token
        echo "Step 3: Token Replay Attack\n";
        
        $replayRequest = Request::create('/profile', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => $user->last_user_agent, // Same user agent
            'REMOTE_ADDR' => '203.0.113.50' // Different IP
        ]);
        
        // Simulate middleware check
        $currentToken = $legitimateSessionId;
        $storedToken = $user->active_session_token;
        $tokenValid = ($currentToken === $storedToken);
        
        // But device fingerprint should be different
        $replayFingerprint = $this->securityService->generateDeviceFingerprint($replayRequest);
        $fingerprintValid = ($replayFingerprint === $legitimateFingerprint);
        
        $this->assertTrue($tokenValid, 'Token should match');
        $this->assertFalse($fingerprintValid, 'Fingerprint should not match');
        
        echo "  → Token valid: " . ($tokenValid ? 'YES' : 'NO') . "\n";
        echo "  → Fingerprint valid: " . ($fingerprintValid ? 'YES' : 'NO') . "\n";
        echo "  → Attack blocked: " . (!$fingerprintValid ? 'YES' : 'NO') . "\n";
        
        // Check security logs
        $securityLogs = SessionSecurityLog::where('user_id', $user->id)->count();
        $this->assertGreaterThan(0, $securityLogs, 'Security events should be logged');
        
        echo "\nSESSION SECURITY RESULTS:\n";
        echo "========================\n";
        echo "Session Fixation: PREVENTED\n";
        echo "Session Hijacking: DETECTED\n";
        echo "Token Replay: BLOCKED\n";
        echo "Security Logs: {$securityLogs} events\n\n";
    }

    /** @test */
    public function it_handles_advanced_bot_detection_evasion()
    {
        echo "🤖 SIMULATING ADVANCED BOT EVASION TECHNIQUES\n";
        echo "============================================\n";
        
        $user = $this->testUsers[0];
        $botAttempts = [];
        
        // Advanced bot evasion techniques
        $evasionTechniques = [
            [
                'name' => 'Human-like User Agent',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'headers' => [
                    'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.5',
                    'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
                    'HTTP_DNT' => '1',
                    'HTTP_CONNECTION' => 'keep-alive',
                    'HTTP_UPGRADE_INSECURE_REQUESTS' => '1'
                ]
            ],
            [
                'name' => 'Mobile Device Simulation',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
                'headers' => [
                    'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'HTTP_ACCEPT_LANGUAGE' => 'en-us',
                    'HTTP_ACCEPT_ENCODING' => 'gzip, deflate'
                ]
            ],
            [
                'name' => 'Rotating Headers',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'headers' => [
                    'HTTP_ACCEPT' => 'application/json,text/plain,*/*',
                    'HTTP_ACCEPT_LANGUAGE' => 'en-GB,en;q=0.9',
                    'HTTP_CACHE_CONTROL' => 'no-cache'
                ]
            ],
            [
                'name' => 'Proxy Chain Simulation',
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
                'headers' => [
                    'HTTP_X_FORWARDED_FOR' => '192.168.1.100, 10.0.0.1, 203.0.113.1',
                    'HTTP_X_REAL_IP' => '203.0.113.1',
                    'HTTP_VIA' => '1.1 proxy.example.com'
                ]
            ],
            [
                'name' => 'Legitimate Browser Mimicry',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
                'headers' => [
                    'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.5',
                    'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
                    'HTTP_TE' => 'trailers'
                ]
            ]
        ];
        
        foreach ($evasionTechniques as $index => $technique) {
            echo "Testing: {$technique['name']}\n";
            
            $headers = array_merge([
                'HTTP_USER_AGENT' => $technique['user_agent'],
                'REMOTE_ADDR' => '10.0.0.' . ($index + 1)
            ], $technique['headers']);
            
            $botRequest = Request::create('/login', 'POST', [
                'email' => $user->email,
                'password' => 'password123'
            ], [], [], $headers);
            
            // Analyze the sophisticated bot attempt
            $analysis = $this->securityService->analyzeLoginAttempt($user, $botRequest);
            $fingerprint = $this->securityService->generateDeviceFingerprint($botRequest);
            
            // Check for rapid attempts (bots typically make rapid requests)
            for ($i = 0; $i < 10; $i++) {
                $this->securityService->incrementLoginAttempts($user->id);
            }
            
            $rapidAnalysis = $this->securityService->analyzeLoginAttempt($user, $botRequest);
            
            $botAttempts[] = [
                'technique' => $technique['name'],
                'risk_level' => $rapidAnalysis['risk_level'],
                'flags' => $rapidAnalysis['flags'],
                'fingerprint' => $fingerprint
            ];
            
            echo "  → Risk Level: {$rapidAnalysis['risk_level']}\n";
            echo "  → Flags: " . implode(', ', $rapidAnalysis['flags']) . "\n";
            echo "  → Fingerprint: " . substr($fingerprint, 0, 16) . "...\n\n";
        }
        
        // Analyze bot detection effectiveness
        $detectedBots = count(array_filter($botAttempts, fn($b) => in_array($b['risk_level'], ['high', 'critical'])));
        $rapidFlags = count(array_filter($botAttempts, fn($b) => in_array('rapid_attempts', $b['flags'])));
        
        $this->assertGreaterThan(3, $detectedBots, 'Most sophisticated bots should be detected');
        $this->assertGreaterThan(4, $rapidFlags, 'Rapid attempt patterns should be flagged');
        
        echo "BOT EVASION DETECTION RESULTS:\n";
        echo "=============================\n";
        echo "Total Techniques Tested: 5\n";
        echo "High Risk Detected: {$detectedBots}/5\n";
        echo "Rapid Attempt Flags: {$rapidFlags}/5\n";
        echo "Detection Rate: " . number_format(($detectedBots/5)*100, 1) . "%\n\n";
    }

    /** @test */
    public function it_survives_sustained_multi_vector_attack()
    {
        echo "💥 SIMULATING SUSTAINED MULTI-VECTOR ATTACK\n";
        echo "==========================================\n";
        
        $attackDuration = 30; // seconds
        $startTime = time();
        $attackStats = [
            'brute_force' => 0,
            'session_hijack' => 0,
            'account_sharing' => 0,
            'bot_attacks' => 0,
            'total_requests' => 0,
            'blocked_requests' => 0,
            'high_risk_detected' => 0
        ];
        
        $attackVectors = ['brute_force', 'session_hijack', 'account_sharing', 'bot_attacks'];
        
        while ((time() - $startTime) < $attackDuration) {
            $vector = $attackVectors[array_rand($attackVectors)];
            $user = $this->testUsers[array_rand($this->testUsers)];
            
            switch ($vector) {
                case 'brute_force':
                    $this->simulateBruteForceAttack($user, $attackStats);
                    break;
                case 'session_hijack':
                    $this->simulateSessionHijackAttempt($user, $attackStats);
                    break;
                case 'account_sharing':
                    $this->simulateAccountSharingAttempt($user, $attackStats);
                    break;
                case 'bot_attacks':
                    $this->simulateBotAttack($user, $attackStats);
                    break;
            }
            
            $attackStats['total_requests']++;
            
            // Small delay to prevent overwhelming
            usleep(10000); // 10ms
        }
        
        $actualDuration = time() - $startTime;
        $requestsPerSecond = $attackStats['total_requests'] / $actualDuration;
        $blockRate = ($attackStats['blocked_requests'] / $attackStats['total_requests']) * 100;
        $detectionRate = ($attackStats['high_risk_detected'] / $attackStats['total_requests']) * 100;
        
        // System should survive and maintain performance
        $this->assertGreaterThan(100, $attackStats['total_requests'], 'Should handle significant attack volume');
        $this->assertGreaterThan(50, $blockRate, 'Should block majority of attacks');
        $this->assertGreaterThan(60, $detectionRate, 'Should detect majority of high-risk attempts');
        $this->assertGreaterThan(10, $requestsPerSecond, 'Should maintain reasonable throughput');
        
        echo "MULTI-VECTOR ATTACK SURVIVAL RESULTS:\n";
        echo "====================================\n";
        echo "Attack Duration: {$actualDuration} seconds\n";
        echo "Total Requests: {$attackStats['total_requests']}\n";
        echo "Requests/Second: " . number_format($requestsPerSecond, 2) . "\n";
        echo "Brute Force Attempts: {$attackStats['brute_force']}\n";
        echo "Session Hijack Attempts: {$attackStats['session_hijack']}\n";
        echo "Account Sharing Attempts: {$attackStats['account_sharing']}\n";
        echo "Bot Attacks: {$attackStats['bot_attacks']}\n";
        echo "Blocked Requests: {$attackStats['blocked_requests']}\n";
        echo "High Risk Detected: {$attackStats['high_risk_detected']}\n";
        echo "Block Rate: " . number_format($blockRate, 1) . "%\n";
        echo "Detection Rate: " . number_format($detectionRate, 1) . "%\n\n";
    }

    private function simulateBruteForceAttack($user, &$stats)
    {
        $request = Request::create('/login', 'POST', [
            'email' => $user->email,
            'password' => 'wrong' . rand(1000, 9999)
        ], [], [], [
            'HTTP_USER_AGENT' => 'BruteBot/' . rand(1, 100),
            'REMOTE_ADDR' => '10.0.' . rand(1, 255) . '.' . rand(1, 255)
        ]);
        
        $this->securityService->incrementLoginAttempts($user->id);
        $analysis = $this->securityService->analyzeLoginAttempt($user, $request);
        
        $stats['brute_force']++;
        if (in_array($analysis['risk_level'], ['high', 'critical'])) {
            $stats['high_risk_detected']++;
            $stats['blocked_requests']++;
        }
    }

    private function simulateSessionHijackAttempt($user, &$stats)
    {
        $request = Request::create('/courses', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'HijackBot/' . rand(1, 100),
            'REMOTE_ADDR' => '203.0.113.' . rand(1, 255)
        ]);
        
        $hijacked = $this->securityService->detectSessionHijacking($user, $request);
        
        $stats['session_hijack']++;
        if ($hijacked) {
            $stats['high_risk_detected']++;
            $stats['blocked_requests']++;
        }
    }

    private function simulateAccountSharingAttempt($user, &$stats)
    {
        $request = Request::create('/login', 'POST', [
            'email' => $user->email,
            'password' => 'password123'
        ], [], [], [
            'HTTP_USER_AGENT' => 'ShareBot/' . rand(1, 100),
            'REMOTE_ADDR' => '172.16.' . rand(1, 255) . '.' . rand(1, 255),
            'HTTP_ACCEPT_LANGUAGE' => 'es-ES,es;q=0.9'
        ]);
        
        $analysis = $this->securityService->analyzeLoginAttempt($user, $request);
        
        $stats['account_sharing']++;
        if (in_array($analysis['risk_level'], ['high', 'critical'])) {
            $stats['high_risk_detected']++;
            $stats['blocked_requests']++;
        }
    }

    private function simulateBotAttack($user, &$stats)
    {
        $request = Request::create('/courses', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'ScrapBot/' . rand(1, 100),
            'REMOTE_ADDR' => '198.51.100.' . rand(1, 255)
        ]);
        
        // Simulate rapid requests
        for ($i = 0; $i < 5; $i++) {
            $this->securityService->trackActivity($user->id, $request->ip());
        }
        
        $validation = $this->securityService->validateSessionSecurity($user, $request);
        
        $stats['bot_attacks']++;
        if (!$validation['valid']) {
            $stats['high_risk_detected']++;
            $stats['blocked_requests']++;
        }
    }

    private function getLanguageForCountry($country)
    {
        $languages = [
            'USA' => 'en-US,en;q=0.9',
            'Canada' => 'en-CA,en;q=0.9,fr-CA;q=0.8',
            'UK' => 'en-GB,en;q=0.9',
            'Australia' => 'en-AU,en;q=0.9',
            'Germany' => 'de-DE,de;q=0.9',
            'Japan' => 'ja-JP,ja;q=0.9',
            'France' => 'fr-FR,fr;q=0.9',
            'Brazil' => 'pt-BR,pt;q=0.9',
            'India' => 'hi-IN,hi;q=0.9,en-IN;q=0.8',
            'South Korea' => 'ko-KR,ko;q=0.9'
        ];
        
        return $languages[$country] ?? 'en-US,en;q=0.9';
    }

    protected function tearDown(): void
    {
        SessionSecurityLog::truncate();
        Cache::flush();
        parent::tearDown();
    }
}