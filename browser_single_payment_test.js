/**
 * 🔍 BROWSER SINGLE PAYMENT DIAGNOSTIC SCRIPT 🔍
 * Copy and paste this into your browser console on a course page with payment modal
 */

// console.log('🔍 Starting Single Payment Diagnostic...'); // Removed for production

// Step 1: Check if payment form exists
const form = document.getElementById('coursePaymentForm');
// console.log('1. Payment Form:', form ? 'Found' : 'Missing'); // Removed for production

// Step 2: Check if payment method tabs exist
const tabs = document.querySelectorAll('#paymentMethodTabs .nav-link');
// console.log('2. Payment Method Tabs:', tabs.length); // Removed for production

// Step 3: Check which tab is active
const activeTab = document.querySelector('#paymentMethodTabs .nav-link.active');
// console.log('3. Active Tab:', activeTab ? activeTab.id : 'None'); // Removed for production

// Step 4: Test getCurrentPaymentMethod function
function getCurrentPaymentMethod() {
    const activeTab = document.querySelector('#paymentMethodTabs .nav-link.active');
    if (activeTab) {
        if (activeTab.id === 'vodafone-tab') return 'vodafone_cash';
        if (activeTab.id === 'fawry-tab') return 'fawry';
        if (activeTab.id === 'instapay-tab') return 'instapay';
    }
    return 'vodafone_cash'; // default
}

const detectedMethod = getCurrentPaymentMethod();
// console.log('4. Detected Payment Method:', detectedMethod); // Removed for production

// Step 5: Test form submission with payment method
async function testSinglePaymentSubmission() {
// console.log('5. Testing Single Payment Submission...'); // Removed for production
    
    if (!form) {
// console.log('❌ No form found'); // Removed for production
        return;
    }
    
    const transactionId = 'BROWSER_SINGLE_TEST_' + Date.now();
    const paymentMethod = getCurrentPaymentMethod();
    
// console.log('   Transaction ID:', transactionId); // Removed for production
// console.log('   Payment Method:', paymentMethod); // Removed for production
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
// console.log('   CSRF Token:', csrfToken ? 'Found' : 'Missing'); // Removed for production
    
    // Prepare form data
    const formData = new FormData();
    formData.append('transaction_id', transactionId);
    formData.append('payment_method', paymentMethod);
    formData.append('_token', csrfToken);
    
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });
        
// console.log('   Response Status:', response.status); // Removed for production
        
        if (response.ok) {
            const data = await response.json();
// console.log('   ✅ Single Payment Success:', data.success); // Removed for production
            if (data.success) {
// console.log('   ✅ Payment ID:', data.payment_id); // Removed for production
// console.log('   ✅ Message:', data.message); // Removed for production
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

// Step 6: Check if form has proper event listeners
// console.log('6. Form Event Listeners:', form ? 'Checking...' : 'No form'); // Removed for production
if (form) {
    // Check if our custom submit handler is attached
    const hasCustomHandler = form.onsubmit !== null;
// console.log('   Custom Submit Handler:', hasCustomHandler ? 'Found' : 'Missing'); // Removed for production
}

// Run the test
testSinglePaymentSubmission().then(() => {
// console.log(''); // Removed for production
// console.log('🔍 Single Payment Diagnostic Complete!'); // Removed for production
// console.log(''); // Removed for production
// console.log('📋 Next Steps:'); // Removed for production
// console.log('1. If payment method is not detected correctly, check tab switching'); // Removed for production
// console.log('2. If CSRF token is missing, check meta tag in <head>'); // Removed for production
// console.log('3. If form submission fails, check the form action URL'); // Removed for production
// console.log('4. Check browser Network tab for actual request details'); // Removed for production
});

// Export function for manual testing
window.testSinglePaymentSubmission = testSinglePaymentSubmission;
window.getCurrentPaymentMethod = getCurrentPaymentMethod;