/**
 * Mobile Responsive Utilities
 * Enhanced mobile experience with performance optimizations
 */

(function() {
    'use strict';

    // Performance: Use requestAnimationFrame for smooth scrolling
    const smoothScroll = (element, to, duration) => {
        const start = element.scrollTop;
        const change = to - start;
        const startTime = performance.now();
        const easeInOutQuad = (t, b, c, d) => {
            t /= d / 2;
            if (t < 1) return c / 2 * t * t + b;
            t--;
            return -c / 2 * (t * (t - 2) - 1) + b;
        };

        const animateScroll = (currentTime) => {
            const timeElapsed = currentTime - startTime;
            const run = easeInOutQuad(timeElapsed, start, change, duration);
            element.scrollTop = run;

            if (timeElapsed < duration) {
                requestAnimationFrame(animateScroll);
            }
        };

        requestAnimationFrame(animateScroll);
    };

    // Mobile touch handling
    const TouchHandler = {
        init() {
            this.addTouchEvents();
            this.preventDoubleTap();
            this.addSwipeDetection();
        },

        addTouchEvents() {
            // Add touch event listeners to interactive elements
            const interactiveElements = document.querySelectorAll('button, a, [role="button"], [tabindex], input, select, textarea');
            interactiveElements.forEach(el => {
                el.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
                el.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
            });
        },

        handleTouchStart(e) {
            const target = e.target.closest('button, a, [role="button"]');
            if (target) {
                target.style.transform = 'scale(0.98)';
                target.style.transition = 'transform 0.1s ease';
            }
        },

        handleTouchEnd(e) {
            const target = e.target.closest('button, a, [role="button"]');
            if (target) {
                target.style.transform = 'scale(1)';
            }
        },

        preventDoubleTap() {
            let lastTouchEnd = 0;
            document.addEventListener('touchend', function(e) {
                const now = (new Date()).getTime();
                if (now - lastTouchEnd <= 300) {
                    e.preventDefault();
                }
                lastTouchEnd = now;
            }, false);
        },

        addSwipeDetection() {
            let startX, startY, startTime, endX, endY, endTime;
            const minSwipeDistance = 50;
            const maxSwipeTime = 300;

            document.addEventListener('touchstart', (e) => {
                const touch = e.touches[0];
                startX = touch.pageX;
                startY = touch.pageY;
                startTime = Date.now();
            }, { passive: true });

            document.addEventListener('touchend', (e) => {
                const touch = e.changedTouches[0];
                endX = touch.pageX;
                endY = touch.pageY;
                endTime = Date.now();

                const deltaX = endX - startX;
                const deltaY = endY - startY;
                const deltaTime = endTime - startTime;

                if (deltaTime <= maxSwipeTime) {
                    const absDeltaX = Math.abs(deltaX);
                    const absDeltaY = Math.abs(deltaY);

                    if (absDeltaX > absDeltaY && absDeltaX > minSwipeDistance) {
                        // Horizontal swipe
                        const direction = deltaX > 0 ? 'right' : 'left';
                        this.handleSwipe(direction, absDeltaX);
                    } else if (absDeltaY > absDeltaX && absDeltaY > minSwipeDistance) {
                        // Vertical swipe
                        const direction = deltaY > 0 ? 'down' : 'up';
                        this.handleSwipe(direction, absDeltaY);
                    }
                }
            }, { passive: true });
        },

        handleSwipe(direction, distance) {
            // Emit custom event for swipe handling
            const event = new CustomEvent('responsive-swipe', {
                detail: { direction, distance }
            });
            document.dispatchEvent(event);
        }
    };

    // Mobile navigation enhancements
    const MobileNav = {
        init() {
            this.createBottomNavigation();
            this.addViewportHeightFix();
            this.addInputZoomPrevention();
            this.addKeyboardHandling();
        },

        createBottomNavigation() {
            // Check if bottom nav is needed
            if (window.innerWidth <= 768) {
                const bottomNav = this.createBottomNavElement();
                if (bottomNav) {
                    document.body.appendChild(bottomNav);
                    this.addBottomNavFunctionality(bottomNav);
                }
            }
        },

        createBottomNavElement() {
            // Create bottom navigation only if it doesn't exist
            if (document.querySelector('.mobile-bottom-nav')) return null;

            const nav = document.createElement('nav');
            nav.className = 'mobile-nav';
            nav.innerHTML = `
                <div class="mobile-nav-item">
                    <a href="${document.querySelector('a[href*="courses"]')?.href || '/courses'}" class="mobile-nav-link">
                        <i class="fas fa-book"></i>
                        <span>Courses</span>
                    </a>
                </div>
                <div class="mobile-nav-item">
                    <a href="${document.querySelector('a[href*="mistakes"]')?.href || '/mistakes'}" class="mobile-nav-link">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Review</span>
                    </a>
                </div>
                <div class="mobile-nav-item">
                    <a href="${document.querySelector('a[href*="profile"]')?.href || '/profile'}" class="mobile-nav-link">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </div>
                <div class="mobile-nav-item">
                    <a href="${document.querySelector('a[href*="quizzes"]')?.href || '/quizzes'}" class="mobile-nav-link">
                        <i class="fas fa-question-circle"></i>
                        <span>Quiz</span>
                    </a>
                </div>
            `;

            return nav;
        },

        addBottomNavFunctionality(nav) {
            const links = nav.querySelectorAll('.mobile-nav-link');
            const currentPath = window.location.pathname;

            links.forEach(link => {
                const href = link.getAttribute('href');
                if (currentPath === href || currentPath.startsWith(href.replace(/\/$/, ''))) {
                    link.classList.add('active');
                }

                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleNavClick(href);
                });
            });
        },

        handleNavClick(href) {
            window.location.href = href;
        },

        addViewportHeightFix() {
            // Fix for iOS Safari viewport height issues
            let vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);

            window.addEventListener('resize', () => {
                vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            });
        },

        addInputZoomPrevention() {
            // Prevent zoom on focus for mobile Safari
            const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="search"], input[type="tel"], textarea, select');

            inputs.forEach(input => {
                input.addEventListener('focus', () => {
                    if (window.innerWidth <= 768 && window.innerHeight > window.innerWidth) {
                        // Small hack to prevent zoom
                        input.style.fontSize = '16px';
                    }
                });

                input.addEventListener('blur', () => {
                    // Reset after a delay
                    setTimeout(() => {
                        input.style.fontSize = '';
                    }, 300);
                });
            });
        },

        addKeyboardHandling() {
            // Handle virtual keyboard appearance
            let viewport = document.documentElement;
            let viewportHeight = viewport.clientHeight;
            let keyboardVisible = false;

            const checkViewport = () => {
                const newViewportHeight = viewport.clientHeight;
                const heightDiff = Math.abs(viewportHeight - newViewportHeight);

                if (heightDiff > 150) { // Significant height change indicates keyboard
                    keyboardVisible = viewportHeight > newViewportHeight;
                    document.body.classList.toggle('keyboard-visible', keyboardVisible);

                    if (keyboardVisible) {
                        viewport.classList.add('keyboard-open');
                        this.adjustForKeyboard();
                    } else {
                        viewport.classList.remove('keyboard-open');
                        this.resetFromKeyboard();
                    }
                }

                viewportHeight = newViewportHeight;
            };

            window.addEventListener('resize', checkViewport);
            window.addEventListener('orientationchange', () => {
                setTimeout(checkViewport, 100);
            });

            // Focus/blur events
            const inputElements = document.querySelectorAll('input, textarea, select');
            inputElements.forEach(el => {
                el.addEventListener('focus', () => {
                    document.body.classList.add('input-focused');
                });
                el.addEventListener('blur', () => {
                    document.body.classList.remove('input-focused');
                });
            });
        },

        adjustForKeyboard() {
            // Adjust fixed elements when keyboard appears
            const fixedElements = document.querySelectorAll('[style*="position: fixed"], .fixed');
            fixedElements.forEach(el => {
                if (el.offsetTop + el.offsetHeight > window.innerHeight * 0.6) {
                    el.style.transform = 'translateY(-20vh)';
                    el.classList.add('keyboard-adjusted');
                }
            });
        },

        resetFromKeyboard() {
            // Reset fixed element positions
            const adjustedElements = document.querySelectorAll('.keyboard-adjusted');
            adjustedElements.forEach(el => {
                el.style.transform = '';
                el.classList.remove('keyboard-adjusted');
            });
        }
    };

    // Performance monitoring
    const PerformanceMonitor = {
        init() {
            this.monitorSlowTasks();
            this.addNetworkStatusIndicator();

            // Report performance metrics
            if ('PerformanceObserver' in window) {
                this.observePerformance();
            }
        },

        monitorSlowTasks() {
            // Monitor for long-running tasks that could block the UI
            let lastTime = performance.now();

            const checkForBlockedUI = () => {
                const currentTime = performance.now();
                const delta = currentTime - lastTime;

                if (delta > 100) { // More than 100ms blocked
                    console.warn(`UI was blocked for ${delta.toFixed(2)}ms`);
                    this.reportUIBlock(delta);
                }

                lastTime = currentTime;
                requestAnimationFrame(checkForBlockedUI);
            };

            requestAnimationFrame(checkForBlockedUI);
        },

        reportUIBlock(delta) {
            // Send to analytics or display warning
            const event = new CustomEvent('responsive-ui-block', {
                detail: { duration: delta }
            });
            document.dispatchEvent(event);
        },

        addNetworkStatusIndicator() {
            if ('connection' in navigator) {
                const connection = navigator.connection;

                const updateConnectionStatus = () => {
                    const effectiveType = connection.effectiveType;
                    const rtt = connection.rtt;

                    document.documentElement.classList.remove('connection-poor', 'connection-good', 'connection-excellent');
                    document.documentElement.classList.add(`connection-${this.getConnectionQuality(effectiveType)}`);

                    // Adjust loading strategies based on connection
                    if (effectiveType === 'slow-2g' || effectiveType === '2g') {
                        this.enableConservativeLoading();
                    } else {
                        this.disableConservativeLoading();
                    }
                };

                connection.addEventListener('change', updateConnectionStatus);
                updateConnectionStatus();
            }
        },

        getConnectionQuality(type) {
            const typeMap = {
                'slow-2g': 'poor',
                '2g': 'poor',
                '3g': 'fair',
                '4g': 'good'
            };
            return typeMap[type] || 'good';
        },

        enableConservativeLoading() {
            document.documentElement.classList.add('conservative-loading');
        },

        disableConservativeLoading() {
            document.documentElement.classList.remove('conservative-loading');
        },

        observePerformance() {
            // Observe Largest Contentful Paint
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                console.log('LCP:', Math.round(lastEntry.startTime), 'ms');
            });

            try {
                lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            } catch (e) {
                console.warn('LCP observation not supported');
            }

            // Observe First Input Delay
            const fidObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach(entry => {
                    console.log('FID:', Math.round(entry.processingStart - entry.startTime), 'ms');
                });
            });

            try {
                fidObserver.observe({ entryTypes: ['first-input'] });
            } catch (e) {
                console.warn('FID observation not supported');
            }
        }
    };

    // Initialize all utilities when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeResponsiveUtils);
    } else {
        initializeResponsiveUtils();
    }

    function initializeResponsiveUtils() {
        try {
            TouchHandler.init();
            MobileNav.init();
            PerformanceMonitor.init();

            // Mark as initialized
            document.documentElement.classList.add('responsive-utils-loaded');

            console.log('📱 Mobile responsive utilities loaded successfully');
        } catch (error) {
            console.error('Failed to initialize responsive utilities:', error);
        }
    }

    // Expose globally for debugging
    window.ResponsiveUtils = {
        TouchHandler,
        MobileNav,
        PerformanceMonitor
    };

})();
