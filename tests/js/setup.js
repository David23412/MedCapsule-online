/**
 * Jest Setup File for Notification System Tests
 * Global test configuration and mocks
 */

// Import required testing utilities
require('@testing-library/jest-dom');

// Mock DOM APIs
global.ResizeObserver = jest.fn().mockImplementation(() => ({
    observe: jest.fn(),
    unobserve: jest.fn(),
    disconnect: jest.fn(),
}));

global.IntersectionObserver = jest.fn().mockImplementation(() => ({
    observe: jest.fn(),
    unobserve: jest.fn(),
    disconnect: jest.fn(),
}));

// Mock performance API
global.performance = {
    now: jest.fn(() => Date.now()),
    mark: jest.fn(),
    measure: jest.fn(),
    getEntriesByType: jest.fn(() => []),
    getEntriesByName: jest.fn(() => []),
    clearMarks: jest.fn(),
    clearMeasures: jest.fn(),
    memory: {
        usedJSHeapSize: 1000000,
        totalJSHeapSize: 2000000,
        jsHeapSizeLimit: 4000000
    }
};

// Mock localStorage
const localStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn(),
    length: 0,
    key: jest.fn()
};
global.localStorage = localStorageMock;

// Mock sessionStorage
global.sessionStorage = localStorageMock;

// Mock fetch API
global.fetch = jest.fn();

// Mock console methods to reduce noise in tests
const originalConsole = global.console;
global.console = {
    ...originalConsole,
    log: jest.fn(),
    debug: jest.fn(),
    info: jest.fn(),
    warn: jest.fn(),
    error: jest.fn()
};

// Mock window.matchMedia
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

// Mock window.getComputedStyle
Object.defineProperty(window, 'getComputedStyle', {
    value: jest.fn().mockImplementation(() => ({
        getPropertyValue: jest.fn(),
        animation: 'none',
        transition: 'none'
    }))
});

// Mock CSS animations
Object.defineProperty(Element.prototype, 'animate', {
    value: jest.fn().mockImplementation(() => ({
        finished: Promise.resolve(),
        cancel: jest.fn(),
        pause: jest.fn(),
        play: jest.fn()
    }))
});

// Mock getBoundingClientRect
Element.prototype.getBoundingClientRect = jest.fn(() => ({
    width: 100,
    height: 100,
    top: 0,
    left: 0,
    bottom: 100,
    right: 100,
    x: 0,
    y: 0
}));

// Mock scrollIntoView
Element.prototype.scrollIntoView = jest.fn();

// Mock focus and blur
Element.prototype.focus = jest.fn();
Element.prototype.blur = jest.fn();

// Mock navigator
Object.defineProperty(navigator, 'onLine', {
    writable: true,
    value: true
});

Object.defineProperty(navigator, 'connection', {
    writable: true,
    value: {
        effectiveType: '4g',
        downlink: 10,
        rtt: 50,
        addEventListener: jest.fn(),
        removeEventListener: jest.fn()
    }
});

// Mock requestAnimationFrame
global.requestAnimationFrame = jest.fn(cb => setTimeout(cb, 16));
global.cancelAnimationFrame = jest.fn(id => clearTimeout(id));

// Mock MutationObserver
global.MutationObserver = jest.fn().mockImplementation(() => ({
    observe: jest.fn(),
    disconnect: jest.fn(),
    takeRecords: jest.fn(() => [])
}));

// Custom matchers for accessibility testing
expect.extend({
    toHaveAccessibleName(received, expected) {
        const accessibleName = received.getAttribute('aria-label') || 
                              received.getAttribute('aria-labelledby') ||
                              received.textContent;
        
        const pass = accessibleName === expected;
        
        return {
            message: () => 
                `expected element to have accessible name "${expected}" but got "${accessibleName}"`,
            pass
        };
    },
    
    toBeAccessible(received) {
        const hasRole = received.hasAttribute('role');
        const hasAriaLabel = received.hasAttribute('aria-label') || 
                           received.hasAttribute('aria-labelledby');
        const hasTabIndex = received.hasAttribute('tabindex');
        
        const pass = hasRole && hasAriaLabel && hasTabIndex;
        
        return {
            message: () => 
                `expected element to be accessible (have role, aria-label/labelledby, and tabindex)`,
            pass
        };
    },
    
    toHaveMinimumTouchTarget(received) {
        const rect = received.getBoundingClientRect();
        const pass = rect.width >= 44 && rect.height >= 44;
        
        return {
            message: () => 
                `expected element to have minimum touch target size of 44x44px but got ${rect.width}x${rect.height}px`,
            pass
        };
    }
});

// Global test utilities
global.testUtils = {
    // Create mock notification data
    createMockNotification: (overrides = {}) => ({
        id: '1',
        type: 'info',
        title: 'Test Notification',
        message: 'This is a test notification',
        read: false,
        created_at: new Date().toISOString(),
        ...overrides
    }),
    
    // Create mock API response
    createMockApiResponse: (data, success = true) => ({
        ok: success,
        status: success ? 200 : 500,
        json: () => Promise.resolve({
            success,
            ...data
        })
    }),
    
    // Wait for animations
    waitForAnimation: (duration = 300) => 
        new Promise(resolve => setTimeout(resolve, duration)),
    
    // Trigger keyboard event
    triggerKeyboardEvent: (element, key, options = {}) => {
        const event = new KeyboardEvent('keydown', {
            key,
            bubbles: true,
            cancelable: true,
            ...options
        });
        element.dispatchEvent(event);
        return event;
    },
    
    // Trigger mouse event
    triggerMouseEvent: (element, type, options = {}) => {
        const event = new MouseEvent(type, {
            bubbles: true,
            cancelable: true,
            ...options
        });
        element.dispatchEvent(event);
        return event;
    },
    
    // Create DOM element with attributes
    createElement: (tag, attributes = {}, children = []) => {
        const element = document.createElement(tag);
        
        Object.entries(attributes).forEach(([key, value]) => {
            element.setAttribute(key, value);
        });
        
        children.forEach(child => {
            if (typeof child === 'string') {
                element.textContent = child;
            } else {
                element.appendChild(child);
            }
        });
        
        return element;
    }
};

// Setup and teardown helpers
beforeEach(() => {
    // Clear all mocks before each test
    jest.clearAllMocks();
    
    // Reset DOM
    document.body.innerHTML = '';
    document.head.innerHTML = '';
    
    // Reset localStorage
    localStorage.clear();
    
    // Reset console mocks
    if (console.log.mockClear) console.log.mockClear();
    if (console.error.mockClear) console.error.mockClear();
    if (console.warn.mockClear) console.warn.mockClear();
    
    // Reset fetch mock
    if (fetch.mockClear) fetch.mockClear();
    
    // Reset performance mock
    if (performance.now.mockClear) performance.now.mockClear();
});

afterEach(() => {
    // Clean up any remaining timers
    jest.runOnlyPendingTimers();
    jest.useRealTimers();
    
    // Clean up DOM
    document.body.innerHTML = '';
    
    // Reset mocks
    jest.restoreAllMocks();
});

// Global error handler for unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
    console.error('Unhandled Rejection at:', promise, 'reason:', reason);
});

// Suppress specific warnings in tests
const originalError = console.error;
beforeAll(() => {
    console.error = (...args) => {
        if (
            typeof args[0] === 'string' &&
            args[0].includes('Warning: ReactDOM.render is deprecated')
        ) {
            return;
        }
        originalError.call(console, ...args);
    };
});

afterAll(() => {
    console.error = originalError;
});