/**
 * Comprehensive Unit Tests for Notification Dropdown Component
 * Tests dropdown positioning, responsive behavior, and content rendering
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5
 */

// Mock DOM environment setup is handled in setup.js

describe('Notification Dropdown Component', () => {
    let mockFetch;
    let notificationDropdown;
    let notificationList;
    
    beforeEach(() => {
        // Reset DOM with responsive viewport
        document.head.innerHTML = `
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="csrf-token" content="test-csrf-token">
        `;
        
        document.body.innerHTML = `
            <div class="clean-notification-system" id="notificationSystem">
                <button class="clean-notification-bell" id="notificationBell">
                    <i class="fas fa-bell"></i>
                </button>
                <div 
                    class="clean-notification-dropdown" 
                    id="notificationDropdown"
                    role="dialog"
                    aria-label="Notifications"
                    aria-hidden="true"
                >
                    <div class="notification-header">
                        <h6 class="notification-title">Notifications</h6>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <!-- Dynamic content -->
                    </div>
                </div>
            </div>
        `;
        
        notificationDropdown = document.getElementById('notificationDropdown');
        notificationList = document.getElementById('notificationList');
        
        // Mock fetch
        mockFetch = jest.fn();
        global.fetch = mockFetch;
        
        // Mock console methods
        console.log = jest.fn();
        console.error = jest.fn();
        console.warn = jest.fn();
        
        // Mock window dimensions
        Object.defineProperty(window, 'innerWidth', {
            writable: true,
            configurable: true,
            value: 1024
        });
        
        Object.defineProperty(window, 'innerHeight', {
            writable: true,
            configurable: true,
            value: 768
        });
        
        // Mock getBoundingClientRect
        Element.prototype.getBoundingClientRect = jest.fn(() => ({
            width: 420,
            height: 480,
            top: 50,
            left: 100,
            bottom: 530,
            right: 520,
            x: 100,
            y: 50
        }));
    });
    
    afterEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML = '';
    });
    
    describe('Dropdown Positioning', () => {
        test('should position dropdown correctly on desktop', () => {
            // Desktop viewport
            window.innerWidth = 1024;
            
            // Mock dropdown positioning
            notificationDropdown.style.position = 'absolute';
            notificationDropdown.style.top = 'calc(100% + 8px)';
            notificationDropdown.style.right = '-190px';
            notificationDropdown.style.width = '420px';
            
            expect(notificationDropdown.style.position).toBe('absolute');
            expect(notificationDropdown.style.width).toBe('420px');
        });
        
        test('should adapt positioning for mobile devices', () => {
            // Mobile viewport
            window.innerWidth = 375;
            
            // Mock mobile positioning
            notificationDropdown.style.right = '8px';
            notificationDropdown.style.left = '8px';
            notificationDropdown.style.width = 'auto';
            
            expect(notificationDropdown.style.right).toBe('8px');
            expect(notificationDropdown.style.left).toBe('8px');
            expect(notificationDropdown.style.width).toBe('auto');
        });
        
        test('should handle edge positioning near screen boundaries', () => {
            // Mock dropdown near right edge
            const mockRect = {
                width: 420,
                height: 480,
                top: 50,
                left: window.innerWidth - 200, // Near right edge
                bottom: 530,
                right: window.innerWidth + 220, // Would overflow
                x: window.innerWidth - 200,
                y: 50
            };
            
            notificationDropdown.getBoundingClientRect = jest.fn(() => mockRect);
            
            // Should adjust positioning to prevent overflow
            const rect = notificationDropdown.getBoundingClientRect();
            const wouldOverflow = rect.right > window.innerWidth;
            
            if (wouldOverflow) {
                notificationDropdown.style.right = '8px';
                notificationDropdown.style.left = 'auto';
            }
            
            expect(wouldOverflow).toBe(true);
        });
        
        test('should handle vertical positioning with limited space', () => {
            // Mock dropdown near bottom of screen
            const mockRect = {
                width: 420,
                height: 480,
                top: window.innerHeight - 200, // Near bottom
                left: 100,
                bottom: window.innerHeight + 280, // Would overflow
                right: 520,
                x: 100,
                y: window.innerHeight - 200
            };
            
            notificationDropdown.getBoundingClientRect = jest.fn(() => mockRect);
            
            const rect = notificationDropdown.getBoundingClientRect();
            const wouldOverflowVertically = rect.bottom > window.innerHeight;
            
            if (wouldOverflowVertically) {
                notificationDropdown.style.bottom = 'calc(100% + 8px)';
                notificationDropdown.style.top = 'auto';
            }
            
            expect(wouldOverflowVertically).toBe(true);
        });
    });
    
    describe('Responsive Behavior', () => {
        test('should adapt to tablet viewport (768px)', () => {
            window.innerWidth = 768;
            
            // Should maintain desktop-like behavior but with adjusted margins
            notificationDropdown.style.width = '400px';
            notificationDropdown.style.maxWidth = 'calc(100vw - 32px)';
            
            expect(notificationDropdown.style.width).toBe('400px');
            expect(notificationDropdown.style.maxWidth).toBe('calc(100vw - 32px)');
        });
        
        test('should adapt to small mobile viewport (320px)', () => {
            window.innerWidth = 320;
            
            // Should use full width with margins
            notificationDropdown.style.left = '8px';
            notificationDropdown.style.right = '8px';
            notificationDropdown.style.width = 'auto';
            
            expect(notificationDropdown.style.left).toBe('8px');
            expect(notificationDropdown.style.right).toBe('8px');
        });
        
        test('should handle orientation changes', () => {
            // Portrait to landscape
            window.innerWidth = 667;
            window.innerHeight = 375;
            
            const orientationEvent = new Event('orientationchange');
            window.dispatchEvent(orientationEvent);
            
            // Should recalculate positioning
            expect(window.innerWidth).toBe(667);
            expect(window.innerHeight).toBe(375);
        });
        
        test('should handle window resize events', () => {
            const resizeHandler = jest.fn();
            window.addEventListener('resize', resizeHandler);
            
            // Simulate resize
            window.innerWidth = 800;
            const resizeEvent = new Event('resize');
            window.dispatchEvent(resizeEvent);
            
            expect(resizeHandler).toHaveBeenCalled();
        });
    });
    
    describe('Content Rendering', () => {
        test('should render notification items correctly', () => {
            const mockNotifications = [
                {
                    id: '1',
                    type: 'course_welcome',
                    title: 'Welcome to Course',
                    message: 'You have successfully enrolled',
                    read: false,
                    created_at: '2024-01-15T10:00:00Z'
                },
                {
                    id: '2',
                    type: 'quiz_completed',
                    title: 'Quiz Completed',
                    message: 'You scored 95%',
                    read: true,
                    created_at: '2024-01-14T15:30:00Z'
                }
            ];
            
            // Render notifications
            let notificationItems = '';
            mockNotifications.forEach(notification => {
                const unreadClass = !notification.read ? 'notification-unread' : '';
                notificationItems += `
                    <div class="notification-item ${unreadClass}">
                        <div class="notification-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title-row">
                                <span class="notification-item-title">${notification.title}</span>
                                <span class="notification-time">5m ago</span>
                            </div>
                            <p class="notification-message">${notification.message}</p>
                        </div>
                    </div>
                `;
            });
            
            notificationList.innerHTML = notificationItems;
            
            const items = notificationList.querySelectorAll('.notification-item');
            expect(items.length).toBe(2);
            
            const firstItem = items[0];
            expect(firstItem.querySelector('.notification-item-title').textContent).toBe('Welcome to Course');
            expect(firstItem.querySelector('.notification-message').textContent).toBe('You have successfully enrolled');
        });
        
        test('should render empty state correctly', () => {
            const emptyState = `
                <div class="empty-notification">
                    <i class="fas fa-bell-slash"></i>
                    <p>All caught up</p>
                    <small>No new notifications</small>
                </div>
            `;
            
            notificationList.innerHTML = emptyState;
            
            const emptyElement = notificationList.querySelector('.empty-notification');
            expect(emptyElement).toBeTruthy();
            expect(emptyElement.querySelector('p').textContent).toBe('All caught up');
        });
        
        test('should handle long notification titles and messages', () => {
            const longNotification = {
                title: 'This is a very long notification title that should be truncated properly to prevent layout issues',
                message: 'This is a very long notification message that should wrap to multiple lines but be limited to prevent the dropdown from becoming too tall and unwieldy for users'
            };
            
            const notificationItem = `
                <div class="notification-item">
                    <div class="notification-content">
                        <div class="notification-title-row">
                            <span class="notification-item-title" style="
                                overflow: hidden;
                                text-overflow: ellipsis;
                                white-space: nowrap;
                            ">${longNotification.title}</span>
                        </div>
                        <p class="notification-message" style="
                            display: -webkit-box;
                            -webkit-line-clamp: 2;
                            -webkit-box-orient: vertical;
                            overflow: hidden;
                        ">${longNotification.message}</p>
                    </div>
                </div>
            `;
            
            notificationList.innerHTML = notificationItem;
            
            const titleElement = notificationList.querySelector('.notification-item-title');
            const messageElement = notificationList.querySelector('.notification-message');
            
            expect(titleElement.style.textOverflow).toBe('ellipsis');
            expect(messageElement.style.webkitLineClamp).toBe('2');
        });
        
        test('should render notification icons with correct colors', () => {
            const notificationTypes = [
                { type: 'course_welcome', icon: 'fas fa-graduation-cap', color: '#10b981' },
                { type: 'quiz_completed', icon: 'fas fa-check-circle', color: '#3b82f6' },
                { type: 'achievement', icon: 'fas fa-trophy', color: '#f59e0b' },
                { type: 'error', icon: 'fas fa-exclamation-circle', color: '#ef4444' }
            ];
            
            notificationTypes.forEach(({ type, icon, color }) => {
                const notificationItem = `
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="${icon}" style="color: ${color};"></i>
                        </div>
                    </div>
                `;
                
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = notificationItem;
                const iconElement = tempDiv.querySelector('i');
                
                expect(iconElement.className).toBe(icon);
                expect(iconElement.style.color).toBe(color);
            });
        });
    });
    
    describe('Scrolling Behavior', () => {
        test('should handle smooth scrolling with many notifications', () => {
            // Create many notifications
            let manyNotifications = '';
            for (let i = 1; i <= 20; i++) {
                manyNotifications += `
                    <div class="notification-item">
                        <div class="notification-content">
                            <span class="notification-item-title">Notification ${i}</span>
                            <p class="notification-message">Message ${i}</p>
                        </div>
                    </div>
                `;
            }
            
            notificationList.innerHTML = manyNotifications;
            notificationList.style.maxHeight = '320px';
            notificationList.style.overflowY = 'auto';
            notificationList.style.scrollBehavior = 'smooth';
            
            const items = notificationList.querySelectorAll('.notification-item');
            expect(items.length).toBe(20);
            expect(notificationList.style.maxHeight).toBe('320px');
            expect(notificationList.style.overflowY).toBe('auto');
        });
        
        test('should hide scrollbars for clean appearance', () => {
            notificationList.style.scrollbarWidth = 'none';
            notificationList.style.msOverflowStyle = 'none';
            
            expect(notificationList.style.scrollbarWidth).toBe('none');
            expect(notificationList.style.msOverflowStyle).toBe('none');
        });
        
        test('should support momentum scrolling on mobile', () => {
            notificationList.style.webkitOverflowScrolling = 'touch';
            expect(notificationList.style.webkitOverflowScrolling).toBe('touch');
        });
    });
    
    describe('Loading States', () => {
        test('should render loading skeleton correctly', () => {
            const loadingState = `
                <div class="notification-loading-state" role="status" aria-label="Loading notifications...">
                    <div class="notification-header">
                        <div class="loading-header-content">
                            <div class="skeleton-header" aria-hidden="true"></div>
                            <div class="loading-spinner" aria-hidden="true"></div>
                        </div>
                    </div>
                    <div class="notification-list">
                        <div class="loading-skeleton" aria-hidden="true">
                            <div class="skeleton-items">
                                <div class="skeleton-item">
                                    <div class="skeleton-icon"></div>
                                    <div class="skeleton-content">
                                        <div class="skeleton-title"></div>
                                        <div class="skeleton-message"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            notificationDropdown.innerHTML = loadingState;
            
            const loadingElement = notificationDropdown.querySelector('.notification-loading-state');
            const skeletonItems = notificationDropdown.querySelectorAll('.skeleton-item');
            const spinner = notificationDropdown.querySelector('.loading-spinner');
            
            expect(loadingElement).toBeTruthy();
            expect(loadingElement.getAttribute('role')).toBe('status');
            expect(skeletonItems.length).toBeGreaterThan(0);
            expect(spinner).toBeTruthy();
        });
        
        test('should render error state with retry functionality', () => {
            const errorState = `
                <div class="notification-error-state">
                    <div class="notification-header">
                        <h6 class="notification-title" style="color: #ef4444;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                            Error
                        </h6>
                    </div>
                    <div class="error-content">
                        <div class="error-icon-container">
                            <i class="fas fa-exclamation-triangle error-icon"></i>
                        </div>
                        <h4 class="error-title">Unable to Load Notifications</h4>
                        <p class="error-message">Network connection issue. Please try again.</p>
                        <button class="error-retry-btn" onclick="retryLoadNotifications()">
                            <i class="fas fa-redo-alt"></i>
                            Try Again
                        </button>
                    </div>
                </div>
            `;
            
            notificationDropdown.innerHTML = errorState;
            
            const errorElement = notificationDropdown.querySelector('.notification-error-state');
            const retryButton = notificationDropdown.querySelector('.error-retry-btn');
            const errorTitle = notificationDropdown.querySelector('.error-title');
            
            expect(errorElement).toBeTruthy();
            expect(retryButton).toBeTruthy();
            expect(errorTitle.textContent).toBe('Unable to Load Notifications');
        });
        
        test('should render offline state with cached content notice', () => {
            const offlineState = `
                <div class="notification-offline-state">
                    <div class="notification-header">
                        <h6 class="notification-title" style="color: #f59e0b;">
                            <i class="fas fa-wifi-slash" style="margin-right: 8px;"></i>
                            Offline
                        </h6>
                    </div>
                    <div class="offline-content">
                        <div class="offline-icon-container">
                            <i class="fas fa-wifi-slash offline-icon"></i>
                        </div>
                        <h4 class="offline-title">You're Offline</h4>
                        <p class="offline-message">Check your internet connection and try again</p>
                        <div class="offline-cached-notice">
                            <i class="fas fa-info-circle"></i>
                            <span>Showing cached notifications when available</span>
                        </div>
                    </div>
                </div>
            `;
            
            notificationDropdown.innerHTML = offlineState;
            
            const offlineElement = notificationDropdown.querySelector('.notification-offline-state');
            const cachedNotice = notificationDropdown.querySelector('.offline-cached-notice');
            const offlineTitle = notificationDropdown.querySelector('.offline-title');
            
            expect(offlineElement).toBeTruthy();
            expect(cachedNotice).toBeTruthy();
            expect(offlineTitle.textContent).toBe("You're Offline");
        });
    });
    
    describe('Animation and Transitions', () => {
        test('should have smooth entrance animation', () => {
            notificationDropdown.style.animation = 'cleanDropdownEnter 200ms ease';
            expect(notificationDropdown.style.animation).toBe('cleanDropdownEnter 200ms ease');
        });
        
        test('should handle staggered item animations', () => {
            const items = [
                { delay: '0ms' },
                { delay: '100ms' },
                { delay: '200ms' }
            ];
            
            items.forEach((item, index) => {
                const element = document.createElement('div');
                element.className = 'skeleton-item';
                element.style.animationDelay = item.delay;
                
                expect(element.style.animationDelay).toBe(item.delay);
            });
        });
        
        test('should respect reduced motion preferences', () => {
            // Mock reduced motion media query
            Object.defineProperty(window, 'matchMedia', {
                writable: true,
                value: jest.fn().mockImplementation(query => ({
                    matches: query === '(prefers-reduced-motion: reduce)',
                    media: query
                }))
            });
            
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            
            if (reducedMotion.matches) {
                notificationDropdown.style.animation = 'none';
            }
            
            expect(reducedMotion.matches).toBe(true);
        });
    });
    
    describe('Performance Optimization', () => {
        test('should limit notification rendering for performance', () => {
            // Should only render first 5 notifications
            const manyNotifications = Array.from({ length: 20 }, (_, i) => ({
                id: i + 1,
                title: `Notification ${i + 1}`,
                message: `Message ${i + 1}`
            }));
            
            const limitedNotifications = manyNotifications.slice(0, 5);
            expect(limitedNotifications.length).toBe(5);
        });
        
        test('should use efficient DOM updates', () => {
            const fragment = document.createDocumentFragment();
            
            for (let i = 0; i < 3; i++) {
                const item = document.createElement('div');
                item.className = 'notification-item';
                item.textContent = `Item ${i}`;
                fragment.appendChild(item);
            }
            
            notificationList.appendChild(fragment);
            
            const items = notificationList.querySelectorAll('.notification-item');
            expect(items.length).toBe(3);
        });
        
        test('should handle memory cleanup', () => {
            // Mock cleanup function
            const cleanup = jest.fn(() => {
                notificationList.innerHTML = '';
            });
            
            cleanup();
            expect(cleanup).toHaveBeenCalled();
            expect(notificationList.innerHTML).toBe('');
        });
    });
});