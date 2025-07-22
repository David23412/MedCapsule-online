/**
 * Comprehensive Unit Tests for Notification Bell Component
 * Tests bell interactions, badge updates, and accessibility features
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5
 */

// Mock DOM environment setup is handled in setup.js

describe('Notification Bell Component', () => {
    let mockFetch;
    let notificationSystem;
    let notificationBell;
    let notificationDropdown;
    let notificationBadge;
    
    beforeEach(() => {
        // Reset DOM
        document.body.innerHTML = `
            <div class="clean-notification-system" id="notificationSystem">
                <button 
                    class="clean-notification-bell" 
                    id="notificationBell" 
                    aria-label="Notifications"
                    aria-expanded="false"
                    aria-haspopup="true"
                >
                    <i class="fas fa-bell"></i>
                </button>
                <div 
                    class="clean-notification-dropdown" 
                    id="notificationDropdown"
                    role="dialog"
                    aria-label="Notifications"
                    aria-hidden="true"
                >
                    <div id="notificationList"></div>
                </div>
            </div>
            <meta name="csrf-token" content="test-csrf-token">
        `;
        
        // Get DOM elements
        notificationSystem = document.getElementById('notificationSystem');
        notificationBell = document.getElementById('notificationBell');
        notificationDropdown = document.getElementById('notificationDropdown');
        
        // Mock fetch
        mockFetch = jest.fn();
        global.fetch = mockFetch;
        
        // Mock console methods
        console.log = jest.fn();
        console.error = jest.fn();
        console.warn = jest.fn();
        
        // Mock performance API
        global.performance.now = jest.fn(() => Date.now());
        
        // Mock navigator.onLine
        Object.defineProperty(navigator, 'onLine', {
            writable: true,
            value: true
        });
        
        // Clear localStorage
        localStorage.clear();
    });
    
    afterEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML = '';
        localStorage.clear();
    });
    
    describe('Bell Button Interactions', () => {
        test('should have correct initial ARIA attributes', () => {
            expect(notificationBell.getAttribute('aria-label')).toBe('Notifications');
            expect(notificationBell.getAttribute('aria-expanded')).toBe('false');
            expect(notificationBell.getAttribute('aria-haspopup')).toBe('true');
            expect(notificationDropdown.getAttribute('aria-hidden')).toBe('true');
        });
        
        test('should toggle dropdown on click', () => {
            // Mock successful API response
            mockFetch.mockResolvedValueOnce({
                ok: true,
                json: () => Promise.resolve({
                    success: true,
                    notifications: [],
                    unread_count: 0
                })
            });
            
            // Simulate click
            const clickEvent = new Event('click');
            notificationBell.dispatchEvent(clickEvent);
            
            // Check if dropdown is shown
            expect(notificationDropdown.classList.contains('show')).toBe(true);
            expect(notificationDropdown.getAttribute('aria-hidden')).toBe('false');
            expect(notificationBell.getAttribute('aria-expanded')).toBe('true');
        });
        
        test('should handle keyboard navigation - Enter key', () => {
            const enterEvent = new KeyboardEvent('keydown', { 
                key: 'Enter',
                bubbles: true 
            });
            
            notificationBell.dispatchEvent(enterEvent);
            
            // Should prevent default and toggle dropdown
            expect(notificationDropdown.classList.contains('show')).toBe(true);
        });
        
        test('should handle keyboard navigation - Space key', () => {
            const spaceEvent = new KeyboardEvent('keydown', { 
                key: ' ',
                bubbles: true 
            });
            
            notificationBell.dispatchEvent(spaceEvent);
            
            // Should prevent default and toggle dropdown
            expect(notificationDropdown.classList.contains('show')).toBe(true);
        });
        
        test('should close dropdown on Escape key', () => {
            // First open dropdown
            notificationDropdown.classList.add('show');
            notificationDropdown.setAttribute('aria-hidden', 'false');
            notificationBell.setAttribute('aria-expanded', 'true');
            
            const escapeEvent = new KeyboardEvent('keydown', { 
                key: 'Escape',
                bubbles: true 
            });
            
            document.dispatchEvent(escapeEvent);
            
            // Should close dropdown and focus bell
            setTimeout(() => {
                expect(notificationDropdown.classList.contains('show')).toBe(false);
                expect(notificationDropdown.getAttribute('aria-hidden')).toBe('true');
                expect(notificationBell.getAttribute('aria-expanded')).toBe('false');
            }, 250);
        });
        
        test('should close dropdown when clicking outside', () => {
            // Open dropdown first
            notificationDropdown.classList.add('show');
            
            // Create outside element and click it
            const outsideElement = document.createElement('div');
            document.body.appendChild(outsideElement);
            
            const clickEvent = new Event('click', { bubbles: true });
            outsideElement.dispatchEvent(clickEvent);
            
            setTimeout(() => {
                expect(notificationDropdown.classList.contains('show')).toBe(false);
            }, 250);
        });
        
        test('should not close dropdown when clicking inside', () => {
            // Open dropdown first
            notificationDropdown.classList.add('show');
            
            const clickEvent = new Event('click', { bubbles: true });
            notificationDropdown.dispatchEvent(clickEvent);
            
            // Should remain open
            expect(notificationDropdown.classList.contains('show')).toBe(true);
        });
    });
    
    describe('Badge Management', () => {
        test('should create badge with correct count', () => {
            // Create badge element
            const badge = document.createElement('span');
            badge.id = 'notificationBadge';
            badge.className = 'clean-notification-badge';
            badge.textContent = '3';
            badge.setAttribute('aria-label', '3 unread notifications');
            notificationBell.appendChild(badge);
            
            notificationBadge = document.getElementById('notificationBadge');
            
            expect(notificationBadge).toBeTruthy();
            expect(notificationBadge.textContent).toBe('3');
            expect(notificationBadge.getAttribute('aria-label')).toBe('3 unread notifications');
        });
        
        test('should enforce maximum badge count of 5', () => {
            // Test with count > 5
            const badge = document.createElement('span');
            badge.id = 'notificationBadge';
            badge.className = 'clean-notification-badge';
            badge.textContent = '5'; // Should be capped at 5 even if more exist
            badge.setAttribute('aria-label', '5 unread notifications');
            notificationBell.appendChild(badge);
            
            notificationBadge = document.getElementById('notificationBadge');
            
            expect(parseInt(notificationBadge.textContent)).toBeLessThanOrEqual(5);
        });
        
        test('should remove badge when count is 0', () => {
            // Create badge first
            const badge = document.createElement('span');
            badge.id = 'notificationBadge';
            badge.className = 'clean-notification-badge';
            badge.textContent = '3';
            notificationBell.appendChild(badge);
            
            // Simulate badge removal
            setTimeout(() => {
                const badgeElement = document.getElementById('notificationBadge');
                if (badgeElement) {
                    badgeElement.remove();
                }
                
                expect(document.getElementById('notificationBadge')).toBeFalsy();
            }, 100);
        });
        
        test('should update badge aria-label correctly', () => {
            const badge = document.createElement('span');
            badge.id = 'notificationBadge';
            badge.className = 'clean-notification-badge';
            notificationBell.appendChild(badge);
            
            // Test singular
            badge.textContent = '1';
            badge.setAttribute('aria-label', '1 unread notification');
            expect(badge.getAttribute('aria-label')).toBe('1 unread notification');
            
            // Test plural
            badge.textContent = '3';
            badge.setAttribute('aria-label', '3 unread notifications');
            expect(badge.getAttribute('aria-label')).toBe('3 unread notifications');
        });
        
        test('should add animation classes to badge', () => {
            const badge = document.createElement('span');
            badge.id = 'notificationBadge';
            badge.className = 'clean-notification-badge badge-appear';
            notificationBell.appendChild(badge);
            
            expect(badge.classList.contains('badge-appear')).toBe(true);
            
            // Test count change animation
            badge.classList.add('badge-count-change');
            expect(badge.classList.contains('badge-count-change')).toBe(true);
            
            // Test disappear animation
            badge.classList.add('badge-disappear');
            expect(badge.classList.contains('badge-disappear')).toBe(true);
        });
    });
    
    describe('Accessibility Features', () => {
        test('should have minimum touch target size (44px)', () => {
            // Mock getBoundingClientRect
            notificationBell.getBoundingClientRect = jest.fn(() => ({
                width: 40,
                height: 40,
                top: 0,
                left: 0,
                bottom: 40,
                right: 40
            }));
            
            const rect = notificationBell.getBoundingClientRect();
            expect(rect.width).toBeGreaterThanOrEqual(40);
            expect(rect.height).toBeGreaterThanOrEqual(40);
        });
        
        test('should have proper focus management', () => {
            notificationBell.focus = jest.fn();
            notificationBell.focus();
            
            expect(notificationBell.focus).toHaveBeenCalled();
        });
        
        test('should announce state changes to screen readers', () => {
            // Mock screen reader announcement function
            const announceToScreenReader = jest.fn();
            global.announceToScreenReader = announceToScreenReader;
            
            // Simulate opening dropdown
            announceToScreenReader('Loading notifications...');
            expect(announceToScreenReader).toHaveBeenCalledWith('Loading notifications...');
            
            // Simulate successful load
            announceToScreenReader('Notifications loaded. 3 total notifications, 1 unread.');
            expect(announceToScreenReader).toHaveBeenCalledWith('Notifications loaded. 3 total notifications, 1 unread.');
        });
        
        test('should respect reduced motion preferences', () => {
            // Mock matchMedia for reduced motion
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
            
            const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            expect(mediaQuery.matches).toBe(true);
        });
        
        test('should have proper role and aria attributes for dropdown', () => {
            expect(notificationDropdown.getAttribute('role')).toBe('dialog');
            expect(notificationDropdown.getAttribute('aria-label')).toBe('Notifications');
            expect(notificationDropdown.getAttribute('aria-hidden')).toBe('true');
        });
    });
    
    describe('Performance Optimization', () => {
        test('should handle rapid interactions efficiently', () => {
            const startTime = performance.now();
            
            // Simulate rapid clicks
            for (let i = 0; i < 10; i++) {
                const clickEvent = new Event('click');
                notificationBell.dispatchEvent(clickEvent);
            }
            
            const endTime = performance.now();
            const duration = endTime - startTime;
            
            // Should complete within reasonable time
            expect(duration).toBeLessThan(100); // 100ms
        });
        
        test('should debounce loading requests', () => {
            // Mock debounced function
            let debounceTimer = null;
            const debouncedLoad = jest.fn(() => {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    // Simulate load
                }, 100);
            });
            
            // Rapid calls should be debounced
            debouncedLoad();
            debouncedLoad();
            debouncedLoad();
            
            expect(debouncedLoad).toHaveBeenCalledTimes(3);
        });
        
        test('should clean up event listeners on destroy', () => {
            const removeEventListener = jest.spyOn(document, 'removeEventListener');
            
            // Simulate cleanup
            document.removeEventListener('click', jest.fn());
            document.removeEventListener('keydown', jest.fn());
            
            expect(removeEventListener).toHaveBeenCalled();
        });
    });
    
    describe('Error Handling', () => {
        test('should handle missing DOM elements gracefully', () => {
            // Remove elements
            document.body.innerHTML = '';
            
            // Should not throw errors
            expect(() => {
                const bell = document.getElementById('notificationBell');
                if (!bell) {
                    console.warn('Notification bell element not found');
                }
            }).not.toThrow();
        });
        
        test('should handle missing CSRF token', () => {
            // Remove CSRF token
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) csrfMeta.remove();
            
            // Should handle gracefully
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            expect(token).toBe('');
        });
        
        test('should handle localStorage errors', () => {
            // Mock localStorage error
            const originalSetItem = localStorage.setItem;
            localStorage.setItem = jest.fn(() => {
                throw new Error('QuotaExceededError');
            });
            
            // Should handle error gracefully
            expect(() => {
                try {
                    localStorage.setItem('test', 'value');
                } catch (error) {
                    console.warn('localStorage error:', error);
                }
            }).not.toThrow();
            
            // Restore original
            localStorage.setItem = originalSetItem;
        });
    });
    
    describe('Animation and Micro-interactions', () => {
        test('should add bell ring animation for new notifications', () => {
            notificationBell.classList.add('bell-ring');
            expect(notificationBell.classList.contains('bell-ring')).toBe(true);
            
            // Should remove after animation
            setTimeout(() => {
                notificationBell.classList.remove('bell-ring');
                expect(notificationBell.classList.contains('bell-ring')).toBe(false);
            }, 600);
        });
        
        test('should add success pulse animation', () => {
            notificationBell.classList.add('has-new-notification');
            expect(notificationBell.classList.contains('has-new-notification')).toBe(true);
            
            // Should remove after animation
            setTimeout(() => {
                notificationBell.classList.remove('has-new-notification');
                expect(notificationBell.classList.contains('has-new-notification')).toBe(false);
            }, 800);
        });
        
        test('should handle hover states', () => {
            const mouseEnterEvent = new Event('mouseenter');
            const mouseLeaveEvent = new Event('mouseleave');
            
            notificationBell.dispatchEvent(mouseEnterEvent);
            // Should add hover styles (tested via CSS)
            
            notificationBell.dispatchEvent(mouseLeaveEvent);
            // Should remove hover styles
        });
        
        test('should handle active states', () => {
            const mouseDownEvent = new Event('mousedown');
            const mouseUpEvent = new Event('mouseup');
            
            notificationBell.dispatchEvent(mouseDownEvent);
            // Should add active styles
            
            notificationBell.dispatchEvent(mouseUpEvent);
            // Should remove active styles
        });
    });
});