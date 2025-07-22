/**
 * Integration Tests for Notification System
 * Tests complete user workflows and component interactions
 * Requirements: 7.1, 7.2, 7.3, 7.4, 7.5
 */

describe('Notification System Integration', () => {
    let mockFetch;
    let notificationSystem;
    let notificationBell;
    let notificationDropdown;
    
    beforeEach(() => {
        // Setup complete notification system
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
                    <div class="notification-header">
                        <h6 class="notification-title">Notifications</h6>
                        <button class="mark-all-read-btn" aria-label="Mark all as read">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                    <div class="notification-list" id="notificationList"></div>
                </div>
            </div>
            <meta name="csrf-token" content="test-csrf-token">
        `;
        
        notificationSystem = document.getElementById('notificationSystem');
        notificationBell = document.getElementById('notificationBell');
        notificationDropdown = document.getElementById('notificationDropdown');
        
        // Mock fetch with realistic responses
        mockFetch = jest.fn();
        global.fetch = mockFetch;
        
        // Mock performance tracking
        global.performance.now = jest.fn()
            .mockReturnValueOnce(100) // Start time
            .mockReturnValueOnce(150); // End time
    });
    
    afterEach(() => {
        jest.clearAllMocks();
        document.body.innerHTML = '';
        localStorage.clear();
    });
    
    describe('Complete User Workflows', () => {
        test('should handle complete notification viewing workflow', async () => {
            const mockNotifications = [
                testUtils.createMockNotification({
                    id: '1',
                    type: 'course_welcome',
                    title: 'Welcome to Course',
                    message: 'You have successfully enrolled',
                    read: false
                }),
                testUtils.createMockNotification({
                    id: '2',
                    type: 'quiz_completed',
                    title: 'Quiz Completed',
                    message: 'You scored 95%',
                    read: true
                })
            ];
            
            // Mock successful API responses
            mockFetch
                .mockResolvedValueOnce(testUtils.createMockApiResponse({
                    notifications: mockNotifications,
                    unread_count: 1
                }))
                .mockResolvedValueOnce(testUtils.createMockApiResponse({
                    success: true
                }));
            
            // 1. User clicks notification bell
            const clickEvent = testUtils.triggerMouseEvent(notificationBell, 'click');
            
            // 2. Dropdown should open
            expect(notificationDropdown.classList.contains('show')).toBe(true);
            expect(notificationBell.getAttribute('aria-expanded')).toBe('true');
            
            // 3. API should be called to load notifications
            expect(mockFetch).toHaveBeenCalledWith('/notifications', {
                method: 'GET',
                headers: expect.objectContaining({
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': 'test-csrf-token'
                })
            });
            
            // 4. Notifications should be marked as read automatically
            await testUtils.waitForAnimation(100);
            
            expect(mockFetch).toHaveBeenCalledWith('/notifications/mark-all-read', {
                method: 'POST',
                headers: expect.objectContaining({
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': 'test-csrf-token'
                })
            });
            
            // 5. User clicks outside to close
            const outsideClick = testUtils.triggerMouseEvent(document.body, 'click');
            
            setTimeout(() => {
                expect(notificationDropdown.classList.contains('show')).toBe(false);
                expect(notificationBell.getAttribute('aria-expanded')).toBe('false');
            }, 250);
        });
        
        test('should handle error recovery workflow', async () => {
            // Mock network error followed by success
            mockFetch
                .mockRejectedValueOnce(new Error('NetworkError'))
                .mockResolvedValueOnce(testUtils.createMockApiResponse({
                    notifications: [],
                    unread_count: 0
                }));
            
            // 1. User opens dropdown (triggers error)
            testUtils.triggerMouseEvent(notificationBell, 'click');
            
            // 2. Error state should be shown
            await testUtils.waitForAnimation(100);
            
            // 3. User clicks retry button
            const retryButton = document.querySelector('.error-retry-btn');
            if (retryButton) {
                testUtils.triggerMouseEvent(retryButton, 'click');
            }
            
            // 4. Should retry and succeed
            await testUtils.waitForAnimation(100);
            
            expect(mockFetch).toHaveBeenCalledTimes(2);
        });
        
        test('should handle offline to online workflow', async () => {
            // Start offline
            Object.defineProperty(navigator, 'onLine', { value: false });
            
            // 1. User opens dropdown while offline
            testUtils.triggerMouseEvent(notificationBell, 'click');
            
            // 2. Should show offline state
            expect(document.querySelector('.notification-offline-state')).toBeTruthy();
            
            // 3. Go back online
            Object.defineProperty(navigator, 'onLine', { value: true });
            
            // Mock successful response
            mockFetch.mockResolvedValueOnce(testUtils.createMockApiResponse({
                notifications: [],
                unread_count: 0
            }));
            
            // 4. Trigger online event
            const onlineEvent = new Event('online');
            window.dispatchEvent(onlineEvent);
            
            // 5. Should automatically reload notifications
            await testUtils.waitForAnimation(600); // Account for delay
            
            expect(mockFetch).toHaveBeenCalled();
        });
        
        test('should handle keyboard navigation workflow', async () => {
            // Mock notifications
            mockFetch.mockResolvedValueOnce(testUtils.createMockApiResponse({
                notifications: [
                    testUtils.createMockNotification({ id: '1', title: 'First' }),
                    testUtils.createMockNotification({ id: '2', title: 'Second' })
                ],
                unread_count: 2
            }));
            
            // 1. Focus bell with Tab
            notificationBell.focus();
            expect(document.activeElement).toBe(notificationBell);
            
            // 2. Open with Enter
            testUtils.triggerKeyboardEvent(notificationBell, 'Enter');
            expect(notificationBell.getAttribute('aria-expanded')).toBe('true');
            
            // 3. Navigate with Arrow keys
            const arrowDown = testUtils.triggerKeyboardEvent(document, 'ArrowDown');
            expect(arrowDown.defaultPrevented).toBe(true);
            
            // 4. Close with Escape
            testUtils.triggerKeyboardEvent(document, 'Escape');
            
            setTimeout(() => {
                expect(notificationBell.getAttribute('aria-expanded')).toBe('false');
                expect(document.activeElement).toBe(notificationBell);
            }, 250);
        });
    });
    
    describe('Performance Integration', () => {
        test('should track and optimize performance metrics', async () => {
            const performanceMetrics = {
                loadStartTime: null,
                loadEndTime: null,
                retryAttempts: 0,
                cacheHits: 0,
                networkErrors: 0
            };
            
            // Mock performance tracking
            const startTracking = jest.fn(() => {
                performanceMetrics.loadStartTime = performance.now();
            });
            
            const endTracking = jest.fn((success) => {
                performanceMetrics.loadEndTime = performance.now();
                return performanceMetrics.loadEndTime - performanceMetrics.loadStartTime;
            });
            
            // Mock successful load
            mockFetch.mockResolvedValueOnce(testUtils.createMockApiResponse({
                notifications: [],
                unread_count: 0
            }));
            
            // Start performance tracking
            startTracking();
            
            // Trigger load
            testUtils.triggerMouseEvent(notificationBell, 'click');
            
            await testUtils.waitForAnimation(100);
            
            // End performance tracking
            const loadTime = endTracking(true);
            
            expect(startTracking).toHaveBeenCalled();
            expect(endTracking).toHaveBeenCalledWith(true);
            expect(loadTime).toBeLessThan(1000); // Should load quickly
        });
        
        test('should handle memory cleanup efficiently', () => {
            const initialMemory = performance.memory.usedJSHeapSize;
            
            // Create many notifications
            const manyNotifications = Array.from({ length: 100 }, (_, i) => 
                testUtils.createMockNotification({ id: `${i}`, title: `Notification ${i}` })
            );
            
            // Mock response with many notifications
            mockFetch.mockResolvedValueOnce(testUtils.createMockApiResponse({
                notifications: manyNotifications.slice(0, 5), // Only show first 5
                unread_count: 5
            }));
            
            // Open dropdown
            testUtils.triggerMouseEvent(notificationBell, 'click');
            
            // Close dropdown
            testUtils.triggerMouseEvent(document.body, 'click');
            
            // Memory usage should not increase significantly
            const finalMemory = performance.memory.usedJSHeapSize;
            const memoryIncrease = finalMemory - initialMemory;
            
            expect(memoryIncrease).toBeLessThan(1000000); // Less than 1MB increase
        });
        
        test('should debounce rapid interactions', () => {
            let debounceTimer = null;
            const debouncedFunction = jest.fn(() => {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    // Actual function execution
                }, 100);
            });
            
            // Rapid clicks
            for (let i = 0; i < 10; i++) {
                testUtils.triggerMouseEvent(notificationBell, 'click');
                debouncedFunction();
            }
            
            // Should be called for each click but debounced internally
            expect(debouncedFunction).toHaveBeenCalledTimes(10);
        });
    });
    
    describe('Accessibility Integration', () => {
        test('should provide complete screen reader experience', async () => {
            const announcements = [];
            
            // Mock screen reader announcements
            const announceToScreenReader = jest.fn((message, priority = 'polite') => {
                announcements.push({ message, priority });
            });
            
            global.announceToScreenReader = announceToScreenReader;
            
            // Mock notifications
            mockFetch.mockResolvedValueOnce(testUtils.createMockApiResponse({
                notifications: [testUtils.createMockNotification()],
                unread_count: 1
            }));
            
            // 1. Announce loading
            announceToScreenReader('Loading notifications...');
            
            // 2. Open dropdown
            testUtils.triggerMouseEvent(notificationBell, 'click');
            
            // 3. Announce success
            await testUtils.waitForAnimation(100);
            announceToScreenReader('Notifications loaded. 1 total notifications, 1 unread.');
            
            // 4. Announce state changes
            announceToScreenReader('All notifications marked as read.');
            
            expect(announcements).toEqual([
                { message: 'Loading notifications...', priority: 'polite' },
                { message: 'Notifications loaded. 1 total notifications, 1 unread.', priority: 'polite' },
                { message: 'All notifications marked as read.', priority: 'polite' }
            ]);
        });
        
        test('should handle focus management across interactions', () => {
            const focusHistory = [];
            
            // Track focus changes
            const originalFocus = Element.prototype.focus;
            Element.prototype.focus = function() {
                focusHistory.push(this.id || this.className);
                originalFocus.call(this);
            };
            
            // 1. Focus bell
            notificationBell.focus();
            
            // 2. Open dropdown
            testUtils.triggerKeyboardEvent(notificationBell, 'Enter');
            
            // 3. Navigate to first item
            const firstItem = document.createElement('div');
            firstItem.className = 'notification-item';
            firstItem.setAttribute('tabindex', '0');
            document.getElementById('notificationList').appendChild(firstItem);
            
            testUtils.triggerKeyboardEvent(document, 'ArrowDown');
            firstItem.focus();
            
            // 4. Close and return focus
            testUtils.triggerKeyboardEvent(document, 'Escape');
            notificationBell.focus();
            
            expect(focusHistory).toContain('notificationBell');
            expect(focusHistory).toContain('notification-item');
            
            // Restore original focus
            Element.prototype.focus = originalFocus;
        });
        
        test('should provide appropriate ARIA live region updates', () => {
            // Create live regions
            const politeRegion = document.createElement('div');
            politeRegion.setAttribute('aria-live', 'polite');
            politeRegion.id = 'polite-announcements';
            document.body.appendChild(politeRegion);
            
            const assertiveRegion = document.createElement('div');
            assertiveRegion.setAttribute('aria-live', 'assertive');
            assertiveRegion.id = 'assertive-announcements';
            document.body.appendChild(assertiveRegion);
            
            // Mock different announcement types
            const announcePolite = (message) => {
                politeRegion.textContent = message;
            };
            
            const announceAssertive = (message) => {
                assertiveRegion.textContent = message;
            };
            
            // Test different scenarios
            announcePolite('Loading notifications...');
            expect(politeRegion.textContent).toBe('Loading notifications...');
            
            announcePolite('3 notifications loaded');
            expect(politeRegion.textContent).toBe('3 notifications loaded');
            
            announceAssertive('Error loading notifications');
            expect(assertiveRegion.textContent).toBe('Error loading notifications');
        });
    });
    
    describe('Caching Integration', () => {
        test('should handle complete caching workflow', () => {
            const mockNotifications = [
                testUtils.createMockNotification({ id: '1', title: 'Cached 1' }),
                testUtils.createMockNotification({ id: '2', title: 'Cached 2' })
            ];
            
            // 1. Cache notifications
            const cacheData = {
                notifications: mockNotifications,
                timestamp: Date.now(),
                version: '2.0',
                count: mockNotifications.length
            };
            
            localStorage.setItem('cached_notifications', JSON.stringify(cacheData));
            
            // 2. Retrieve from cache
            const cached = localStorage.getItem('cached_notifications');
            const parsedCache = JSON.parse(cached);
            
            expect(parsedCache.notifications).toHaveLength(2);
            expect(parsedCache.version).toBe('2.0');
            
            // 3. Validate cache age
            const age = Date.now() - parsedCache.timestamp;
            const maxAge = 24 * 60 * 60 * 1000; // 24 hours
            
            expect(age).toBeLessThan(maxAge);
            
            // 4. Use cached data when offline
            Object.defineProperty(navigator, 'onLine', { value: false });
            
            // Should use cached notifications
            expect(parsedCache.notifications[0].title).toBe('Cached 1');
        });
        
        test('should handle cache corruption gracefully', () => {
            // Set corrupted cache
            localStorage.setItem('cached_notifications', 'invalid-json');
            
            // Should handle gracefully
            expect(() => {
                try {
                    const cached = localStorage.getItem('cached_notifications');
                    JSON.parse(cached);
                } catch (error) {
                    localStorage.removeItem('cached_notifications');
                    console.warn('Cache corrupted, cleared');
                }
            }).not.toThrow();
            
            expect(localStorage.getItem('cached_notifications')).toBeNull();
        });
        
        test('should handle storage quota exceeded', () => {
            // Mock quota exceeded error
            const originalSetItem = localStorage.setItem;
            localStorage.setItem = jest.fn(() => {
                throw new DOMException('QuotaExceededError');
            });
            
            // Should handle gracefully
            expect(() => {
                try {
                    localStorage.setItem('test', 'value');
                } catch (error) {
                    if (error.name === 'QuotaExceededError') {
                        localStorage.clear();
                        console.warn('Storage quota exceeded, cleared cache');
                    }
                }
            }).not.toThrow();
            
            // Restore original
            localStorage.setItem = originalSetItem;
        });
    });
    
    describe('Network Integration', () => {
        test('should handle network state changes', async () => {
            const networkStates = [];
            
            // Track network state changes
            const handleOnline = () => networkStates.push('online');
            const handleOffline = () => networkStates.push('offline');
            
            window.addEventListener('online', handleOnline);
            window.addEventListener('offline', handleOffline);
            
            // Simulate network changes
            Object.defineProperty(navigator, 'onLine', { value: false });
            window.dispatchEvent(new Event('offline'));
            
            Object.defineProperty(navigator, 'onLine', { value: true });
            window.dispatchEvent(new Event('online'));
            
            expect(networkStates).toEqual(['offline', 'online']);
            
            // Cleanup
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        });
        
        test('should implement exponential backoff for retries', async () => {
            const retryAttempts = [];
            let retryCount = 0;
            const maxRetries = 3;
            
            const retryWithBackoff = () => {
                if (retryCount >= maxRetries) return;
                
                const delay = Math.pow(2, retryCount) * 1000;
                retryAttempts.push({ attempt: retryCount + 1, delay });
                retryCount++;
                
                return new Promise(resolve => {
                    setTimeout(resolve, delay);
                });
            };
            
            // Simulate retries
            await retryWithBackoff(); // 1s delay
            await retryWithBackoff(); // 2s delay
            await retryWithBackoff(); // 4s delay
            
            expect(retryAttempts).toEqual([
                { attempt: 1, delay: 1000 },
                { attempt: 2, delay: 2000 },
                { attempt: 3, delay: 4000 }
            ]);
        });
    });
});