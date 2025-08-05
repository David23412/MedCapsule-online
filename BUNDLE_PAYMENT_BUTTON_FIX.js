/**
 * 🔥 LETHAL BUNDLE PAYMENT BUTTON FIX 🔥
 * AGGRESSIVE JAVASCRIPT TO ENSURE BUTTONS SHOW PENDING STATE
 */

// Enhanced function to update course buttons to pending state
function updateCourseButtonsToPendingAggressive(courseIds) {
    console.log('🔥 AGGRESSIVE BUTTON UPDATE: Starting for courses:', courseIds);
    
    courseIds.forEach(courseId => {
        console.log(`🎯 Processing course ID: ${courseId}`);
        
        // Store pending state in localStorage FIRST
        localStorage.setItem(`payment_pending_${courseId}`, JSON.stringify({
            timestamp: Date.now(),
            status: 'pending',
            type: 'bundle'
        }));
        console.log(`💾 Stored pending state in localStorage for course: ${courseId}`);
        
        // Try multiple selectors to find the button
        const selectors = [
            `#paymentBtn_${courseId}`,
            `[data-course-id="${courseId}"]`,
            `.show-payment-modal[data-course-id="${courseId}"]`,
            `button[data-course-id="${courseId}"]`
        ];
        
        let button = null;
        
        // Search in current window
        for (const selector of selectors) {
            button = document.querySelector(selector);
            if (button) {
                console.log(`✅ Found button with selector: ${selector}`);
                break;
            }
        }
        
        // Search in parent window if not found
        if (!button && window.parent && window.parent !== window) {
            for (const selector of selectors) {
                try {
                    button = window.parent.document.querySelector(selector);
                    if (button) {
                        console.log(`✅ Found button in parent window with selector: ${selector}`);
                        break;
                    }
                } catch (e) {
                    console.log('Cannot access parent window:', e.message);
                }
            }
        }
        
        // Search in top window if still not found
        if (!button && window.top && window.top !== window) {
            for (const selector of selectors) {
                try {
                    button = window.top.document.querySelector(selector);
                    if (button) {
                        console.log(`✅ Found button in top window with selector: ${selector}`);
                        break;
                    }
                } catch (e) {
                    console.log('Cannot access top window:', e.message);
                }
            }
        }
        
        if (button) {
            console.log(`🔄 Updating button for course ${courseId} to pending state`);
            
            // Update button appearance
            button.innerHTML = '<i class="fas fa-clock"></i><span style="color: #c2410c; margin-left: 8px;">Payment Pending</span>';
            
            // Update button classes
            button.classList.remove('btn-primary', 'show-payment-modal', 'enroll');
            button.classList.add('btn-warning');
            
            // Disable button
            button.disabled = true;
            button.style.cursor = 'default';
            button.style.pointerEvents = 'none';
            
            // Remove click event listeners
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            console.log(`✅ Successfully updated button for course ${courseId}`);
        } else {
            console.log(`❌ Could not find button for course ${courseId} - but localStorage is set for page reload`);
        }
    });
    
    console.log('🏁 AGGRESSIVE BUTTON UPDATE: Complete');
}

// Enhanced function to check and apply pending states from localStorage
function applyPendingStatesFromLocalStorage() {
    console.log('🔍 AGGRESSIVE PENDING CHECK: Starting localStorage scan...');
    
    Object.keys(localStorage).forEach(key => {
        if (key.startsWith('payment_pending_')) {
            const courseId = key.replace('payment_pending_', '');
            console.log(`📋 Found pending payment for course: ${courseId}`);
            
            try {
                const pendingData = JSON.parse(localStorage.getItem(key));
                const timeDiff = Date.now() - pendingData.timestamp;
                
                // Clear old pending states (older than 24 hours)
                if (timeDiff > 24 * 60 * 60 * 1000) {
                    console.log(`🗑️ Clearing old pending state for course: ${courseId}`);
                    localStorage.removeItem(key);
                    return;
                }
                
                console.log(`⏰ Pending state age: ${Math.round(timeDiff / 1000 / 60)} minutes`);
                
            } catch (e) {
                // Handle old format (just 'true' string)
                console.log(`📝 Converting old format pending state for course: ${courseId}`);
            }
            
            // Find and update the button
            const button = document.querySelector(`#paymentBtn_${courseId}`);
            
            if (button) {
                console.log(`🎯 Found button for course: ${courseId}`);
                
                // Check if the server-side state shows the user is already enrolled
                const isAlreadyPending = button.hasAttribute('disabled');
                const buttonText = button.textContent.trim();
                
                console.log(`Button state - disabled: ${isAlreadyPending}, text: "${buttonText}"`);
                
                // If server says user is enrolled, clear localStorage
                if (buttonText.includes('Access Course') || buttonText.includes('Continue Learning') || button.href) {
                    console.log(`🗑️ User already enrolled, clearing localStorage for course: ${courseId}`);
                    localStorage.removeItem(key);
                    return;
                }
                
                // If not already pending on server side, update button
                if (!isAlreadyPending && !buttonText.includes('Payment Pending')) {
                    console.log(`⏳ Updating button to pending state for course: ${courseId}`);
                    button.innerHTML = '<i class="fas fa-clock"></i><span style="color: #c2410c; margin-left: 8px;">Payment Pending</span>';
                    button.classList.remove('btn-primary', 'show-payment-modal', 'enroll');
                    button.classList.add('btn-warning');
                    button.disabled = true;
                    button.style.cursor = 'default';
                    button.style.pointerEvents = 'none';
                } else {
                    console.log(`✅ Button already in correct pending state for course: ${courseId}`);
                }
            } else {
                console.log(`❌ Button not found for course: ${courseId} - may be on different page`);
            }
        }
    });
    
    console.log('🏁 AGGRESSIVE PENDING CHECK: Complete');
}

// Enhanced bundle payment form handler
function initializeBundlePaymentFormAggressive() {
    console.log('🔍 AGGRESSIVE FORM INIT: Looking for bundle payment form...');
    
    const form = document.getElementById('bundlePaymentForm');
    if (!form) {
        console.log('❌ Bundle payment form not found');
        return;
    }
    
    console.log('✅ Bundle payment form found, adding aggressive handler');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('🚀 AGGRESSIVE FORM SUBMIT: Bundle payment form submitted!');
        
        // Disable submit button immediately
        const submitBtn = this.querySelector('#submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        }
        
        // Get form data
        const transactionId = document.getElementById('transactionId').value.trim();
        
        if (!transactionId) {
            alert('Please enter your transaction reference number.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Bundle Payment';
            }
            return;
        }
        
        // Get course IDs
        const coursesInput = document.querySelector('input[name="courses"]');
        const courseIds = coursesInput.value.split(',').map(id => parseInt(id));
        
        // Get current payment method
        function getCurrentPaymentMethod() {
            const activeTab = document.querySelector('.nav-link.active');
            if (activeTab.id === 'vodafone-tab') return 'vodafone_cash';
            if (activeTab.id === 'fawry-tab') return 'fawry';
            if (activeTab.id === 'instapay-tab') return 'instapay';
            return 'vodafone_cash'; // default
        }
        
        // Prepare submission data
        const submissionData = {
            courses: courseIds,
            payment_method: getCurrentPaymentMethod(),
            transaction_id: transactionId,
            total_amount: parseFloat(document.querySelector('input[name="total_amount"]').value)
        };
        
        console.log('🚀 AGGRESSIVE SUBMIT: Posting to /payment/submit-bundle-proof with data:', submissionData);
        
        // Submit the form
        fetch('/payment/submit-bundle-proof', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(submissionData)
        })
        .then(response => {
            console.log('📡 AGGRESSIVE SUBMIT: Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 AGGRESSIVE SUBMIT: Response data:', data);
            
            if (data.success) {
                console.log('✅ AGGRESSIVE SUBMIT: Bundle payment successful!');
                
                // IMMEDIATELY update course buttons to show pending state
                updateCourseButtonsToPendingAggressive(courseIds);
                
                // Show success message
                showSuccessMessageAggressive();
                
            } else {
                console.log('❌ AGGRESSIVE SUBMIT: Bundle payment failed:', data.message);
                showErrorMessageAggressive(data.message || 'Bundle payment submission failed. Please try again.');
                
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit Bundle Payment';
                }
            }
        })
        .catch(error => {
            console.error('❌ AGGRESSIVE SUBMIT: Error:', error);
            showErrorMessageAggressive('An error occurred while submitting your bundle payment. Please try again.');
            
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Bundle Payment';
            }
        });
    });
}

// Enhanced success message
function showSuccessMessageAggressive() {
    console.log('🎉 AGGRESSIVE SUCCESS: Showing success message');
    
    // Create success overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.98);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        backdrop-filter: blur(5px);
    `;
    
    overlay.innerHTML = `
        <div style="text-align: center; padding: 50px; background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1);">
            <div style="color: #28a745; font-size: 120px;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 style="margin-top: 30px; color: #28a745; font-size: 28px; font-weight: bold;">
                🎉 Bundle Payment Submitted Successfully!
            </h3>
            <p style="margin-top: 15px; color: #666; font-size: 16px;">
                We've created separate payment records for each course.<br>
                Your course buttons now show "Payment Pending" status.<br>
                We'll verify your payment within 24 hours.
            </p>
            <div style="margin-top: 25px;">
                <button onclick="location.reload()" style="
                    background: #28a745;
                    color: white;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 50px;
                    font-size: 16px;
                    font-weight: 500;
                    cursor: pointer;
                ">
                    <i class="fas fa-refresh"></i> Refresh Page
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    // Auto-refresh after 3 seconds
    setTimeout(() => {
        window.location.reload();
    }, 3000);
}

// Enhanced error message
function showErrorMessageAggressive(message) {
    console.log('❌ AGGRESSIVE ERROR: Showing error message:', message);
    
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.98);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        backdrop-filter: blur(5px);
    `;
    
    overlay.innerHTML = `
        <div style="text-align: center; padding: 50px; background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1);">
            <div style="color: #dc3545; font-size: 120px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="margin-top: 30px; color: #dc3545; font-size: 28px; font-weight: bold;">
                Bundle Payment Failed
            </h3>
            <p style="margin-top: 15px; color: #666; font-size: 16px;">
                ${message}
            </p>
            <div style="margin-top: 25px;">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" style="
                    background: #dc3545;
                    color: white;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 50px;
                    font-size: 16px;
                    font-weight: 500;
                    cursor: pointer;
                ">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 AGGRESSIVE INIT: DOM loaded, initializing bundle payment system');
    
    // Apply pending states from localStorage
    applyPendingStatesFromLocalStorage();
    
    // Initialize bundle payment form if present
    initializeBundlePaymentFormAggressive();
    
    console.log('✅ AGGRESSIVE INIT: Bundle payment system initialized');
});

// Also run when page becomes visible (for tab switching)
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        console.log('👁️ AGGRESSIVE VISIBILITY: Page became visible, checking pending states');
        applyPendingStatesFromLocalStorage();
    }
});

console.log('🔥 LETHAL BUNDLE PAYMENT BUTTON FIX LOADED 🔥');