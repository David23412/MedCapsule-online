/**
 * Global Jest Setup
 * Runs once before all tests
 */

module.exports = async () => {
    console.log('🔧 Setting up global test environment...');
    
    // Set up global test environment variables
    process.env.NODE_ENV = 'test';
    process.env.APP_ENV = 'testing';
    
    // Mock browser APIs that might not be available in jsdom
    global.ResizeObserver = class ResizeObserver {
        constructor(callback) {
            this.callback = callback;
        }
        observe() {}
        unobserve() {}
        disconnect() {}
    };
    
    // Mock IntersectionObserver
    global.IntersectionObserver = class IntersectionObserver {
        constructor(callback) {
            this.callback = callback;
        }
        observe() {}
        unobserve() {}
        disconnect() {}
    };
    
    // Mock matchMedia
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: jest.fn().mockImplementation(query => ({
            matches: false,
            media: query,
            onchange: null,
            addListener: jest.fn(),
            removeListener: jest.fn(),
            addEventListener: jest.fn(),
            removeEventListener: jest.fn(),
            dispatchEvent: jest.fn(),
        })),
    });
    
    console.log('✅ Global test environment setup complete');
};