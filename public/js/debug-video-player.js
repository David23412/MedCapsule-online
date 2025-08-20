/**
 * DEBUG VIDEO PLAYER SCRIPT
 * Tests and debugs the secure video player implementation
 */

console.log('🔍 DEBUG: Starting video player diagnostics...');

// Test 1: Check if functions are available
function testFunctionAvailability() {
    console.log('🧪 TEST 1: Function Availability');
    
    const functions = [
        'generateSecureVideoOverlay',
        'initializeSecureVideoPlayer', 
        'playVideo',
        'stopAllVideos'
    ];
    
    functions.forEach(funcName => {
        const isAvailable = typeof window[funcName] === 'function';
        console.log(`${isAvailable ? '✅' : '❌'} ${funcName}: ${isAvailable ? 'Available' : 'Missing'}`);
    });
}

// Test 2: Check browser compatibility
function testBrowserCompatibility() {
    console.log('🧪 TEST 2: Browser Compatibility');
    
    const userAgent = navigator.userAgent;
    const isEdge = userAgent.includes('Edge') || userAgent.includes('Edg');
    const isSafari = userAgent.includes('Safari') && !userAgent.includes('Chrome');
    const isChrome = userAgent.includes('Chrome') && !userAgent.includes('Edg');
    const isFirefox = userAgent.includes('Firefox');
    
    console.log('🌐 Browser detected:', {
        isEdge,
        isSafari, 
        isChrome,
        isFirefox,
        userAgent: userAgent.substring(0, 100) + '...'
    });
}

// Test 3: Test HTML generation
function testHTMLGeneration() {
    console.log('🧪 TEST 3: HTML Generation');
    
    if (typeof window.generateSecureVideoOverlay === 'function') {
        try {
            const testHTML = window.generateSecureVideoOverlay('dQw4w9WgXcQ', 'Test Video', true, true);
            console.log('✅ HTML generated successfully');
            console.log('📏 HTML length:', testHTML.length);
            console.log('🔍 Contains controls:', testHTML.includes('custom-youtube-controls') ? 'YES' : 'NO');
            console.log('🔍 Contains skip buttons:', testHTML.includes('skip-backward-btn') ? 'YES' : 'NO');
            console.log('🔍 Contains blue progress bar:', testHTML.includes('progress-bar') ? 'YES' : 'NO');
        } catch (error) {
            console.error('❌ Error generating HTML:', error);
        }
    } else {
        console.error('❌ generateSecureVideoOverlay function not available');
    }
}

// Test 4: Check CSS styles
function testCSSStyles() {
    console.log('🧪 TEST 4: CSS Styles');
    
    const testElement = document.createElement('div');
    testElement.className = 'custom-youtube-controls';
    testElement.style.cssText = `
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.9));
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 30;
        pointer-events: auto;
    `;
    
    document.body.appendChild(testElement);
    
    const computedStyle = window.getComputedStyle(testElement);
    console.log('✅ Test element created');
    console.log('📏 Computed z-index:', computedStyle.zIndex);
    console.log('📏 Computed opacity:', computedStyle.opacity);
    
    document.body.removeChild(testElement);
}

// Test 5: Check YouTube API
function testYouTubeAPI() {
    console.log('🧪 TEST 5: YouTube API');
    
    console.log('🔍 YT object available:', typeof window.YT !== 'undefined');
    console.log('🔍 YT.Player available:', typeof window.YT?.Player !== 'undefined');
    console.log('🔍 YT.get available:', typeof window.YT?.get !== 'undefined');
}

// Run all tests
function runAllTests() {
    console.log('🚀 Starting comprehensive video player diagnostics...\n');
    
    testFunctionAvailability();
    console.log('');
    
    testBrowserCompatibility();
    console.log('');
    
    testHTMLGeneration();
    console.log('');
    
    testCSSStyles();
    console.log('');
    
    testYouTubeAPI();
    console.log('');
    
    console.log('✅ All diagnostics completed!');
}

// Auto-run tests when script loads
runAllTests();

// Export for manual testing
window.debugVideoPlayer = runAllTests;
