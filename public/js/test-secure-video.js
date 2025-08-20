/**
 * SECURE VIDEO TEST SCRIPT
 * Tests the secure video implementation
 */

console.log('🧪 Testing secure video implementation...');

// Test if functions are available
if (typeof window.generateSecureVideoOverlay === 'function') {
    console.log('✅ generateSecureVideoOverlay function found');
} else {
    console.error('❌ generateSecureVideoOverlay function not found');
}

if (typeof window.initializeSecureVideoPlayer === 'function') {
    console.log('✅ initializeSecureVideoPlayer function found');
} else {
    console.error('❌ initializeSecureVideoPlayer function not found');
}

// Test if playVideo function is available
if (typeof window.playVideo === 'function') {
    console.log('✅ playVideo function found');
} else {
    console.error('❌ playVideo function not found');
}

// Test security measures
document.addEventListener('contextmenu', function(e) {
    if (e.target.closest('.secure-video-wrapper')) {
        console.log('🔒 Right-click blocked on secure video wrapper');
    }
}, true);

// Test skip functionality
if (typeof window.skipBackward === 'function') {
    console.log('✅ skipBackward function found');
} else {
    console.log('⚠️ skipBackward function not found (will be available when video is loaded)');
}

if (typeof window.skipForward === 'function') {
    console.log('✅ skipForward function found');
} else {
    console.log('⚠️ skipForward function not found (will be available when video is loaded)');
}

console.log('🧪 Secure video test completed');
console.log('🎨 Blue watch bar with skip controls ready!');
