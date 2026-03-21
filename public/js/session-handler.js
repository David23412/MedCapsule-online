/**
 * Session Handler - Gracefully handle session expiration and authentication state
 * CRITICAL FIX: Prevents 419 errors and ensures smooth user experience
 */

(function() {
    'use strict';

    // Configuration
    const SESSION_CHECK_INTERVAL = 60000; // Check every 60 seconds
    const SESSION_WARNING_TIME = 300000; // Warn 5 minutes before expiration
    const SESSION_LIFETIME = 240 * 60 * 1000; // 4 hours in milliseconds

    let sessionStartTime = Date.now();
    let warningShown = false;
    let authRedirectInProgress = false;

    /**
     * Initialize session handler
     */
    function init() {
        // Get session start time from meta tag if available
        const sessionMeta = document.querySelector('meta[name="session-start"]');
        if (sessionMeta) {
            sessionStartTime = parseInt(sessionMeta.content) * 1000;
        }

        // Set up periodic session checks
        setInterval(checkSession, SESSION_CHECK_INTERVAL);

        // Intercept AJAX errors for 419 handling
        setupAjaxErrorHandling();

        // Handle form submissions
        setupFormHandling();

        console.log('Session handler initialized');
    }

    /**
     * Check session validity
     */
    function checkSession() {
        const now = Date.now();
        const sessionAge = now - sessionStartTime;
        const timeUntilExpiry = SESSION_LIFETIME - sessionAge;

        // Show warning if session is about to expire
        if (timeUntilExpiry <= SESSION_WARNING_TIME && !warningShown) {
            showSessionWarning(Math.floor(timeUntilExpiry / 60000));
            warningShown = true;
        }

        // Session expired
        if (timeUntilExpiry <= 0) {
            handleSessionExpired();
        }
    }

    /**
     * Show session expiration warning
     */
    function showSessionWarning(minutesLeft) {
        const message = `Your session will expire in ${minutesLeft} minutes. Please save your work.`;
        
        // Create warning banner
        const banner = document.createElement('div');
        banner.id = 'session-warning-banner';
        banner.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #f59e0b;
            color: white;
            padding: 12px 20px;
            text-align: center;
            z-index: 10000;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        `;
        banner.innerHTML = `
            ${message}
            <button onclick="location.reload()" style="margin-left: 20px; padding: 6px 12px; background: white; color: #f59e0b; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                Refresh Session
            </button>
        `;
        
        document.body.insertBefore(banner, document.body.firstChild);
    }

    /**
     * Handle session expiration
     */
    function handleSessionExpired() {
        console.warn('Session expired - redirecting to login');
        showNotification('Your session expired. Redirecting to login...', 'warning');
        redirectToLogin('/login');
    }

    function redirectToLogin(url) {
        if (authRedirectInProgress) {
            return;
        }

        authRedirectInProgress = true;
        window.location.assign(url || '/login');
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
        console.warn('Authentication/session error detected - redirecting to login');
        
        // Try to get redirect URL from response
        let redirectUrl = '/login';
        
        if (response.data && response.data.redirect) {
            redirectUrl = response.data.redirect;
        }
        
        // Show user-friendly message
        const message = 'You were logged out. Redirecting to login...';
        
        // Create notification
        showNotification(message, 'warning');
        
        // Redirect after short delay
        setTimeout(() => {
            redirectToLogin(redirectUrl);
        }, 1500);
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

            // Check if session is expired
            const now = Date.now();
            const sessionAge = now - sessionStartTime;
            
            if (sessionAge >= SESSION_LIFETIME) {
                e.preventDefault();
                alert('Your session has expired. Please refresh the page and try again.');
                location.reload();
                return false;
            }

            // Ensure CSRF token is present
            const csrfToken = form.querySelector('input[name="_token"]');
            if (!csrfToken || !csrfToken.value) {
                e.preventDefault();
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
                    
                    // Resubmit form
                    form.submit();
                } else {
                    alert('Security token missing. Please refresh the page and try again.');
                    location.reload();
                }
                return false;
            }
        }, true);
    }

    /**
     * Show notification to user
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            background: ${type === 'warning' ? '#f59e0b' : '#3b82f6'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10001;
            font-weight: 500;
            max-width: 400px;
            animation: slideIn 0.3s ease-out;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export for manual session refresh
    window.refreshSession = function() {
        sessionStartTime = Date.now();
        warningShown = false;
        
        // Remove warning banner if exists
        const banner = document.getElementById('session-warning-banner');
        if (banner) {
            banner.remove();
        }
        
        showNotification('Session refreshed successfully', 'info');
    };

})();
