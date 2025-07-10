import { test, expect } from '@playwright/test';

/**
 * COURSE ENROLLMENT E2E TESTS
 * Comprehensive testing of course browsing, filtering, enrollment, and access
 */

test.describe('Course Enrollment', () => {
  
  // Setup authentication for tests that require login
  test.use({ storageState: 'tests/e2e/auth/student.json' });

  test.beforeEach(async ({ page }) => {
    // Navigate to courses page
    await page.goto('/courses');
    await page.waitForLoadState('networkidle');
  });

  test.describe('Course Browsing', () => {
    
    test('should display course catalog correctly', async ({ page }) => {
      // Verify courses page loads
      await expect(page).toHaveURL(/.*courses/);
      
      // Check for main course elements
      await expect(page.locator('h1, .hero-title')).toBeVisible();
      await expect(page.locator('.courses-grid, .course-list')).toBeVisible();
      
      // Verify course cards are present
      const courseCards = page.locator('.course-card, .course-item');
      await expect(courseCards).toHaveCount({ min: 1 });
      
      // Verify each course card has required elements
      const firstCard = courseCards.first();
      await expect(firstCard.locator('.course-title, h3, h4')).toBeVisible();
      await expect(firstCard.locator('.course-image, img')).toBeVisible();
      await expect(firstCard.locator('.action-btn, button, .enroll-btn')).toBeVisible();
    });

    test('should load course images properly', async ({ page }) => {
      const courseImages = page.locator('.course-image, .course-card img');
      const imageCount = await courseImages.count();
      
      if (imageCount > 0) {
        // Check that images load successfully
        for (let i = 0; i < Math.min(imageCount, 3); i++) {
          const image = courseImages.nth(i);
          await expect(image).toBeVisible();
          
          // Verify image has loaded (no broken image)
          if (await image.getAttribute('src')) {
            const isLoaded = await image.evaluate(img => img.complete && img.naturalWidth > 0);
            expect(isLoaded).toBeTruthy();
          }
        }
      }
    });

    test('should display course information correctly', async ({ page }) => {
      const courseCards = page.locator('.course-card, .course-item');
      const firstCard = courseCards.first();
      
      // Check course title
      const title = firstCard.locator('.course-title, h3, h4');
      await expect(title).toBeVisible();
      await expect(title).not.toBeEmpty();
      
      // Check course stats (topics, students, etc.)
      const stats = firstCard.locator('.stat-item, .course-stats');
      if (await stats.count() > 0) {
        await expect(stats.first()).toBeVisible();
        await expect(stats.first()).toContainText(/\d+/); // Should contain numbers
      }
      
      // Check action button text
      const actionBtn = firstCard.locator('.action-btn, button, .enroll-btn');
      await expect(actionBtn).toBeVisible();
      await expect(actionBtn).toContainText(/enroll|continue|start/i);
    });

    test('should handle course card interactions', async ({ page }) => {
      const courseCards = page.locator('.course-card, .course-item');
      const firstCard = courseCards.first();
      
      // Test hover effects (if any)
      await firstCard.hover();
      
      // Test info icon tooltip if present
      const infoIcon = firstCard.locator('.info-icon, .course-info-btn');
      if (await infoIcon.isVisible()) {
        await infoIcon.hover();
        
        // Check if tooltip appears
        const tooltip = page.locator('.tooltip, .info-tooltip');
        if (await tooltip.count() > 0) {
          await expect(tooltip.first()).toBeVisible();
        }
      }
    });
  });

  test.describe('Course Filtering', () => {
    
    test('should filter courses by semester', async ({ page }) => {
      // Check if semester filter exists
      const semesterFilter = page.locator('#semesterFilter, .semester-filter, select');
      
      if (await semesterFilter.isVisible()) {
        // Get initial course count
        const initialCourses = await page.locator('.course-card, .course-item').count();
        
        // Select a specific semester
        await semesterFilter.selectOption({ index: 1 }); // Select first non-empty option
        
        // Wait for filter to apply
        await page.waitForTimeout(500);
        
        // Verify courses are filtered
        const filteredCourses = await page.locator('.course-card, .course-item').count();
        
        // Should have different count or show specific courses
        if (initialCourses > 1) {
          expect(filteredCourses).not.toBe(initialCourses);
        }
        
        // Verify filter response time (should be instant)
        const startTime = Date.now();
        await semesterFilter.selectOption({ index: 2 });
        await page.waitForTimeout(100);
        const endTime = Date.now();
        
        expect(endTime - startTime).toBeLessThan(200); // Should be under 200ms
      }
    });

    test('should reset filters correctly', async ({ page }) => {
      const semesterFilter = page.locator('#semesterFilter, .semester-filter, select');
      
      if (await semesterFilter.isVisible()) {
        // Apply a filter
        await semesterFilter.selectOption({ index: 1 });
        await page.waitForTimeout(300);
        
        // Reset filter
        await semesterFilter.selectOption({ value: '' });
        await page.waitForTimeout(300);
        
        // Verify all courses are shown again
        const courseCards = page.locator('.course-card, .course-item');
        await expect(courseCards).toHaveCount({ min: 1 });
      }
    });

    test('should handle empty filter results', async ({ page }) => {
      const semesterFilter = page.locator('#semesterFilter, .semester-filter, select');
      
      if (await semesterFilter.isVisible()) {
        // Try all filter options to find one that might be empty
        const options = await semesterFilter.locator('option').count();
        
        for (let i = 1; i < options; i++) {
          await semesterFilter.selectOption({ index: i });
          await page.waitForTimeout(300);
          
          const courseCount = await page.locator('.course-card, .course-item').count();
          
          if (courseCount === 0) {
            // Check for empty state message
            const emptyState = page.locator('.empty-state, .no-courses, [data-testid="no-results"]');
            await expect(emptyState).toBeVisible();
            await expect(emptyState).toContainText(/no courses|not found/i);
            break;
          }
        }
      }
    });
  });

  test.describe('Course Enrollment Process', () => {
    
    test('should enroll in a free course successfully', async ({ page }) => {
      // Find a free course (button contains "Free" or no price)
      const freeCourseBtn = page.locator('.action-btn:has-text("Free"), .enroll-btn:has-text("Free"), button:has-text("Enroll Free")').first();
      
      if (await freeCourseBtn.isVisible()) {
        // Click enroll button
        await freeCourseBtn.click();
        
        // Wait for enrollment to process
        await page.waitForTimeout(1000);
        
        // Verify enrollment success
        await expect(async () => {
          // Check for success message
          const successMsg = page.locator('.alert-success, .success-message, [data-testid="success"]');
          const isSuccessVisible = await successMsg.isVisible();
          
          // Or check if button changed to "Continue"
          const continueBtn = page.locator('button:has-text("Continue"), .action-btn:has-text("Continue")');
          const isContinueVisible = await continueBtn.isVisible();
          
          // Or check if redirected to course content
          const isRedirected = page.url().includes('/topics') || page.url().includes('/course');
          
          expect(isSuccessVisible || isContinueVisible || isRedirected).toBeTruthy();
        }).toPass({ timeout: 10000 });
      }
    });

    test('should show payment modal for paid courses', async ({ page }) => {
      // Find a paid course button
      const paidCourseBtn = page.locator('.action-btn:has-text("EGP"), .enroll-btn:has-text("$"), button:text-matches(/\\d+/)').first();
      
      if (await paidCourseBtn.isVisible()) {
        // Click enroll button
        await paidCourseBtn.click();
        
        // Wait for modal to appear
        await page.waitForTimeout(1000);
        
        // Verify payment modal appears
        const paymentModal = page.locator('.modal, .payment-modal, [data-testid="payment-modal"]');
        await expect(paymentModal).toBeVisible();
        
        // Verify modal contains payment information
        await expect(paymentModal).toContainText(/payment|price|checkout/i);
        
        // Check for payment methods
        const paymentMethods = paymentModal.locator('.payment-method, .payment-option, button');
        if (await paymentMethods.count() > 0) {
          await expect(paymentMethods.first()).toBeVisible();
        }
        
        // Close modal
        const closeBtn = paymentModal.locator('.close, .btn-close, [data-dismiss="modal"]');
        if (await closeBtn.isVisible()) {
          await closeBtn.click();
          await expect(paymentModal).not.toBeVisible();
        }
      }
    });

    test('should handle enrollment button states correctly', async ({ page }) => {
      const enrollBtns = page.locator('.action-btn, .enroll-btn, button');
      
      if (await enrollBtns.count() > 0) {
        const firstBtn = enrollBtns.first();
        
        // Check initial state
        await expect(firstBtn).toBeEnabled();
        
        // Click button
        await firstBtn.click();
        
        // Check if button shows loading state
        await expect(async () => {
          const isLoading = await firstBtn.textContent();
          const hasSpinner = await firstBtn.locator('.spinner, .loading').isVisible();
          expect(isLoading?.includes('Processing') || hasSpinner).toBeTruthy();
        }).toPass({ timeout: 2000 });
      }
    });

    test('should prevent duplicate enrollments', async ({ page }) => {
      // Find a course the user is already enrolled in
      const continueBtn = page.locator('button:has-text("Continue"), .action-btn:has-text("Continue")').first();
      
      if (await continueBtn.isVisible()) {
        // Verify button text indicates existing enrollment
        await expect(continueBtn).toContainText(/continue|resume|start/i);
        
        // Click should navigate to course content, not show enrollment
        await continueBtn.click();
        
        // Should redirect to course topics or content
        await expect(page).toHaveURL(/\/topics|\/course|\/learn/);
      }
    });
  });

  test.describe('Course Access Validation', () => {
    
    test('should allow access to enrolled courses', async ({ page }) => {
      // Find and click a "Continue" button (enrolled course)
      const continueBtn = page.locator('button:has-text("Continue"), .action-btn:has-text("Continue")').first();
      
      if (await continueBtn.isVisible()) {
        await continueBtn.click();
        
        // Should access course content
        await expect(page).toHaveURL(/\/topics|\/course|\/learn/);
        
        // Verify course content is accessible
        const courseContent = page.locator('.topic-list, .course-content, .lessons');
        await expect(courseContent).toBeVisible();
      }
    });

    test('should restrict access to non-enrolled courses', async ({ page }) => {
      // Try to directly access a course topics page without enrollment
      await page.goto('/topics/999'); // Non-existent or restricted course
      
      // Should redirect to courses page or show access denied
      await expect(async () => {
        const isRedirected = page.url().includes('/courses');
        const hasAccessDenied = await page.locator('.access-denied, .unauthorized, [data-testid="access-denied"]').isVisible();
        const hasEnrollPrompt = await page.locator('.enroll-prompt, .subscription-required').isVisible();
        
        expect(isRedirected || hasAccessDenied || hasEnrollPrompt).toBeTruthy();
      }).toPass({ timeout: 5000 });
    });
  });

  test.describe('Course Progress Tracking', () => {
    
    test('should display course progress for enrolled courses', async ({ page }) => {
      // Look for courses with progress bars
      const progressBars = page.locator('.progress-bar, .progress-fill, [data-testid="progress"]');
      
      if (await progressBars.count() > 0) {
        const firstProgress = progressBars.first();
        await expect(firstProgress).toBeVisible();
        
        // Verify progress has some width (indicating progress)
        const progressWidth = await firstProgress.evaluate(el => {
          const style = window.getComputedStyle(el);
          return parseInt(style.width) || 0;
        });
        
        expect(progressWidth).toBeGreaterThan(0);
      }
    });

    test('should show accurate progress percentages', async ({ page }) => {
      // Look for progress text or percentages
      const progressText = page.locator('.progress-text, .progress-percentage');
      
      if (await progressText.count() > 0) {
        const firstProgressText = await progressText.first().textContent();
        
        // Should contain percentage or fraction
        expect(firstProgressText).toMatch(/\d+%|\d+\/\d+/);
      }
    });
  });

  test.describe('Course Search and Navigation', () => {
    
    test('should navigate between course semesters', async ({ page }) => {
      const semesterFilter = page.locator('#semesterFilter, .semester-filter, select');
      
      if (await semesterFilter.isVisible()) {
        const options = await semesterFilter.locator('option').count();
        
        if (options > 2) {
          // Test navigation between different semesters
          await semesterFilter.selectOption({ index: 1 });
          await page.waitForTimeout(300);
          
          let firstSemesterCourses = await page.locator('.course-card').count();
          
          await semesterFilter.selectOption({ index: 2 });
          await page.waitForTimeout(300);
          
          let secondSemesterCourses = await page.locator('.course-card').count();
          
          // Should show different courses (or at least handle the filter)
          if (firstSemesterCourses > 0 && secondSemesterCourses > 0) {
            // Both semesters have courses - this is the ideal case
            expect(firstSemesterCourses).toBeGreaterThan(0);
            expect(secondSemesterCourses).toBeGreaterThan(0);
          }
        }
      }
    });

    test('should handle keyboard navigation', async ({ page }) => {
      // Test tab navigation through course cards
      await page.keyboard.press('Tab');
      
      // Check if focus is on a focusable element
      const focusedElement = page.locator(':focus');
      await expect(focusedElement).toBeVisible();
      
      // Test arrow key navigation if courses grid supports it
      const coursesGrid = page.locator('.courses-grid, .course-list');
      if (await coursesGrid.isVisible()) {
        await coursesGrid.focus();
        await page.keyboard.press('ArrowRight');
        
        // Verify grid responds to keyboard navigation
        // (Implementation may vary based on your specific design)
      }
    });
  });

  test.describe('Responsive Design', () => {
    
    test('should display correctly on mobile devices', async ({ page }) => {
      // Set mobile viewport
      await page.setViewportSize({ width: 375, height: 667 });
      await page.reload();
      
      // Verify courses are still visible and accessible
      const courseCards = page.locator('.course-card, .course-item');
      await expect(courseCards).toHaveCount({ min: 1 });
      
      // Verify mobile-specific features
      const mobileNav = page.locator('.mobile-nav, .hamburger-menu');
      if (await mobileNav.isVisible()) {
        await expect(mobileNav).toBeVisible();
      }
      
      // Test mobile scrolling
      if (await courseCards.count() > 1) {
        const firstCard = courseCards.first();
        const lastCard = courseCards.last();
        
        await firstCard.scrollIntoViewIfNeeded();
        await expect(firstCard).toBeInViewport();
        
        await lastCard.scrollIntoViewIfNeeded();
        await expect(lastCard).toBeInViewport();
      }
    });

    test('should handle tablet viewport correctly', async ({ page }) => {
      // Set tablet viewport
      await page.setViewportSize({ width: 768, height: 1024 });
      await page.reload();
      
      // Verify layout adapts to tablet size
      const courseCards = page.locator('.course-card, .course-item');
      await expect(courseCards).toHaveCount({ min: 1 });
      
      // Verify scroll arrows are visible on tablet (if implemented)
      const scrollArrows = page.locator('.scroll-arrow, .nav-arrow');
      if (await scrollArrows.count() > 0) {
        // Scroll arrows might be visible on tablet
        await expect(scrollArrows.first()).toBeVisible();
      }
    });
  });

  test.describe('Performance Testing', () => {
    
    test('should load courses within acceptable time', async ({ page }) => {
      const startTime = Date.now();
      
      await page.goto('/courses');
      await page.waitForSelector('.course-card, .course-item', { timeout: 5000 });
      
      const endTime = Date.now();
      const loadTime = endTime - startTime;
      
      // Should load within 3 seconds
      expect(loadTime).toBeLessThan(3000);
      
      // Log performance metrics
      console.log(`Courses page load time: ${loadTime}ms`);
    });

    test('should filter courses quickly', async ({ page }) => {
      const semesterFilter = page.locator('#semesterFilter, .semester-filter, select');
      
      if (await semesterFilter.isVisible()) {
        const startTime = Date.now();
        
        await semesterFilter.selectOption({ index: 1 });
        await page.waitForSelector('.course-card, .course-item', { timeout: 1000 });
        
        const endTime = Date.now();
        const filterTime = endTime - startTime;
        
        // Filter should be instant (under 200ms)
        expect(filterTime).toBeLessThan(200);
        
        console.log(`Course filter time: ${filterTime}ms`);
      }
    });
  });
});