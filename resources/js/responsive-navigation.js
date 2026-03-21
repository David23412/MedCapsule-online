/**
 * Responsive Navigation Module
 * 
 * Handles mobile navigation functionality including:
 * - Hamburger menu toggle
 * - Mobile menu overlay
 * - Keyboard navigation
 * - Focus management
 * - Accessibility features
 */

class ResponsiveNavigation {
    constructor(options = {}) {
        // Configuration
        this.config = {
            navbarSelector: '#responsive-navbar',
            toggleSelector: '#responsive-nav-toggle',
            closeSelector: '#responsive-nav-close',
            menuSelector: '#responsive-nav-menu',
            overlaySelector: '#responsive-nav-overlay',
            mobileBreakpoint: 768,
            animationDuration: 300,
            focusTrap: true,
            closeOnLinkClick: true,
            closeOnEscape: true,
            closeOnOverlayClick: true,
            ...options
        };

        // Elements
        this.elements = {};
        
        // State
        this.state = {
            isOpen: false,
            isInitialized: false,
            isMobile: false,
            focusableElements: [],
            lastFocusedElement: null
        };

        // Bind methods
        this.handleToggleClick = this.handleToggleClick.bind(this);
        this.handleCloseClick = this.handleCloseClick.bind(this);
        this.handleOverlayClick = this.handleOverlayClick.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleResize = this.handleResize.bind(this);
        this.handleLinkClick = this.handleLinkClick.bind(this);

        // Initialize
        this.init();
    }

    /**
     * Initialize the navigation
     */
    init() {
        if (this.state.isInitialized) {
            console.warn('ResponsiveNavigation already initialized');
            return;
        }

        try {
            this.findElements();
            this.checkMobileState();
            this.bindEvents();
            this.setupAccessibility();
            this.state.isInitialized = true;
            
            this.emit('nav:initialized', { instance: this });
        } catch (error) {
            console.error('Failed to initialize ResponsiveNavigation:', error);
        }
    }

    /**
     * Find and cache DOM elements
     */
    findElements() {
        const selectors = {
            navbar: this.config.navbarSelector,
            toggle: this.config.toggleSelector,
            close: this.config.closeSelector,
            menu: this.config.menuSelector,
            overlay: this.config.overlaySelector
        };

        for (const [key, selector] of Object.entries(selectors)) {
            const element = document.querySelector(selector);
            if (!element && key !== 'close') { // close button is optional
                throw new Error(`Required element not found: ${selector}`);
            }
            this.elements[key] = element;
        }

        // Find focusable elements within the menu
        this.updateFocusableElements();
    }

    /**
     * Update focusable elements list
     */
    updateFocusableElements() {
        if (!this.elements.menu) return;

        const focusableSelectors = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ].join(', ');

        this.state.focusableElements = Array.from(
            this.elements.menu.querySelectorAll(focusableSelectors)
        );
    }

    /**
     * Check if we're in mobile state
     */
    checkMobileState() {
        this.state.isMobile = window.innerWidth < this.config.mobileBreakpoint;
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Toggle button
        if (this.elements.toggle) {
            this.elements.toggle.addEventListener('click', this.handleToggleClick);
        }

        // Close button
        if (this.elements.close) {
            this.elements.close.addEventListener('click', this.handleCloseClick);
        }

        // Overlay
        if (this.elements.overlay && this.config.closeOnOverlayClick) {
            this.elements.overlay.addEventListener('click', this.handleOverlayClick);
        }

        // Keyboard events
        if (this.config.closeOnEscape) {
            document.addEventListener('keydown', this.handleKeyDown);
        }

        // Resize events
        window.addEventListener('resize', this.debounce(this.handleResize, 150));

        // Link clicks (for mobile)
        if (this.config.closeOnLinkClick && this.elements.menu) {
            const links = this.elements.menu.querySelectorAll('a:not([href="#"])');
            links.forEach(link => {
                link.addEventListener('click', this.handleLinkClick);
            });
        }
    }

    /**
     * Setup accessibility attributes
     */
    setupAccessibility() {
        if (!this.elements.toggle || !this.elements.menu) return;

        // Set initial ARIA states
        this.elements.toggle.setAttribute('aria-expanded', 'false');
        this.elements.toggle.setAttribute('aria-controls', this.elements.menu.id || 'responsive-nav-menu');
        
        this.elements.menu.setAttribute('aria-hidden', 'true');
        this.elements.menu.setAttribute('role', 'navigation');
        this.elements.menu.setAttribute('aria-label', 'Main navigation');

        if (this.elements.overlay) {
            this.elements.overlay.setAttribute('role', 'presentation');
            this.elements.overlay.setAttribute('aria-hidden', 'true');
        }
    }

    /**
     * Handle toggle button click
     */
    handleToggleClick(event) {
        event.preventDefault();
        event.stopPropagation();
        
        this.toggle();
    }

    /**
     * Handle close button click
     */
    handleCloseClick(event) {
        event.preventDefault();
        event.stopPropagation();
        
        this.close();
    }

    /**
     * Handle overlay click
     */
    handleOverlayClick(event) {
        if (event.target === this.elements.overlay) {
            this.close();
        }
    }

    /**
     * Handle keyboard events
     */
    handleKeyDown(event) {
        if (!this.state.isOpen) return;

        switch (event.key) {
            case 'Escape':
                event.preventDefault();
                this.close();
                break;
            
            case 'Tab':
                if (this.config.focusTrap && this.state.isMobile) {
                    this.handleTabKey(event);
                }
                break;
        }
    }

    /**
     * Handle tab key for focus trapping
     */
    handleTabKey(event) {
        if (this.state.focusableElements.length === 0) return;

        const firstElement = this.state.focusableElements[0];
        const lastElement = this.state.focusableElements[this.state.focusableElements.length - 1];

        if (event.shiftKey) {
            // Shift + Tab
            if (document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            }
        } else {
            // Tab
            if (document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }
    }

    /**
     * Handle window resize
     */
    handleResize() {
        const wasMobile = this.state.isMobile;
        this.checkMobileState();

        // Close menu if switching from mobile to desktop
        if (wasMobile && !this.state.isMobile && this.state.isOpen) {
            this.close();
        }

        this.emit('nav:resize', {
            isMobile: this.state.isMobile,
            wasOpen: this.state.isOpen
        });
    }

    /**
     * Handle navigation link clicks
     */
    handleLinkClick(event) {
        // Only close on mobile and if menu is open
        if (this.state.isMobile && this.state.isOpen) {
            // Small delay to allow navigation to start
            setTimeout(() => {
                this.close();
            }, 100);
        }
    }

    /**
     * Toggle menu open/closed
     */
    toggle() {
        if (this.state.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    /**
     * Open the menu
     */
    open() {
        if (this.state.isOpen) return;

        this.state.isOpen = true;
        this.state.lastFocusedElement = document.activeElement;

        // Add active classes
        this.elements.toggle?.classList.add('active');
        this.elements.menu?.classList.add('active');
        this.elements.overlay?.classList.add('active');

        // Update ARIA states
        this.elements.toggle?.setAttribute('aria-expanded', 'true');
        this.elements.menu?.setAttribute('aria-hidden', 'false');

        // Prevent body scroll on mobile
        if (this.state.isMobile) {
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.width = '100%';
        }

        // Focus management
        if (this.config.focusTrap && this.state.isMobile) {
            this.trapFocus();
        }

        // Emit event
        this.emit('nav:opened', { instance: this });
    }

    /**
     * Close the menu
     */
    close() {
        if (!this.state.isOpen) return;

        this.state.isOpen = false;

        // Remove active classes
        this.elements.toggle?.classList.remove('active');
        this.elements.menu?.classList.remove('active');
        this.elements.overlay?.classList.remove('active');

        // Update ARIA states
        this.elements.toggle?.setAttribute('aria-expanded', 'false');
        this.elements.menu?.setAttribute('aria-hidden', 'true');

        // Restore body scroll
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';

        // Restore focus
        if (this.state.lastFocusedElement) {
            this.state.lastFocusedElement.focus();
            this.state.lastFocusedElement = null;
        }

        // Emit event
        this.emit('nav:closed', { instance: this });
    }

    /**
     * Trap focus within the menu
     */
    trapFocus() {
        this.updateFocusableElements();
        
        if (this.state.focusableElements.length > 0) {
            // Focus first element
            this.state.focusableElements[0].focus();
        }
    }

    /**
     * Check if menu is open
     */
    isOpen() {
        return this.state.isOpen;
    }

    /**
     * Check if in mobile state
     */
    isMobile() {
        return this.state.isMobile;
    }

    /**
     * Get current state
     */
    getState() {
        return { ...this.state };
    }

    /**
     * Destroy the navigation instance
     */
    destroy() {
        if (!this.state.isInitialized) return;

        // Close menu if open
        if (this.state.isOpen) {
            this.close();
        }

        // Remove event listeners
        this.elements.toggle?.removeEventListener('click', this.handleToggleClick);
        this.elements.close?.removeEventListener('click', this.handleCloseClick);
        this.elements.overlay?.removeEventListener('click', this.handleOverlayClick);
        document.removeEventListener('keydown', this.handleKeyDown);
        window.removeEventListener('resize', this.handleResize);

        // Remove link event listeners
        if (this.elements.menu) {
            const links = this.elements.menu.querySelectorAll('a:not([href="#"])');
            links.forEach(link => {
                link.removeEventListener('click', this.handleLinkClick);
            });
        }

        // Reset state
        this.state.isInitialized = false;
        this.elements = {};

        this.emit('nav:destroyed', { instance: this });
    }

    /**
     * Emit custom events
     */
    emit(eventName, data = {}) {
        const event = new CustomEvent(eventName, {
            detail: data,
            bubbles: true,
            cancelable: true
        });
        
        document.dispatchEvent(event);
    }

    /**
     * Add event listener
     */
    on(eventName, callback) {
        document.addEventListener(eventName, callback);
        return this;
    }

    /**
     * Remove event listener
     */
    off(eventName, callback) {
        document.removeEventListener(eventName, callback);
        return this;
    }

    /**
     * Debounce utility function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Static method to create instance
     */
    static create(options = {}) {
        return new ResponsiveNavigation(options);
    }

    /**
     * Static method to initialize when DOM is ready
     */
    static init(options = {}) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                return new ResponsiveNavigation(options);
            });
        } else {
            return new ResponsiveNavigation(options);
        }
    }
}

// Auto-initialize if elements are found
document.addEventListener('DOMContentLoaded', () => {
    const navbarElement = document.querySelector('#responsive-navbar');
    if (navbarElement) {
        window.ResponsiveNavigation = ResponsiveNavigation.init();
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ResponsiveNavigation;
}

// AMD support
if (typeof define === 'function' && define.amd) {
    define([], function() {
        return ResponsiveNavigation;
    });
}

// Global export
if (typeof window !== 'undefined') {
    window.ResponsiveNavigation = ResponsiveNavigation;
}