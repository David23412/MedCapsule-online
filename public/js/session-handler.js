/**
 * Session Handler - Gracefully handle session expiration and authentication state
 * CRITICAL FIX: Prevents 419 errors and ensures smooth user experience
 */

(function() {
    'use strict';

    let authRedirectInProgress = false;

    function init() {
        // Laravel owns session expiry; a login timestamp is not an inactivity timer.
        setupAjaxErrorHandling();
        setupFormHandling();
    }

    function redirectToHome(url) {
        if (authRedirectInProgress) return;
        authRedirectInProgress = true;
        const destination = new URL(url || '/', window.location.origin);
        window.location.replace(destination.origin === window.location.origin ? destination.href : '/');
    }

    /**
     * Setup AJAX error handling for 419 errors
     */
    function setupAjaxErrorHandling() {
        // jQuery AJAX error handler
        if (window.jQuery) {
            $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
                if (jqXHR.status === 419 || jqXHR.status === 401) {
                    handleAuthError(jqXHR);
                }
            });
        }

        // Fetch API interceptor
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args).then(response => {
                if (response.status === 419 || response.status === 401) {
                    handleAuthError(response);
                }
                return response;
            });
        };

        // Axios interceptor if available
        if (window.axios) {
            window.axios.interceptors.response.use(
                response => response,
                error => {
                    if (error.response && (error.response.status === 419 || error.response.status === 401)) {
                        handleAuthError(error.response);
                    }
                    return Promise.reject(error);
                }
            );
        }
    }

    /**
     * Handle CSRF token mismatch (419 error)
     */
    function handleAuthError(response) {
        console.warn('Authentication/session error detected - redirecting to home');
        
        // Try to get redirect URL from response
        let redirectUrl = '/';
        
        if (response.data && response.data.redirect) {
            redirectUrl = response.data.redirect;
        }
        
        // Redirect instantly — no delay
        redirectToHome(redirectUrl);
    }

    /**
     * Setup form handling to prevent submission with expired sessions
     */
    function setupFormHandling() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            
            // Skip if form has data-no-session-check attribute
            if (form.hasAttribute('data-no-session-check')) {
                return;
            }

            // GET forms and external forms do not use this application's CSRF token.
            if ((form.method || 'get').toLowerCase() === 'get' ||
                new URL(form.action || window.location.href, window.location.origin).origin !== window.location.origin) {
                return;
            }

            // Ensure CSRF token is present
            const csrfToken = form.querySelector('input[name="_token"]');
            if (!csrfToken || !csrfToken.value) {
                console.error('CSRF token missing from form');
                
                // Try to get token from meta tag
                const metaToken = document.querySelector('meta[name="csrf-token"]');
                if (metaToken && metaToken.content) {
                    // Add token to form
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = metaToken.content;
                    form.appendChild(tokenInput);
                    
                    // Continue the original submit so validation, submitter and other handlers survive.
                } else {
                    e.preventDefault();
                    redirectToHome('/');
                }
                return false;
            }
        }, true);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Compatibility for callers: refresh the real server session, not a browser clock.
    window.refreshSession = function() {
        window.location.reload();
    };

})();
