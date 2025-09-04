<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Responsive Design Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the responsive design
    | system of the MedCapsule application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Breakpoints
    |--------------------------------------------------------------------------
    |
    | Define the breakpoints used throughout the application for responsive
    | design. These values should match the CSS custom properties.
    |
    */
    'breakpoints' => [
        'mobile' => 320,
        'mobile_large' => 480,
        'tablet' => 768,
        'desktop' => 1024,
        'desktop_large' => 1200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Touch Target Sizes
    |--------------------------------------------------------------------------
    |
    | Minimum sizes for touch targets to ensure accessibility and usability
    | on mobile devices. Values are in pixels.
    |
    */
    'touch_targets' => [
        'minimum' => 44,
        'comfortable' => 48,
        'spacing' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile Optimizations
    |--------------------------------------------------------------------------
    |
    | Configuration for mobile-specific optimizations and features.
    |
    */
    'mobile_optimizations' => [
        'reduce_animations' => true,
        'lazy_load_images' => true,
        'compress_images' => true,
        'enable_touch_gestures' => true,
        'optimize_fonts' => true,
        'preload_critical_css' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for responsive image handling and optimization.
    |
    */
    'images' => [
        'lazy_loading' => [
            'enabled' => true,
            'threshold' => '50px',
            'placeholder' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0IiBmaWxsPSIjNmM3NTdkIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+TG9hZGluZy4uLjwvdGV4dD48L3N2Zz4=',
        ],
        'responsive_sizes' => [
            'mobile' => 320,
            'mobile_large' => 480,
            'tablet' => 768,
            'desktop' => 1024,
        ],
        'quality' => [
            'mobile' => 75,
            'tablet' => 80,
            'desktop' => 85,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimizations on different devices.
    |
    */
    'performance' => [
        'critical_css_inline' => true,
        'defer_non_critical_css' => true,
        'preload_fonts' => ['Poppins'],
        'enable_service_worker' => false, // Future enhancement
        'cache_static_assets' => true,
        'minify_css' => env('APP_ENV') === 'production',
        'minify_js' => env('APP_ENV') === 'production',
    ],

    /*
    |--------------------------------------------------------------------------
    | Accessibility Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for accessibility features and compliance.
    |
    */
    'accessibility' => [
        'respect_reduced_motion' => true,
        'high_contrast_support' => true,
        'focus_visible_support' => true,
        'screen_reader_support' => true,
        'keyboard_navigation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Detection
    |--------------------------------------------------------------------------
    |
    | Settings for device detection and mobile-specific features.
    |
    */
    'device_detection' => [
        'enabled' => true,
        'cache_duration' => 3600, // 1 hour
        'user_agent_patterns' => [
            'mobile' => [
                'iPhone', 'iPod', 'Android', 'BlackBerry', 'Opera Mini',
                'IEMobile', 'Mobile', 'mobile', 'webOS', 'Windows Phone'
            ],
            'tablet' => [
                'iPad', 'Android.*Tablet', 'Kindle', 'Silk', 'PlayBook'
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CSS Framework Integration
    |--------------------------------------------------------------------------
    |
    | Settings for integrating with existing CSS frameworks and libraries.
    |
    */
    'css_integration' => [
        'bootstrap_compatibility' => true,
        'tailwind_compatibility' => true,
        'custom_properties_support' => true,
        'css_grid_fallback' => true,
        'flexbox_fallback' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for responsive design testing and validation.
    |
    */
    'testing' => [
        'viewport_sizes' => [
            'mobile_small' => [320, 568],
            'mobile_medium' => [375, 667],
            'mobile_large' => [414, 896],
            'tablet_portrait' => [768, 1024],
            'tablet_landscape' => [1024, 768],
            'desktop_small' => [1024, 768],
            'desktop_medium' => [1280, 720],
            'desktop_large' => [1920, 1080],
        ],
        'test_devices' => [
            'iPhone SE',
            'iPhone 12',
            'iPhone 12 Pro Max',
            'Samsung Galaxy S21',
            'iPad',
            'iPad Pro',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific responsive features for testing or gradual rollout.
    |
    */
    'features' => [
        'mobile_navigation' => true,
        'touch_gestures' => true,
        'responsive_images' => true,
        'mobile_forms' => true,
        'responsive_tables' => true,
        'mobile_modals' => true,
        'progressive_enhancement' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for debugging responsive design issues.
    |
    */
    'debug' => [
        'show_breakpoints' => env('APP_DEBUG', false),
        'log_device_detection' => env('APP_DEBUG', false),
        'performance_monitoring' => env('APP_DEBUG', false),
        'css_validation' => env('APP_DEBUG', false),
    ],
];