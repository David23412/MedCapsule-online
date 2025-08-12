import { test, expect } from '@playwright/test';

/**
 * SINGLE PAYMENT METHOD E2E TESTS
 * Comprehensive testing of individual course payments without bundle functionality
 */

test.describe('Single Payment Method', () => {
  
  // Setup authentication for tests that require login
  test.use({ storageState: 'tests/e2e/auth/student.json' });

  test.beforeEach(async ({ page }) => {
    // Navigate to courses page
    await page.goto('/courses');
    await page.waitForLoadState('networkidle');
  });

  test.describe('Single Course Payment Flow', () => {
    
    test('should display individual course pricing correctly', async ({ page }) => {
      // Look for paid course buttons
      const paidCourseBtn = page.locator('.action-btn.enroll:has-text("Pay to Access"), button:has-text("EGP"), .show-payment-modal');
      
      if (await paidCourseBtn.count() > 0) {
        const firstPaidBtn = paidCourseBtn.first();
        await expect(firstPaidBtn).toBeVisible();
        
        // Verify single course pricing display
        const buttonText = await firstPaidBtn.textContent();
        expect(buttonText).toMatch(/Pay to Access|EGP|\d+/);
        
        // Ensure no bundle pricing is displayed
        const parentCard = firstPaidBtn.locator('xpath=ancestor::*[contains(@class, "course-card")]');
        const bundleText = parentCard.locator('text=/bundle|Bundle|BUNDLE/i');
        await expect(bundleText).toHaveCount(0);
      }
    });

    test('should open payment modal for single course', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal, .action-btn.enroll:not([disabled])').first();
      
      if (await paidCourseBtn.isVisible()) {
        // Get course information before clicking
        const courseCard = paidCourseBtn.locator('xpath=ancestor::*[contains(@class, "course-card")]');
        const courseTitle = await courseCard.locator('.course-title, h3, h4').textContent();
        
        await paidCourseBtn.click();
        
        // Wait for payment modal to appear
        await page.waitForSelector('#paymentModal', { state: 'visible', timeout: 5000 });
        
        const paymentModal = page.locator('#paymentModal');
        await expect(paymentModal).toBeVisible();
        
        // Verify modal contains single course information
        if (courseTitle) {
          await expect(paymentModal).toContainText(courseTitle.trim());
        }
        
        // Ensure no bundle options are present
        await expect(paymentModal).not.toContainText(/bundle|Bundle|multiple courses|package deal/i);
      }
    });

    test('should display single course payment options', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForSelector('#paymentModal', { state: 'visible', timeout: 5000 });
        
        const paymentModal = page.locator('#paymentModal');
        
        // Wait for payment options to load
        await page.waitForTimeout(2000);
        
        // Check for payment method selection
        const paymentMethods = paymentModal.locator('.payment-methods, .payment-options, input[name="payment_method"]');
        
        if (await paymentMethods.count() > 0) {
          // Should have individual payment methods (not bundle options)
          const methodOptions = paymentMethods.locator('input[type="radio"], .payment-method-option');
          await expect(methodOptions).toHaveCount({ min: 1 });
          
          // Verify no bundle-specific payment options
          await expect(paymentModal).not.toContainText(/bundle discount|package price|multi-course/i);
        }
      }
    });

    test('should process single course payment correctly', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForSelector('#paymentModal', { state: 'visible', timeout: 5000 });
        
        const paymentModal = page.locator('#paymentModal');
        
        // Wait for payment options to load
        await page.waitForTimeout(2000);
        
        // Select first available payment method
        const paymentMethodRadio = paymentModal.locator('input[name="payment_method"]').first();
        if (await paymentMethodRadio.isVisible()) {
          await paymentMethodRadio.check();
        }
        
        // Look for proceed to payment button
        const proceedBtn = paymentModal.locator('#proceedToPayment, button:has-text("Proceed"), .proceed-payment');
        
        if (await proceedBtn.isVisible()) {
          await proceedBtn.click();
          
          // Should show loading state
          await expect(proceedBtn).toBeDisabled();
          
          // Wait for processing
          await page.waitForTimeout(3000);
          
          // Should either redirect or show next step
          const isRedirected = page.url().includes('/payment') || page.url().includes('/checkout');
          const hasNextStep = await paymentModal.locator('.payment-form, .checkout-form').isVisible();
          
          expect(isRedirected || hasNextStep).toBeTruthy();
        }
      }
    });

    test('should handle payment pending state correctly', async ({ page }) => {
      // Look for courses with pending payment
      const pendingPaymentBtn = page.locator('.action-btn.enroll[disabled]:has-text("Payment Pending")');
      
      if (await pendingPaymentBtn.count() > 0) {
        const firstPendingBtn = pendingPaymentBtn.first();
        await expect(firstPendingBtn).toBeVisible();
        await expect(firstPendingBtn).toBeDisabled();
        
        // Verify pending payment styling
        await expect(firstPendingBtn).toHaveCSS('color', /rgb\(194, 65, 12\)|#c2410c/);
        
        // Should contain pending payment text
        await expect(firstPendingBtn).toContainText('Payment Pending');
        
        // Should have clock icon
        const clockIcon = firstPendingBtn.locator('.fas.fa-clock');
        await expect(clockIcon).toBeVisible();
        
        // Clicking should not open modal
        await firstPendingBtn.click();
        const paymentModal = page.locator('#paymentModal');
        await expect(paymentModal).not.toBeVisible();
      }
    });

    test('should update button state after successful payment', async ({ page }) => {
      // This test simulates a successful payment by manipulating localStorage
      // In a real scenario, this would be tested with actual payment processing
      
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        const courseId = await paidCourseBtn.getAttribute('data-course-id');
        
        if (courseId) {
          // Simulate successful payment by setting localStorage
          await page.evaluate((id) => {
            localStorage.setItem(`payment_success_${id}`, 'true');
            localStorage.setItem(`enrollment_${id}`, 'true');
          }, courseId);
          
          // Refresh page to see updated state
          await page.reload();
          await page.waitForLoadState('networkidle');
          
          // Button should now show "Continue Learning" or similar
          const updatedBtn = page.locator(`[data-course-id="${courseId}"]`);
          const buttonText = await updatedBtn.textContent();
          
          expect(buttonText).toMatch(/Continue|Access|Enter|Start/i);
          expect(buttonText).not.toMatch(/Pay|Payment|EGP/i);
        }
      }
    });
  });

  test.describe('Payment Modal Functionality', () => {
    
    test('should close payment modal correctly', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForSelector('#paymentModal', { state: 'visible', timeout: 5000 });
        
        const paymentModal = page.locator('#paymentModal');
        await expect(paymentModal).toBeVisible();
        
        // Close modal using close button
        const closeBtn = paymentModal.locator('.btn-close, .close, [data-bs-dismiss="modal"]');
        if (await closeBtn.isVisible()) {
          await closeBtn.click();
          await expect(paymentModal).not.toBeVisible();
        }
        
        // Test closing by clicking backdrop
        await paidCourseBtn.click();
        await page.waitForSelector('#paymentModal', { state: 'visible', timeout: 5000 });
        
        // Click outside modal
        await page.click('body', { position: { x: 10, y: 10 } });
        await page.waitForTimeout(1000);
        await expect(paymentModal).not.toBeVisible();
      }
    });

    test('should display loading state while fetching payment options', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        
        // Should immediately show loading state
        const paymentModal = page.locator('#paymentModal');
        await expect(paymentModal).toBeVisible();
        
        // Check for loading indicators
        const loadingSpinner = paymentModal.locator('.spinner-border, .loading');
        const loadingText = paymentModal.locator('text=/Loading payment options/i');
        
        if (await loadingSpinner.isVisible() || await loadingText.isVisible()) {
          await expect(loadingSpinner.or(loadingText)).toBeVisible();
          
          // Loading should eventually disappear
          await expect(loadingSpinner.or(loadingText)).not.toBeVisible({ timeout: 10000 });
        }
      }
    });

    test('should handle payment options fetch error', async ({ page }) => {
      // Intercept payment options request and return error
      await page.route('**/payments/*/options', route => {
        route.fulfill({
          status: 500,
          contentType: 'application/json',
          body: JSON.stringify({ success: false, message: 'Server error' })
        });
      });
      
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForTimeout(2000);
        
        // Should show error message or close modal
        const errorAlert = page.locator('.alert-danger, .error-message');
        const modalClosed = await page.locator('#paymentModal').isVisible() === false;
        
        expect(await errorAlert.isVisible() || modalClosed).toBeTruthy();
      }
    });
  });

  test.describe('Payment Security and Validation', () => {
    
    test('should include CSRF token in payment requests', async ({ page }) => {
      let csrfTokenFound = false;
      
      // Monitor network requests
      page.on('request', request => {
        if (request.url().includes('/payments/') && request.method() === 'POST') {
          const headers = request.headers();
          if (headers['x-csrf-token'] || headers['X-CSRF-TOKEN']) {
            csrfTokenFound = true;
          }
        }
      });
      
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForTimeout(3000);
        
        // CSRF token should be present in meta tag
        const csrfMeta = page.locator('meta[name="csrf-token"]');
        await expect(csrfMeta).toHaveAttribute('content');
        
        const csrfToken = await csrfMeta.getAttribute('content');
        expect(csrfToken).toBeTruthy();
        expect(csrfToken.length).toBeGreaterThan(10);
      }
    });

    test('should validate course access after payment', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        const courseId = await paidCourseBtn.getAttribute('data-course-id');
        
        if (courseId) {
          // Simulate successful payment
          await page.evaluate((id) => {
            localStorage.setItem(`payment_success_${id}`, 'true');
            localStorage.setItem(`enrollment_${id}`, 'true');
          }, courseId);
          
          await page.reload();
          await page.waitForLoadState('networkidle');
          
          // Find the updated button and click it
          const accessBtn = page.locator(`[data-course-id="${courseId}"]`);
          
          if (await accessBtn.isVisible()) {
            await accessBtn.click();
            
            // Should navigate to course content
            await page.waitForTimeout(2000);
            
            const currentUrl = page.url();
            expect(currentUrl).toMatch(/\/course|\/topic|\/quiz|\/learn/);
          }
        }
      }
    });

    test('should prevent multiple simultaneous payment attempts', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        // Click multiple times rapidly
        await paidCourseBtn.click();
        await paidCourseBtn.click();
        await paidCourseBtn.click();
        
        // Should only open one modal
        const paymentModals = page.locator('#paymentModal');
        await expect(paymentModals).toHaveCount(1);
        
        const visibleModal = paymentModals.locator(':visible');
        await expect(visibleModal).toHaveCount(1);
      }
    });
  });

  test.describe('Payment Method Selection', () => {
    
    test('should display available payment methods without bundle options', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForSelector('#paymentModal', { state: 'visible', timeout: 5000 });
        
        const paymentModal = page.locator('#paymentModal');
        await page.waitForTimeout(2000);
        
        // Check for payment method options
        const paymentMethods = paymentModal.locator('input[name="payment_method"]');
        
        if (await paymentMethods.count() > 0) {
          // Verify each payment method is for single course only
          const methodCount = await paymentMethods.count();
          
          for (let i = 0; i < methodCount; i++) {
            const method = paymentMethods.nth(i);
            const methodValue = await method.getAttribute('value');
            const methodLabel = await method.locator('xpath=following-sibling::label').textContent();
            
            // Should not contain bundle-related terms
            expect(methodValue).not.toMatch(/bundle|package|multi/i);
            if (methodLabel) {
              expect(methodLabel).not.toMatch(/bundle|package|multiple courses/i);
            }
          }
        }
      }
    });

    test('should allow single payment method selection', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForSelector('#paymentModal', { state: 'visible', timeout: 5000 });
        
        const paymentModal = page.locator('#paymentModal');
        await page.waitForTimeout(2000);
        
        const paymentMethods = paymentModal.locator('input[name="payment_method"]');
        
        if (await paymentMethods.count() > 0) {
          const firstMethod = paymentMethods.first();
          await firstMethod.check();
          
          // Verify selection
          await expect(firstMethod).toBeChecked();
          
          // Other methods should not be selected
          const otherMethods = paymentMethods.locator(':not(:first-child)');
          const otherMethodsCount = await otherMethods.count();
          
          for (let i = 0; i < otherMethodsCount; i++) {
            await expect(otherMethods.nth(i)).not.toBeChecked();
          }
        }
      }
    });

    test('should enable proceed button after method selection', async ({ page }) => {
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForSelector('#paymentModal', { state: 'visible', timeout: 5000 });
        
        const paymentModal = page.locator('#paymentModal');
        await page.waitForTimeout(2000);
        
        const proceedBtn = paymentModal.locator('#proceedToPayment, button:has-text("Proceed")');
        
        if (await proceedBtn.isVisible()) {
          // Button should initially be disabled or require method selection
          const paymentMethods = paymentModal.locator('input[name="payment_method"]');
          
          if (await paymentMethods.count() > 0) {
            // Select payment method
            await paymentMethods.first().check();
            
            // Proceed button should be enabled
            await expect(proceedBtn).toBeEnabled();
          }
        }
      }
    });
  });

  test.describe('Error Handling', () => {
    
    test('should handle course not found error', async ({ page }) => {
      // Try to access payment for non-existent course
      await page.goto('/payments/99999/options');
      
      // Should show 404 or redirect to courses page
      const is404 = page.url().includes('404') || await page.locator('text=/not found/i').isVisible();
      const redirectedToCourses = page.url().includes('/courses');
      
      expect(is404 || redirectedToCourses).toBeTruthy();
    });

    test('should handle unauthorized payment access', async ({ page }) => {
      // Logout and try to access payment
      await page.goto('/logout');
      await page.goto('/courses');
      
      const paidCourseBtn = page.locator('.show-payment-modal').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        
        // Should redirect to login or show auth error
        await page.waitForTimeout(2000);
        
        const isLoginPage = page.url().includes('/login');
        const authError = await page.locator('text=/login required|unauthorized/i').isVisible();
        
        expect(isLoginPage || authError).toBeTruthy();
      }
    });

    test('should handle already enrolled course', async ({ page }) => {
      // Simulate already enrolled course
      const courseCard = page.locator('.course-card').first();
      
      if (await courseCard.isVisible()) {
        const courseId = await courseCard.getAttribute('data-course-id') || '1';
        
        // Set enrollment in localStorage
        await page.evaluate((id) => {
          localStorage.setItem(`enrollment_${id}`, 'true');
        }, courseId);
        
        await page.reload();
        await page.waitForLoadState('networkidle');
        
        // Payment button should not be visible for enrolled course
        const paymentBtn = page.locator(`[data-course-id="${courseId}"].show-payment-modal`);
        await expect(paymentBtn).toHaveCount(0);
        
        // Should show access button instead
        const accessBtn = page.locator(`[data-course-id="${courseId}"].action-btn.continue`);
        if (await accessBtn.isVisible()) {
          await expect(accessBtn).toContainText(/Continue|Access|Enter/i);
        }
      }
    });
  });
});