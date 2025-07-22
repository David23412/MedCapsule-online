/**
 * Global Jest Teardown
 * Runs once after all tests
 */

module.exports = async () => {
    console.log('🧹 Cleaning up global test environment...');
    
    // Clean up any global resources
    if (global.gc) {
        global.gc();
    }
    
    console.log('✅ Global test environment cleanup complete');
};