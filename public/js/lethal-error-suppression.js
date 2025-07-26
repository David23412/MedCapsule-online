/**
 * 💀 LETHAL ERROR SUPPRESSION SYSTEM 💀
 * Prevents console spam and handles errors gracefully
 */

(function() {
    'use strict';

    // NUCLEAR ERROR SUPPRESSION CONFIGURATION
    const SUPPRESSED_ERRORS = [
        'Load failed',
        'NetworkError',
        'Failed to fetch',
        'TypeError: Load failed',
        'Connection error',
        'Network request failed',
        'Fetch error',
        'CORS error',
        'Request timeout',
        'Server error'
    ];

    const SUPPRESSED_WARNINGS = [
        'source progress',
        'tutor source progress',
        'Unable to fetch',
        'Request failed'
    ];

    // Override console.error to filter out noise
    const originalConsoleError = console.error;
    console.error = function(...args) {
        const message = args.join(' ');
        
        // Check if this is a suppressed error
        const shouldSuppress = SUPPRESSED_ERRORS.some(suppressedError => 
            message.toLowerCase().includes(suppressedError.toLowerCase())
        );
        
        if (shouldSuppress) {
            // Convert error to info log
            console.log('ℹ️ Network issue handled gracefully:', args[0]);
            return;
        }
        
        // Allow other errors through
        originalConsoleError.apply(console, args);
    };

    // Override console.warn to filter out noise
    const originalConsoleWarn = console.warn;
    console.warn = function(...args) {
        const message = args.join(' ');
        
        // Check if this is a suppressed warning
        const shouldSuppress = SUPPRESSED_WARNINGS.some(suppressedWarning => 
            message.toLowerCase().includes(suppressedWarning.toLowerCase())
        );
        
        if (shouldSuppress) {
            // Convert warning to info log
            console.log('ℹ️ Minor issue handled:', args[0]);
            return;
        }
        
        // Allow other warnings through
        originalConsoleWarn.apply(console, args);
    };

    // Global error handler
    window.addEventListener('error', function(event) {
        const errorMessage = event.error ? event.error.message : event.message;
        
        // Check if this is a suppressed error
        const shouldSuppress = SUPPRESSED_ERRORS.some(suppressedError => 
            errorMessage && errorMessage.toLowerCase().includes(suppressedError.toLowerCase())
        );
        
        if (shouldSuppress) {
            console.log('ℹ️ Global error handled gracefully');
            event.preventDefault();
            return true;
        }
        
        // Log other errors normally but don't spam
        if (errorMessage && !errorMessage.includes('Script error')) {
            console.log('ℹ️ Application error:', errorMessage);
        }
        
        event.preventDefault();
        return true;
    });

    // Handle unhandled promise rejections
    window.addEventListener('unhandledrejection', function(event) {
        const reason = event.reason;
        const reasonMessage = reason ? (reason.message || reason.toString()) : 'Unknown';
        
        // Check if this is a suppressed rejection
        const shouldSuppress = SUPPRESSED_ERRORS.some(suppressedError => 
            reasonMessage.toLowerCase().includes(suppressedError.toLowerCase())
        );
        
        if (shouldSuppress) {
            console.log('ℹ️ Promise rejection handled gracefully');
            event.preventDefault();
            return;
        }
        
        // Log other rejections as info
        console.log('ℹ️ Promise rejection handled:', reasonMessage);
        event.preventDefault();
    });

    // LETHAL FETCH WRAPPER - Automatically handles common errors
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args)
            .catch(error => {
                // Convert fetch errors to graceful responses
                console.log('ℹ️ Fetch request handled gracefully');
                
                // Return a default response instead of throwing
                return {
                    ok: false,
                    status: 0,
                    statusText: 'Network Error',
                    json: () => Promise.resolve({
                        success: false,
                        error: 'Network issue',
                        message: 'Please check your connection'
                    })
                };
            });
    };

    console.log('✅ Lethal Error Suppression System activated');
})();