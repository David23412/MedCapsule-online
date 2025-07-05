<div class="modal-header border-0 pb-0">
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body px-4 pt-2">
    <div class="text-center mb-4">
        <h4 class="modal-title fw-bold">Vodafone Cash Payment</h4>
        <p class="text-muted">Complete your payment to access the course</p>
    </div>
    
    <div class="course-premium-card mb-4">
        <div class="d-flex align-items-center">
            <div class="course-image me-3">
                <img src="{{ $course->image }}" alt="{{ $course->name }}" class="img-fluid rounded shadow-sm" style="max-width: 80px; object-fit: cover;" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgdmlld0JveD0iMCAwIDgwIDgwIiBmaWxsPSJub25lIj48cmVjdCB3aWR0aD0iODAiIGhlaWdodD0iODAiIGZpbGw9IiNlOWVjZWYiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTRweCIgZmlsbD0iIzZjNzU3ZCI+Q291cnNlIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
            </div>
            <div class="course-info">
                <h5 class="course-title fw-bold mb-1">{{ $course->name }}</h5>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="course-badges">
                        <span class="badge bg-primary">Premium</span>
                    </div>
                    <span class="fw-bold text-success">{{ $course->price }} EGP</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="payment-code-container bg-light p-4 rounded-3 mb-4 text-center">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0 fw-bold">Your Payment Code</h5>
            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Valid for 24 hours</span>
        </div>
        <div class="payment-code py-3 my-3 bg-white rounded-3 shadow-sm" style="letter-spacing: 2px;">
            <h2 class="display-6 fw-bold mb-2" id="paymentCode">{{ $paymentCode }}</h2>
            <button class="btn btn-sm btn-primary copy-btn" onclick="copyPaymentCode('{{ $paymentCode }}')">
                <i class="fas fa-copy me-1"></i> Copy Code
            </button>
        </div>
    </div>
    
    <div class="payment-status mb-4">
        <div class="alert alert-info d-flex align-items-center p-3">
            <div class="status-icon me-3">
                <i class="fas fa-spinner fa-spin fa-lg"></i>
            </div>
            <div id="status-message" class="flex-grow-1">
                Waiting for your payment... We'll redirect you automatically once the payment is processed.
            </div>
        </div>
    </div>

    <div class="payment-instructions card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i>How to Pay</h5>
        </div>
        <div class="card-body">
            <ol class="mb-0 ps-3">
                <li class="mb-2">Dial <strong>*9#</strong> from your Vodafone number</li>
                <li class="mb-2">Select "Pay" from the menu</li>
                <li class="mb-2">Enter the merchant code: <strong>{{ config('payment.vodafone_cash.merchant_id') ?: 'MEDCAPS' }}</strong></li>
                <li class="mb-2">Enter the amount: <strong>{{ $course->price }} EGP</strong></li>
                <li class="mb-2">Enter the payment code shown above</li>
                <li>Confirm your payment</li>
            </ol>
        </div>
    </div>
    
    <div class="payment-details bg-light p-3 rounded-3">
        <h6 class="fw-bold mb-3">Payment Details</h6>
        <div class="row mb-2">
            <div class="col-6 text-muted">Reference:</div>
            <div class="col-6 text-end fw-semibold">{{ $payment->reference_number }}</div>
        </div>
        <div class="row mb-2">
            <div class="col-6 text-muted">Course:</div>
            <div class="col-6 text-end fw-semibold">{{ $course->name }}</div>
        </div>
        <div class="row">
            <div class="col-6 text-muted">Amount:</div>
            <div class="col-6 text-end fw-semibold">{{ $course->price }} EGP</div>
        </div>
    </div>
</div>
<div class="modal-footer border-0 justify-content-center pt-0 pb-4">
    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
    <a href="{{ route('courses.index') }}" class="btn btn-secondary px-4">
        <i class="fas fa-arrow-left me-2"></i>Back to Courses
    </a>
    @if(app()->environment('local', 'testing'))
    <button type="button" class="btn btn-success px-4" onclick="(function(paymentId) {
        console.log('Simulating payment success for ID:', paymentId);
        const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content');
        if (!csrfToken) { alert('CSRF token not found!'); return; }
        
        const simButton = event.target;
        simButton.innerHTML = '<span class=\'spinner-border spinner-border-sm\' role=\'status\'></span> Processing...';
        simButton.disabled = true;
        
        fetch('/payments/' + paymentId + '/simulate-success', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const statusAlert = document.querySelector('.alert');
                if (statusAlert) statusAlert.classList.replace('alert-info', 'alert-success');
                
                const statusIcon = document.querySelector('.status-icon i');
                if (statusIcon) {
                    statusIcon.classList.remove('fa-spinner', 'fa-spin');
                    statusIcon.classList.add('fa-check-circle');
                }
                
                const statusMessage = document.getElementById('status-message');
                if (statusMessage) statusMessage.innerHTML = 'Payment successful! Redirecting...';
                
                setTimeout(() => { window.location.href = data.redirect; }, 2000);
            } else {
                alert('Payment simulation failed');
                simButton.innerHTML = '<i class=\'fas fa-vial me-2\'></i>Simulate Success';
                simButton.disabled = false;
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            simButton.innerHTML = '<i class=\'fas fa-vial me-2\'></i>Simulate Success';
            simButton.disabled = false;
        });
    })('{{ $payment->id }}')">
        <i class="fas fa-vial me-2"></i>Simulate Success
    </button>
    @endif
</div>

<style>
.modal-content {
    border: none;
    border-radius: 15px;
}
.course-premium-card {
    background-color: #f8f9fa;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.payment-code {
    position: relative;
    overflow: hidden;
}
.payment-code::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none;
}
.copy-btn {
    transition: all 0.2s ease;
}
.copy-btn:hover {
    transform: translateY(-2px);
}
.payment-instructions .card-header {
    border-bottom: none;
}
.status-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-title {
    color: #212529;
    font-size: 1.5rem;
    letter-spacing: -0.02em;
}
</style>

<script>
    function copyPaymentCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            const copyBtn = document.querySelector('.copy-btn');
            copyBtn.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
            copyBtn.classList.remove('btn-primary');
            copyBtn.classList.add('btn-success');
            
            setTimeout(() => {
                copyBtn.innerHTML = '<i class="fas fa-copy me-1"></i> Copy Code';
                copyBtn.classList.remove('btn-success');
                copyBtn.classList.add('btn-primary');
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy code:', err);
            alert('Failed to copy code. Please try selecting and copying it manually.');
        });
    }
    
    function initVodafoneCashPolling(paymentId) {
        console.log('Vodafone Cash polling initialized for payment ID:', paymentId);
        
        // Set up pulsing animation on the spinner
        const statusIcon = document.querySelector('.status-icon i');
        if (statusIcon) {
            const pulseInterval = setInterval(() => {
                statusIcon.style.opacity = '0.6';
                setTimeout(() => {
                    statusIcon.style.opacity = '1';
                }, 500);
            }, 1000);
        }
        
        // Check payment status every 5 seconds
        const statusInterval = setInterval(() => {
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch(`/payments/${paymentId}/check-status`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Payment status check:', data);
                
                if (data.status === 'completed') {
                    clearInterval(statusInterval);
                    
                    const statusAlert = document.querySelector('.alert');
                    if (statusAlert) {
                        statusAlert.classList.remove('alert-info');
                        statusAlert.classList.add('alert-success');
                    }
                    
                    const statusIcon = document.querySelector('.status-icon i');
                    if (statusIcon) {
                        statusIcon.classList.remove('fa-spinner', 'fa-spin');
                        statusIcon.classList.add('fa-check-circle');
                    }
                    
                    const statusMessage = document.getElementById('status-message');
                    if (statusMessage) {
                        statusMessage.innerHTML = 'Payment successful! Redirecting to your course...';
                    }
                    
                    // Redirect after a short delay
                    console.log('Payment completed, redirecting to:', data.redirect);
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else if (data.status === 'failed') {
                    clearInterval(statusInterval);
                    
                    const statusAlert = document.querySelector('.alert');
                    if (statusAlert) {
                        statusAlert.classList.remove('alert-info');
                        statusAlert.classList.add('alert-danger');
                    }
                    
                    const statusIcon = document.querySelector('.status-icon i');
                    if (statusIcon) {
                        statusIcon.classList.remove('fa-spinner', 'fa-spin');
                        statusIcon.classList.add('fa-times-circle');
                    }
                    
                    const statusMessage = document.getElementById('status-message');
                    if (statusMessage) {
                        statusMessage.innerHTML = 'Payment failed. Please try again with a different payment method.';
                    }
                } else if (data.status === 'expired') {
                    clearInterval(statusInterval);
                    
                    const statusAlert = document.querySelector('.alert');
                    if (statusAlert) {
                        statusAlert.classList.remove('alert-info');
                        statusAlert.classList.add('alert-warning');
                    }
                    
                    const statusIcon = document.querySelector('.status-icon i');
                    if (statusIcon) {
                        statusIcon.classList.remove('fa-spinner', 'fa-spin');
                        statusIcon.classList.add('fa-exclamation-circle');
                    }
                    
                    const statusMessage = document.getElementById('status-message');
                    if (statusMessage) {
                        statusMessage.innerHTML = 'Payment code has expired. Please start over with a new payment.';
                    }
                }
            })
            .catch(error => {
                console.error('Error checking payment status:', error);
            });
        }, 5000);
    }
    
    // Make the function globally available
    window.initVodafoneCashPolling = initVodafoneCashPolling;
    
    // Initialize the polling when the modal is shown
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Vodafone Cash modal loaded, initializing polling');
        initVodafoneCashPolling('{{ $payment->id }}');
    });
</script> 