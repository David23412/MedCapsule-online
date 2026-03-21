<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InjectDesignProtection
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || ! preg_match('/<\/body>/i', $content)) {
            return $response;
        }

        if (str_contains($content, 'window.__medcapsuleDesignProtectionLoaded')) {
            return $response;
        }

        $snippet = view('partials.design-protection')->render();

        $response->setContent(
            (string) preg_replace('/<\/body>/i', $snippet.PHP_EOL.'</body>', $content, 1)
        );

        return $response;
    }

    private function shouldInject(Request $request, Response $response): bool
    {
        if (! config('features.design_protection_enabled', true)) {
            return false;
        }

        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return false;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        if ($contentType !== '' && ! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'application/xhtml+xml')) {
            return false;
        }

        return true;
    }
}
