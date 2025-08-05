<!-- Modal Header -->
<meta name="csrf-token" content="{{ csrf_token() }}">

@php
    // Calculate with rounded prices for each course
    $individualTotal = $courses->sum(function($course) {
        return floor($course->price) + 0.99;
    });
    
    // Round the original total to .99 as well
    $roundedTotal = floor($individualTotal) + 0.99;
    
    $courseCount = $courses->count();
    $discount = 0;
    if ($courseCount === 2) $discount = 50;
    elseif ($courseCount === 3) $discount = 75;
    elseif ($courseCount >= 4) $discount = 100;
    
    // Round final total to .99 as well
    $finalTotal = floor($roundedTotal - $discount) + 0.99;
@endphp

<div class="modal-header py-3">
    <h5 class="modal-title fs-5" id="bundlePaymentModalLabel">
        <i class="fas fa-shopping-cart me-2"></i>Bundle Payment
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<!-- Modal Body -->
<div class="modal-body p-4">
    <!-- Bundle Summary Box -->
    <div class="card mb-4 border shadow-sm">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0 fs-5">Bundle Summary</h6>
        </div>
        <div class="card-body p-3">
            @foreach($courses as $course)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="mb-1">{{ $course->name }}</h6>
                    </div>
                    <div class="text-primary">{{ number_format(floor($course->price) + 0.99, 2) }} EGP</div>
                </div>
            @endforeach
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>Original Price:</span>
                <span>{{ number_format($roundedTotal, 2) }} EGP</span>
            </div>
            @if($discount > 0)
            <div class="d-flex justify-content-between align-items-center mb-1 text-success">
                <span>Bundle Discount:</span>
                <span>- {{ number_format($discount, 2) }} EGP</span>
            </div>
            @endif
            <div class="d-flex justify-content-between align-items-center">
                <div class="h5 text-primary mb-0">Total: {{ number_format($finalTotal, 2) }} EGP</div>
            </div>
        </div>
    </div>
    
    <!-- Admin Note -->
    @if(Auth::user()->role === 'admin')
        <div class="text-muted mb-3">
            <i class="fas fa-info-circle me-1"></i> Admin: All payment methods visible
        </div>
    @endif
    
    <!-- Payment Method Tabs -->
    <ul class="nav nav-tabs nav-fill mb-0" id="paymentMethodTabs" role="tablist">
        @if(config('payment.vodafone_cash.enabled', true) || Auth::user()->role === 'admin')
            <li class="nav-item" role="presentation">
                <button class="nav-link active payment-tab-vodafone py-3" id="vodafone-tab" data-bs-toggle="tab" data-bs-target="#vodafone-panel" type="button" role="tab">
                    <i class="fas fa-mobile-alt me-2"></i>Vodafone Cash
                    @if(Auth::user()->role === 'admin' && !config('payment.vodafone_cash.enabled', true))
                        <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem;">Disabled</span>
                    @endif
                </button>
            </li>
        @endif
        
        @if(config('payment.fawry.enabled', true) || Auth::user()->role === 'admin')
            <li class="nav-item" role="presentation">
                <button class="nav-link payment-tab-fawry py-3" id="fawry-tab" data-bs-toggle="tab" data-bs-target="#fawry-panel" type="button" role="tab">
                    <i class="fas fa-store me-2"></i>Fawry
                    @if(Auth::user()->role === 'admin' && !config('payment.fawry.enabled', true))
                        <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem;">Disabled</span>
                    @endif
                </button>
            </li>
        @endif
        
        <li class="nav-item" role="presentation">
            <button class="nav-link payment-tab-instapay py-3" id="instapay-tab" data-bs-toggle="tab" data-bs-target="#instapay-panel" type="button" role="tab">
                <i class="fas fa-credit-card me-2"></i>InstaPay
            </button>
        </li>
    </ul>
    
    <!-- Payment Method Details -->
    <div class="tab-content border border-top-0 rounded-bottom p-4 mb-4" id="paymentMethodContent">
        <!-- Vodafone Cash Panel -->
        <div class="tab-pane fade show active" id="vodafone-panel" role="tabpanel">
            <div class="d-flex align-items-center mb-3">
                <div class="text-danger me-3"><i class="fas fa-mobile-alt fa-2x"></i></div>
                <div class="fw-bold fs-5">Send {{ number_format($finalTotal, 2) }} EGP to:</div>
            </div>
            <div class="d-flex align-items-center my-3">
                <code class="bg-white py-2 px-3 rounded border flex-grow-1 me-2 fs-5">{{ config('payment.vodafone_cash.number', '01XXXXXXXXX') }}</code>
                <button class="btn btn-lg text-secondary copy-btn" data-clipboard="{{ config('payment.vodafone_cash.number', '01XXXXXXXXX') }}">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="text-success fs-6">
                <i class="fas fa-check-circle me-2"></i>Auto-verify with <code>VC</code> followed by your transaction number
            </div>
        </div>
        
        <!-- Fawry Panel -->
        <div class="tab-pane fade" id="fawry-panel" role="tabpanel">
            <div class="d-flex align-items-center mb-3">
                <div class="text-warning me-3"><i class="fas fa-store fa-2x"></i></div>
                <div class="fw-bold fs-5">Pay {{ number_format($finalTotal, 2) }} EGP to:</div>
            </div>
            <div class="d-flex align-items-center my-3">
                <code class="bg-white py-2 px-3 rounded border flex-grow-1 me-2 fs-5">{{ config('payment.fawry.number', 'FAWRY-XXXXXXXXX') }}</code>
                <button class="btn btn-lg text-secondary copy-btn" data-clipboard="{{ config('payment.fawry.number', 'FAWRY-XXXXXXXXX') }}">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="text-success fs-6">
                <i class="fas fa-check-circle me-2"></i>Auto-verify with <code>FWY</code> followed by your transaction number
            </div>
        </div>
        
        <!-- InstaPay Panel -->
        <div class="tab-pane fade" id="instapay-panel" role="tabpanel">
            <div class="d-flex align-items-center mb-3">
                <div style="color: #c13584;" class="me-3"><i class="fas fa-credit-card fa-2x"></i></div>
                <div class="fw-bold fs-5">Pay {{ number_format($finalTotal, 2) }} EGP via:</div>
            </div>
            <div class="d-flex align-items-center my-3">
                <code class="bg-white py-2 px-3 rounded border flex-grow-1 me-2 fs-5">{{ config('payment.instapay.number', 'INSTAPAY-XXXXXXXXX') }}</code>
                <button class="btn btn-lg text-secondary copy-btn" data-clipboard="{{ config('payment.instapay.number', 'INSTAPAY-XXXXXXXXX') }}">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="text-success fs-6">
                <i class="fas fa-check-circle me-2"></i>Auto-verify with <code>IP</code> followed by your transaction number
            </div>
        </div>
    </div>
    
    <!-- Payment Form -->
    <form id="bundlePaymentForm" class="mb-0">
        @csrf
        <input type="hidden" name="courses" value="{{ $courses->pluck('id')->implode(',') }}">
        <input type="hidden" name="total_amount" value="{{ $finalTotal }}">
        
        <!-- Transaction ID Input -->
        <div class="mb-4">
            <label for="transactionId" class="form-label">Transaction Code</label>
            <div class="input-group">
                <input type="text" class="form-control form-control-lg" id="transactionId" name="transaction_id" placeholder="Enter your transaction reference number" required>
            </div>
            <div class="form-text">Enter the same reference number for all courses in this bundle</div>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
            Submit Bundle Payment
        </button>
    </form>
</div>

<!-- Modal Footer -->
<div class="modal-footer p-3">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>

<!-- Styles -->
<style>
    /* General Styles */
    .modal-content {
        font-size: 16px;
    }
    
    /* Tab Navigation Styles */
    .nav-tabs .nav-link {
        padding: 0.7rem;
        border-radius: 0;
        font-weight: 500;
        font-size: 1rem;
    }
    
    /* Vodafone Cash Tab Styles */
    .payment-tab-vodafone {
        color: #e53935;
    }
    
    .payment-tab-vodafone:hover, .payment-tab-vodafone.active {
        background-color: #ffebee !important;
        color: #c62828 !important;
        border-color: #ffcdd2 !important;
    }
    
    /* Fawry Tab Styles */
    .payment-tab-fawry {
        color: #f57c00;
    }
    
    .payment-tab-fawry:hover, .payment-tab-fawry.active {
        background-color: #fff8e1 !important;
        color: #ef6c00 !important;
        border-color: #ffe082 !important;
    }
    
    /* InstaPay Tab Styles */
    .payment-tab-instapay {
        color: #c13584;
    }
    
    .payment-tab-instapay:hover, .payment-tab-instapay.active {
        background-color: rgba(193, 53, 132, 0.1) !important;
        color: #833ab4 !important;
        border-color: rgba(193, 53, 132, 0.3) !important;
    }
    
    /* Code Styles */
    code {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 1rem;
        letter-spacing: 0.5px;
    }
    
    /* Form Elements */
    .form-control {
        padding: 0.75rem 1rem;
    }
    
    /* Copy Button */
    .copy-btn {
        padding: 0.5rem 1rem;
    }
    
    /* Improved Readability */
    .text-muted {
        color: #5a6268 !important;
    }
    
    /* Alert Styles */
    .alert {
        margin-bottom: 1rem;
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: none;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: none;
    }
    
    /* Button Styles */
    .btn-primary {
        background-color: #007bff;
        border: none;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .btn-primary:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
</style>

<!-- Payment Form JavaScript -->
<script>
// Function to get current payment method from active tab
function getCurrentPaymentMethod() {
    const activeTab = document.querySelector('.nav-link.active');
    if (activeTab.id === 'vodafone-tab') return 'vodafone_cash';
    if (activeTab.id === 'fawry-tab') return 'fawry';
    if (activeTab.id === 'instapay-tab') return 'instapay';
    return 'vodafone_cash'; // default
}

function showSuccessMessage() {
    // Create success message container
    const container = document.createElement('div');
    container.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.98);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999998;
        backdrop-filter: blur(5px);
    `;

    // Add success message content
    container.innerHTML = `
        <style>
            @keyframes modal-in {
                0% { transform: scale(0.8); opacity: 0; }
                100% { transform: scale(1); opacity: 1; }
            }
            @keyframes checkmark {
                0% { transform: scale(0.5) rotate(-45deg); opacity: 0; }
                40% { transform: scale(1.2) rotate(10deg); }
                70% { transform: scale(0.9) rotate(-5deg); }
                100% { transform: scale(1) rotate(0); opacity: 1; }
            }
            @keyframes text-fade {
                0% { opacity: 0; transform: translateY(15px); }
                100% { opacity: 1; transform: translateY(0); }
            }
            @keyframes shine {
                0% { background-position: -100% 0; }
                100% { background-position: 200% 0; }
            }
            @keyframes pulse {
                0% { transform: scale(1); opacity: 1; }
                50% { transform: scale(1.05); opacity: 0.8; }
                100% { transform: scale(1); opacity: 1; }
            }
        </style>
        <div style="text-align: center; padding: 50px; background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); transform: scale(0.8); opacity: 0; animation: modal-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;">
            <div style="color: #28a745; font-size: 140px; animation: checkmark 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;">
                <i class="fas fa-check-circle" style="filter: drop-shadow(0 6px 8px rgba(40, 167, 69, 0.2));"></i>
            </div>
            <h3 style="margin-top: 30px; color: #28a745; font-size: 32px; font-weight: bold; animation: text-fade 0.6s ease forwards 0.6s; opacity: 0; text-shadow: 0 2px 4px rgba(40, 167, 69, 0.1);">
                Amazing! Bundle Payment Submitted! 🎉
            </h3>
            <p style="margin-top: 15px; color: #666; font-size: 18px; animation: text-fade 0.6s ease forwards 0.8s; opacity: 0;">
                We've created separate payment records for each course!<br>Get ready to start learning! We'll verify it shortly.
            </p>
            <div style="margin-top: 25px; animation: text-fade 0.6s ease forwards 1s; opacity: 0;">
                <span style="
                    background: linear-gradient(90deg, #e8f5e9, #c8e6c9, #e8f5e9);
                    background-size: 200% 100%;
                    color: #28a745;
                    padding: 12px 24px;
                    border-radius: 50px;
                    font-size: 18px;
                    font-weight: 500;
                    display: inline-block;
                    animation: shine 2s linear infinite, pulse 2s ease-in-out infinite;
                ">
                    <i class="fas fa-bolt"></i> Unlocking your courses...
                </span>
            </div>
        </div>
    `;

    document.body.appendChild(container);

    // Hide modal if it exists
    const modal = document.querySelector('.modal');
    if (modal) modal.style.display = 'none';

    // Redirect after animations complete
    setTimeout(() => {
        // Close modal first
        const modal = document.querySelector('.modal');
        if (modal) {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
        
        // Reload the current page to show updated pending status from database
        window.location.reload();
    }, 2500);
}

function showErrorMessage(message) {
    // Create error message container
    const container = document.createElement('div');
    container.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.98);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999998;
        backdrop-filter: blur(5px);
    `;

    // Add error message content
    container.innerHTML = `
        <div style="text-align: center; padding: 50px; background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1);">
            <div style="color: #dc3545; font-size: 140px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="margin-top: 30px; color: #dc3545; font-size: 32px; font-weight: bold;">
                Bundle Payment Submission Failed
            </h3>
            <p style="margin-top: 15px; color: #666; font-size: 18px;">
                ${message}
            </p>
            <div style="margin-top: 25px;">
                <button onclick="location.reload()" style="
                    background: #dc3545;
                    color: white;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 50px;
                    font-size: 18px;
                    font-weight: 500;
                    cursor: pointer;
                ">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(container);
}

// 🔥 AGGRESSIVE FUNCTION TO UPDATE COURSE BUTTONS TO PENDING STATE 🔥
function updateCourseButtonsToPending() {
    const coursesInput = document.querySelector('input[name="courses"]');
    if (!coursesInput) {
        console.log('❌ Could not find courses input field');
        return;
    }
    
    const courseIds = coursesInput.value.split(',');
    console.log('🔥 AGGRESSIVE UPDATE: Updating course buttons to pending for courses:', courseIds);
    
    courseIds.forEach(courseId => {
        console.log(`🎯 Processing course ID: ${courseId}`);
        
        // Store pending state in localStorage with timestamp
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
        let windowContext = 'current';
        
        // Search in current window
        for (const selector of selectors) {
            button = document.querySelector(selector);
            if (button) break;
        }
        
        // Search in parent window if not found
        if (!button && window.parent && window.parent !== window) {
            windowContext = 'parent';
            for (const selector of selectors) {
                try {
                    button = window.parent.document.querySelector(selector);
                    if (button) break;
                } catch (e) {
                    console.log('Cannot access parent window:', e.message);
                }
            }
        }
        
        // Search in top window if still not found
        if (!button && window.top && window.top !== window) {
            windowContext = 'top';
            for (const selector of selectors) {
                try {
                    button = window.top.document.querySelector(selector);
                    if (button) break;
                } catch (e) {
                    console.log('Cannot access top window:', e.message);
                }
            }
        }
        
        if (button) {
            console.log(`✅ Found button for course ${courseId} in ${windowContext} window - updating to pending state`);
            
            // Update button appearance with more prominent styling
            button.innerHTML = '<i class="fas fa-clock"></i><span style="color: #c2410c; margin-left: 8px; font-weight: bold;">Payment Pending</span>';
            
            // Update button classes
            button.classList.remove('btn-primary', 'show-payment-modal', 'enroll');
            button.classList.add('btn-warning');
            
            // Disable button completely
            button.disabled = true;
            button.style.cursor = 'default';
            button.style.pointerEvents = 'none';
            button.style.opacity = '0.8';
            
            // Remove all event listeners by cloning the button
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            console.log(`🎯 Successfully updated button for course ${courseId}`);
        } else {
            console.log(`❌ Could not find button for course ${courseId} - localStorage set for page reload`);
        }
    });
    
    console.log('🏁 AGGRESSIVE UPDATE: Complete - all course buttons processed');
}

// Initialize payment form handling
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Looking for bundle payment form...');
    const form = document.getElementById('bundlePaymentForm');
    if (!form) {
        console.log('❌ Bundle payment form not found! Available forms:');
        document.querySelectorAll('form').forEach((f, i) => {
            console.log(`Form ${i}:`, f.id || 'no-id', f);
        });
        return;
    }
    console.log('✅ Bundle payment form found:', form);

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('🚀 Bundle payment form submitted!');
        console.log('Form data:', new FormData(form));
        console.log('Current URL:', window.location.href);

        // Disable submit button
        const submitBtn = this.querySelector('#submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }

        // Get form data
        const formData = new FormData(form);
        const transactionId = document.getElementById('transactionId').value.trim();
        
        if (!transactionId) {
            alert('Please enter your transaction reference number.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Bundle Payment';
            }
            return;
        }

        // Convert courses string to array
        const coursesString = formData.get('courses');
        const courseIds = coursesString.split(',').map(id => parseInt(id));

        // Prepare submission data
        const submissionData = {
            courses: courseIds,
            payment_method: getCurrentPaymentMethod(),
            transaction_id: transactionId,
            total_amount: parseFloat(formData.get('total_amount'))
        };

        // Check CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        console.log('🔐 CSRF Token:', csrfToken ? 'Found' : 'Missing');
        
        // Submit the form data
        console.log('🚀 Submitting bundle payment with data:', submissionData);
        console.log('📍 Posting to URL: /payment/submit-bundle-proof');
        
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
            console.log('📡 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 AGGRESSIVE RESPONSE: Bundle payment submission response:', data);
            
            if (data.success) {
                console.log('✅ AGGRESSIVE SUCCESS: Bundle payment successful!');
                console.log('🎯 Bundle ID:', data.bundle_id);
                console.log('📊 Payments created:', data.payments_created);
                
                // IMMEDIATELY update course buttons to show pending state
                updateCourseButtonsToPending();
                
                // Show enhanced success message
                showSuccessMessage();
                
                // Also trigger a page reload after a short delay to ensure server-side state is reflected
                setTimeout(() => {
                    console.log('🔄 AGGRESSIVE RELOAD: Refreshing page to show server-side pending state');
                    window.location.reload();
                }, 2000);
                
            } else {
                console.log('❌ AGGRESSIVE FAILURE: Bundle payment failed:', data.message);
                showErrorMessage(data.message || 'Bundle payment submission failed. Please try again.');
                
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Bundle Payment';
                }
            }
        })
        .catch(error => {
            console.error('Bundle payment submission error:', error);
            
            // Show error message
            showErrorMessage('An error occurred while submitting your bundle payment. Please try again.');
            
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Bundle Payment';
            }
        });
    });
});
</script>