<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\HttpsRedirectMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsRedirectMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::forceScheme(null);

        parent::tearDown();
    }

    public function test_insecure_post_requests_are_redirected_to_https_with_308(): void
    {
        config(['app.force_https' => true]);

        $middleware = new HttpsRedirectMiddleware();
        $request = Request::create('http://example.com/payments/process?foo=bar', 'POST');

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(308, $response->getStatusCode());
        $this->assertSame('https://example.com/payments/process?foo=bar', $response->headers->get('Location'));
    }

    public function test_secure_requests_receive_hsts_header_when_https_is_forced(): void
    {
        config(['app.force_https' => true]);

        $middleware = new HttpsRedirectMiddleware();
        $request = Request::create('https://example.com/profile', 'GET');
        $request->server->set('HTTPS', 'on');

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }
}
