<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_csp_no_longer_allows_unsafe_eval(): void
    {
        $middleware = new SecurityHeaders();
        $request = Request::create('/courses', 'GET');

        $response = $middleware->handle($request, fn () => response('<html></html>', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]));

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        $this->assertSame('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
        $this->assertSame('same-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
        $this->assertSame('?1', $response->headers->get('Origin-Agent-Cluster'));
    }

    public function test_inline_script_blocks_receive_a_nonce_and_do_not_require_unsafe_inline(): void
    {
        $middleware = new SecurityHeaders();
        $request = Request::create('/dashboard', 'GET');

        $response = $middleware->handle($request, fn () => response(
            '<html><body><script>window.test = true;</script><script src="https://cdn.jsdelivr.net/npm/test.js"></script></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        ));

        $content = (string) $response->getContent();
        $csp = (string) $response->headers->get('Content-Security-Policy');

        preg_match('/<script nonce="([^"]+)">window\.test = true;<\/script>/', $content, $matches);

        $this->assertNotEmpty($matches);
        $this->assertStringContainsString('script-src-attr \'unsafe-inline\'', $csp);
        $this->assertStringContainsString("'nonce-{$matches[1]}'", $csp);

        preg_match('/script-src ([^;]+)/', $csp, $scriptSrcDirective);

        $this->assertNotEmpty($scriptSrcDirective);
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSrcDirective[1]);
    }
}
