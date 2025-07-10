import { chromium } from '@playwright/test';
import { execSync } from 'child_process';
import dotenv from 'dotenv';

/**
 * GLOBAL E2E TEST SETUP
 * Prepares environment, database, and authentication for all tests
 */
async function globalSetup(config) {
  console.log('🚀 Setting up E2E test environment...');
  
  // Load test environment variables
  dotenv.config({ path: '.env.testing' });
  
  try {
    // 1. DATABASE SETUP
    console.log('📊 Setting up test database...');
    await setupTestDatabase();
    
    // 2. AUTHENTICATION SETUP  
    console.log('🔐 Setting up authentication state...');
    await setupAuthenticationStates(config);
    
    // 3. TEST DATA SEEDING
    console.log('🌱 Seeding test data...');
    await seedTestData();
    
    // 4. PERFORMANCE MONITORING SETUP
    console.log('📈 Setting up performance monitoring...');
    await setupPerformanceMonitoring();
    
    console.log('✅ E2E test environment ready!');
    
  } catch (error) {
    console.error('❌ Global setup failed:', error);
    throw error;
  }
}

/**
 * Setup test database with clean state
 */
async function setupTestDatabase() {
  try {
    // Refresh test database
    execSync('php artisan migrate:refresh --env=testing --force', { 
      stdio: 'inherit' 
    });
    
    // Run seeders for test data
    execSync('php artisan db:seed --env=testing --force', { 
      stdio: 'inherit' 
    });
    
    console.log('✅ Test database prepared');
  } catch (error) {
    throw new Error(`Database setup failed: ${error.message}`);
  }
}

/**
 * Setup authentication states for different user types
 */
async function setupAuthenticationStates(config) {
  const baseURL = config.projects[0].use.baseURL;
  const browser = await chromium.launch();
  
  try {
    // Student user authentication
    await createAuthState(browser, baseURL, {
      email: 'student@test.com',
      password: 'password',
      role: 'student',
      statePath: 'tests/e2e/auth/student.json'
    });
    
    // Premium student authentication
    await createAuthState(browser, baseURL, {
      email: 'premium@test.com', 
      password: 'password',
      role: 'premium_student',
      statePath: 'tests/e2e/auth/premium-student.json'
    });
    
    // Admin user authentication
    await createAuthState(browser, baseURL, {
      email: 'admin@test.com',
      password: 'password', 
      role: 'admin',
      statePath: 'tests/e2e/auth/admin.json'
    });
    
    console.log('✅ Authentication states created');
    
  } finally {
    await browser.close();
  }
}

/**
 * Create authentication state for specific user
 */
async function createAuthState(browser, baseURL, userConfig) {
  const context = await browser.newContext();
  const page = await context.newPage();
  
  try {
    // Navigate to login page
    await page.goto(`${baseURL}/login`);
    
    // Fill login form
    await page.fill('input[name="email"]', userConfig.email);
    await page.fill('input[name="password"]', userConfig.password);
    
    // Submit login
    await page.click('button[type="submit"]');
    
    // Wait for successful login redirect
    await page.waitForURL(/\/dashboard|\/courses/, { timeout: 10000 });
    
    // Verify login success by checking for logout button or user menu
    await page.waitForSelector('[data-testid="user-menu"], .logout-btn, .profile-menu', {
      timeout: 5000
    });
    
    // Save authentication state
    await context.storageState({ path: userConfig.statePath });
    
    console.log(`✅ ${userConfig.role} authentication state saved`);
    
  } catch (error) {
    throw new Error(`Failed to create ${userConfig.role} auth state: ${error.message}`);
  } finally {
    await context.close();
  }
}

/**
 * Seed test data for comprehensive testing
 */
async function seedTestData() {
  try {
    // Create test courses with different states
    execSync('php artisan db:seed --class=E2ETestDataSeeder --env=testing', {
      stdio: 'inherit'
    });
    
    // Create payment test data
    execSync('php artisan db:seed --class=PaymentTestDataSeeder --env=testing', {
      stdio: 'inherit'
    });
    
    console.log('✅ Test data seeded');
  } catch (error) {
    console.warn('⚠️ Test data seeding failed, using default data');
  }
}

/**
 * Setup performance monitoring for tests
 */
async function setupPerformanceMonitoring() {
  // Create performance results directory
  execSync('mkdir -p test-results/performance', { stdio: 'inherit' });
  
  // Initialize performance baseline file
  const performanceBaseline = {
    setup_timestamp: new Date().toISOString(),
    baseline_metrics: {
      page_load_time: 3000, // 3s baseline
      first_contentful_paint: 1500, // 1.5s baseline
      largest_contentful_paint: 2500, // 2.5s baseline
      cumulative_layout_shift: 0.1,
      first_input_delay: 100 // 100ms baseline
    }
  };
  
  require('fs').writeFileSync(
    'test-results/performance/baseline.json',
    JSON.stringify(performanceBaseline, null, 2)
  );
  
  console.log('✅ Performance monitoring ready');
}

export default globalSetup;