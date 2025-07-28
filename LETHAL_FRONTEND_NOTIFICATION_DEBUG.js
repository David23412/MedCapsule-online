/**
 * 🔥 LETHAL FRONTEND NOTIFICATION DEBUG SCRIPT 🔥
 * 
 * This script will debug the frontend notification loading issue
 * Run this in the browser console on any page with notifications
 */

console.log('🔥🔥🔥 LETHAL FRONTEND NOTIFICATION DEBUG 🔥🔥🔥');
console.log('='.repeat(80));

// STEP 1: Check if notification elements exist
console.log('\n1. 🔍 CHECKING NOTIFICATION ELEMENTS:');
console.log('-'.repeat(50));

const notificationSystem = document.getElementById('notificationSystem');
const notificationBell = document.getElementById('notificationBell');
const notificationDropdown = document.getElementById('notificationDropdown');
const notificationBadge = document.getElementById('notificationBadge');

console.log('✅ Notification System:', notificationSystem ? 'EXISTS' : '❌ MISSING');
console.log('✅ Notification Bell:', notificationBell ? 'EXISTS' : '❌ MISSING');
console.log('✅ Notification Dropdown:', notificationDropdown ? 'EXISTS' : '❌ MISSING');
console.log('✅ Notification Badge:', notificationBadge ? 'EXISTS' : '❌ MISSING');

// STEP 2: Check CSRF token
console.log('\n2. 🔐 CHECKING CSRF TOKEN:');
console.log('-'.repeat(50));

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
console.log('✅ CSRF Token:', csrfToken ? 'EXISTS' : '❌ MISSING');
if (csrfToken) {
    console.log('🔑 Token value:', csrfToken.substring(0, 20) + '...');
}

// STEP 3: Check authentication
console.log('\n3. 👤 CHECKING AUTHENTICATION:');
console.log('-'.repeat(50));

// Check if user is logged in by looking for common auth indicators
const authIndicators = [
    document.querySelector('[data-user-id]'),
    document.querySelector('.user-name'),
    document.querySelector('.logout-btn'),
    document.querySelector('[href*="logout"]')
];

const isAuthenticated = authIndicators.some(el => el !== null);
console.log('✅ Authentication:', isAuthenticated ? 'AUTHENTICATED' : '❌ NOT AUTHENTICATED');

// STEP 4: Manual API test
console.log('\n4. 🌐 MANUAL API TEST:');
console.log('-'.repeat(50));

async function testNotificationAPI() {
    try {
        console.log('📡 Making request to /notifications...');
        
        const response = await fetch('/notifications', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        });
        
        console.log('📊 Response Status:', response.status);
        console.log('📊 Response OK:', response.ok);
        console.log('📊 Response Headers:', Object.fromEntries(response.headers.entries()));
        
        if (response.ok) {
            const data = await response.json();
            console.log('✅ Response Data:', data);
            console.log('🔔 Notifications Count:', data.notifications?.length || 0);
            console.log('🔢 Unread Count:', data.unread_count || 0);
            
            if (data.notifications && data.notifications.length > 0) {
                console.log('📝 First Notification:', data.notifications[0]);
            }
            
            return data;
        } else {
            const errorText = await response.text();
            console.log('❌ Error Response:', errorText);
            return null;
        }
        
    } catch (error) {
        console.log('❌ Fetch Error:', error);
        return null;
    }
}

// STEP 5: Test the notification loading function
console.log('\n5. 🔧 TESTING NOTIFICATION LOADING FUNCTION:');
console.log('-'.repeat(50));

// Check if loadNotifications function exists
if (typeof window.loadNotifications === 'function') {
    console.log('✅ loadNotifications function EXISTS');
    try {
        console.log('🔄 Calling loadNotifications...');
        window.loadNotifications();
    } catch (error) {
        console.log('❌ loadNotifications failed:', error);
    }
} else {
    console.log('❌ loadNotifications function MISSING');
}

// STEP 6: Check for JavaScript errors
console.log('\n6. 🐛 CHECKING FOR JAVASCRIPT ERRORS:');
console.log('-'.repeat(50));

// Override console.error to catch errors
const originalError = console.error;
const errors = [];
console.error = function(...args) {
    errors.push(args);
    originalError.apply(console, args);
};

// STEP 7: Simulate notification dropdown opening
console.log('\n7. 🎭 SIMULATING DROPDOWN OPENING:');
console.log('-'.repeat(50));

if (notificationBell && notificationDropdown) {
    console.log('🔄 Simulating bell click...');
    
    // Add click event listener to see what happens
    notificationBell.addEventListener('click', function(e) {
        console.log('🔔 Bell clicked!');
        console.log('📊 Dropdown display:', window.getComputedStyle(notificationDropdown).display);
        console.log('📊 Dropdown visibility:', window.getComputedStyle(notificationDropdown).visibility);
    });
    
    // Trigger click
    notificationBell.click();
    
    // Check dropdown content after click
    setTimeout(() => {
        console.log('📝 Dropdown HTML after click:');
        console.log(notificationDropdown.innerHTML);
    }, 1000);
} else {
    console.log('❌ Cannot simulate - elements missing');
}

// STEP 8: Run API test
testNotificationAPI().then(data => {
    console.log('\n8. 📊 API TEST RESULTS:');
    console.log('-'.repeat(50));
    
    if (data) {
        console.log('✅ API is working correctly');
        console.log('🔔 Backend has', data.notifications?.length || 0, 'notifications');
        
        if (data.notifications && data.notifications.length > 0) {
            console.log('🎯 DIAGNOSIS: Backend is working, frontend is not displaying notifications');
            console.log('💡 Check notification rendering function');
            console.log('💡 Check if dropdown is being populated');
            console.log('💡 Check CSS display properties');
        } else {
            console.log('⚠️  Backend has no notifications');
            console.log('💡 Create some notifications first');
        }
    } else {
        console.log('❌ API is not working');
        console.log('💡 Check authentication');
        console.log('💡 Check CSRF token');
        console.log('💡 Check server logs');
    }
});

// STEP 9: Check notification rendering
console.log('\n9. 🎨 CHECKING NOTIFICATION RENDERING:');
console.log('-'.repeat(50));

// Look for notification rendering functions
const renderFunctions = [
    'renderNotifications',
    'displayNotifications',
    'showNotifications',
    'updateNotifications'
];

renderFunctions.forEach(funcName => {
    if (typeof window[funcName] === 'function') {
        console.log('✅', funcName, 'function EXISTS');
    } else {
        console.log('❌', funcName, 'function MISSING');
    }
});

// STEP 10: Final diagnosis
setTimeout(() => {
    console.log('\n10. 🎯 FINAL DIAGNOSIS:');
    console.log('-'.repeat(50));
    
    if (errors.length > 0) {
        console.log('❌ JavaScript errors detected:');
        errors.forEach((error, index) => {
            console.log(`   ${index + 1}.`, ...error);
        });
    } else {
        console.log('✅ No JavaScript errors detected');
    }
    
    console.log('\n🚀 RECOMMENDED ACTIONS:');
    console.log('-'.repeat(50));
    console.log('1. Check if notifications are being rendered in dropdown');
    console.log('2. Verify CSS is not hiding notifications');
    console.log('3. Check if event listeners are properly attached');
    console.log('4. Verify notification HTML structure');
    console.log('5. Test with browser developer tools network tab');
    
    console.log('\n🔥 FRONTEND DEBUG COMPLETE! 🔥');
    console.log('='.repeat(80));
}, 2000);