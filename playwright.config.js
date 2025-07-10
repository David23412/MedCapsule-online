import { defineConfig, devices } from '@playwright/test';

/**
 * BULLETPROOF E2E TESTING CONFIGURATION
 * Comprehensive test setup for critical user journeys
 */
export default defineConfig({
  // Test directory structure
  testDir: './tests/e2e',
  
  // Global test configuration
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  
  // Reporter configuration
  reporter: [
    ['html', { open: 'never' }],
    ['json', { outputFile: 'test-results/results.json' }],
    ['junit', { outputFile: 'test-results/junit.xml' }],
    ['allure-playwright']
  ],
  
  // Global test settings
  use: {
    // Base URL for tests
    baseURL: process.env.TEST_BASE_URL || 'http://localhost:8000',
    
    // Browser settings
    headless: process.env.CI ? true : false,
    viewport: { width: 1280, height: 720 },
    ignoreHTTPSErrors: true,
    
    // Screenshots and videos
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'retain-on-failure',
    
    // Network settings
    actionTimeout: 15000,
    navigationTimeout: 30000,
    
    // Additional context options
    locale: 'en-US',
    timezoneId: 'America/New_York',
  },

  // Environment-specific configurations
  projects: [
    // Desktop browsers
    {
      name: 'chromium-desktop',
      use: { 
        ...devices['Desktop Chrome'],
        channel: 'chrome'
      },
    },
    {
      name: 'firefox-desktop',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit-desktop',
      use: { ...devices['Desktop Safari'] },
    },
    
    // Mobile devices
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'mobile-safari',
      use: { ...devices['iPhone 12'] },
    },
    
    // Tablet devices
    {
      name: 'tablet-chrome',
      use: { ...devices['iPad Pro'] },
    },
    
    // Performance testing
    {
      name: 'performance-desktop',
      use: {
        ...devices['Desktop Chrome'],
        launchOptions: {
          args: ['--disable-web-security', '--disable-features=VizDisplayCompositor']
        }
      },
    },
    
    // Accessibility testing
    {
      name: 'accessibility-testing',
      use: {
        ...devices['Desktop Chrome'],
        // Additional a11y testing configurations
      },
    }
  ],

  // Test timeout settings
  timeout: 60000,
  expect: {
    timeout: 10000,
  },

  // Global setup and teardown
  globalSetup: require.resolve('./tests/e2e/global-setup.js'),
  globalTeardown: require.resolve('./tests/e2e/global-teardown.js'),

  // Web server configuration for local testing
  webServer: process.env.CI ? undefined : {
    command: 'php artisan serve --port=8000',
    url: 'http://localhost:8000',
    timeout: 120000,
    reuseExistingServer: !process.env.CI,
  },
});