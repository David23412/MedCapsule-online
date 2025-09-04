/**
 * MedCapsule Responsive JavaScript Utilities
 * 
 * This file provides JavaScript utilities for responsive design,
 * device detection, and mobile-specific functionality.
 */

class ResponsiveUtils {
    constructor() {
        this.config = window.ResponsiveConfig || {};
        this.breakpoints = this.config.breakpoints || {};
        this.deviceType = this.config.deviceType || 'desktop';
        this.isMobile = this.config.isMobile || false;
        this.isTablet = this.config.isTablet || false;
        this.hasTouch = this.config.hasTouchCapability || false;
        
        this.currentBreakpoint = this.getCurrentBreakpoint();
        this.previousBreakpoint = this.currentBreakpoint;
        
        this.init();
    }

    /**
     * Initialize responsive utilities
     */
    init() {
        this.setupEventListeners();
        this.initLazyLoading();
        this.initTouchOptimizations();
        this.initViewportFixes();
        this.initPerformanceOptimizations();
        
        // Emit ready event
        this.emit('responsive:ready', {
            deviceType: this.deviceType,
            breakpoint: this.currentBreakpoint,
            isMobile: this.isMobile,
            hasTouch: this.hasTouch
        });
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Resize handler with debouncing
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.handleResize();
            }, 150);
        });

        // Orientation change handler
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.handleOrientationChange();
            }, 100);
        });

        // Touch event optimizations
        if (this.hasTouch) {
            this.setupTouchEventOptimizations();
        }
    }

    /**
     * Handle window resize
     */
    handleResize() {
        const newBreakpoint = this.getCurrentBreakpoint();
        
        if (newBreakpoint !== this.currentBreakpoint) {
            this.previousBreakpoint = this.currentBreakpoint;
            this.currentBreakpoint = newBreakpoint;
            
            this.emit('responsive:breakpointChange', {
                from: this.previousBreakpoint,
                to: this.currentBreakpoint,
                width: window.innerWidth
            });
        }

        this.updateViewportHeight();
        this.emit('responsive:resize', {
            width: window.innerWidth,
            height: window.innerHeight,
            breakpoint: this.currentBreakpoint
        });
    }

    /**
     * Handle orientation change
     */
    handleOrientationChange() {
        this.updateViewportHeight();
        this.emit('responsive:orientationChange', {
            orientation: this.getOrientation(),
            width: window.innerWidth,
            height: window.innerHeight
        });
    }

    /**
     * Get current breakpoint based on window width
     */
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        
        if (width < this.breakpoints.tablet) {
            return width < this.breakpoints.mobile_large ? 'mobile' : 'mobile-large';
        } else if (width < this.breakpoints.desktop) {
            return 'tablet';
        } else if (width < this.breakpoints.desktop_large) {
            return 'desktop';
        } else {
            return 'desktop-large';
        }
    }

    /**
     * Get device orientation
     */
    getOrientation() {
        return window.innerHeight > window.innerWidth ? 'portrait' : 'landscape';
    }

    /**
     * Check if current viewport matches a breakpoint
     */
    isBreakpoint(breakpoint) {
        return this.currentBreakpoint === breakpoint;
    }

    /**
     * Check if current viewport is at or above a breakpoint
     */
    isBreakpointUp(breakpoint) {
        const breakpointOrder = ['mobile', 'mobile-large', 'tablet', 'desktop', 'desktop-large'];
        const currentIndex = breakpointOrder.indexOf(this.currentBreakpoint);
        const targetIndex = breakpointOrder.indexOf(breakpoint);
        return currentIndex >= targetIndex;
    }

    /**
     * Check if current viewport is below a breakpoint
     */
    isBreakpointDown(breakpoint) {
        const breakpointOrder = ['mobile', 'mobile-large', 'tablet', 'desktop', 'desktop-large'];
        const currentIndex = breakpointOrder.indexOf(this.currentBreakpoint);
        const targetIndex = breakpointOrder.indexOf(breakpoint);
        return currentIndex < targetIndex;
    }

    /**
     * Initialize lazy loading for images
     */
    initLazyLoading() {
        if (!this.config.lazyLoading || !('IntersectionObserver' in window)) {
            return;
        }

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.dataset.src;
                    
                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                        img.classList.remove('lazy-load');
                        img.classList.add('lazy-loaded');
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px'
        });

        // Observe all lazy images
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    /**
     * Initialize touch-specific optimizations
     */
    initTouchOptimizations() {
        if (!this.hasTouch) return;

        // Prevent double-tap zoom on buttons
        document.addEventListener('touchend', (e) => {
            if (e.target.matches('button, .btn, [role="button"]')) {
                e.preventDefault();
                e.target.click();
            }
        });

        // Add touch classes
        document.documentElement.classList.add('touch-device');
        
        // Optimize scroll performance
        this.optimizeScrolling();
    }

    /**
     * Optimize scrolling performance on mobile
     */
    optimizeScrolling() {
        // Add momentum scrolling to scrollable elements
        const scrollableElements = document.querySelectorAll('.scrollable, .modal-body, .dropdown-menu');
        scrollableElements.forEach(element => {
            element.style.webkitOverflowScrolling = 'touch';
        });
    }

    /**
     * Initialize viewport fixes for mobile browsers
     */
    initViewportFixes() {
        this.updateViewportHeight();
        
        // Fix for iOS Safari viewport height issues
        if (this.isMobile) {
            this.fixMobileViewport();
        }
    }

    /**
     * Update CSS custom property for viewport height
     */
    updateViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }

    /**
     * Fix mobile viewport issues
     */
    fixMobileViewport() {
        // Prevent zoom on form focus
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', this.preventZoom.bind(this));
            input.addEventListener('blur', this.restoreZoom.bind(this));
        });
    }

    /**
     * Prevent zoom on form focus (iOS)
     */
    preventZoom() {
        const viewport = document.querySelector('meta[name="viewport"]');
        if (viewport) {
            viewport.setAttribute('content', 
                'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'
            );
        }
    }

    /**
     * Restore zoom after form blur
     */
    restoreZoom() {
        const viewport = document.querySelector('meta[name="viewport"]');
        if (viewport) {
            viewport.setAttribute('content', 
                'width=device-width, initial-scale=1.0'
            );
        }
    }

    /**
     * Initialize performance optimizations
     */
    initPerformanceOptimizations() {
        // Reduce animations on mobile if preferred
        if (this.config.prefersReducedMotion || (this.isMobile && this.shouldReduceAnimations())) {
            document.documentElement.classList.add('reduce-motion');
        }

        // Optimize images for device pixel ratio
        this.optimizeImages();
    }

    /**
     * Check if animations should be reduced
     */
    shouldReduceAnimations() {
        // Reduce animations on slower devices or low battery
        if ('connection' in navigator) {
            const connection = navigator.connection;
            return connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g';
        }
        return false;
    }

    /**
     * Optimize images for current device
     */
    optimizeImages() {
        const pixelRatio = window.devicePixelRatio || 1;
        const images = document.querySelectorAll('img[data-responsive]');
        
        images.forEach(img => {
            const baseSrc = img.dataset.responsive;
            const optimizedSrc = this.getOptimizedImageSrc(baseSrc, pixelRatio);
            img.src = optimizedSrc;
        });
    }

    /**
     * Get optimized image source for device
     */
    getOptimizedImageSrc(baseSrc, pixelRatio = 1) {
        // This would integrate with your image optimization service
        // For now, return the base source
        return baseSrc;
    }

    /**
     * Setup touch event optimizations
     */
    setupTouchEventOptimizations() {
        // Passive event listeners for better scroll performance
        const passiveEvents = ['touchstart', 'touchmove', 'wheel'];
        passiveEvents.forEach(event => {
            document.addEventListener(event, () => {}, { passive: true });
        });

        // Fast click implementation to reduce 300ms delay
        this.initFastClick();
    }

    /**
     * Initialize fast click to eliminate 300ms delay
     */
    initFastClick() {
        let touchStartX, touchStartY, touchStartTime;

        document.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                const touch = e.touches[0];
                touchStartX = touch.clientX;
                touchStartY = touch.clientY;
                touchStartTime = Date.now();
            }
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            if (e.changedTouches.length === 1 && touchStartTime) {
                const touch = e.changedTouches[0];
                const touchEndX = touch.clientX;
                const touchEndY = touch.clientY;
                const touchEndTime = Date.now();

                const deltaX = Math.abs(touchEndX - touchStartX);
                const deltaY = Math.abs(touchEndY - touchStartY);
                const deltaTime = touchEndTime - touchStartTime;

                // If it's a quick tap without much movement
                if (deltaTime < 200 && deltaX < 10 && deltaY < 10) {
                    const target = document.elementFromPoint(touchEndX, touchEndY);
                    if (target && this.isClickableElement(target)) {
                        e.preventDefault();
                        target.click();
                    }
                }
            }
        });
    }

    /**
     * Check if element is clickable
     */
    isClickableElement(element) {
        const clickableSelectors = [
            'button', 'a', '[role="button"]', '[role="link"]',
            'input[type="button"]', 'input[type="submit"]',
            '.btn', '.clickable'
        ];
        
        return clickableSelectors.some(selector => element.matches(selector));
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
     * Add event listener for responsive events
     */
    on(eventName, callback) {
        document.addEventListener(eventName, callback);
    }

    /**
     * Remove event listener for responsive events
     */
    off(eventName, callback) {
        document.removeEventListener(eventName, callback);
    }

    /**
     * Get current responsive state
     */
    getState() {
        return {
            deviceType: this.deviceType,
            breakpoint: this.currentBreakpoint,
            isMobile: this.isMobile,
            isTablet: this.isTablet,
            hasTouch: this.hasTouch,
            orientation: this.getOrientation(),
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            }
        };
    }

    /**
     * Utility method to wait for breakpoint change
     */
    waitForBreakpoint(targetBreakpoint) {
        return new Promise((resolve) => {
            if (this.currentBreakpoint === targetBreakpoint) {
                resolve(this.getState());
                return;
            }

            const handler = (e) => {
                if (e.detail.to === targetBreakpoint) {
                    this.off('responsive:breakpointChange', handler);
                    resolve(this.getState());
                }
            };

            this.on('responsive:breakpointChange', handler);
        });
    }
}

// Initialize responsive utilities when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ResponsiveUtils = new ResponsiveUtils();
    });
} else {
    window.ResponsiveUtils = new ResponsiveUtils();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ResponsiveUtils;
}