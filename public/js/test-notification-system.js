/**
 * Test script to verify notification system is working
 */
console.log('🧪 Testing notification system...');

// Test 1: Check if notification functions are available
console.log('✅ startPerformanceTracking available:', typeof window.startPerformanceTracking === 'function');
console.log('✅ endPerformanceTracking available:', typeof window.endPerformanceTracking === 'function');
console.log('✅ loadNotifications available:', typeof window.loadNotifications === 'function');

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
    console.log('📡 Notification endpoint response status:', response.status);
    if (response.status === 200) {
        console.log('✅ Notification endpoint is working!');
        return response.json();
    } else if (response.status === 302) {
        console.log('⚠️ Notification endpoint requires authentication (redirect)');
        return null;
    } else {
        console.log('❌ Notification endpoint error:', response.status);
        return null;
    }
})
.then(data => {
    if (data) {
        console.log('📊 Notification data:', data);
        if (data.success) {
            console.log('✅ Notification system is fully functional!');
        } else {
            console.log('❌ Notification system returned error:', data.message);
        }
    }
})
.catch(error => {
    console.log('❌ Notification endpoint test failed:', error);
});

console.log('🧪 Notification system test completed'); 