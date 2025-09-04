/**
 * Responsive Navigation Component JavaScript
 * Advanced mobile navigation system with accessibility and performance
 */

(function() {
    'use strict';

    const ResponsiveNavigation = {
        // Configuration
        config: {
            breakpoint: 768,
            animationDuration: 300,
            touchTargetSize: 44,
            swipeThreshold: 50,
            scrollOffset: 100
        },

        // Elements
        elements: {
            nav: null,
            toggle: null,
            close: null,
            menu: null,
            overlay: null,
            items: null
        },

        // State
        state: {
            isOpen: false,
            isAnimating: false,
            menuHeight: 0,
            lastScrollY: 0,
            scrollDirection: 'down'
        },

        // Initialization
        init() {
            this.cacheElements();
            this.setupAccessibility();
            this.bindEvents();
            this.initializeScrollBehavior();
            this.initializeKeyboardNavigation();

            if (this.elements.nav) {
                console.log('🚀 Responsive navigation initialized');
            } else {
                console.warn('⚠️ Responsive navigation elements not found');
                return;
            }

            // Auto-init for mobile if toggle exists
            if (window.innerWidth <= this.config.breakpoint && this.elements.toggle) {
                this.initializeMobileNavigation();
            }

            // Global accessor
            window.ResponsiveNavigation = this;
        },

        // Cache DOM elements
        cacheElements() {
            this.elements.nav = document.querySelector('.responsive-navbar');
            this.elements.toggle = document.querySelector('.responsive-nav-toggle');
            this.elements.close = document.querySelector('.responsive-nav-close');
            this.elements.menu = document.querySelector('.responsive-nav-menu');
            this.elements.overlay = document.querySelector('.responsive-nav-overlay');
            this.elements.items = document.querySelectorAll('.responsive-nav-item');

            // Lazy load overlay if not present
            if (!this.elements.overlay && this.elements.menu) {
                this.createOverlay();
            }
        },

        // Create overlay if missing
        createOverlay() {
            this.elements.overlay = document.createElement('div');
            this.elements.overlay.className = 'responsive-nav-overlay';
            this.elements.overlay.setAttribute('aria-hidden', 'true');

            if (this.elements.nav) {
                this.elements.nav.after(this.elements.overlay);
            }
        },

        // Setup accessibility attributes
        setupAccessibility() {
            if (this.elements.toggle) {
                this.elements.toggle.setAttribute('aria-expanded', 'false');
                this.elements.toggle.setAttribute('aria-label', 'Toggle navigation menu');
                this.elements.toggle.setAttribute('aria-controls', 'responsive-nav-menu');
            }

            if (this.elements.menu) {
                this.elements.menu.setAttribute('role', 'navigation');
                this.elements.menu.setAttribute('aria-hidden', this.state.isOpen ? 'false' : 'true');
            }

            if (this.elements.overlay) {
                this.elements.overlay.setAttribute('aria-hidden', 'true');
                this.elements.overlay.setAttribute('role', 'presentation');
            }

            // Set aria-current on active links
            this.updateActiveLinkAccessibility();
        },

        // Update active link accessibility
        updateActiveLinkAccessibility() {
            const currentPath = window.location.pathname;
            const links = document.querySelectorAll('.responsive-nav-link[href]');

            links.forEach(link => {
                const href = link.getAttribute('href');
                const isActive = currentPath === href || currentPath.startsWith(href + '/');

                if (isActive) {
                    link.setAttribute('aria-current', 'page');
                    link.classList.add('active');
                } else {
                    link.removeAttribute('aria-current');
                    link.classList.remove('active');
                }
            });
        },

        // Bind events
        bindEvents() {
            // Navigation toggle
            if (this.elements.toggle) {
                this.elements.toggle.addEventListener('click', this.toggleMenu.bind(this));
            }

            // Close button
            if (this.elements.close) {
                this.elements.close.addEventListener('click', this.closeMenu.bind(this));
            }

            // Overlay click
            if (this.elements.overlay) {
                this.elements.overlay.addEventListener('click', this.closeMenu.bind(this));
            }

            // Navigation links
            this.bindNavigationLinks();

            // Keyboard events
            document.addEventListener('keydown', this.handleKeyboard.bind(this));

            // Resize and orientation events
            window.addEventListener('resize', this.handleResize.bind(this));
            window.addEventListener('orientationchange', () => {
                setTimeout(this.handleResize.bind(this), 100);
            });

            // Scroll behavior
            window.addEventListener('scroll', this.handleScroll.bind(this), { passive: true });
        },

        // Bind navigation link events
        bindNavigationLinks() {
            const links = this.elements.menu ? this.elements.menu.querySelectorAll('.responsive-nav-link') : [];

            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    // Close menu on link click (mobile only)
                    if (window.innerWidth <= this.config.breakpoint && this.state.isOpen) {
                        setTimeout(() => this.closeMenu(), 150);
                    }

                    // Handle smooth scrolling for anchor links
                    const href = link.getAttribute('href');
                    if (href && href.startsWith('#')) {
                        e.preventDefault();
                        this.smoothScrollToElement(href.substring(1));
                    }
                });
            });
        },

        // Toggle navigation menu
        toggleMenu(e) {
            e.preventDefault();

            if (this.state.isAnimating) return;

            if (this.state.isOpen) {
                this.closeMenu();
            } else {
                this.openMenu();
            }
        },

        // Open navigation menu
        openMenu() {
            if (this.state.isAnimating || this.state.isOpen) return;

            this.state.isAnimating = true;
            this.state.isOpen = true;

            this.updateAccessibilityState(true);
            this.applyTransformClasses(true);

            // Focus management
            if (this.elements.toggle) {
                this.elements.toggle.setAttribute('aria-expanded', 'true');
                this.elements.toggle.classList.add('active');
            }

            // Prevent body scroll
            document.body.style.overflow = 'hidden';

            // Animation complete
            setTimeout(() => {
                this.state.isAnimating = false;
                this.emitEvent('navigation:opened');
            }, this.config.animationDuration);
        },

        // Close navigation menu
        closeMenu() {
            if (this.state.isAnimating || !this.state.isOpen) return;

            this.state.isAnimating = true;
            this.state.isOpen = false;

            this.updateAccessibilityState(false);
            this.applyTransformClasses(false);

            // Restore focus
            if (this.elements.toggle) {
                this.elements.toggle.setAttribute('aria-expanded', 'false');
                this.elements.toggle.classList.remove('active');
                this.elements.toggle.focus();
            }

            // Restore body scroll
            document.body.style.overflow = '';

            // Animation complete
            setTimeout(() => {
                this.state.isAnimating = false;
                this.emitEvent('navigation:closed');
            }, this.config.animationDuration);
        },

        // Update accessibility state
        updateAccessibilityState(isOpen) {
            if (this.elements.menu) {
                this.elements.menu.setAttribute('aria-hidden', !isOpen);
            }

            if (this.elements.overlay) {
                this.elements.overlay.setAttribute('aria-hidden', !isOpen);
            }
        },

        // Apply transform classes
        applyTransformClasses(isOpen) {
            const classes = {
                menu: 'active',
                overlay: 'show'
            };

            for (const [element, className] of Object.entries(classes)) {
                if (this.elements[element]) {
                    if (isOpen) {
                        this.elements[element].classList.add(className);
                    } else {
                        this.elements[element].classList.remove(className);
                    }
                }
            }
        },

        // Initialize mobile navigation
        initializeMobileNavigation() {
            if (!this.elements.menu) return;

            // Add swipe gesture support
            let startX = 0;
            let startY = 0;

            this.elements.menu.addEventListener('touchstart', (e) => {
                const touch = e.touches[0];
                startX = touch.clientX;
                startY = touch.clientY;
            }, { passive: true });

            this.elements.menu.addEventListener('touchmove', (e) => {
                if (!this.state.isOpen) return;

                const touch = e.touches[0];
                const deltaX = startX - touch.clientX;
                const deltaY = startY - touch.clientY;

                // Swipe threshold
                if (Math.abs(deltaX) > 50 && Math.abs(deltaX) > Math.abs(deltaY)) {
                    this.closeMenu();
                }
            }, { passive: true });
        },

        // Handle keyboard events
        handleKeyboard(e) {
            if (!this.state.isOpen) return;

            switch (e.key) {
                case 'Escape':
                    this.closeMenu();
                    break;
                case 'Tab':
                    this.handleTabNavigation(e);
                    break;
            }
        },

        // Handle tab navigation within menu
        handleTabNavigation(e) {
            if (!this.elements.menu) return;

            const focusableElements = this.elements.menu.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );

            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        },

        // Initialize keyboard navigation
        initializeKeyboardNavigation() {
            // Add keyboard event listeners to nav items
            this.elements.items.forEach((item, index) => {
                const link = item.querySelector('.responsive-nav-link');
                if (link) {
                    link.setAttribute('tabindex', '0');
                    link.addEventListener('keydown', (e) => {
                        this.handleNavigationKey(e, index);
                    });
                }
            });
        },

        // Handle navigation key events
        handleNavigationKey(e, currentIndex) {
            switch (e.key) {
                case 'ArrowDown':
                case 'ArrowRight':
                    e.preventDefault();
                    this.focusNextItem(currentIndex);
                    break;
                case 'ArrowUp':
                case 'ArrowLeft':
                    e.preventDefault();
                    this.focusPreviousItem(currentIndex);
                    break;
                case 'Home':
                    e.preventDefault();
                    this.focusFirstItem();
                    break;
                case 'End':
                    e.preventDefault();
                    this.focusLastItem();
                    break;
            }
        },

        // Focus navigation methods
        focusNextItem(currentIndex) {
            const nextIndex = (currentIndex + 1) % this.elements.items.length;
            this.focusItem(nextIndex);
        },

        focusPreviousItem(currentIndex) {
            const prevIndex = currentIndex === 0 ? this.elements.items.length - 1 : currentIndex - 1;
            this.focusItem(prevIndex);
        },

        focusFirstItem() {
            this.focusItem(0);
        },

        focusLastItem() {
            this.focusItem(this.elements.items.length - 1);
        },

        focusItem(index) {
            if (this.elements.items[index]) {
                const link = this.elements.items[index].querySelector('.responsive-nav-link');
                if (link) {
                    link.focus();
                }
            }
        },

        // Handle resize events
        handleResize() {
            const isMobile = window.innerWidth <= this.config.breakpoint;

            // Close menu on desktop if open
            if (!isMobile && this.state.isOpen) {
                this.closeMenu();
            }

            // Update body scroll based on menu state
            document.body.style.overflow = (isMobile && this.state.isOpen) ? 'hidden' : '';

            this.emitEvent('navigation:resize', { width: window.innerWidth, isMobile });
        },

        // Handle scroll events (for navigation behavior)
        handleScroll() {
            const scrollY = window.scrollY;
            const scrollDirection = scrollY > this.state.lastScrollY ? 'down' : 'up';

            if (Math.abs(scrollY - this.state.lastScrollY) > 10) {
                this.state.scrollDirection = scrollDirection;
                this.updateNavigationVisibility();
            }

            this.state.lastScrollY = scrollY;
        },

        // Update navigation visibility based on scroll
        updateNavigationVisibility() {
            if (!this.elements.nav) return;

            const shouldHide = this.state.scrollDirection === 'down' && window.scrollY > this.config.scrollOffset;

            if (shouldHide && !this.elements.nav.classList.contains('nav-hidden')) {
                this.elements.nav.classList.add('nav-hidden');
                this.emitEvent('navigation:hidden');
            } else if (!shouldHide && this.elements.nav.classList.contains('nav-hidden')) {
                this.elements.nav.classList.remove('nav-hidden');
                this.emitEvent('navigation:visible');
            }
        },

        // Smooth scroll to element
        smoothScrollToElement(targetId) {
            const targetElement = document.getElementById(targetId);
            if (!targetElement) return;

            const offsetTop = targetElement.getBoundingClientRect().top + window.scrollY;
            const navHeight = this.elements.nav ? this.elements.nav.offsetHeight : 0;

            window.scrollTo({
                top: offsetTop - navHeight - 20,
                behavior: 'smooth'
            });
        },

        // Scroll behavior initialization
        initializeScrollBehavior() {
            // Add smooth scrolling to same-page links
            document.querySelectorAll('a[href^="#"]').forEach(link => {
                link.addEventListener('click', (e) => {
                    const targetId = link.getAttribute('href').substring(1);
                    if (targetId && document.getElementById(targetId)) {
                        e.preventDefault();
                        this.smoothScrollToElement(targetId);
                    }
                });
            });
        },

        // Event emission
        emitEvent(eventName, detail = {}) {
            const event = new CustomEvent(eventName, {
                detail: {
                    navigation: this,
                    ...detail
                }
            });
            document.dispatchEvent(event);
        },

        // Public API methods
        isOpen() {
            return this.state.isOpen;
        },

        open() {
            this.openMenu();
        },

        close() {
            this.closeMenu();
        },

        toggle() {
            this.toggleMenu();
        },

        destroy() {
            // Clean up event listeners
            if (this.elements.toggle) {
                this.elements.toggle.removeEventListener('click', this.toggleMenu.bind(this));
            }

            document.removeEventListener('keydown', this.handleKeyboard.bind(this));
            window.removeEventListener('resize', this.handleResize.bind(this));

            console.log('🗑️ Responsive navigation destroyed');
        }
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => ResponsiveNavigation.init());
    } else {
        ResponsiveNavigation.init();
    }

    // Handle dynamic content loading
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
                // Check if new navigation elements were added
                const newNav = document.querySelector('.responsive-navbar');
                if (newNav && !ResponsiveNavigation.elements.nav) {
                    ResponsiveNavigation.elements.nav = newNav;
                    ResponsiveNavigation.cacheElements();
                    ResponsiveNavigation.bindEvents();
                }
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Expose globally for external control
    window.ResponsiveNavigation = ResponsiveNavigation;

    // CSP compliance: No inline event handlers or eval usage
    console.log('📱 Responsive navigation component loaded');

})();
