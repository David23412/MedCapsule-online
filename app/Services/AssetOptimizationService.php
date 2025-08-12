<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Asset Optimization Service
 * Handles critical CSS extraction and resource loading optimization
 */
class AssetOptimizationService
{
    /**
     * Get critical CSS for above-the-fold content
     */
    public static function getCriticalCSS(): string
    {
        return Cache::rememberForever('critical_css_v1', function () {
            return '
                html,body{margin:0;padding:0;background:#f8fafc;font-family:Poppins,-apple-system,BlinkMacSystemFont,sans-serif;overflow-x:hidden;opacity:1!important}
                .navbar{background-color:#fff;box-shadow:0 2px 4px rgba(0,0,0,.1);padding:1rem 0}
                .navbar-brand{display:flex;align-items:center;font-weight:700;font-size:2rem;background:linear-gradient(90deg,#2A6DFF,#7B3FE4,#E63946);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none}
                .hero-section{position:relative;min-height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;padding:2rem}
                .hero-section h1{font-size:3.5rem;font-weight:700;color:#2196f3;margin-bottom:1rem;text-shadow:2px 2px 4px rgba(0,0,0,.1)}
                .btn-primary{padding:15px 40px;font-size:1.5rem;border-radius:50px;background:#2196f3;border:none;color:#fff;text-decoration:none;display:inline-block;transition:background .3s ease}
                .btn-primary:hover{background:#1976d2}
                .container{max-width:1200px;margin:0 auto;padding:0 15px}
                .row{display:flex;flex-wrap:wrap;margin:0 -15px}
                .col-md-4{flex:0 0 33.333333%;max-width:33.333333%;padding:0 15px}
                @media(max-width:768px){.col-md-4{flex:0 0 100%;max-width:100%}.hero-section h1{font-size:2.5rem}}
            ';
        });
    }

    /**
     * Get resource hints for faster loading
     */
    public static function getResourceHints(): array
    {
        return [
            'dns-prefetch' => [
                'https://fonts.googleapis.com',
                'https://cdn.jsdelivr.net',
                'https://cdnjs.cloudflare.com'
            ],
            'preconnect' => [
                'https://fonts.gstatic.com'
            ],
            'preload' => [
                [
                    'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
                    'as' => 'style'
                ],
                [
                    'href' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap',
                    'as' => 'style'
                ]
            ]
        ];
    }

    /**
     * Get optimized script loading order
     */
    public static function getScriptLoadingOrder(): array
    {
        return [
            'critical' => [
                '/js/notification-system.js'
            ],
            'deferred' => [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
            ],
            'async' => [
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js'
            ]
        ];
    }
}