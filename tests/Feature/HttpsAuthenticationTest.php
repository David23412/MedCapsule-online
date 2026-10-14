<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Symfony\Component\Process\Process;

class HttpsAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.url' => 'https://localhost',
            'app.force_https' => true,
            'trustedproxy.proxies' => ['172.18.0.1'],
            'trustedproxy.hosts' => ['^localhost$'],
            'session.secure' => false,
        ]);
        // Laravel skips CSRF in tests by default; exercise the real token check.
        $this->app->bind(ValidateCsrfToken::class, HttpsTestCsrfMiddleware::class);
        $this->withServerVariables([
            'REMOTE_ADDR' => '172.18.0.1',
            'HTTP_HOST' => 'localhost',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_USER_AGENT' => 'MedCapsule HTTPS Test',
        ]);
        Route::middleware('web')->match(['POST', 'PUT', 'PATCH', 'DELETE'], '/__https-form-test',
            fn (Request $request) => response()->json(['value' => $request->input('value')])
        );
    }

    public function test_https_proxy_produces_https_forms_and_secure_session_cookie(): void
    {
        $response = $this->get('/register')->assertOk();
        $response->assertSee('action="https://localhost/register"', false);
        $cookie = collect($response->headers->getCookies())->first(
            fn ($cookie) => $cookie->getName() === config('session.cookie')
        );
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
        $this->get('/login')->assertOk()->assertSee('action="https://localhost/login"', false);
    }

    public function test_https_proxy_settings_work_with_cached_configuration(): void
    {
        $cacheFile = tempnam(sys_get_temp_dir(), 'medcapsule-https-config-');
        chmod($cacheFile, 0600);
        file_put_contents($cacheFile, '<?php return '.var_export(config()->all(), true).';');
        $script = <<<'SCRIPT'
            putenv('APP_CONFIG_CACHE='.$argv[2]);
            require $argv[1].'/vendor/autoload.php';
            $app = require $argv[1].'/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            $request = Illuminate\Http\Request::create('http://localhost/register', 'GET', [], [], [], [
                'REMOTE_ADDR' => '172.18.0.1', 'HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '443',
            ]);
            $response = $kernel->handle($request);
            echo json_encode([
                'status' => $response->getStatusCode(),
                'https_form' => str_contains($response->getContent(), 'action="https://localhost/register"'),
                'cached' => $app->configurationIsCached(),
            ]);
            SCRIPT;
        try {
            // Cached values must work even with no matching process environment.
            $process = new Process([PHP_BINARY, '-r', $script, base_path(), $cacheFile], base_path(), [
                'TRUSTED_PROXIES' => '', 'TRUSTED_HOSTS' => '', 'APP_URL' => '',
            ]);
            $process->mustRun();
            $this->assertSame(['status' => 200, 'https_form' => true, 'cached' => true], json_decode($process->getOutput(), true));
        } finally {
            unlink($cacheFile);
        }
    }

    public function test_registration_and_logout_work_with_csrf_over_https_proxy(): void
    {
        $this->withSession(['_token' => 'registration-token'])->post('/register', [
            '_token' => 'registration-token',
            'name' => 'HTTPS Student',
            'email' => 'https.student@example.com',
            'number' => '01012345678',
            'university' => 'Cairo University',
            'password' => 'HttpsTestPassword123!',
            'password_confirmation' => 'HttpsTestPassword123!',
            'terms' => '1',
        ])->assertRedirect('https://localhost/courses');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'https.student@example.com']);
        $this->withSession(['_token' => 'logout-token'])->post('/logout', [
            '_token' => 'logout-token',
        ])->assertRedirect('https://localhost');
        $this->assertGuest();
    }

    public function test_login_and_logout_work_with_csrf_over_https_proxy(): void
    {
        $user = User::factory()->student()->create(['password' => 'HttpsTestPassword123!']);
        $this->withSession(['_token' => 'login-token'])->post('/login', [
            '_token' => 'login-token', 'email' => $user->email, 'password' => 'HttpsTestPassword123!',
        ])->assertRedirect('https://localhost/courses');
        $this->assertAuthenticatedAs($user);
        $this->withSession(['_token' => 'logout-token'])->post('/logout', [
            '_token' => 'logout-token',
        ])->assertRedirect('https://localhost');
        $this->assertGuest();
    }

    #[DataProvider('formMethods')]
    public function test_form_methods_retain_data_over_https(string $method): void
    {
        $this->withSession(['_token' => 'form-token'])->call($method, '/__https-form-test', [
            '_token' => 'form-token', 'value' => 'submitted value',
        ])->assertOk()->assertJsonPath('value', 'submitted value');
    }

    public static function formMethods(): array
    {
        return [['POST'], ['PUT'], ['PATCH'], ['DELETE']];
    }

    public function test_missing_csrf_token_is_still_rejected(): void
    {
        $this->withSession(['_token' => 'form-token'])->postJson('/__https-form-test', [
            'value' => 'submitted value',
        ])->assertStatus(419);
    }

    public function test_http_post_redirects_before_csrf_checks_preserving_method(): void
    {
        $this->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'http', 'HTTP_X_FORWARDED_PORT' => '80']);
        $this->post('http://localhost/register', ['name' => 'Preserved body'])
            ->assertStatus(308)->assertRedirect('https://localhost/register');
    }

    public function test_untrusted_forwarded_https_header_does_not_bypass_redirect(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.25']);
        $this->post('http://localhost/register', ['name' => 'Preserved body'])
            ->assertStatus(308)->assertRedirect('https://localhost/register');
    }

    public function test_container_health_check_remains_available_over_http(): void
    {
        $this->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'http', 'HTTP_X_FORWARDED_PORT' => '80']);
        $this->get('/up')->assertOk();
    }
}

class HttpsTestCsrfMiddleware extends ValidateCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}
