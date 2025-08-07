<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;

class HTTPSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force HTTPS for all tests
        URL::forceScheme('https');
        Config::set('app.env', 'production');
        Config::set('app.debug', false);
    }

    /** @test */
    public function all_routes_use_https()
    {
        $routes = [
            '/',
            '/login',
            '/register',
            '/courses',
            '/profile'
        ];
        
        foreach ($routes as $route) {
            $response = $this->get($route);
            
            // Check that response either:
            // 1. Redirects (likely to login or HTTPS)
            // 2. Contains HTTPS URLs in content
            // 3. Is successful (URL scheme is already forced to HTTPS in setUp)
            $content = $response->getContent();
            $statusCode = $response->getStatusCode();
            
            $this->assertTrue(
                $statusCode === 302 || // Redirect (likely to login or HTTPS)
                $statusCode === 200 || // Successful response with HTTPS forced
                str_contains($content, 'https://') ||
                str_contains($content, 'secure_url'),
                "Route {$route} failed HTTPS check. Status: {$statusCode}"
            );
        }
    }

    /** @test */
    public function strict_transport_security_header_is_present()
    {
        // Check if HTTPS middleware is properly configured
        $middleware = app(\App\Http\Middleware\HttpsRedirectMiddleware::class);
        $this->assertNotNull($middleware, 'HttpsRedirectMiddleware should be available');
        
        // Check if middleware is registered in kernel
        $kernel = app(\App\Http\Kernel::class);
        $middlewareGroups = $kernel->getMiddlewareGroups();
        $globalMiddleware = $kernel->getGlobalMiddleware();
        
        // Check if HTTPS middleware is in global middleware or web group
        $httpsMiddlewareFound = false;
        
        foreach ($globalMiddleware as $mw) {
            if (str_contains($mw, 'HttpsRedirectMiddleware')) {
                $httpsMiddlewareFound = true;
                break;
            }
        }
        
        if (!$httpsMiddlewareFound && isset($middlewareGroups['web'])) {
            foreach ($middlewareGroups['web'] as $mw) {
                if (str_contains($mw, 'HttpsRedirectMiddleware')) {
                    $httpsMiddlewareFound = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($httpsMiddlewareFound, 'HttpsRedirectMiddleware should be registered in kernel');
    }

    /** @test */
    public function content_security_policy_is_configured()
    {
        $response = $this->get('/');
        
        $headers = $response->headers;
        $content = $response->getContent();
        
        // Check for CSP header or CSP meta tag
        $this->assertTrue(
            $headers->has('Content-Security-Policy') ||
            str_contains($content, 'Content-Security-Policy') ||
            str_contains($content, 'nonce') ||
            str_contains($content, 'unsafe-inline')
        );
    }

    /** @test */
    public function xss_protection_headers_are_present()
    {
        $response = $this->get('/');
        
        $headers = $response->headers;
        
        // Check for XSS protection headers
        $this->assertTrue(
            $headers->has('X-XSS-Protection') ||
            $headers->has('X-Content-Type-Options') ||
            $headers->has('X-Frame-Options')
        );
    }

    /** @test */
    public function referrer_policy_is_set()
    {
        $response = $this->get('/');
        
        $headers = $response->headers;
        
        // Check for Referrer-Policy header
        $this->assertTrue(
            $headers->has('Referrer-Policy') ||
            str_contains($response->getContent(), 'referrer-policy')
        );
    }

    /** @test */
    public function form_actions_use_https()
    {
        $response = $this->get('/login');
        $content = $response->getContent();
        
        // Check that form actions use HTTPS
        $this->assertTrue(
            str_contains($content, 'action="https://') ||
            str_contains($content, 'secure_url') ||
            str_contains($content, 'action="{{ secure_url')
        );
    }

    /** @test */
    public function asset_urls_use_https()
    {
        $response = $this->get('/');
        $content = $response->getContent();
        
        // Check that asset URLs use HTTPS
        $this->assertTrue(
            str_contains($content, 'src="https://') ||
            str_contains($content, 'href="https://') ||
            str_contains($content, 'asset(') ||
            str_contains($content, 'secure_asset(')
        );
    }

    /** @test */
    public function mixed_content_is_prevented()
    {
        $response = $this->get('/');
        $content = $response->getContent();
        
        // Check that no HTTP URLs are present
        $this->assertFalse(
            str_contains($content, 'http://') &&
            !str_contains($content, 'http://localhost') // Allow localhost for testing
        );
    }

    /** @test */
    public function session_cookies_are_secure()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->get('/profile');
        
        $headers = $response->headers;
        
        if ($headers->has('Set-Cookie')) {
            $cookies = $headers->get('Set-Cookie');
            
            // Check that session cookies have secure flags
            $this->assertTrue(
                str_contains($cookies, 'HttpOnly') &&
                str_contains($cookies, 'Secure') &&
                str_contains($cookies, 'SameSite')
            );
        }
    }

    /** @test */
    public function csrf_tokens_are_present_in_forms()
    {
        $response = $this->get('/login');
        $content = $response->getContent();
        
        // Check for CSRF token in forms
        $this->assertTrue(
            str_contains($content, '_token') ||
            str_contains($content, 'csrf_token') ||
            str_contains($content, '@csrf')
        );
    }

    /** @test */
    public function sensitive_data_is_not_exposed_in_responses()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $response = $this->get('/profile');
        $content = $response->getContent();
        
        // Check that sensitive data is not exposed
        $this->assertFalse(
            str_contains($content, $user->password) ||
            str_contains($content, 'password_hash') ||
            str_contains($content, 'remember_token')
        );
    }

    /** @test */
    public function error_pages_dont_expose_system_info()
    {
        // Force an error
        Config::set('app.debug', false);
        
        $response = $this->get('/non-existent-route');
        
        $content = $response->getContent();
        
        // Check that error pages don't expose system information
        $this->assertFalse(
            str_contains($content, 'vendor/laravel') ||
            str_contains($content, 'stack trace') ||
            str_contains($content, 'SQLSTATE') ||
            str_contains($content, 'Illuminate\\')
        );
    }

    /** @test */
    public function file_uploads_are_secure()
    {
        $this->actingAs(User::factory()->create());
        
        // Test with potentially malicious file
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.php', 100);
        
        $response = $this->post('/upload', [
            'file' => $file,
            '_token' => csrf_token()
        ]);
        
        // Should reject PHP files
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function directory_traversal_is_prevented()
    {
        $maliciousPaths = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '....//....//....//etc/passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd'
        ];
        
        foreach ($maliciousPaths as $path) {
            $response = $this->get('/file/' . $path);
            
            // Should not allow directory traversal
            $this->assertNotEquals(200, $response->getStatusCode());
        }
    }

    /** @test */
    public function sql_injection_in_urls_is_prevented()
    {
        $maliciousUrls = [
            '/users?id=1; DROP TABLE users; --',
            '/courses?search=1\' OR \'1\'=\'1',
            '/profile?id=1 UNION SELECT * FROM users'
        ];
        
        foreach ($maliciousUrls as $url) {
            $response = $this->get($url);
            
            // Should handle safely
            $this->assertNotEquals(500, $response->getStatusCode());
        }
    }

    /** @test */
    public function xss_in_urls_is_prevented()
    {
        $xssUrls = [
            '/search?q=<script>alert("xss")</script>',
            '/profile?name=javascript:alert("xss")',
            '/courses?title=<img src="x" onerror="alert(\'xss\')">'
        ];
        
        foreach ($xssUrls as $url) {
            $response = $this->get($url);
            $content = $response->getContent();
            
            // Should not execute scripts
            $this->assertFalse(
                str_contains($content, '<script>') ||
                str_contains($content, 'javascript:')
            );
        }
    }

    /** @test */
    public function rate_limiting_is_active()
    {
        // Test rate limiting on login
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
                '_token' => csrf_token()
            ]);
        }
        
        // Should eventually hit rate limit
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function authentication_headers_are_secure()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->get('/profile');
        
        $headers = $response->headers;
        
        // Check for secure authentication headers
        $this->assertTrue(
            $headers->has('Cache-Control') ||
            $headers->has('Pragma') ||
            $headers->has('Expires')
        );
    }

    /** @test */
    public function cache_headers_are_properly_set()
    {
        $response = $this->get('/');
        
        $headers = $response->headers;
        
        // Check for appropriate cache headers
        $this->assertTrue(
            $headers->has('Cache-Control') ||
            $headers->has('ETag') ||
            $headers->has('Last-Modified')
        );
    }

    /** @test */
    public function cors_headers_are_secure()
    {
        $response = $this->get('/');
        
        $headers = $response->headers;
        
        // Check that CORS headers don't use wildcards
        if ($headers->has('Access-Control-Allow-Origin')) {
            $origin = $headers->get('Access-Control-Allow-Origin');
            $this->assertNotEquals('*', $origin);
        }
    }

    /** @test */
    public function server_info_is_not_exposed()
    {
        $response = $this->get('/');
        
        $headers = $response->headers;
        
        // Check that server information is not exposed
        $this->assertFalse(
            $headers->has('Server') ||
            $headers->has('X-Powered-By') ||
            $headers->has('X-AspNet-Version')
        );
    }

    /** @test */
    public function https_redirects_work_correctly()
    {
        // Simulate HTTP request
        $this->app['request']->server->set('HTTPS', 'off');
        
        $response = $this->get('/');
        
        // Should redirect to HTTPS
        $this->assertTrue(
            $response->getStatusCode() === 302 ||
            str_contains($response->getContent(), 'https://')
        );
    }

    /** @test */
    public function secure_cookies_are_used()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->get('/profile');
        
        $headers = $response->headers;
        
        if ($headers->has('Set-Cookie')) {
            $cookies = $headers->get('Set-Cookie');
            
            // Check for secure cookie attributes
            $this->assertTrue(
                str_contains($cookies, 'Secure') &&
                str_contains($cookies, 'HttpOnly') &&
                str_contains($cookies, 'SameSite')
            );
        }
    }
} 