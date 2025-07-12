<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class UltraFrontendOptimizationService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const PERFORMANCE_CACHE_TTL = 300; // 5 minutes
    
    /**
     * Get critical CSS for above-the-fold content
     * This should be inlined in the HTML head for fastest rendering
     */
    public function getCriticalCSS(string $page = 'default'): string
    {
        $cacheKey = "critical_css_{$page}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function() use ($page) {
            switch ($page) {
                case 'courses':
                    return $this->getCoursesCriticalCSS();
                case 'quiz':
                    return $this->getQuizCriticalCSS();
                case 'mistakes':
                    return $this->getMistakesCriticalCSS();
                default:
                    return $this->getDefaultCriticalCSS();
            }
        });
    }
    
    /**
     * Get optimized resource hints for preloading critical resources
     */
    public function getResourceHints(string $page = 'default'): array
    {
        $cacheKey = "resource_hints_{$page}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function() use ($page) {
            $hints = [
                'preconnect' => [
                    'https://fonts.googleapis.com',
                    'https://fonts.gstatic.com',
                    'https://cdn.jsdelivr.net',
                    'https://cdnjs.cloudflare.com'
                ],
                'dns-prefetch' => [
                    'https://fonts.googleapis.com',
                    'https://fonts.gstatic.com',
                    'https://cdn.jsdelivr.net',
                    'https://cdnjs.cloudflare.com'
                ],
                'preload' => [
                    [
                        'href' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
                        'as' => 'style',
                        'crossorigin' => 'anonymous'
                    ],
                    [
                        'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
                        'as' => 'style'
                    ],
                    [
                        'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
                        'as' => 'script'
                    ]
                ]
            ];
            
            // Add page-specific hints
            switch ($page) {
                case 'courses':
                    $hints['preload'][] = [
                        'href' => '/api/courses/optimized',
                        'as' => 'fetch',
                        'crossorigin' => 'anonymous'
                    ];
                    break;
                case 'quiz':
                    $hints['preload'][] = [
                        'href' => '/api/quiz/preload',
                        'as' => 'fetch',
                        'crossorigin' => 'anonymous'
                    ];
                    break;
            }
            
            return $hints;
        });
    }
    
    /**
     * Get performance optimized JavaScript module configuration
     */
    public function getJavaScriptModules(string $page = 'default'): array
    {
        $cacheKey = "js_modules_{$page}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function() use ($page) {
            $modules = [
                'core' => [
                    'src' => asset('js/core/performance-optimized.js'),
                    'defer' => true,
                    'priority' => 'high'
                ],
                'ui' => [
                    'src' => asset('js/ui/responsive-interactions.js'),
                    'defer' => true,
                    'priority' => 'medium'
                ],
                'analytics' => [
                    'src' => asset('js/analytics/performance-tracking.js'),
                    'defer' => true,
                    'priority' => 'low'
                ]
            ];
            
            // Add page-specific modules
            switch ($page) {
                case 'courses':
                    $modules['courses'] = [
                        'src' => asset('js/courses/ultra-smooth-scrolling.js'),
                        'defer' => true,
                        'priority' => 'high'
                    ];
                    break;
                case 'quiz':
                    $modules['quiz'] = [
                        'src' => asset('js/quiz/optimized-timer.js'),
                        'defer' => true,
                        'priority' => 'critical'
                    ];
                    break;
                case 'mistakes':
                    $modules['mistakes'] = [
                        'src' => asset('js/mistakes/smooth-pagination.js'),
                        'defer' => true,
                        'priority' => 'medium'
                    ];
                    break;
            }
            
            return $modules;
        });
    }
    
    /**
     * Get Service Worker configuration for offline capabilities
     */
    public function getServiceWorkerConfig(): array
    {
        return Cache::remember('sw_config', self::CACHE_TTL, function() {
            return [
                'enabled' => config('app.env') === 'production',
                'cache_version' => 'v' . config('app.version', '1.0.0'),
                'assets_to_cache' => [
                    '/',
                    '/courses',
                    '/review',
                    '/offline',
                    asset('css/critical.css'),
                    asset('js/core/performance-optimized.js'),
                    asset('js/ui/responsive-interactions.js'),
                    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
                    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
                ],
                'api_routes_to_cache' => [
                    '/api/courses/optimized',
                    '/api/user/progress',
                    '/api/notifications'
                ],
                'cache_strategies' => [
                    'pages' => 'NetworkFirst',
                    'api' => 'StaleWhileRevalidate',
                    'assets' => 'CacheFirst',
                    'fonts' => 'CacheFirst'
                ]
            ];
        });
    }
    
    /**
     * Get Web Vitals optimization configuration
     */
    public function getWebVitalsConfig(): array
    {
        return [
            'largest_contentful_paint' => [
                'target' => 2500, // 2.5 seconds
                'optimizations' => [
                    'preload_hero_image' => true,
                    'optimize_fonts' => true,
                    'minimize_render_blocking' => true
                ]
            ],
            'first_input_delay' => [
                'target' => 100, // 100ms
                'optimizations' => [
                    'defer_non_critical_js' => true,
                    'use_passive_listeners' => true,
                    'minimize_main_thread_work' => true
                ]
            ],
            'cumulative_layout_shift' => [
                'target' => 0.1,
                'optimizations' => [
                    'specify_image_dimensions' => true,
                    'reserve_space_for_ads' => true,
                    'avoid_non_composited_animations' => true
                ]
            ]
        ];
    }
    
    /**
     * Get responsive image configuration
     */
    public function getResponsiveImageConfig(): array
    {
        return [
            'breakpoints' => [
                'xs' => 320,
                'sm' => 576,
                'md' => 768,
                'lg' => 992,
                'xl' => 1200,
                'xxl' => 1400
            ],
            'formats' => ['webp', 'avif', 'jpg'],
            'quality' => [
                'webp' => 85,
                'avif' => 80,
                'jpg' => 85
            ],
            'lazy_loading' => [
                'enabled' => true,
                'threshold' => '50px',
                'placeholder' => 'blur'
            ]
        ];
    }
    
    /**
     * Get connection optimization settings
     */
    public function getConnectionOptimizations(): array
    {
        return [
            'http2_push' => [
                'enabled' => true,
                'resources' => [
                    'critical.css',
                    'core.js',
                    'fonts/inter-400.woff2'
                ]
            ],
            'compression' => [
                'gzip' => true,
                'brotli' => true,
                'level' => 6
            ],
            'caching' => [
                'browser_cache' => [
                    'html' => '1 hour',
                    'css' => '1 year',
                    'js' => '1 year',
                    'images' => '1 year',
                    'fonts' => '1 year'
                ],
                'cdn_cache' => [
                    'enabled' => true,
                    'ttl' => 31536000 // 1 year
                ]
            ]
        ];
    }
    
    /**
     * Get performance monitoring configuration
     */
    public function getPerformanceMonitoringConfig(): array
    {
        return [
            'enabled' => config('app.env') === 'production',
            'metrics' => [
                'page_load_time' => true,
                'first_contentful_paint' => true,
                'largest_contentful_paint' => true,
                'first_input_delay' => true,
                'cumulative_layout_shift' => true,
                'time_to_interactive' => true
            ],
            'thresholds' => [
                'page_load_time' => 3000,
                'first_contentful_paint' => 1800,
                'largest_contentful_paint' => 2500,
                'first_input_delay' => 100,
                'cumulative_layout_shift' => 0.1
            ],
            'reporting' => [
                'endpoint' => '/api/performance-metrics',
                'sample_rate' => 0.1, // 10% of users
                'batch_size' => 10
            ]
        ];
    }
    
    /**
     * Generate optimized CSS for courses page
     */
    private function getCoursesCriticalCSS(): string
    {
        return '
            /* Critical CSS for Courses Page */
            html{font-size:12.8px}
            body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background-color:#f8f9fa;color:#1a1a1a;margin:0;padding:0;min-height:100vh;overflow-x:hidden;line-height:1.6}
            .navbar{background:rgba(255,255,255,0.95);padding:1rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,0.05);position:fixed;top:0;left:0;right:0;z-index:1000;backdrop-filter:blur(10px)}
            .navbar-brand{color:#007bff;font-weight:700;font-size:1.8rem;transition:transform 0.3s ease}
            .hero-section{background:linear-gradient(135deg,#f8f9fa 0%,#e9ecef 100%);padding:8rem 2rem 4rem;text-align:center;position:relative;margin-bottom:2rem;overflow:hidden}
            .hero-title{font-size:2.5rem;font-weight:700;color:#2196f3;margin-bottom:1rem;position:relative}
            .courses-grid{display:flex;gap:1.5rem;padding:1rem;margin:0.5rem 2rem;max-width:100%;overflow-x:auto;scroll-behavior:smooth;scrollbar-width:none;-ms-overflow-style:none;position:relative;scroll-snap-type:x mandatory;padding-bottom:20px;-webkit-overflow-scrolling:touch}
            .course-card{flex:0 0 360px;scroll-snap-align:start;background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05);transition:all 0.3s ease;width:360px;border:1px solid transparent}
            .course-card:hover{transform:translateY(-4px);box-shadow:0 4px 12px rgba(0,0,0,0.1)}
            .course-image{position:relative;height:450px;background-size:cover;background-position:center;background-repeat:no-repeat}
            .loading-skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:loading 1.5s infinite}
            @keyframes loading{0%{background-position:200% 0}100%{background-position:-200% 0}}
            .fade-in{opacity:0;transform:translateY(20px);transition:all 0.3s cubic-bezier(0.4,0,0.2,1)}
            .fade-in.visible{opacity:1;transform:translateY(0)}
        ';
    }
    
    /**
     * Generate optimized CSS for quiz page
     */
    private function getQuizCriticalCSS(): string
    {
        return '
            /* Critical CSS for Quiz Page */
            html{font-size:12.8px}
            body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background-color:#f8f9fa;color:#1a1a1a;margin:0;padding:0;min-height:100vh;line-height:1.6}
            .quiz-container{max-width:900px;margin:0 auto;padding:2rem;min-height:100vh;display:flex;flex-direction:column}
            .quiz-header{background:white;border-radius:12px;padding:2rem;margin-bottom:2rem;box-shadow:0 2px 8px rgba(0,0,0,0.05)}
            .question-card{background:white;border-radius:12px;padding:2.5rem;margin-bottom:2rem;box-shadow:0 2px 8px rgba(0,0,0,0.05);transition:all 0.3s ease}
            .timer-display{background:linear-gradient(135deg,#ff6b6b,#ee5a52);color:white;padding:0.75rem 1.5rem;border-radius:8px;font-weight:600;font-size:1.1rem;text-align:center;min-width:120px}
            .progress-bar{height:8px;background:#e9ecef;border-radius:4px;overflow:hidden;margin:1rem 0}
            .progress-fill{height:100%;background:linear-gradient(90deg,#28a745,#20c997);transition:width 0.3s ease;border-radius:4px}
            .option-btn{display:block;width:100%;text-align:left;padding:1rem 1.5rem;margin-bottom:0.75rem;background:white;border:2px solid #e9ecef;border-radius:8px;color:#495057;font-weight:500;transition:all 0.2s ease;cursor:pointer}
            .option-btn:hover{border-color:#007bff;background:#f8f9fa;transform:translateY(-1px)}
            .option-btn.selected{border-color:#007bff;background:#e3f2fd;color:#1976d2}
        ';
    }
    
    /**
     * Generate optimized CSS for mistakes page
     */
    private function getMistakesCriticalCSS(): string
    {
        return '
            /* Critical CSS for Mistakes Page */
            html{font-size:12.8px}
            body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:linear-gradient(135deg,#f6f9fc,#f1f4f8);margin:0;padding:0;min-height:100vh}
            .main-content{margin-top:80px;padding:2rem}
            .container{max-width:1200px;margin:0 auto;padding:0 2rem}
            .header-section{display:flex;justify-content:space-between;align-items:center;margin-bottom:3.5rem;padding:1.5rem 0;position:relative}
            .mistake-card{background:white;border-radius:24px;padding:3rem;margin-bottom:3rem;box-shadow:0 10px 40px rgba(0,0,0,0.06),0 0 0 1px rgba(0,0,0,0.03);transition:all 0.4s cubic-bezier(0.4,0,0.2,1);position:relative;overflow:hidden;backdrop-filter:blur(10px)}
            .mistake-card:hover{transform:translateY(-5px);box-shadow:0 20px 50px rgba(0,0,0,0.08),0 0 0 1px rgba(0,0,0,0.02)}
            .question-section{border-bottom:2px solid #f0f2f5;padding-bottom:2rem;margin-right:3rem}
            .question-text{font-size:1.3rem;color:#2c3e50;font-weight:600;line-height:1.6;margin-bottom:1.5rem}
            .empty-state{text-align:center;padding:3rem;background:white;border-radius:24px;box-shadow:0 4px 20px rgba(148,163,184,0.08);position:relative;overflow:hidden;border:1px solid #e2e8f0}
            @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
        ';
    }
    
    /**
     * Generate default critical CSS
     */
    private function getDefaultCriticalCSS(): string
    {
        return '
            /* Default Critical CSS */
            html{font-size:12.8px}
            body{font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background-color:#f8f9fa;color:#1a1a1a;margin:0;padding:0;min-height:100vh;line-height:1.6}
            .navbar{background:rgba(255,255,255,0.95);padding:1rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,0.05);position:fixed;top:0;left:0;right:0;z-index:1000;backdrop-filter:blur(10px)}
            .navbar-brand{color:#007bff;font-weight:700;font-size:1.8rem}
            .main-content{margin-top:80px;padding:2rem}
            .loading-skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:loading 1.5s infinite}
            @keyframes loading{0%{background-position:200% 0}100%{background-position:-200% 0}}
            .fade-in{opacity:0;transform:translateY(20px);transition:all 0.3s cubic-bezier(0.4,0,0.2,1)}
            .fade-in.visible{opacity:1;transform:translateY(0)}
        ';
    }
    
    /**
     * Get performance optimization headers
     */
    public function getPerformanceHeaders(): array
    {
        return [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Expires' => gmdate('D, d M Y H:i:s T', time() + 31536000),
            'Vary' => 'Accept-Encoding',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self'"
        ];
    }
    
    /**
     * Track performance metrics
     */
    public function trackPerformanceMetrics(array $metrics): void
    {
        try {
            $cacheKey = 'performance_metrics_' . date('Y-m-d-H');
            
            $currentMetrics = Cache::get($cacheKey, []);
            $currentMetrics[] = array_merge($metrics, [
                'timestamp' => now(),
                'user_agent' => request()->userAgent(),
                'ip' => request()->ip()
            ]);
            
            Cache::put($cacheKey, $currentMetrics, self::PERFORMANCE_CACHE_TTL);
            
            // Log performance issues
            if (isset($metrics['page_load_time']) && $metrics['page_load_time'] > 3000) {
                Log::warning('Slow page load detected', $metrics);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to track performance metrics', [
                'error' => $e->getMessage(),
                'metrics' => $metrics
            ]);
        }
    }
}