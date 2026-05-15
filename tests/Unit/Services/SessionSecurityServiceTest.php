<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
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
}
