/**
 * 🔍 BROWSER BUNDLE PAYMENT DIAGNOSTIC SCRIPT 🔍
 * Copy and paste this into your browser console on the courses page
 */

// console.log('🔍 Starting Bundle Payment Diagnostic...'); // Removed for production

// Step 1: Check if CSRF token exists
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
// console.log('1. CSRF Token:', csrfToken ? 'Found' : 'Missing'); // Removed for production
if (csrfToken) {
// console.log('   Token:', csrfToken.substring(0, 10) + '...'); // Removed for production
}

// Step 2: Check if bundle button exists
const bundleBtn = document.getElementById('bundlePurchaseBtn');
// console.log('2. Bundle Button:', bundleBtn ? 'Found' : 'Missing'); // Removed for production

// Step 3: Check if bundle modal exists
const bundleModal = document.getElementById('bundleModal');
// console.log('3. Bundle Modal:', bundleModal ? 'Found' : 'Missing'); // Removed for production

// Step 4: Check if course checkboxes exist
const courseCheckboxes = document.querySelectorAll('.bundle-course-checkbox');
// console.log('4. Course Checkboxes:', courseCheckboxes.length); // Removed for production

// Step 5: Test bundle options API call
async function testBundleOptions() {
// console.log('5. Testing Bundle Options API...'); // Removed for production
    
    // Get first two available courses
    const availableCourses = Array.from(courseCheckboxes).slice(0, 2);
    if (availableCourses.length < 2) {
// console.log('❌ Need at least 2 courses for bundle test'); // Removed for production
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
    
// console.log('   Test Data:', testData); // Removed for production
    
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
        
// console.log('   Response Status:', response.status); // Removed for production
        
        if (response.ok) {
            const data = await response.json();
// console.log('   ✅ Bundle Options Success:', data.success); // Removed for production
            if (data.success) {
// console.log('   ✅ HTML Length:', data.html?.length || 0); // Removed for production
// console.log('   ✅ Total Price:', data.total_price); // Removed for production
            } else {
// console.log('   ❌ Error:', data.message); // Removed for production
            }
        } else {
            const errorText = await response.text();
// console.log('   ❌ HTTP Error:', response.status); // Removed for production
// console.log('   ❌ Error Details:', errorText.substring(0, 200)); // Removed for production
        }
    } catch (error) {
// console.log('   ❌ Network Error:', error.message); // Removed for production
    }
}

// Step 6: Test bundle submission API call
async function testBundleSubmission() {
// console.log('6. Testing Bundle Submission API...'); // Removed for production
    
    const availableCourses = Array.from(courseCheckboxes).slice(0, 2);
    if (availableCourses.length < 2) {
// console.log('❌ Need at least 2 courses for bundle test'); // Removed for production
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
    
// console.log('   Test Data:', testData); // Removed for production
    
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
        
// console.log('   Response Status:', response.status); // Removed for production
        
        if (response.ok) {
            const data = await response.json();
// console.log('   ✅ Bundle Submission Success:', data.success); // Removed for production
            if (data.success) {
// console.log('   ✅ Payments Created:', data.payments_created); // Removed for production
// console.log('   ✅ Bundle ID:', data.bundle_id); // Removed for production
            } else {
// console.log('   ❌ Error:', data.message); // Removed for production
            }
        } else {
            const errorText = await response.text();
// console.log('   ❌ HTTP Error:', response.status); // Removed for production
// console.log('   ❌ Error Details:', errorText.substring(0, 200)); // Removed for production
        }
    } catch (error) {
// console.log('   ❌ Network Error:', error.message); // Removed for production
    }
}

// Run the tests
testBundleOptions().then(() => {
// console.log(''); // Removed for production
    return testBundleSubmission();
}).then(() => {
// console.log(''); // Removed for production
// console.log('🔍 Bundle Payment Diagnostic Complete!'); // Removed for production
// console.log(''); // Removed for production
// console.log('📋 Next Steps:'); // Removed for production
// console.log('1. If CSRF token is missing, check the meta tag in <head>'); // Removed for production
// console.log('2. If bundle button is missing, check if user is logged in'); // Removed for production
// console.log('3. If API calls fail, check browser Network tab for details'); // Removed for production
// console.log('4. If everything works here but not in UI, check JavaScript console for errors'); // Removed for production
});

// Export functions for manual testing
window.testBundleOptions = testBundleOptions;
window.testBundleSubmission = testBundleSubmission;