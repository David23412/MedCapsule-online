/**
 * Visual Regression Tests for Notification System
 * Tests visual consistency across different states and screen sizes
 * Requirements: 7.3, 7.5
 */

const { test, expect } = require('@playwright/test');

test.describe('Notification System Visual Regression', () => {
    test.beforeEach(async ({ page }) => {
        // Navigate to page with notifications
        await page.goto('/dashboard');
        
        // Wait for notification system to load
        await page.waitForSelector('#notificationSystem');
        
        // Mock notification data for consistent testing
        await page.route('/notifications', async route => {
            const mockNotifications = {
                success: true,
                notifications: [
                    {
                        id: '1',
                        type: 'course_welcome',
                        title: 'Welcome to Advanced Biology',
                        message: 'You have successfully enrolled in the course',
                        read: false,
                        created_at: '2024-01-15T10:00:00Z'
                    },
                    {
                        id: '2',
                        type: 'quiz_completed',
                        title: 'Quiz Completed',
                        message: 'You scored 95% on Chapter 1 Quiz',
                        read: true,
                        created_at: '2024-01-14T15:30:00Z'
                    },
                    {
                        id: '3',
                        type: 'achievement',
                        title: 'Achievement Unlocked',
                        message: 'First Quiz Master - Complete your first quiz',
                        read: false,
                        created_at: '2024-01-13T09:15:00Z'
                    }
                ],
                unread_count: 2
            };
            
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(mockNotifications)
            });
        });
    });
    
    test.describe('Bell Component Visual Tests', () => {
        test('should render notification bell correctly', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            
            // Test default state
            await expect(notificationBell).toBeVisible();
            await expect(notificationBell).toHaveScreenshot('notification-bell-default.png');
        });
        
        test('should render notification badge correctly', async ({ page }) => {
            const notificationBadge = page.locator('#notificationBadge');
            
            // Should show badge with count
            await expect(notificationBadge).toBeVisible();
            await expect(notificationBadge).toContainText('2');
            await expect(notificationBadge).toHaveScreenshot('notification-badge.png');
        });
        
        test('should show hover state correctly', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            
            // Hover over bell
            await notificationBell.hover();
            await expect(notificationBell).toHaveScreenshot('notification-bell-hover.png');
        });
        
        test('should show focus state correctly', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            
            // Focus bell with keyboard
            await notificationBell.focus();
            await expect(notificationBell).toHaveScreenshot('notification-bell-focus.png');
        });
        
        test('should show active state correctly', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            
            // Press and hold
            await notificationBell.hover();
            await page.mouse.down();
            await expect(notificationBell).toHaveScreenshot('notification-bell-active.png');
            await page.mouse.up();
        });
    });
    
    test.describe('Dropdown Visual Tests', () => {
        test('should render dropdown correctly when opened', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            await expect(notificationDropdown).toBeVisible();
            
            // Wait for animations to complete
            await page.waitForTimeout(300);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-dropdown-open.png');
        });
        
        test('should render notification items correctly', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            const notificationList = page.locator('#notificationList');
            
            // Open dropdown
            await notificationBell.click();
            await expect(notificationList).toBeVisible();
            
            // Wait for content to load
            await page.waitForTimeout(500);
            
            await expect(notificationList).toHaveScreenshot('notification-items.png');
        });
        
        test('should show unread notification styling', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            
            // Open dropdown
            await notificationBell.click();
            
            // Find unread notification
            const unreadNotification = page.locator('.notification-item').first();
            await expect(unreadNotification).toHaveScreenshot('notification-unread.png');
        });
        
        test('should show read notification styling', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            
            // Open dropdown
            await notificationBell.click();
            
            // Find read notification
            const readNotification = page.locator('.notification-item').nth(1);
            await expect(readNotification).toHaveScreenshot('notification-read.png');
        });
        
        test('should show notification hover state', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            
            // Open dropdown
            await notificationBell.click();
            
            // Hover over notification item
            const notificationItem = page.locator('.notification-item').first();
            await notificationItem.hover();
            await expect(notificationItem).toHaveScreenshot('notification-item-hover.png');
        });
    });
    
    test.describe('Loading States Visual Tests', () => {
        test('should render loading skeleton correctly', async ({ page }) => {
            // Mock slow response to capture loading state
            await page.route('/notifications', async route => {
                await new Promise(resolve => setTimeout(resolve, 2000));
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, notifications: [], unread_count: 0 })
                });
            });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown to trigger loading
            await notificationBell.click();
            
            // Capture loading state
            await expect(notificationDropdown).toHaveScreenshot('notification-loading.png');
        });
        
        test('should render error state correctly', async ({ page }) => {
            // Mock error response
            await page.route('/notifications', async route => {
                await route.fulfill({
                    status: 500,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: false, message: 'Server error' })
                });
            });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown to trigger error
            await notificationBell.click();
            
            // Wait for error state
            await page.waitForTimeout(1000);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-error.png');
        });
        
        test('should render offline state correctly', async ({ page }) => {
            // Simulate offline
            await page.context().setOffline(true);
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            
            // Wait for offline state
            await page.waitForTimeout(1000);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-offline.png');
        });
        
        test('should render empty state correctly', async ({ page }) => {
            // Mock empty response
            await page.route('/notifications', async route => {
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, notifications: [], unread_count: 0 })
                });
            });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            
            // Wait for empty state
            await page.waitForTimeout(500);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-empty.png');
        });
    });
    
    test.describe('Responsive Design Visual Tests', () => {
        test('should render correctly on desktop (1920x1080)', async ({ page }) => {
            await page.setViewportSize({ width: 1920, height: 1080 });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            await page.waitForTimeout(300);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-desktop-1920.png');
        });
        
        test('should render correctly on laptop (1366x768)', async ({ page }) => {
            await page.setViewportSize({ width: 1366, height: 768 });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            await page.waitForTimeout(300);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-laptop-1366.png');
        });
        
        test('should render correctly on tablet (768x1024)', async ({ page }) => {
            await page.setViewportSize({ width: 768, height: 1024 });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            await page.waitForTimeout(300);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-tablet-768.png');
        });
        
        test('should render correctly on mobile (375x667)', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 667 });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            await page.waitForTimeout(300);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-mobile-375.png');
        });
        
        test('should render correctly on small mobile (320x568)', async ({ page }) => {
            await page.setViewportSize({ width: 320, height: 568 });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            await page.waitForTimeout(300);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-mobile-320.png');
        });
    });
    
    test.describe('Animation Visual Tests', () => {
        test('should show dropdown entrance animation', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Capture before opening
            await expect(notificationDropdown).toHaveScreenshot('dropdown-before-open.png');
            
            // Open dropdown
            await notificationBell.click();
            
            // Capture during animation (50ms in)
            await page.waitForTimeout(50);
            await expect(notificationDropdown).toHaveScreenshot('dropdown-opening.png');
            
            // Capture after animation completes
            await page.waitForTimeout(300);
            await expect(notificationDropdown).toHaveScreenshot('dropdown-opened.png');
        });
        
        test('should show badge animation for new notifications', async ({ page }) => {
            // Mock notification update
            await page.route('/notifications', async route => {
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({
                        success: true,
                        notifications: [],
                        unread_count: 5
                    })
                });
            });
            
            const notificationBadge = page.locator('#notificationBadge');
            
            // Trigger badge update
            await page.reload();
            await page.waitForTimeout(100);
            
            await expect(notificationBadge).toHaveScreenshot('badge-animation.png');
        });
        
        test('should respect reduced motion preferences', async ({ page }) => {
            // Enable reduced motion
            await page.emulateMedia({ reducedMotion: 'reduce' });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            
            // Should appear immediately without animation
            await expect(notificationDropdown).toHaveScreenshot('dropdown-reduced-motion.png');
        });
    });
    
    test.describe('Theme and Color Visual Tests', () => {
        test('should render correctly in light theme', async ({ page }) => {
            // Ensure light theme
            await page.addStyleTag({
                content: `
                    :root {
                        --bg-color: #ffffff;
                        --text-color: #000000;
                        --border-color: #e5e7eb;
                    }
                `
            });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            await notificationBell.click();
            await page.waitForTimeout(300);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-light-theme.png');
        });
        
        test('should render correctly in high contrast mode', async ({ page }) => {
            // Simulate high contrast
            await page.emulateMedia({ colorScheme: 'dark', forcedColors: 'active' });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            await notificationBell.click();
            await page.waitForTimeout(300);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-high-contrast.png');
        });
        
        test('should show correct notification type colors', async ({ page }) => {
            const notificationBell = page.locator('#notificationBell');
            
            // Open dropdown
            await notificationBell.click();
            
            // Test different notification type colors
            const courseNotification = page.locator('.notification-item').first();
            const quizNotification = page.locator('.notification-item').nth(1);
            const achievementNotification = page.locator('.notification-item').nth(2);
            
            await expect(courseNotification).toHaveScreenshot('notification-course-color.png');
            await expect(quizNotification).toHaveScreenshot('notification-quiz-color.png');
            await expect(achievementNotification).toHaveScreenshot('notification-achievement-color.png');
        });
    });
    
    test.describe('Cross-browser Visual Consistency', () => {
        test('should render consistently across browsers', async ({ page, browserName }) => {
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            // Open dropdown
            await notificationBell.click();
            await page.waitForTimeout(300);
            
            // Take screenshot with browser name
            await expect(notificationDropdown).toHaveScreenshot(`notification-${browserName}.png`);
        });
        
        test('should handle font rendering consistently', async ({ page }) => {
            // Ensure consistent font loading
            await page.waitForLoadState('networkidle');
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            await notificationBell.click();
            await page.waitForTimeout(500); // Extra time for font loading
            
            await expect(notificationDropdown).toHaveScreenshot('notification-fonts.png');
        });
    });
    
    test.describe('Performance Visual Tests', () => {
        test('should render quickly without layout shifts', async ({ page }) => {
            // Monitor layout shifts
            let layoutShifts = [];
            
            await page.addInitScript(() => {
                new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (entry.entryType === 'layout-shift') {
                            window.layoutShifts = window.layoutShifts || [];
                            window.layoutShifts.push(entry.value);
                        }
                    }
                }).observe({ entryTypes: ['layout-shift'] });
            });
            
            const notificationBell = page.locator('#notificationBell');
            
            // Open dropdown
            await notificationBell.click();
            await page.waitForTimeout(500);
            
            // Check for layout shifts
            layoutShifts = await page.evaluate(() => window.layoutShifts || []);
            
            // Should have minimal layout shifts
            const totalShift = layoutShifts.reduce((sum, shift) => sum + shift, 0);
            expect(totalShift).toBeLessThan(0.1); // CLS threshold
            
            const notificationDropdown = page.locator('#notificationDropdown');
            await expect(notificationDropdown).toHaveScreenshot('notification-no-layout-shift.png');
        });
        
        test('should handle many notifications without visual degradation', async ({ page }) => {
            // Mock many notifications
            const manyNotifications = Array.from({ length: 20 }, (_, i) => ({
                id: `${i + 1}`,
                type: 'info',
                title: `Notification ${i + 1}`,
                message: `This is notification message ${i + 1}`,
                read: i % 3 === 0,
                created_at: new Date(Date.now() - i * 60000).toISOString()
            }));
            
            await page.route('/notifications', async route => {
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({
                        success: true,
                        notifications: manyNotifications.slice(0, 5), // Only show first 5
                        unread_count: 5
                    })
                });
            });
            
            const notificationBell = page.locator('#notificationBell');
            const notificationDropdown = page.locator('#notificationDropdown');
            
            await notificationBell.click();
            await page.waitForTimeout(500);
            
            await expect(notificationDropdown).toHaveScreenshot('notification-many-items.png');
        });
    });
});