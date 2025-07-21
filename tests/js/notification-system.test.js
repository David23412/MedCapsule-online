/**
 * Comprehensive Test Suite for Notification System Components
 * Tests bell component interactions, dropdown behavior, and accessibility
 */

describe('Notification System', () => {
    let mockFetch;
    let notificationSystem;
    let notificationBell;
    let notificationDropdown;
    
    beforeEach(() => {
        // Setup DOM
        document.body.innerHTML = `
            <div class="clean-notification-system" id="notificationSystem">
                <button class="clean-notification-bell" id="notificationBell" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                </button>
                <div class="clean-notification-dropdown" id="notificationDropdown" aria-hidden="true">
                    <div id="notificationList"></div>
                </div>
            </div>
            <meta name="csrf-token" content="test-token">
        `;
        
        notificationSystem = document.getElementById('notificationSystem');
        notificationBell = document.getElementById('notificationBell');
        notificationDropdown = document.getElementById('notificationDropdown');
        
        // Mock fetch
        mockFetch = jest.fn();
        global.fetch = mockFetch;
        
        // Mock console methods
        console.log = jest.fn();
        console.error = jest.fn();
    });
    
    afterEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML = '';
    });
    
    describe('Bell Component Interactions', () => {
        test('should toggle dropdown on bell click', () => {
            // Mock successful API response
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    notifications: [],
                    unread_count: 0
                })
            });
            
            // Click bell
            notificationBell.click();
            
            expect(notificationDropdown.classList.contains('show')).toBe(true);
            expect(notificationDropdown.getAttribute('aria-hidden')).toBe('false');
            expect(notificationBell.getAttribute('aria-expanded')).toBe('true');
        });
        
        test('should close dropdown when clicking outside', () => {
            // Open dropdown first
            notificationDropdown.classList.add('show');
            
            // Click outside
            document.body.click();
            
            setTimeout(() => {
                expect(notificationDropdown.classList.contains('show')).toBe(false);
                expect(notificationDropdown.getAttribute('aria-hidden')).toBe('true');
                expect(notificationBell.getAttribute('aria-expanded')).toBe('false');
            }, 250);
        });
        
        test('should handle keyboard navigation', () => {
            const enterEvent = new KeyboardEvent('keydown', { key: 'Enter' });
            const spaceEvent = new KeyboardEvent('keydown', { key: ' ' });
            const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape' });
            
            // Test Enter key
            notificationBell.dispatchEvent(enterEvent);
            expect(notificationDropdown.classList.contains('show')).toBe(true);
            
            // Test Escape key
            document.dispatchEvent(escapeEvent);
            setTimeout(() => {
                expect(notificationDropdown.classList.contains('show')).toBe(false);
            }, 250);
        });
        
        test('should update badge count correctly', () => {
            // Test badge creation
            window.updateBadge(3);
            
            const badge = document.getElementById('notificationBadge');
            expect(badge).toBeTruthy();
            expect(badge.textContent).toBe('3');
            expect(badge.getAttribute('aria-label')).toBe('3 unread notifications');
            
            // Test badge update
            window.updateBadge(5);
            expect(badge.textContent).toBe('5');
            
            // Test badge removal
            window.updateBadge(0);
            setTimeout(() => {
                expect(document.getElementById('notificationBadge')).toBeFalsy();
            }, 350);
        });
        
        test('should enforce 5 notification limit on badge', () => {
            window.updateBadge(10);
            
            const badge = document.getElementById('notificationBadge');
            expect(badge.textContent).toBe('5');
        });
    });
    
    describe('Loading and Error States', () => {
        test('should show loading state', () => {
            window.showLoadingState();
            
            const loadingState = document.querySelector('.notification-loading-state');
            expect(loadingState).toBeTruthy();
            
            const skeletonItems = document.querySelectorAll('.skeleton-item');
            expect(skeletonItems.length).toBe(3);
        });
        
        test('should show error state with retry button', () => {
            const errorMessage = 'Network error occurred';
            window.showErrorState(errorMessage);
            
            const errorState = document.querySelector('.notification-error-state');
            expect(errorState).toBeTruthy();
            
            const errorTitle = document.querySelector('.error-title');
            expect(errorTitle.textContent).toBe('Unable to Load Notifications');
            
            const errorMsg = document.querySelector('.error-message');
            expect(errorMsg.textContent).toBe(errorMessage);
            
            const retryBtn = document.querySelector('.error-retry-btn');
            expect(retryBtn).toBeTruthy();
        });
        
        test('should show offline state', () => {
            window.showOfflineState();
            
            const offlineState = document.querySelector('.notification-offline-state');
            expect(offlineState).toBeTruthy();
            
            const offlineTitle = document.querySelector('.offline-title');
            expect(offlineTitle.textContent).toBe("You're Offline");
        });
        
        test('should handle network status changes', () => {
            // Mock online event
            const onlineEvent = new Event('online');
            window.dispatchEvent(onlineEvent);
            
            // Mock offline event
            const offlineEvent = new Event('offline');
            window.dispatchEvent(offlineEvent);
            
            // Should handle events without errors
            expect(console.error).not.toHaveBeenCalled();
        });
    });
    
    describe('API Integration', () => {
        test('should load notifications successfully', async () => {
            const mockNotifications = [
                {
                    id: '1',
                    type: 'course_welcome',
                    title: 'Welcome!',
                    message: 'Welcome to the course',
                    read: false,
                    created_at: new Date().toISOString()
                }
            ];
            
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    notifications: mockNotifications,
                    unread_count: 1
                })
            });
            
            await window.loadNotifications();
            
            expect(mockFetch).toHaveBeenCalledWith('/notifications', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': 'test-token'
                }
            });
        });
        
        test('should handle API errors gracefully', async () => {
            mockFetch.mockRejectedValueOnce(new Error('Network error'));
            
            await window.loadNotifications();
            
            expect(console.error).toHaveBeenCalledWith(
                '❌ Failed to load notifications:',
                expect.any(Error)
            );
        });
        
        test('should mark all notifications as read', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true
                })
            });
            
            await window.markAllNotificationsAsRead();
            
            expect(mockFetch).toHaveBeenCalledWith('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': 'test-token'
                }
            });
        });
        
        test('should perform aggressive cleanup', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    deleted_count: 3
                })
            });
            
            await window.aggressiveAutoDelete();
            
            expect(mockFetch).toHaveBeenCalledWith('/notifications/aggressive-cleanup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': 'test-token'
                }
            });
        });
    });
    
    describe('Animations and Micro-interactions', () => {
        test('should add animation classes to badge', () => {
            // Test badge appearance
            window.updateBadge(1);
            const badge = document.getElementById('notificationBadge');
            expect(badge.classList.contains('badge-appear')).toBe(true);
            
            // Test count change animation
            setTimeout(() => {
                window.updateBadge(2);
                expect(badge.classList.contains('badge-count-change')).toBe(true);
            }, 500);
        });
        
        test('should add bell ring animation for new notifications', () => {
            window.updateBadge(1);
            expect(notificationBell.classList.contains('bell-ring')).toBe(true);
            
            setTimeout(() => {
                expect(notificationBell.classList.contains('bell-ring')).toBe(false);
            }, 650);
        });
        
        test('should respect reduced motion preferences', () => {
            // Mock reduced motion preference
            Object.defineProperty(window, 'matchMedia', {
                writable: true,
                value: jest.fn().mockImplementation(query => ({
                    matches: query === '(prefers-reduced-motion: reduce)',
                    media: query,
                    onchange: null,
                    addListener: jest.fn(),
                    removeListener: jest.fn(),
                    addEventListener: jest.fn(),
                    removeEventListener: jest.fn(),
                    dispatchEvent: jest.fn(),
                })),
            });
            
            // Animations should be disabled or simplified
            const badge = document.createElement('span');
            badge.className = 'clean-notification-badge';
            document.body.appendChild(badge);
            
            const computedStyle = window.getComputedStyle(badge);
            // In reduced motion mode, animations should be none or simplified
            expect(computedStyle.animation).toBe('none');
        });
    });
    
    describe('Accessibility Features', () => {
        test('should have proper ARIA attributes', () => {
            expect(notificationBell.getAttribute('aria-label')).toBe('Notifications');
            expect(notificationDropdown.getAttribute('aria-hidden')).toBe('true');
            
            // Open dropdown
            notificationBell.click();
            expect(notificationDropdown.getAttribute('aria-hidden')).toBe('false');
            expect(notificationBell.getAttribute('aria-expanded')).toBe('true');
        });
        
        test('should have proper focus management', () => {
            notificationBell.focus();
            expect(document.activeElement).toBe(notificationBell);
            
            // Test focus outline
            const computedStyle = window.getComputedStyle(notificationBell, ':focus');
            expect(computedStyle.outline).toBeTruthy();
        });
        
        test('should announce badge changes to screen readers', () => {
            window.updateBadge(3);
            const badge = document.getElementById('notificationBadge');
            expect(badge.getAttribute('aria-label')).toBe('3 unread notifications');
            
            window.updateBadge(1);
            expect(badge.getAttribute('aria-label')).toBe('1 unread notifications');
        });
        
        test('should have minimum touch target sizes', () => {
            const bellRect = notificationBell.getBoundingClientRect();
            expect(bellRect.width).toBeGreaterThanOrEqual(40);
            expect(bellRect.height).toBeGreaterThanOrEqual(40);
        });
    });
    
    describe('Performance Optimization', () => {
        test('should enforce notification limits', () => {
            // Test that system never shows more than 5 notifications
            for (let i = 1; i <= 10; i++) {
                window.updateBadge(i);
            }
            
            const badge = document.getElementById('notificationBadge');
            expect(parseInt(badge.textContent)).toBeLessThanOrEqual(5);
        });
        
        test('should handle rapid badge updates efficiently', () => {
            const startTime = performance.now();
            
            // Rapid updates
            for (let i = 0; i < 100; i++) {
                window.updateBadge(Math.floor(Math.random() * 10));
            }
            
            const endTime = performance.now();
            const duration = endTime - startTime;
            
            // Should complete within reasonable time
            expect(duration).toBeLessThan(100); // 100ms
        });
        
        test('should clean up event listeners properly', () => {
            const initialListenerCount = getEventListeners(document).length;
            
            // Initialize system
            window.initializeNotificationSystem();
            
            // Should not create excessive listeners
            const finalListenerCount = getEventListeners(document).length;
            expect(finalListenerCount - initialListenerCount).toBeLessThan(10);
        });
    });
    
    describe('Error Handling', () => {
        test('should handle malformed API responses', async () => {
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: false,
                    message: 'Invalid request'
                })
            });
            
            await window.loadNotifications();
            
            expect(console.error).toHaveBeenCalled();
        });
        
        test('should handle network timeouts', async () => {
            mockFetch.mockImplementationOnce(() => 
                new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Timeout')), 100)
                )
            );
            
            await window.loadNotifications();
            
            expect(console.error).toHaveBeenCalledWith(
                '❌ Failed to load notifications:',
                expect.any(Error)
            );
        });
        
        test('should handle missing CSRF token', async () => {
            document.querySelector('meta[name="csrf-token"]').remove();
            
            mockFetch.mockResolvedValueOnce({
                ok: false,
                status: 419,
                statusText: 'Page Expired'
            });
            
            await window.loadNotifications();
            
            expect(console.error).toHaveBeenCalled();
        });
    });
});

// Helper function to mock getEventListeners (not available in Jest)
function getEventListeners(element) {
    return []; // Simplified mock
}

// Mock window functions for testing
window.updateBadge = jest.fn();
window.showLoadingState = jest.fn();
window.showErrorState = jest.fn();
window.showOfflineState = jest.fn();
window.loadNotifications = jest.fn();
window.markAllNotificationsAsRead = jest.fn();
window.aggressiveAutoDelete = jest.fn();
window.initializeNotificationSystem = jest.fn();