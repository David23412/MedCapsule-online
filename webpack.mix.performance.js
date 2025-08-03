// Asset Optimization Configuration
// This file contains settings for optimizing CSS, JS, and image assets

const mix = require('laravel-mix');

// Production optimizations
if (mix.inProduction()) {
    mix.options({
        terser: {
            terserOptions: {
                compress: {
// // // // // drop_console: true, // Remove console.log statements // Removed for production // Removed for production // Removed for production // Removed for production // Removed for production
                },
            },
        },
        cssNano: {
            discardComments: {
                removeAll: true,
            },
        },
    });
    
    // Enable versioning for cache busting
    mix.version();
    
    // Optimize images
    mix.options({
        imgLoaderOptions: {
            mozjpeg: {
                progressive: true,
                quality: 85,
            },
            optipng: {
                optimizationLevel: 5,
            },
            pngquant: {
                quality: [0.65, 0.8],
            },
            gifsicle: {
                optimizationLevel: 3,
            },
        },
    });
}

// Enable source maps for development
if (!mix.inProduction()) {
    mix.sourceMaps();
}

// Bundle splitting for better caching
mix.extract(['vue', 'axios', 'lodash']);