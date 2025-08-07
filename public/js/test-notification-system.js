/**
 * Test script to verify notification system is working
 */
// console.log('🧪 Testing notification system...'); // Removed for production

// Test 1: Check if notification functions are available
// console.log('✅ startPerformanceTracking available:', typeof window.startPerformanceTracking === 'function'); // Removed for production
// console.log('✅ endPerformanceTracking available:', typeof window.endPerformanceTracking === 'function'); // Removed for production
// console.log('✅ loadNotifications available:', typeof window.loadNotifications === 'function'); // Removed for production

// Test 2: Test notification endpoint
fetch('/notifications', {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => {
// console.log('📡 Notification endpoint response status:', response.status); // Removed for production
    if (response.status === 200) {
// console.log('✅ Notification endpoint is working!'); // Removed for production
        return response.json();
    } else if (response.status === 302) {
// console.log('⚠️ Notification endpoint requires authentication (redirect)'); // Removed for production
        return null;
    } else {
// console.log('❌ Notification endpoint error:', response.status); // Removed for production
        return null;
    }
})
.then(data => {
    if (data) {
// console.log('📊 Notification data:', data); // Removed for production
        if (data.success) {
// console.log('✅ Notification system is fully functional!'); // Removed for production
        } else {
// console.log('❌ Notification system returned error:', data.message); // Removed for production
        }
    }
})
.catch(error => {
// console.log('❌ Notification endpoint test failed:', error); // Removed for production
});

// console.log('🧪 Notification system test completed'); // Removed for production