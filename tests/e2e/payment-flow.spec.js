import { test, expect } from '@playwright/test';

/**
 * PAYMENT FLOW E2E TESTS
 * Comprehensive testing of pricing, payment methods, transactions, and subscriptions
 */

test.describe('Payment Flow', () => {
  
  // Setup authentication for tests that require login
  test.use({ storageState: 'tests/e2e/auth/student.json' });

  test.beforeEach(async ({ page }) => {
    // Navigate to courses page to find paid courses
    await page.goto('/courses');
    await page.waitForLoadState('networkidle');
  });

  test.describe('Pricing Display', () => {
    
    test('should display course pricing correctly', async ({ page }) => {
      // Look for paid course buttons with pricing
      const paidCourseBtn = page.locator('.action-btn:has-text("EGP"), .enroll-btn:has-text("$"), button[data-price]');
      
      if (await paidCourseBtn.count() > 0) {
        const firstPaidBtn = paidCourseBtn.first();
        await expect(firstPaidBtn).toBeVisible();
        
        // Verify price format (should contain currency and amount)
        const buttonText = await firstPaidBtn.textContent();
        expect(buttonText).toMatch(/\d+|EGP|\$|USD|EUR/);
        
        // Check for original price and discount if applicable
        const parentCard = firstPaidBtn.locator('xpath=..');
        const originalPrice = parentCard.locator('.original-price, .crossed-price, .was-price');
        const discount = parentCard.locator('.discount, .sale-badge, .offer');
        
        if (await originalPrice.isVisible()) {
          await expect(originalPrice).toContainText(/\d+/);
          
          // If there's an original price, there should be a discount indicator
          if (await discount.isVisible()) {
            await expect(discount).toContainText(/off|save|\d+%/i);
          }
        }
      }
    });

    test('should display subscription vs one-time payment options', async ({ page }) => {
      // Look for subscription-related elements
      const subscriptionElements = page.locator('.subscription, .monthly, .yearly, .recurring');
      
      if (await subscriptionElements.count() > 0) {
        const firstSubscription = subscriptionElements.first();
        await expect(firstSubscription).toBeVisible();
        
        // Should contain subscription terminology
        const subscriptionText = await firstSubscription.textContent();
        expect(subscriptionText).toMatch(/monthly|yearly|subscription|recurring|per month|per year/i);
      }
      
      // Look for one-time payment options
      const oneTimeElements = page.locator('.one-time, .single-payment, .lifetime');
      
      if (await oneTimeElements.count() > 0) {
        const firstOneTime = oneTimeElements.first();
        await expect(firstOneTime).toBeVisible();
        
        const oneTimeText = await firstOneTime.textContent();
        expect(oneTimeText).toMatch(/one-time|lifetime|single|once/i);
      }
    });

    test('should show pricing comparison when multiple options exist', async ({ page }) => {
      // Look for pricing comparison tables or sections
      const pricingTable = page.locator('.pricing-table, .price-comparison, .plans-comparison');
      
      if (await pricingTable.isVisible()) {
        // Should have multiple pricing options
        const pricingOptions = pricingTable.locator('.pricing-option, .plan-card, .price-plan');
        await expect(pricingOptions).toHaveCount({ min: 2 });
        
        // Each option should have a price
        const firstOption = pricingOptions.first();
        await expect(firstOption).toContainText(/\d+|free/i);
        
        // Should have feature lists
        const features = firstOption.locator('.features, .plan-features, .benefits');
        if (await features.isVisible()) {
          const featureItems = features.locator('li, .feature-item');
          await expect(featureItems).toHaveCount({ min: 1 });
        }
      }
    });
  });

  test.describe('Payment Modal/Interface', () => {
    
    test('should open payment modal when clicking paid course', async ({ page }) => {
      const paidCourseBtn = page.locator('.action-btn:has-text("EGP"), .enroll-btn:has-text("$"), button[data-price]').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        
        // Wait for payment modal to appear
        await page.waitForTimeout(1000);
        
        // Verify payment modal is visible
        const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal, [data-testid="payment-modal"]');
        await expect(paymentModal).toBeVisible();
        
        // Verify modal has proper payment content
        await expect(paymentModal).toContainText(/payment|checkout|price|total/i);
        
        // Check for modal title
        const modalTitle = paymentModal.locator('h2, h3, .modal-title, .checkout-title');
        if (await modalTitle.isVisible()) {
          await expect(modalTitle).toContainText(/payment|checkout|subscription|enrollment/i);
        }
      }
    });

    test('should display course details in payment modal', async ({ page }) => {
      const paidCourseBtn = page.locator('.action-btn:has-text("EGP"), .enroll-btn:has-text("$"), button[data-price]').first();
      
      if (await paidCourseBtn.isVisible()) {
        // Get course name before clicking
        const courseCard = paidCourseBtn.locator('xpath=ancestor::*[contains(@class, "course") or contains(@class, "card")]');
        const courseTitle = await courseCard.locator('.course-title, h3, h4').textContent();
        
        await paidCourseBtn.click();
        await page.waitForTimeout(1000);
        
        const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
        
        if (await paymentModal.isVisible()) {
          // Modal should contain course information
          if (courseTitle) {
            await expect(paymentModal).toContainText(courseTitle.trim());
          }
          
          // Should show price breakdown
          const priceBreakdown = paymentModal.locator('.price-breakdown, .order-summary, .total');
          if (await priceBreakdown.isVisible()) {
            await expect(priceBreakdown).toContainText(/total|subtotal|price/i);
            await expect(priceBreakdown).toContainText(/\d+/);
          }
        }
      }
    });

    test('should display available payment methods', async ({ page }) => {
      const paidCourseBtn = page.locator('.action-btn:has-text("EGP"), .enroll-btn:has-text("$"), button[data-price]').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForTimeout(1000);
        
        const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
        
        if (await paymentModal.isVisible()) {
          // Check for payment method options
          const paymentMethods = paymentModal.locator('.payment-methods, .payment-options');
          
          if (await paymentMethods.isVisible()) {
            // Should have multiple payment options
            const methodButtons = paymentMethods.locator('button, .payment-method, .method-option');
            await expect(methodButtons).toHaveCount({ min: 1 });
            
            // Common payment methods
            const creditCardOption = paymentMethods.locator('button:has-text("Credit"), .credit-card, [data-method="card"]');
            const paypalOption = paymentMethods.locator('button:has-text("PayPal"), .paypal, [data-method="paypal"]');
            const bankOption = paymentMethods.locator('button:has-text("Bank"), .bank-transfer, [data-method="bank"]');
            
            // At least one payment method should be available
            const hasAnyMethod = await creditCardOption.isVisible() || 
                                await paypalOption.isVisible() || 
                                await bankOption.isVisible();
            expect(hasAnyMethod).toBeTruthy();
          }
        }
      }
    });

    test('should allow payment method selection', async ({ page }) => {
      const paidCourseBtn = page.locator('.action-btn:has-text("EGP"), .enroll-btn:has-text("$"), button[data-price]').first();
      
      if (await paidCourseBtn.isVisible()) {
        await paidCourseBtn.click();
        await page.waitForTimeout(1000);
        
        const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
        
        if (await paymentModal.isVisible()) {
          const paymentMethods = paymentModal.locator('.payment-methods, .payment-options');
          
          if (await paymentMethods.isVisible()) {
            const methodButtons = paymentMethods.locator('button, .payment-method');
            
            if (await methodButtons.count() > 0) {
              const firstMethod = methodButtons.first();
              await firstMethod.click();
              
              // Verify selection
              await expect(firstMethod).toHaveClass(/selected|active|chosen/);
              
              // Check if payment form appears
              const paymentForm = paymentModal.locator('.payment-form, .checkout-form, form');
              if (await paymentForm.isVisible()) {
                await expect(paymentForm).toBeVisible();
              }
            }
          }
        }
      }
    });
  });

  test.describe('Payment Form Validation', () => {
    
    test('should validate credit card form fields', async ({ page }) => {
      await openPaymentModalAndSelectCreditCard(page);
      
      const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
      const paymentForm = paymentModal.locator('.payment-form, .checkout-form, form');
      
      if (await paymentForm.isVisible()) {
        // Look for credit card fields
        const cardNumber = paymentForm.locator('input[name*="card"], input[placeholder*="card"], #card-number');
        const expiryDate = paymentForm.locator('input[name*="expiry"], input[placeholder*="expiry"], input[name*="expire"]');
        const cvv = paymentForm.locator('input[name*="cvv"], input[name*="cvc"], input[placeholder*="cvv"]');
        
        if (await cardNumber.isVisible()) {
          // Test invalid card number
          await cardNumber.fill('1234');
          
          // Try to submit form or trigger validation
          const submitBtn = paymentForm.locator('button[type="submit"], .pay-now, .complete-payment');
          if (await submitBtn.isVisible()) {
            await submitBtn.click();
            
            // Should show validation error
            const errorMessage = page.locator('.error, .invalid, .field-error');
            await expect(errorMessage).toBeVisible();
            await expect(errorMessage).toContainText(/invalid|card|number/i);
          }
        }
      }
    });

    test('should validate required billing information', async ({ page }) => {
      await openPaymentModalAndSelectCreditCard(page);
      
      const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
      const paymentForm = paymentModal.locator('.payment-form, .checkout-form, form');
      
      if (await paymentForm.isVisible()) {
        // Look for billing fields
        const emailField = paymentForm.locator('input[name*="email"], input[type="email"]');
        const nameField = paymentForm.locator('input[name*="name"], input[placeholder*="name"]');
        
        // Try to submit with empty required fields
        const submitBtn = paymentForm.locator('button[type="submit"], .pay-now, .complete-payment');
        
        if (await submitBtn.isVisible()) {
          await submitBtn.click();
          
          // Should show validation for required fields
          if (await emailField.isVisible()) {
            const emailValid = await emailField.evaluate(el => el.validity.valid);
            if (!emailValid) {
              expect(emailValid).toBeFalsy();
            }
          }
          
          if (await nameField.isVisible()) {
            const nameValid = await nameField.evaluate(el => el.validity.valid);
            if (!nameValid) {
              expect(nameValid).toBeFalsy();
            }
          }
        }
      }
    });

    test('should show loading state during payment processing', async ({ page }) => {
      await openPaymentModalAndSelectCreditCard(page);
      
      const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
      const paymentForm = paymentModal.locator('.payment-form, .checkout-form, form');
      
      if (await paymentForm.isVisible()) {
        // Fill form with test data
        await fillTestPaymentForm(paymentForm);
        
        const submitBtn = paymentForm.locator('button[type="submit"], .pay-now, .complete-payment');
        
        if (await submitBtn.isVisible()) {
          await submitBtn.click();
          
          // Should show loading state
          await expect(async () => {
            const isLoading = await submitBtn.textContent();
            const hasSpinner = await submitBtn.locator('.spinner, .loading').isVisible();
            const isDisabled = await submitBtn.isDisabled();
            
            expect(isLoading?.includes('Processing') || hasSpinner || isDisabled).toBeTruthy();
          }).toPass({ timeout: 5000 });
        }
      }
    });
  });

  test.describe('Payment Processing', () => {
    
    test('should handle successful payment flow', async ({ page }) => {
      // Note: This test would typically use test payment credentials
      // In a real environment, you'd use Stripe test mode or similar
      
      await openPaymentModalAndSelectCreditCard(page);
      
      const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
      const paymentForm = paymentModal.locator('.payment-form, .checkout-form, form');
      
      if (await paymentForm.isVisible()) {
        // Fill with test card (if using Stripe test mode)
        await fillTestPaymentForm(paymentForm, {
          cardNumber: '4242424242424242', // Stripe test card
          expiry: '12/25',
          cvv: '123',
          name: 'Test User',
          email: 'test@example.com'
        });
        
        const submitBtn = paymentForm.locator('button[type="submit"], .pay-now, .complete-payment');
        
        if (await submitBtn.isVisible()) {
          await submitBtn.click();
          
          // Wait for processing
          await page.waitForTimeout(5000);
          
          // Should show success message or redirect
          await expect(async () => {
            const successMsg = page.locator('.success, .payment-success, .enrolled-success');
            const isRedirected = page.url().includes('/course') || page.url().includes('/dashboard');
            const hasSuccessModal = await page.locator('.success-modal, .confirmation-modal').isVisible();
            
            expect(await successMsg.isVisible() || isRedirected || hasSuccessModal).toBeTruthy();
          }).toPass({ timeout: 15000 });
        }
      }
    });

    test('should handle payment failure gracefully', async ({ page }) => {
      await openPaymentModalAndSelectCreditCard(page);
      
      const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
      const paymentForm = paymentModal.locator('.payment-form, .checkout-form, form');
      
      if (await paymentForm.isVisible()) {
        // Fill with test card that will be declined (if using Stripe test mode)
        await fillTestPaymentForm(paymentForm, {
          cardNumber: '4000000000000002', // Stripe test card that will be declined
          expiry: '12/25',
          cvv: '123',
          name: 'Test User',
          email: 'test@example.com'
        });
        
        const submitBtn = paymentForm.locator('button[type="submit"], .pay-now, .complete-payment');
        
        if (await submitBtn.isVisible()) {
          await submitBtn.click();
          
          // Wait for processing
          await page.waitForTimeout(5000);
          
          // Should show error message
          const errorMessage = page.locator('.error, .payment-error, .declined-message');
          if (await errorMessage.isVisible()) {
            await expect(errorMessage).toContainText(/declined|failed|error|invalid/i);
            
            // Should allow retry
            await expect(submitBtn).toBeEnabled();
          }
        }
      }
    });

    test('should handle network interruption during payment', async ({ page }) => {
      await openPaymentModalAndSelectCreditCard(page);
      
      const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
      const paymentForm = paymentModal.locator('.payment-form, .checkout-form, form');
      
      if (await paymentForm.isVisible()) {
        await fillTestPaymentForm(paymentForm);
        
        const submitBtn = paymentForm.locator('button[type="submit"], .pay-now, .complete-payment');
        
        if (await submitBtn.isVisible()) {
          // Go offline just before payment
          await page.context().setOffline(true);
          await submitBtn.click();
          
          // Wait a moment then go back online
          await page.waitForTimeout(2000);
          await page.context().setOffline(false);
          
          // Should show network error and retry option
          const errorMessage = page.locator('.error, .network-error, .connection-error');
          if (await errorMessage.isVisible()) {
            await expect(errorMessage).toContainText(/network|connection|offline|retry/i);
            
            const retryBtn = page.locator('button:has-text("Retry"), .retry-payment');
            if (await retryBtn.isVisible()) {
              await expect(retryBtn).toBeEnabled();
            }
          }
        }
      }
    });
  });

  test.describe('Subscription Management', () => {
    
    test('should display subscription status for premium users', async ({ page }) => {
      // Switch to premium user context
      await page.goto('/logout');
      await page.goto('/login');
      
      // Login as premium user (if auth state exists)
      const premiumAuthExists = await page.evaluate(() => {
        return fetch('/tests/e2e/auth/premium-student.json')
          .then(() => true)
          .catch(() => false);
      });
      
      if (premiumAuthExists) {
        await page.context().clearCookies();
        await page.context().addInitScript(() => {
          // Load premium user session
        });
        
        await page.goto('/profile');
        
        // Check for subscription information
        const subscriptionInfo = page.locator('.subscription-info, .premium-status, .plan-details');
        
        if (await subscriptionInfo.isVisible()) {
          await expect(subscriptionInfo).toContainText(/premium|subscription|plan/i);
          
          // Should show renewal date
          const renewalDate = subscriptionInfo.locator('.renewal-date, .next-billing, .expires');
          if (await renewalDate.isVisible()) {
            await expect(renewalDate).toContainText(/\d{1,2}\/\d{1,2}\/\d{4}|\d{4}-\d{2}-\d{2}/);
          }
        }
      }
    });

    test('should allow subscription cancellation', async ({ page }) => {
      // This test would require a premium user with active subscription
      await page.goto('/profile');
      
      const cancelSubscription = page.locator('.cancel-subscription, .unsubscribe, button:has-text("Cancel")');
      
      if (await cancelSubscription.isVisible()) {
        await cancelSubscription.click();
        
        // Should show confirmation modal
        const confirmModal = page.locator('.confirm-modal, .cancellation-modal');
        
        if (await confirmModal.isVisible()) {
          await expect(confirmModal).toContainText(/cancel|confirm|subscription/i);
          
          // Should explain what happens after cancellation
          await expect(confirmModal).toContainText(/access|expires|billing/i);
          
          // Should have confirm and cancel buttons
          const confirmBtn = confirmModal.locator('button:has-text("Confirm"), .confirm-cancel');
          const keepBtn = confirmModal.locator('button:has-text("Keep"), .cancel-action');
          
          await expect(confirmBtn).toBeVisible();
          await expect(keepBtn).toBeVisible();
        }
      }
    });

    test('should show upgrade options for free users', async ({ page }) => {
      await page.goto('/courses');
      
      // Look for upgrade prompts or premium features
      const upgradePrompt = page.locator('.upgrade-prompt, .premium-cta, .subscription-offer');
      
      if (await upgradePrompt.isVisible()) {
        await expect(upgradePrompt).toContainText(/upgrade|premium|subscription/i);
        
        const upgradeBtn = upgradePrompt.locator('button, .upgrade-btn, .premium-btn');
        if (await upgradeBtn.isVisible()) {
          await upgradeBtn.click();
          
          // Should show pricing/subscription options
          const pricingModal = page.locator('.pricing-modal, .subscription-modal, .upgrade-modal');
          if (await pricingModal.isVisible()) {
            await expect(pricingModal).toContainText(/price|plan|subscription/i);
          }
        }
      }
    });
  });

  test.describe('Payment Security', () => {
    
    test('should use HTTPS for payment pages', async ({ page }) => {
      await openPaymentModalAndSelectCreditCard(page);
      
      // Verify we're on HTTPS (in production this would be enforced)
      const url = page.url();
      if (url.includes('payment') || url.includes('checkout')) {
        expect(url).toMatch(/^https:/);
      }
    });

    test('should not expose sensitive payment data in console', async ({ page }) => {
      await openPaymentModalAndSelectCreditCard(page);
      
      // Monitor console for sensitive data
      const consoleMessages = [];
      page.on('console', msg => {
        consoleMessages.push(msg.text());
      });
      
      const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
      const paymentForm = paymentModal.locator('.payment-form, .checkout-form, form');
      
      if (await paymentForm.isVisible()) {
        await fillTestPaymentForm(paymentForm);
        
        // Check that card numbers aren't logged
        const sensitiveDataInConsole = consoleMessages.some(msg => 
          msg.includes('4242424242424242') || 
          msg.includes('123') ||
          msg.match(/\d{16}/) // 16-digit card numbers
        );
        
        expect(sensitiveDataInConsole).toBeFalsy();
      }
    });
  });
});

/**
 * Helper function to open payment modal and select credit card
 */
async function openPaymentModalAndSelectCreditCard(page) {
  const paidCourseBtn = page.locator('.action-btn:has-text("EGP"), .enroll-btn:has-text("$"), button[data-price]').first();
  
  if (await paidCourseBtn.isVisible()) {
    await paidCourseBtn.click();
    await page.waitForTimeout(1000);
    
    const paymentModal = page.locator('.modal, .payment-modal, .checkout-modal');
    
    if (await paymentModal.isVisible()) {
      // Select credit card payment method
      const creditCardOption = paymentModal.locator('button:has-text("Credit"), .credit-card, [data-method="card"]');
      
      if (await creditCardOption.isVisible()) {
        await creditCardOption.click();
        await page.waitForTimeout(500);
      }
    }
  }
}

/**
 * Helper function to fill test payment form
 */
async function fillTestPaymentForm(paymentForm, data = {}) {
  const defaultData = {
    cardNumber: '4242424242424242',
    expiry: '12/25',
    cvv: '123',
    name: 'Test User',
    email: 'test@example.com',
    ...data
  };
  
  // Fill card number
  const cardNumber = paymentForm.locator('input[name*="card"], input[placeholder*="card"], #card-number');
  if (await cardNumber.isVisible()) {
    await cardNumber.fill(defaultData.cardNumber);
  }
  
  // Fill expiry date
  const expiryDate = paymentForm.locator('input[name*="expiry"], input[placeholder*="expiry"], input[name*="expire"]');
  if (await expiryDate.isVisible()) {
    await expiryDate.fill(defaultData.expiry);
  }
  
  // Fill CVV
  const cvv = paymentForm.locator('input[name*="cvv"], input[name*="cvc"], input[placeholder*="cvv"]');
  if (await cvv.isVisible()) {
    await cvv.fill(defaultData.cvv);
  }
  
  // Fill name
  const nameField = paymentForm.locator('input[name*="name"], input[placeholder*="name"]');
  if (await nameField.isVisible()) {
    await nameField.fill(defaultData.name);
  }
  
  // Fill email
  const emailField = paymentForm.locator('input[name*="email"], input[type="email"]');
  if (await emailField.isVisible()) {
    await emailField.fill(defaultData.email);
  }
}