<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class SessionSecurityServiceTest extends TestCase
{
    private string $originalDefaultConnection;
    private mixed $originalSqliteDatabase;
    private string $originalSessionDriver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = (string) config('database.default');
        $this->originalSqliteDatabase = config('database.connections.sqlite.database');
        $this->originalSessionDriver = (string) config('session.driver');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'session.driver' => 'database',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');

        config([
            'database.default' => $this->originalDefaultConnection,
            'database.connections.sqlite.database' => $this->originalSqliteDatabase,
            'session.driver' => $this->originalSessionDriver,
        ]);

        DB::reconnect($this->originalDefaultConnection);

        parent::tearDown();
    }

    public function test_session_metadata_sync_tolerates_default_sessions_table(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        DB::table('sessions')->insert([
            'id' => 'session-id',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $user = new User();
        $user->forceFill(['id' => 10, 'email' => 'student@example.com']);

        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.24',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser',
        ]);

        $method = new ReflectionMethod(SessionSecurityService::class, 'syncDatabaseSessionMetadata');
        $method->setAccessible(true);
        $method->invoke(new SessionSecurityService(), $user, $request, 'session-id', 'fingerprint');

        $session = DB::table('sessions')->where('id', 'session-id')->first();

        $this->assertSame('192.168.1.24', $session->ip_address);
        $this->assertSame('Mozilla/5.0 Test Browser', $session->user_agent);
    }

    public function test_private_and_ipv6_addresses_do_not_trigger_geo_change(): void
    {
        $service = new SessionSecurityService();
        $method = new ReflectionMethod(SessionSecurityService::class, 'isDifferentGeolocation');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service, '192.168.1.10', '10.0.0.15'));
        $this->assertFalse($method->invoke($service, '2001:db8::1', '2001:db8::2'));
    }

    public function test_session_token_mismatch_is_allowed_by_default(): void
    {
        config(['features.login_single_session_enforced' => false]);

        $user = new User();
        $user->forceFill([
            'id' => 10,
            'email' => 'student@example.com',
            'active_session_token' => 'previous-session-id',
        ]);

        $request = $this->requestWithSession('current-session-id');

        $result = (new SessionSecurityService())->validateSessionSecurity($user, $request);

        $this->assertTrue($result['valid']);
        $this->assertSame('allow', $result['action']);
    }

    public function test_session_token_mismatch_can_still_be_enforced(): void
    {
        config(['features.login_single_session_enforced' => true]);

        $user = new User();
        $user->forceFill([
            'id' => 10,
            'email' => 'student@example.com',
            'active_session_token' => 'previous-session-id',
        ]);

        $request = $this->requestWithSession('current-session-id');

        $result = (new SessionSecurityService())->validateSessionSecurity($user, $request);

        $this->assertFalse($result['valid']);
        $this->assertSame('logout', $result['action']);
    }

    public function test_ip_anomalies_are_logged_without_forcing_logout_by_default(): void
    {
        config(['features.login_anomaly_force_logout_enabled' => false]);

        $user = new User();
        $user->forceFill([
            'id' => 10,
            'email' => 'student@example.com',
            'last_login_ip' => '8.8.8.8',
            'device_fingerprint' => str_repeat('a', 64),
        ]);

        $request = $this->requestWithSession('current-session-id', [
            'REMOTE_ADDR' => '1.1.1.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser',
        ]);

        $result = (new SessionSecurityService())->validateSessionSecurity($user, $request);

        $this->assertTrue($result['valid']);
        $this->assertSame('allow', $result['action']);
    }

    public function test_failed_login_attempts_apply_temporary_block_at_threshold(): void
    {
        config([
            'features.login_failed_attempt_limit' => 3,
            'features.login_failed_block_minutes' => 15,
        ]);

        Cache::forget('login_attempts_10');
        Cache::forget('blocked_user_10');

        $service = new SessionSecurityService();

        $this->assertSame(1, $service->recordFailedLoginAttempt(10));
        $this->assertFalse(Cache::has('blocked_user_10'));

        $this->assertSame(2, $service->recordFailedLoginAttempt(10));
        $this->assertFalse(Cache::has('blocked_user_10'));

        $this->assertSame(3, $service->recordFailedLoginAttempt(10));
        $this->assertTrue(Cache::has('blocked_user_10'));
    }

    public function test_successful_login_can_clear_failed_login_state(): void
    {
        Cache::put('login_attempts_10', 3, 900);
        Cache::put('blocked_user_10', true, 900);

        (new SessionSecurityService())->clearLoginAttemptState(10);

        $this->assertFalse(Cache::has('login_attempts_10'));
        $this->assertFalse(Cache::has('blocked_user_10'));
    }

    private function requestWithSession(string $sessionId, array $server = []): Request
    {
        $request = Request::create('/courses', 'GET', [], [], [], array_merge([
            'REMOTE_ADDR' => '192.168.1.24',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser',
        ], $server));

        $session = new Store('test-session', new ArraySessionHandler(120));
        $session->setId($sessionId);
        $session->start();
        $request->setLaravelSession($session);

        return $request;
    }
}
