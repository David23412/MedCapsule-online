import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';

/**
 * GLOBAL E2E TEST TEARDOWN
 * Cleanup resources and generate comprehensive test reports
 */
async function globalTeardown(config) {
  console.log('🧹 Cleaning up E2E test environment...');
  
  try {
    // 1. GENERATE TEST REPORTS
    console.log('📊 Generating test reports...');
    await generateTestReports();
    
    // 2. CLEANUP AUTHENTICATION STATES
    console.log('🔐 Cleaning up authentication states...');
    await cleanupAuthStates();
    
    // 3. PERFORMANCE REPORT
    console.log('📈 Generating performance report...');
    await generatePerformanceReport();
    
    // 4. CLEANUP TEST DATABASE (optional)
    if (process.env.CLEANUP_TEST_DB === 'true') {
      console.log('🗄️ Cleaning up test database...');
      await cleanupTestDatabase();
    }
    
    // 5. ARCHIVE TEST ARTIFACTS
    console.log('📁 Archiving test artifacts...');
    await archiveTestArtifacts();
    
    console.log('✅ E2E test cleanup completed!');
    
  } catch (error) {
    console.error('❌ Global teardown failed:', error);
    // Don't throw to avoid masking test failures
  }
}

/**
 * Generate comprehensive test reports
 */
async function generateTestReports() {
  try {
    const resultsDir = 'test-results';
    
    // Check if results exist
    if (!fs.existsSync(resultsDir)) {
      console.log('No test results to process');
      return;
    }
    
    // Generate HTML report if JSON results exist
    if (fs.existsSync(path.join(resultsDir, 'results.json'))) {
      const results = JSON.parse(
        fs.readFileSync(path.join(resultsDir, 'results.json'), 'utf8')
      );
      
      const summary = {
        timestamp: new Date().toISOString(),
        total_tests: results.stats?.total || 0,
        passed_tests: results.stats?.passed || 0,
        failed_tests: results.stats?.failed || 0,
        skipped_tests: results.stats?.skipped || 0,
        duration: results.stats?.duration || 0,
        test_suites: results.suites?.map(suite => ({
          title: suite.title,
          tests: suite.tests?.length || 0,
          passed: suite.tests?.filter(t => t.outcome === 'passed').length || 0,
          failed: suite.tests?.filter(t => t.outcome === 'failed').length || 0
        })) || []
      };
      
      fs.writeFileSync(
        path.join(resultsDir, 'summary.json'),
        JSON.stringify(summary, null, 2)
      );
      
      console.log(`✅ Test summary: ${summary.passed_tests}/${summary.total_tests} passed`);
    }
    
  } catch (error) {
    console.warn('⚠️ Failed to generate test reports:', error.message);
  }
}

/**
 * Cleanup authentication state files
 */
async function cleanupAuthStates() {
  try {
    const authDir = 'tests/e2e/auth';
    
    if (fs.existsSync(authDir)) {
      const authFiles = fs.readdirSync(authDir).filter(file => file.endsWith('.json'));
      
      for (const file of authFiles) {
        const filePath = path.join(authDir, file);
        try {
          fs.unlinkSync(filePath);
          console.log(`🗑️ Removed auth state: ${file}`);
        } catch (error) {
          console.warn(`⚠️ Failed to remove ${file}:`, error.message);
        }
      }
    }
    
  } catch (error) {
    console.warn('⚠️ Auth cleanup failed:', error.message);
  }
}

/**
 * Generate performance analysis report
 */
async function generatePerformanceReport() {
  try {
    const perfDir = 'test-results/performance';
    
    if (!fs.existsSync(perfDir)) {
      return;
    }
    
    // Collect all performance data files
    const perfFiles = fs.readdirSync(perfDir).filter(file => 
      file.endsWith('.json') && file !== 'baseline.json'
    );
    
    if (perfFiles.length === 0) {
      return;
    }
    
    let baseline = {};
    try {
      baseline = JSON.parse(
        fs.readFileSync(path.join(perfDir, 'baseline.json'), 'utf8')
      );
    } catch (error) {
      console.warn('No performance baseline found');
    }
    
    const performanceData = perfFiles.map(file => {
      try {
        return JSON.parse(
          fs.readFileSync(path.join(perfDir, file), 'utf8')
        );
      } catch (error) {
        console.warn(`Failed to parse ${file}:`, error.message);
        return null;
      }
    }).filter(Boolean);
    
    if (performanceData.length > 0) {
      const report = {
        timestamp: new Date().toISOString(),
        baseline: baseline.baseline_metrics || {},
        tests_count: performanceData.length,
        avg_metrics: calculateAverageMetrics(performanceData),
        performance_issues: identifyPerformanceIssues(performanceData, baseline.baseline_metrics || {}),
        recommendations: generatePerformanceRecommendations(performanceData)
      };
      
      fs.writeFileSync(
        path.join(perfDir, 'performance-report.json'),
        JSON.stringify(report, null, 2)
      );
      
      console.log(`📈 Performance report generated (${performanceData.length} tests analyzed)`);
    }
    
  } catch (error) {
    console.warn('⚠️ Performance report generation failed:', error.message);
  }
}

/**
 * Calculate average performance metrics
 */
function calculateAverageMetrics(performanceData) {
  const metrics = ['page_load_time', 'first_contentful_paint', 'largest_contentful_paint'];
  const averages = {};
  
  metrics.forEach(metric => {
    const values = performanceData
      .map(data => data[metric])
      .filter(val => typeof val === 'number');
    
    if (values.length > 0) {
      averages[metric] = Math.round(values.reduce((a, b) => a + b, 0) / values.length);
    }
  });
  
  return averages;
}

/**
 * Identify performance issues by comparing against baseline
 */
function identifyPerformanceIssues(performanceData, baseline) {
  const issues = [];
  
  performanceData.forEach((data, index) => {
    Object.keys(baseline).forEach(metric => {
      if (data[metric] && data[metric] > baseline[metric] * 1.2) { // 20% threshold
        issues.push({
          test_index: index,
          metric,
          value: data[metric],
          baseline: baseline[metric],
          severity: data[metric] > baseline[metric] * 1.5 ? 'high' : 'medium'
        });
      }
    });
  });
  
  return issues;
}

/**
 * Generate performance optimization recommendations
 */
function generatePerformanceRecommendations(performanceData) {
  const recommendations = [];
  const avgMetrics = calculateAverageMetrics(performanceData);
  
  if (avgMetrics.page_load_time > 3000) {
    recommendations.push({
      issue: 'Slow page load times',
      recommendation: 'Consider implementing code splitting and lazy loading',
      priority: 'high'
    });
  }
  
  if (avgMetrics.first_contentful_paint > 1500) {
    recommendations.push({
      issue: 'Slow First Contentful Paint',
      recommendation: 'Optimize critical rendering path and inline critical CSS',
      priority: 'medium'
    });
  }
  
  return recommendations;
}

/**
 * Cleanup test database (optional)
 */
async function cleanupTestDatabase() {
  try {
    execSync('php artisan migrate:reset --env=testing --force', {
      stdio: 'inherit'
    });
    console.log('🗄️ Test database cleaned');
  } catch (error) {
    console.warn('⚠️ Database cleanup failed:', error.message);
  }
}

/**
 * Archive test artifacts for CI/CD
 */
async function archiveTestArtifacts() {
  try {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const archiveDir = `test-archives/${timestamp}`;
    
    // Create archive directory
    execSync(`mkdir -p ${archiveDir}`, { stdio: 'inherit' });
    
    // Copy test results
    if (fs.existsSync('test-results')) {
      execSync(`cp -r test-results ${archiveDir}/`, { stdio: 'inherit' });
    }
    
    // Copy screenshots and videos (if any)
    if (fs.existsSync('test-results/screenshots')) {
      execSync(`cp -r test-results/screenshots ${archiveDir}/`, { stdio: 'inherit' });
    }
    
    if (fs.existsSync('test-results/videos')) {
      execSync(`cp -r test-results/videos ${archiveDir}/`, { stdio: 'inherit' });
    }
    
    console.log(`📁 Test artifacts archived to: ${archiveDir}`);
    
  } catch (error) {
    console.warn('⚠️ Archival failed:', error.message);
  }
}

export default globalTeardown;