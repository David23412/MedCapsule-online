/**
 * 🔍 BROWSER BUNDLE PAYMENT DIAGNOSTIC SCRIPT 🔍
 * Copy and paste this into your browser console on the courses page
 */

console.log('🔍 Starting Bundle Payment Diagnostic...');

// Step 1: Check if CSRF token exists
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
console.log('1. CSRF Token:', csrfToken ? 'Found' : 'Missing');
if (csrfToken) {
    console.log('   Token:', csrfToken.substring(0, 10) + '...');
}

// Step 2: Check if bundle button exists
const bundleBtn = document.getElementById('bundlePurchaseBtn');
console.log('2. Bundle Button:', bundleBtn ? 'Found' : 'Missing');

// Step 3: Check if bundle modal exists
const bundleModal = document.getElementById('bundleModal');
console.log('3. Bundle Modal:', bundleModal ? 'Found' : 'Missing');

// Step 4: Check if course checkboxes exist
const courseCheckboxes = document.querySelectorAll('.bundle-course-checkbox');
console.log('4. Course Checkboxes:', courseCheckboxes.length);

// Step 5: Test bundle options API call
async function testBundleOptions() {
    console.log('5. Testing Bundle Options API...');
    
    // Get first two available courses
    const availableCourses = Array.from(courseCheckboxes).slice(0, 2);
    if (availableCourses.length < 2) {
        console.log('❌ Need at least 2 courses for bundle test');
        return;
    }
    
    const courseIds = availableCourses.map(cb => parseInt(cb.dataset.courseId));
    const coursePrices = availableCourses.map(cb => parseFloat(cb.dataset.coursePrice));
    const originalPrice = coursePrices.reduce((sum, price) => sum + price, 0);
    
    const testData = {
        courses: courseIds,
        original_price: originalPrice,
        discount_amount: 50,
        total_price: originalPrice - 50
    };
    
    console.log('   Test Data:', testData);
    
    try {
        const response = await fetch('/payments/bundle/options', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(testData)
        });
        
        console.log('   Response Status:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            console.log('   ✅ Bundle Options Success:', data.success);
            if (data.success) {
                console.log('   ✅ HTML Length:', data.html?.length || 0);
                console.log('   ✅ Total Price:', data.total_price);
            } else {
                console.log('   ❌ Error:', data.message);
            }
        } else {
            const errorText = await response.text();
            console.log('   ❌ HTTP Error:', response.status);
            console.log('   ❌ Error Details:', errorText.substring(0, 200));
        }
    } catch (error) {
        console.log('   ❌ Network Error:', error.message);
    }
}

// Step 6: Test bundle submission API call
async function testBundleSubmission() {
    console.log('6. Testing Bundle Submission API...');
    
    const availableCourses = Array.from(courseCheckboxes).slice(0, 2);
    if (availableCourses.length < 2) {
        console.log('❌ Need at least 2 courses for bundle test');
        return;
    }
    
    const courseIds = availableCourses.map(cb => parseInt(cb.dataset.courseId));
    const coursePrices = availableCourses.map(cb => parseFloat(cb.dataset.coursePrice));
    const originalPrice = coursePrices.reduce((sum, price) => sum + price, 0);
    
    const testData = {
        courses: courseIds,
        payment_method: 'vodafone_cash',
        transaction_id: 'BROWSER_TEST_' + Date.now(),
        total_amount: originalPrice - 50
    };
    
    console.log('   Test Data:', testData);
    
    try {
        const response = await fetch('/payment/submit-bundle-proof', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(testData)
        });
        
        console.log('   Response Status:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            console.log('   ✅ Bundle Submission Success:', data.success);
            if (data.success) {
                console.log('   ✅ Payments Created:', data.payments_created);
                console.log('   ✅ Bundle ID:', data.bundle_id);
            } else {
                console.log('   ❌ Error:', data.message);
            }
        } else {
            const errorText = await response.text();
            console.log('   ❌ HTTP Error:', response.status);
            console.log('   ❌ Error Details:', errorText.substring(0, 200));
        }
    } catch (error) {
        console.log('   ❌ Network Error:', error.message);
    }
}

// Run the tests
testBundleOptions().then(() => {
    console.log('');
    return testBundleSubmission();
}).then(() => {
    console.log('');
    console.log('🔍 Bundle Payment Diagnostic Complete!');
    console.log('');
    console.log('📋 Next Steps:');
    console.log('1. If CSRF token is missing, check the meta tag in <head>');
    console.log('2. If bundle button is missing, check if user is logged in');
    console.log('3. If API calls fail, check browser Network tab for details');
    console.log('4. If everything works here but not in UI, check JavaScript console for errors');
});

// Export functions for manual testing
window.testBundleOptions = testBundleOptions;
window.testBundleSubmission = testBundleSubmission;