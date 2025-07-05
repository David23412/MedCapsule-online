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
    @if(Auth::user()->is_admin)
        <div class="text-muted mb-3">
            <i class="fas fa-info-circle me-1"></i> Admin: All payment methods visible
        </div>
    @endif
    
    <!-- Payment Method Tabs -->
    <ul class="nav nav-tabs nav-fill mb-0" id="paymentMethodTabs" role="tablist">
        @if(config('payment.vodafone_cash.enabled', true) || Auth::user()->is_admin)
            <li class="nav-item" role="presentation">
                <button class="nav-link active payment-tab-vodafone py-3" id="vodafone-tab" data-bs-toggle="tab" data-bs-target="#vodafone-panel" type="button" role="tab">
                    <i class="fas fa-mobile-alt me-2"></i>Vodafone Cash
                    @if(Auth::user()->is_admin && !config('payment.vodafone_cash.enabled', true))
                        <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem;">Disabled</span>
                    @endif
                </button>
            </li>
        @endif
        
        @if(config('payment.fawry.enabled', true) || Auth::user()->is_admin)
            <li class="nav-item" role="presentation">
                <button class="nav-link payment-tab-fawry py-3" id="fawry-tab" data-bs-toggle="tab" data-bs-target="#fawry-panel" type="button" role="tab">
                    <i class="fas fa-store me-2"></i>Fawry
                    @if(Auth::user()->is_admin && !config('payment.fawry.enabled', true))
                        <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem;">Disabled</span>
                    @endif
                </button>
            </li>
        @endif
        
        <li class="nav-item" role="presentation">
            <button class="nav-link payment-tab-bank py-3" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank-panel" type="button" role="tab">
                <i class="fas fa-university me-2"></i>Bank Transfer
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
        
        <!-- Bank Transfer Panel -->
        <div class="tab-pane fade" id="bank-panel" role="tabpanel">
            <div class="d-flex align-items-center mb-3">
                <div style="color: #c13584;" class="me-3"><i class="fas fa-university fa-2x"></i></div>
                <div class="fw-bold fs-5">Transfer {{ $course->price }} EGP to:</div>
            </div>
            <div class="d-flex align-items-center my-3">
                <code class="bg-white py-2 px-3 rounded border flex-grow-1 me-2 fs-5">{{ config('payment.bank.number', 'BANK-XXXXXXXXX') }}</code>
                <button class="btn btn-lg text-secondary copy-btn" data-clipboard="{{ config('payment.bank.number', 'BANK-XXXXXXXXX') }}">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="text-success fs-6">
                <i class="fas fa-check-circle me-2"></i>Auto-verify with <code>BNK</code> followed by your transaction number
            </div>
        </div>
    </div>
    
    <!-- Payment Form -->
    <form id="coursePaymentForm" class="mb-0" method="POST" action="{{ route('payments.submit-proof', ['course' => $course->id]) }}" data-course-id="{{ $course->id }}" onsubmit="
        event.preventDefault();
        
        // Disable submit button
        const submitBtn = this.querySelector('#submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
        }

        // Create success message
        const container = document.createElement('div');
        container.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.98); display: flex; align-items: center; justify-content: center; z-index: 999998; backdrop-filter: blur(5px);';
        container.innerHTML = `
            <div style='text-align: center; padding: 50px; background: white; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); transform: scale(0.8); opacity: 0; animation: modal-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;'>
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
                <div style='color: #28a745; font-size: 140px; animation: checkmark 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;'>
                    <i class='fas fa-check-circle' style='filter: drop-shadow(0 6px 8px rgba(40, 167, 69, 0.2));'></i>
                </div>
                <h3 style='margin-top: 30px; color: #28a745; font-size: 32px; font-weight: bold; animation: text-fade 0.6s ease forwards 0.6s; opacity: 0; text-shadow: 0 2px 4px rgba(40, 167, 69, 0.1);'>
                    Amazing! Payment Submitted! 🎉
                </h3>
                <p style='margin-top: 15px; color: #666; font-size: 18px; animation: text-fade 0.6s ease forwards 0.8s; opacity: 0;'>
                    Get ready to start learning! We'll verify it shortly.
                </p>
                <div style='margin-top: 25px; animation: text-fade 0.6s ease forwards 1s; opacity: 0;'>
                    <span style='
                        background: linear-gradient(90deg, #e8f5e9, #c8e6c9, #e8f5e9);
                        background-size: 200% 100%;
                        color: #28a745;
                        padding: 12px 24px;
                        border-radius: 50px;
                        font-size: 18px;
                        font-weight: 500;
                        display: inline-block;
                        animation: shine 2s linear infinite, pulse 2s ease-in-out infinite;
                    '>
                        <i class='fas fa-bolt'></i> Unlocking your course...
                    </span>
                </div>
            </div>
        `;

        // Send form data
        const formData = new FormData(this);
        fetch(this.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
            },
            body: new URLSearchParams(formData)
        });

        // Show success message immediately
        document.body.appendChild(container);

        // Hide modal
        const modal = document.querySelector('.modal');
        if (modal) modal.style.display = 'none';

        // Redirect after animations complete
        setTimeout(() => {
            window.location.href = '/courses';
        }, 2500);
    ">
        @csrf
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
    
    /* Bank Transfer Tab Styles */
    .payment-tab-bank {
        color: #c13584;
    }
    
    .payment-tab-bank:hover, .payment-tab-bank.active {
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