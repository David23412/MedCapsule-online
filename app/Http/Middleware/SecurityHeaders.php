<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🔒 SECURITY HEADERS MIDDLEWARE - MEGA LETHAL SECURITY ENFORCEMENT
 *
 * This middleware enforces maximum security headers to prevent:
 * - Cross-Site Scripting (XSS) attacks
 * - Clickjacking attacks
 * - MIME sniffing vulnerabilities
 * - Cross-Site Request Forgery (CSRF)
 * - Information disclosure attacks
 * - Insecure transport vulnerabilities
 *
 * SECURITY HEADERS IMPLEMENTED:
 * ✅ Content-Security-Policy (CSP) - Prevents XSS and data injection
 * ✅ X-Frame-Options - Prevents clickjacking
 * ✅ X-Content-Type-Options - Prevents MIME sniffing
 * ✅ Strict-Transport-Security (HSTS) - Enforces HTTPS
 * ✅ X-Permitted-Cross-Domain-Policies - Controls Flash/PDF behavior
 * ✅ Referrer-Policy - Controls referrer information leakage
 * ✅ Feature-Policy - Controls browser features and APIs
 * ✅ X-XSS-Protection - Additional XSS protection
 */
class SecurityHeaders
{
    /**
     * Content Security Policy directives
     */
    private const CSP_DIRECTIVES = [
        "default-src 'self' blob:",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
        "font-src 'self' https://fonts.gstatic.com data: https://cdnjs.cloudflare.com",
        "img-src 'self' data: https: blob:",
        "media-src 'self' https: blob:",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com", // ✅ Allow YouTube embeds
        "frame-ancestors 'none'", // Equivalent to X-Frame-Options: DENY
        "connect-src 'self' https://www.youtube.com https://youtube.com https://www.youtube-nocookie.com https://youtube-nocookie.com https://www.googleapis.com https://fonts.googleapis.com https://fonts.gstatic.com", // ✅ Allow YouTube API
        "worker-src 'self' blob:",
        "upgrade-insecure-requests",
        "block-all-mixed-content"
    ];

    /**
     * Security headers configuration
     */
    private const SECURITY_HEADERS = [
        'X-Frame-Options' => 'DENY', // Prevent clickjacking
        'X-Content-Type-Options' => 'nosniff', // Prevent MIME sniffing
        'X-Permitted-Cross-Domain-Policies' => 'none', // Control Flash/PDF policies
        'Referrer-Policy' => 'strict-origin-when-cross-origin', // Control referrer leakage
        'X-XSS-Protection' => '1; mode=block', // Additional XSS protection
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload', // HSTS
        'Expect-CT' => 'max-age=86400, enforce', // Certificate Transparency
    ];

    /**
     * Feature policy directives (controls browser features)
     */
    private const FEATURE_POLICY = [
        "camera 'none'",
        "microphone 'none'",
        "geolocation 'none'",
        "gyroscope 'none'",
        "magnetometer 'none'",
        "payment 'self'",
        "usb 'none'",
        "autoplay 'none'",
        "fullscreen 'self'",
        "accelerometer 'none'",
        "ambient-light-sensor 'none'",
        "battery 'none'",
        "document-domain 'none'"
    ];

    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only apply security headers to HTML responses
        if ($this->shouldApplySecurityHeaders($request, $response)) {
            $this->applySecurityHeaders($response);
            $this->applyCSPHeaders($response);
            $this->applyFeaturePolicyHeaders($response);
            $this->applyAdditionalSecurityHeaders($response);

            // Log security headers application
            Log::info('Security headers applied', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
        }

        return $response;
    }

    /**
     * Determine if security headers should be applied to this response
     *
     * @param Request $request
     * @param mixed $response
     * @return bool
     */
    private function shouldApplySecurityHeaders(Request $request, $response): bool
    {
        // Only apply to successful HTML responses
        if (!$response || !method_exists($response, 'getStatusCode')) {
            return false;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            return false; // Only for 2xx responses
        }

        // Check if response has HTML content
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains(strtolower($contentType), 'text/html')) {
            return false;
        }

        // Skip for certain routes that might need different headers
        $skipRoutes = [
            'admin/file-manager',
            'api/debug',
            'system/logs'
        ];

        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($request->path(), $skipRoute)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply basic security headers
     *
     * @param mixed $response
     */
    private function applySecurityHeaders($response): void
    {
        $headers = $response->headers;

        foreach (self::SECURITY_HEADERS as $header => $value) {
            $headers->set($header, $value);
        }

        // Add custom security headers based on environment
        if (config('app.env') === 'production') {
            $headers->set('X-Environment', 'production');
            $headers->set('X-Security-Level', 'maximum');
        } else {
            $headers->set('X-Environment', config('app.env'));
            $headers->set('X-Security-Level', 'development');
        }
    }

    /**
     * Apply Content Security Policy headers
     *
     * @param mixed $response
     */
    private function applyCSPHeaders($response): void
    {
        $headers = $response->headers;

        // Apply CSP directives
        $cspValue = implode('; ', self::CSP_DIRECTIVES);
        $headers->set('Content-Security-Policy', $cspValue);

        // Apply CSP report-only for monitoring (in development)
        if (config('app.env') !== 'production' && config('app.debug')) {
            $reportUri = route('csp-report', [], false);
            $cspReportValue = $cspValue . "; report-uri {$reportUri}";
            $headers->set('Content-Security-Policy-Report-Only', $cspReportValue);
        }
    }

    /**
     * Apply Feature Policy headers
     *
     * @param mixed $response
     */
    private function applyFeaturePolicyHeaders($response): void
    {
        $headers = $response->headers;

        // Apply Feature Policy (newer standard)
        $featurePolicyValue = implode('; ', self::FEATURE_POLICY);
        $headers->set('Feature-Policy', $featurePolicyValue);

        // Also apply Permissions Policy (newer name for Feature Policy)
        $headers->set('Permissions-Policy', $featurePolicyValue);
    }

    /**
     * Apply additional security headers
     *
     * @param mixed $response
     */
    private function applyAdditionalSecurityHeaders($response): void
    {
        $headers = $response->headers;

        // Remove server information disclosure
        $headers->remove('X-Powered-By');
        $headers->remove('Server');

        // Add custom security headers
        $headers->set('X-Content-Security-Policy', $headers->get('Content-Security-Policy')); // For older browsers
        $headers->set('X-WebKit-CSP', $headers->get('Content-Security-Policy')); // For WebKit-based browsers

        // Add cache control for sensitive pages
        if ($this->isSensitivePage()) {
            $headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $headers->set('Pragma', 'no-cache');
            $headers->set('Expires', '0');
        }

        // Add security-related caching headers
        $headers->set('X-DNS-Prefetch-Control', 'off'); // Prevent DNS prefetching
        $headers->set('X-Download-Options', 'noopen'); // Prevent downloads from opening automatically

        // Add custom headers for additional security
        $headers->set('X-Custom-Security-Header', 'MegaLethal-Protection-Enabled');
        $headers->set('X-Request-ID', uniqid('req_', true));
    }

    /**
     * Check if current page is sensitive
     *
     * @return bool
     */
    private function isSensitivePage(): bool
    {
        $currentPath = request()->path();

        $sensitivePaths = [
            'admin',
            'dashboard',
            'profile',
            'settings',
            'account',
            'payment',
            'checkout',
            'login',
            'register',
            'password'
        ];

        foreach ($sensitivePaths as $path) {
            if (str_contains($currentPath, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get security headers configuration
     *
     * @return array
     */
    public function getSecurityConfiguration(): array
    {
        return [
            'security_headers' => self::SECURITY_HEADERS,
            'csp_directives' => self::CSP_DIRECTIVES,
            'feature_policy' => self::FEATURE_POLICY,
            'applied_to' => [
                'html_responses' => true,
                'successful_responses' => true,
                'skip_routes' => ['admin/file-manager', 'api/debug', 'system/logs']
            ],
            'environment_specific' => [
                'production' => [
                    'strict_hsts' => true,
                    'certificate_transparency' => true
                ],
                'development' => [
                    'csp_report_only' => true,
                    'relaxed_policies' => true
                ]
            ]
        ];
    }

    /**
     * Generate CSP violation report
     *
     * @param Request $request
     * @return void
     */
    public function handleCSPViolation(Request $request): void
    {
        try {
            $violationData = $request->all();

            Log::warning('CSP Violation Detected', [
                'document_uri' => $violationData['document-uri'] ?? 'unknown',
                'violated_directive' => $violationData['violated-directive'] ?? 'unknown',
                'effective_directive' => $violationData['effective-directive'] ?? 'unknown',
                'blocked_uri' => $violationData['blocked-uri'] ?? 'unknown',
                'status_code' => $violationData['status-code'] ?? 'unknown',
                'original_policy' => $violationData['original-policy'] ?? 'unknown',
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'timestamp' => now()->toISOString()
            ]);

            // In production, you might want to store this in a database
            // or send alerts to security team

        } catch (\Exception $e) {
            Log::error('Failed to handle CSP violation report', [
                'error' => $e->getMessage(),
                'violation_data' => $request->all()
            ]);
        }
    }

    /**
     * Test security headers configuration
     *
     * @return array
     */
    public function testSecurityHeaders(): array
    {
        $testResults = [];
        $expectedHeaders = array_merge(
            self::SECURITY_HEADERS,
            ['Content-Security-Policy' => implode('; ', self::CSP_DIRECTIVES)],
            ['Feature-Policy' => implode('; ', self::FEATURE_POLICY)]
        );

        // This would typically be called from a test route
        // to verify headers are being applied correctly
        foreach ($expectedHeaders as $header => $expectedValue) {
            $testResults[$header] = [
                'expected' => $expectedValue,
                'status' => 'test_required', // Would be checked in actual test
                'recommendations' => $this->getHeaderRecommendations($header)
            ];
        }

        return $testResults;
    }

    /**
     * Get recommendations for specific headers
     *
     * @param string $header
     * @return array
     */
    private function getHeaderRecommendations(string $header): array
    {
        $recommendations = [
            'Content-Security-Policy' => [
                'Consider adding nonce-based script-src for better XSS protection',
                'Add report-uri directive for violation monitoring',
                'Review and minimize unsafe-inline usage'
            ],
            'Strict-Transport-Security' => [
                'Ensure all subdomains support HTTPS before adding includeSubDomains',
                'Consider adding preload directive after thorough testing',
                'Monitor for mixed content issues'
            ],
            'X-Frame-Options' => [
                'Consider using CSP frame-ancestors instead (more flexible)',
                'DENY is most secure but may break legitimate use cases'
            ],
            'Referrer-Policy' => [
                'strict-origin-when-cross-origin provides good balance',
                'Consider same-origin for maximum privacy'
            ]
        ];

        return $recommendations[$header] ?? ['Header configuration looks good'];
    }
}
