import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import autoprefixer from 'autoprefixer';
import cssnano from 'cssnano';
import react from '@vitejs/plugin-react';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);


export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/landing-page.css',
                'resources/js/app.js',
                'resources/js/landing-entry.tsx',
            ],
            refresh: true,
        }),
        react(),
    ],

    // PERFORMANCE OPTIMIZATIONS
    build: {
        // Enable minification and compression
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
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
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
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

});
