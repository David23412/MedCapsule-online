import { test, expect } from '@playwright/test';

/**
 * USER AUTHENTICATION E2E TESTS
 * Comprehensive testing of login, logout, registration, and session management
 */

test.describe('User Authentication', () => {
  
  test.beforeEach(async ({ page }) => {
    // Navigate to home page before each test
    await page.goto('/');
  });

  test.describe('User Login', () => {
    
    test('should login successfully with valid credentials', async ({ page }) => {
      // Navigate to login page
      await page.click('[data-testid="login-btn"], a[href*="login"]');
      await expect(page).toHaveURL(/.*login/);
      
      // Verify login form elements
      await expect(page.locator('input[name="email"]')).toBeVisible();
      await expect(page.locator('input[name="password"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toBeVisible();
      
      // Fill login form
      await page.fill('input[name="email"]', 'student@test.com');
      await page.fill('input[name="password"]', 'password');
      
      // Submit login form
      await page.click('button[type="submit"]');
      
      // Verify successful login
      await expect(page).toHaveURL(/\/dashboard|\/courses/);
      await expect(page.locator('[data-testid="user-menu"], .logout-btn, .profile-menu')).toBeVisible();
      
      // Verify user is authenticated
      const userIndicator = page.locator('[data-testid="user-name"], .user-email, .profile-circle');
      await expect(userIndicator).toBeVisible();
    });

    test('should show error with invalid credentials', async ({ page }) => {
      await page.click('[data-testid="login-btn"], a[href*="login"]');
      
      // Fill with invalid credentials
      await page.fill('input[name="email"]', 'invalid@test.com');
      await page.fill('input[name="password"]', 'wrongpassword');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Verify error message appears
      const errorMessage = page.locator('.alert-danger, .error-message, [data-testid="error"]');
      await expect(errorMessage).toBeVisible();
      await expect(errorMessage).toContainText(/invalid|incorrect|credentials/i);
      
      // Verify still on login page
      await expect(page).toHaveURL(/.*login/);
    });

    test('should validate required fields', async ({ page }) => {
      await page.click('[data-testid="login-btn"], a[href*="login"]');
      
      // Try to submit empty form
      await page.click('button[type="submit"]');
      
      // Check for HTML5 validation or custom validation messages
      const emailField = page.locator('input[name="email"]');
      const passwordField = page.locator('input[name="password"]');
      
      // Verify validation occurs
      await expect(async () => {
        const emailValid = await emailField.evaluate(el => el.validity.valid);
        const passwordValid = await passwordField.evaluate(el => el.validity.valid);
        expect(emailValid || passwordValid).toBeFalsy();
      }).toPass();
    });

    test('should handle remember me functionality', async ({ page, context }) => {
      await page.click('[data-testid="login-btn"], a[href*="login"]');
      
      // Fill login form
      await page.fill('input[name="email"]', 'student@test.com');
      await page.fill('input[name="password"]', 'password');
      
      // Check remember me if available
      const rememberCheckbox = page.locator('input[name="remember"], input[type="checkbox"]');
      if (await rememberCheckbox.isVisible()) {
        await rememberCheckbox.check();
      }
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Verify login success
      await expect(page).toHaveURL(/\/dashboard|\/courses/);
      
      // Check if remember token cookie is set
      const cookies = await context.cookies();
      const rememberCookie = cookies.find(cookie => 
        cookie.name.includes('remember') || cookie.name.includes('session')
      );
      
      if (rememberCookie) {
        expect(rememberCookie.value).toBeTruthy();
      }
    });
  });

  test.describe('User Registration', () => {
    
    test('should register new user successfully', async ({ page }) => {
      // Navigate to registration page
      await page.click('[data-testid="register-btn"], a[href*="register"]');
      await expect(page).toHaveURL(/.*register/);
      
      // Generate unique email for test
      const timestamp = Date.now();
      const testEmail = `newuser${timestamp}@test.com`;
      
      // Fill registration form
      await page.fill('input[name="name"]', 'Test User');
      await page.fill('input[name="email"]', testEmail);
      await page.fill('input[name="password"]', 'password123');
      await page.fill('input[name="password_confirmation"]', 'password123');
      
      // Submit registration
      await page.click('button[type="submit"]');
      
      // Verify successful registration
      await expect(page).toHaveURL(/\/dashboard|\/courses|\/email\/verify/);
      
      // Check for success indicator or verification notice
      const successIndicator = page.locator(
        '.alert-success, .success-message, [data-testid="success"], [data-testid="verify-email"]'
      );
      
      await expect(async () => {
        const isVisible = await successIndicator.isVisible();
        const hasRedirect = page.url().includes('dashboard') || page.url().includes('courses');
        expect(isVisible || hasRedirect).toBeTruthy();
      }).toPass();
    });

    test('should validate password confirmation', async ({ page }) => {
      await page.click('[data-testid="register-btn"], a[href*="register"]');
      
      // Fill form with mismatched passwords
      await page.fill('input[name="name"]', 'Test User');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.fill('input[name="password_confirmation"]', 'differentpassword');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Verify validation error
      const errorMessage = page.locator('.alert-danger, .error-message, [data-testid="error"]');
      await expect(errorMessage).toBeVisible();
      await expect(errorMessage).toContainText(/password.*match|confirm/i);
    });

    test('should prevent duplicate email registration', async ({ page }) => {
      await page.click('[data-testid="register-btn"], a[href*="register"]');
      
      // Fill form with existing email
      await page.fill('input[name="name"]', 'Test User');
      await page.fill('input[name="email"]', 'student@test.com'); // Existing email
      await page.fill('input[name="password"]', 'password123');
      await page.fill('input[name="password_confirmation"]', 'password123');
      
      // Submit form
      await page.click('button[type="submit"]');
      
      // Verify error message
      const errorMessage = page.locator('.alert-danger, .error-message, [data-testid="error"]');
      await expect(errorMessage).toBeVisible();
      await expect(errorMessage).toContainText(/email.*taken|already.*exists/i);
    });
  });

  test.describe('User Logout', () => {
    
    test('should logout successfully', async ({ page }) => {
      // Login first
      await page.goto('/login');
      await page.fill('input[name="email"]', 'student@test.com');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]');
      
      // Wait for login to complete
      await expect(page).toHaveURL(/\/dashboard|\/courses/);
      
      // Find and click logout button
      const logoutBtn = page.locator('[data-testid="logout-btn"], .logout-btn, button:has-text("Logout")');
      await expect(logoutBtn).toBeVisible();
      await logoutBtn.click();
      
      // Verify logout success
      await expect(page).toHaveURL(/\/login|\/$/);
      
      // Verify login button is visible again
      const loginBtn = page.locator('[data-testid="login-btn"], a[href*="login"]');
      await expect(loginBtn).toBeVisible();
      
      // Verify user menu is no longer visible
      const userMenu = page.locator('[data-testid="user-menu"], .user-menu');
      await expect(userMenu).not.toBeVisible();
    });

    test('should redirect to login when accessing protected routes after logout', async ({ page }) => {
      // Login first
      await page.goto('/login');
      await page.fill('input[name="email"]', 'student@test.com');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/dashboard|\/courses/);
      
      // Logout
      const logoutBtn = page.locator('[data-testid="logout-btn"], .logout-btn, button:has-text("Logout")');
      await logoutBtn.click();
      
      // Try to access protected route
      await page.goto('/dashboard');
      
      // Should redirect to login
      await expect(page).toHaveURL(/\/login/);
    });
  });

  test.describe('Session Management', () => {
    
    test('should maintain session across page reloads', async ({ page }) => {
      // Login
      await page.goto('/login');
      await page.fill('input[name="email"]', 'student@test.com');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/dashboard|\/courses/);
      
      // Reload page
      await page.reload();
      
      // Verify still authenticated
      const userIndicator = page.locator('[data-testid="user-menu"], .logout-btn, .profile-menu');
      await expect(userIndicator).toBeVisible();
      
      // Verify not redirected to login
      await expect(page).not.toHaveURL(/\/login/);
    });

    test('should handle session timeout gracefully', async ({ page }) => {
      // This test would require session timeout configuration
      // For now, we'll test the basic session validation
      
      // Login
      await page.goto('/login');
      await page.fill('input[name="email"]', 'student@test.com');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]');
      
      await expect(page).toHaveURL(/\/dashboard|\/courses/);
      
      // Simulate session expiry by clearing session storage
      await page.evaluate(() => {
        sessionStorage.clear();
        localStorage.removeItem('auth_token');
      });
      
      // Try to perform an authenticated action
      await page.reload();
      
      // May redirect to login or show re-authentication prompt
      await expect(async () => {
        const currentUrl = page.url();
        const hasLoginRedirect = currentUrl.includes('login');
        const hasAuthPrompt = await page.locator('.auth-prompt, .session-expired').isVisible();
        expect(hasLoginRedirect || hasAuthPrompt).toBeTruthy();
      }).toPass({ timeout: 10000 });
    });
  });

  test.describe('Password Reset', () => {
    
    test('should initiate password reset flow', async ({ page }) => {
      await page.goto('/login');
      
      // Click forgot password link
      const forgotPasswordLink = page.locator('a[href*="password/reset"], a:has-text("Forgot")');
      if (await forgotPasswordLink.isVisible()) {
        await forgotPasswordLink.click();
        
        // Fill email
        await page.fill('input[name="email"]', 'student@test.com');
        await page.click('button[type="submit"]');
        
        // Verify success message
        const successMessage = page.locator('.alert-success, .success-message');
        await expect(successMessage).toBeVisible();
        await expect(successMessage).toContainText(/email.*sent|link.*sent/i);
      }
    });
  });

  test.describe('Social Authentication', () => {
    
    test('should display social login options if available', async ({ page }) => {
      await page.goto('/login');
      
      // Check for social login buttons
      const socialBtns = page.locator('[data-testid*="social"], .social-login, button:has-text("Google"), button:has-text("Facebook")');
      
      if (await socialBtns.count() > 0) {
        await expect(socialBtns.first()).toBeVisible();
        
        // Verify buttons are clickable (without actually clicking to avoid external redirects)
        await expect(socialBtns.first()).toBeEnabled();
      }
    });
  });

  test.describe('Account Security', () => {
    
    test('should handle multiple failed login attempts', async ({ page }) => {
      await page.goto('/login');
      
      // Attempt multiple failed logins
      for (let i = 0; i < 3; i++) {
        await page.fill('input[name="email"]', 'student@test.com');
        await page.fill('input[name="password"]', 'wrongpassword');
        await page.click('button[type="submit"]');
        
        // Wait for error message
        await page.waitForSelector('.alert-danger, .error-message', { timeout: 5000 });
      }
      
      // Check if account lockout or CAPTCHA appears
      const lockoutMessage = page.locator('.alert-warning, .lockout-message, [data-testid="account-locked"]');
      const captcha = page.locator('.captcha, [data-testid="captcha"]');
      
      // Either lockout message or CAPTCHA should appear
      await expect(async () => {
        const hasLockout = await lockoutMessage.isVisible();
        const hasCaptcha = await captcha.isVisible();
        expect(hasLockout || hasCaptcha).toBeTruthy();
      }).toPass({ timeout: 5000 });
    });
  });
});