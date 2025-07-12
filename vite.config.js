import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/courses.css',
                'resources/css/quiz.css',
                'resources/css/notifications.css'
            ],
            refresh: true,
        }),
    ],
    
    // PERFORMANCE OPTIMIZATIONS
    build: {
        // Enable minification and compression
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info', 'console.debug']
            }
        },
        
        // Enable source maps for debugging (disable in production)
        sourcemap: false,
        
        // Optimize chunk splitting
        rollupOptions: {
            output: {
                manualChunks: {
                    // Vendor chunks for better caching
                    vendor: ['jquery', 'bootstrap'],
                    utils: ['lodash', 'moment'],
                    ui: ['alpinejs', 'sweetalert2']
                },
                // Optimize chunk naming
                chunkFileNames: 'assets/js/[name]-[hash].js',
                entryFileNames: 'assets/js/[name]-[hash].js',
                assetFileNames: 'assets/[ext]/[name]-[hash].[ext]'
            }
        },
        
        // Enable CSS code splitting
        cssCodeSplit: true,
        
        // Optimize assets
        assetsInlineLimit: 4096, // Inline small assets
        
        // Target modern browsers
        target: 'es2015'
    },
    
    // Development optimizations
    server: {
        hmr: {
            overlay: false
        }
    },
    
    // Optimize dependencies
    optimizeDeps: {
        include: ['jquery', 'bootstrap', 'alpinejs', 'lodash', 'moment']
    },
    
    // Resolve aliases for faster imports
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '~': resolve(__dirname, 'resources/css')
        }
    },
    
    // CSS optimizations
    css: {
        postcss: {
            plugins: [
                require('autoprefixer'),
                require('cssnano')({
                    preset: ['default', {
                        discardComments: { removeAll: true },
                        normalizeWhitespace: true,
                        colormin: true,
                        minifyFontValues: true,
                        minifySelectors: true
                    }]
                })
            ]
        }
    }
});
