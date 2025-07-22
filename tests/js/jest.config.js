/**
 * GOLDEN STANDARD Jest Configuration
 * BRUTAL and SURGICALLY ACCURATE testing setup
 */

module.exports = {
    // Test environment
    testEnvironment: 'jsdom',
    
    // Root directory
    rootDir: '../../',
    
    // Setup files
    setupFilesAfterEnv: [
        '<rootDir>/tests/js/setup.js'
    ],
    
    // Test file patterns - PRECISE targeting
    testMatch: [
        '<rootDir>/tests/js/**/*.test.js'
    ],
    
    // Coverage configuration - BRUTAL accuracy
    collectCoverage: false, // Disable for speed in comprehensive testing
    
    // Module name mapping - SURGICAL precision
    moduleNameMapper: {
        '^@/(.*)$': '<rootDir>/resources/js/$1',
        '^@components/(.*)$': '<rootDir>/resources/views/components/$1'
    },
    
    // Transform files - GOLDEN STANDARD
    transform: {
        '^.+\\.js$': 'babel-jest',
        '^.+\\.blade\\.php$': '<rootDir>/tests/js/blade-transformer.js'
    },
    
    // Ignore patterns - BRUTAL filtering
    testPathIgnorePatterns: [
        '/node_modules/',
        '/vendor/',
        '/storage/',
        '/bootstrap/',
        '/public/'
    ],
    
    // Test timeout - PRECISE timing
    testTimeout: 15000,
    
    // Output control - SURGICAL
    verbose: false,
    silent: false,
    
    // Mock control - BRUTAL cleanup
    clearMocks: true,
    restoreMocks: true,
    resetMocks: true,
    
    // Error handling - GOLDEN STANDARD
    errorOnDeprecated: false, // Allow for compatibility
    
    // Minimal reporters for speed
    reporters: ['default'],
    
    // No watch plugins for CI/automated testing
    watchPlugins: []
};