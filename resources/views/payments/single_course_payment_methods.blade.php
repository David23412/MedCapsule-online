<!-- Modal Header -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Animated Success Message -->
<style>
@keyframes checkmark {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}
@keyframes text-fade {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
}
.success-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.95);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 999999;
}
.success-checkmark {
    color: #28a745;
    font-size: 80px;
    animation: checkmark 0.8s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.success-text {
    opacity: 0;
    animation: text-fade 0.6s ease forwards;
    animation-delay: 0.4s;
}
</style>

<div id="successOverlay" class="success-overlay">
    <div style="text-align: center; padding: 40px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <div class="success-checkmark">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 class="success-text" style="margin-top: 20px; color: #28a745; font-size: 24px;">
            Payment Submitted Successfully!
        </h3>
        <p class="success-text" style="margin-top: 15px; color: #666; font-size: 16px;">
            We will verify it shortly.
        </p>
        <div class="success-text" style="margin-top: 20px; color: #666;">
            Redirecting in <span id="countdown">5</span> seconds...
        </div>
    </div>
</div>

<div id="successMessage" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.95); z-index: 999999; text-align: center; padding-top: 20%;">
    <i class="fas fa-check-circle" style="color: #28a745; font-size: 80px;"></i>
    <h3 style="margin-top: 20px; color: #28a745;">Payment Submitted Successfully!</h3>
    <p>We will verify it shortly.</p>
    <p>Redirecting in <span id="countdown">5</span> seconds...</p>
</div>

<!-- Modal Header -->
<div class="modal-header py-3">
    <h5 class="modal-title fs-5" id="paymentModalLabel">
        <i class="fas fa-shopping-cart me-2"></i>Course Payment
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<!-- Modal Body -->
<div class="modal-body p-4">
    <!-- Course Summary Box -->
    <div class="card mb-4 border shadow-sm">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0 fs-5">Course Summary</h6>
        </div>
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-2 fs-5">{{ $course->name }}</h6>
                    <div class="text-muted">
                        {{ $course->description ? \Illuminate\Support\Str::limit($course->description, 80) : 'Learn and master new skills' }}
                    </div>
                </div>
                <div class="h4 text-primary mb-0">{{ $course->formatted_price }}</div>
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
                <div class="fw-bold fs-5">Send {{ $course->price }} EGP to:</div>
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
                <div class="fw-bold fs-5">Pay {{ $course->price }} EGP to:</div>
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
                <div class="fw-bold fs-5">Pay {{ $course->price }} EGP via:</div>
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
                    <form id="coursePaymentForm" class="mb-0" method="POST" action="{{ route('payments.process', $course) }}" data-course-id="{{ $course->id }}">
        @csrf
        <!-- Hidden payment method field that gets updated by JavaScript -->
        <input type="hidden" id="paymentMethodField" name="payment_method" value="vodafone_cash">
        <input type="hidden" name="amount" value="{{ $course->price }}">
        
        <!-- Transaction ID Input -->
        <div class="mb-4">
            <label for="transactionId" class="form-label">Transaction Code</label>
            <div class="input-group">
                <input type="text" class="form-control form-control-lg" id="transactionId" name="transaction_id" placeholder="Enter anything" required>
            </div>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
            Submit Payment
        </button>
    </form>
</div>

<!-- Modal Footer -->
<div class="modal-footer p-3">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>

<!-- Payment Form JavaScript -->
<script>
// Function to update payment button state
function updatePaymentButton(courseId) {
    const button = document.querySelector(`#paymentBtn_${courseId}`);
    if (button) {
        button.innerHTML = '<i class="fas fa-clock"></i><span style="color: #c2410c; margin-left: 8px; font-weight: bold;">Payment Pending</span>';
        button.classList.remove('btn-primary', 'show-payment-modal', 'enroll');
        button.classList.add('btn-warning');
        button.disabled = true;
        button.style.cursor = 'default';
        button.style.pointerEvents = 'none';
        button.style.opacity = '0.8';
        
        console.log('🔄 Payment button updated to pending state for course:', courseId);
    } else {
        console.warn('⚠️ Payment button not found for course:', courseId);
    }
}

function showSuccessMessage() {
    // Store pending state and update button
    const courseId = document.getElementById('coursePaymentForm').getAttribute('data-course-id');
    if (courseId) {
        // Store pending state with timestamp
        const pendingData = {
            timestamp: Date.now(),
            courseId: courseId,
            status: 'pending'
        };
        localStorage.setItem(`payment_pending_${courseId}`, JSON.stringify(pendingData));
        
        // Update button immediately
        updatePaymentButton(courseId);
        
        // Close the payment modal
        const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
        if (paymentModal) {
            paymentModal.hide();
        }
        
        console.log('✅ Payment pending state stored for course:', courseId);
    }

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
                Amazing! Payment Submitted! 🎉
            </h3>
            <p style="margin-top: 15px; color: #666; font-size: 18px; animation: text-fade 0.6s ease forwards 0.8s; opacity: 0;">
                Get ready to start learning! We'll verify it shortly.
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
                    <i class="fas fa-bolt"></i> Unlocking your course...
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
        window.location.href = '/courses';
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
        <style>
            @keyframes modal-in {
                0% { transform: scale(0.8); opacity: 0; }
                100% { transform: scale(1); opacity: 1; }
            }
            @keyframes text-fade {
                0% { opacity: 0; transform: translateY(15px); }
                100% { opacity: 1; transform: translateY(0); }
            }
        </style>
        <div style="text-align: center; padding: 50px; background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); transform: scale(0.8); opacity: 0; animation: modal-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;">
            <div style="color: #dc3545; font-size: 140px;">
                <i class="fas fa-exclamation-triangle" style="filter: drop-shadow(0 6px 8px rgba(220, 53, 69, 0.2));"></i>
            </div>
            <h3 style="margin-top: 30px; color: #dc3545; font-size: 32px; font-weight: bold; animation: text-fade 0.6s ease forwards 0.6s; opacity: 0;">
                Payment Submission Failed
            </h3>
            <p style="margin-top: 15px; color: #666; font-size: 18px; animation: text-fade 0.6s ease forwards 0.8s; opacity: 0;">
                ${message}
            </p>
            <div style="margin-top: 25px; animation: text-fade 0.6s ease forwards 1s; opacity: 0;">
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

// Function to get current payment method from active tab
function getCurrentPaymentMethod() {
    const activeTab = document.querySelector('#paymentMethodTabs .nav-link.active');
    if (activeTab) {
        if (activeTab.id === 'vodafone-tab') return 'vodafone_cash';
        if (activeTab.id === 'fawry-tab') return 'fawry';
        if (activeTab.id === 'instapay-tab') return 'instapay';
    }
    return 'vodafone_cash'; // default
}

// Initialize payment form handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('coursePaymentForm');
    const paymentMethodField = document.getElementById('paymentMethodField');
    
    if (!form) {
        console.log('Payment form not found');
        return;
    }
    console.log('Payment form found');
    
    // Update payment method field when tabs are clicked
    const paymentTabs = document.querySelectorAll('#paymentMethodTabs .nav-link');
    paymentTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            setTimeout(() => {
                const paymentMethod = getCurrentPaymentMethod();
                if (paymentMethodField) {
                    paymentMethodField.value = paymentMethod;
                    console.log('Payment method updated to:', paymentMethod);
                }
            }, 100); // Small delay to ensure tab is active
        });
    });
    
    // Set initial payment method
    if (paymentMethodField) {
        paymentMethodField.value = getCurrentPaymentMethod();
        console.log('Initial payment method set to:', paymentMethodField.value);
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Payment form submitted');

        // Disable submit button
        const submitBtn = this.querySelector('#submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }

        // Ensure payment method is up to date
        const paymentMethod = getCurrentPaymentMethod();
        if (paymentMethodField) {
            paymentMethodField.value = paymentMethod;
        }
        console.log('Final payment method:', paymentMethod);

        // Submit the form data (payment method is now in the hidden field)
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Payment submission response:', data);
            
            if (data.success) {
                // Show success message
                showSuccessMessage();
            } else {
                // Show error message
                showErrorMessage(data.message || 'An error occurred while submitting your payment. Please try again.');
                
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Payment';
                }
            }
        })
        .catch(error => {
            console.error('Payment submission error:', error);
            
            // Show error message
            showErrorMessage('An error occurred while submitting your payment. Please try again.');
            
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Payment';
            }
        });
    });
});
</script>

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