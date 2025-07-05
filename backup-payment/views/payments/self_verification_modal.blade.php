<div class="modal-content">
    <div class="modal-header bg-success bg-opacity-10">
        <h5 class="modal-title"><i class="fas fa-shield-alt me-2 text-success"></i>Self-Verification Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="alert alert-info">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-info-circle fa-2x text-primary"></i>
                </div>
                <div>
                    <h6 class="mb-1">Quick Instructions</h6>
                    <p class="mb-0">Pay using your preferred method, then submit your transaction details below for instant verification.</p>
                </div>
            </div>
        </div>
        
        <h5 class="mb-1">Payment Reference: <span class="text-primary fw-bold">{{ $instructions['reference_number'] }}</span></h5>
        <p class="small mb-3">
            <i class="fas fa-exclamation-circle me-1 text-warning"></i>
            Include this reference number in your payment description to help us identify your payment.
        </p>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span><strong>Course:</strong> {{ $instructions['course_name'] }}</span>
            <span><strong>Amount:</strong> {{ number_format($instructions['amount'], 2) }} EGP</span>
        </div>
        
        <div class="row g-4 mt-1 mb-4">
            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-money-bill-wave me-2 text-success"></i>Payment Methods</h6>
                    </div>
                    <div class="card-body">
                        <div class="payment-options">
                            <div class="payment-option mb-3">
                                <label class="d-flex align-items-center mb-1">
                                    <input type="radio" name="payment_method_option" value="vodafone_cash" checked class="me-2">
                                    <strong><i class="fas fa-mobile-alt me-2 text-danger"></i>Vodafone Cash</strong>
                                </label>
                                <div class="d-flex align-items-center ps-4">
                                    <span class="me-2 bg-light px-3 py-2 rounded border">{{ $instructions['payment_options']['vodafone_cash'] ?: 'Contact admin' }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary copy-btn" 
                                            onclick="copyToClipboard('{{ $instructions['payment_options']['vodafone_cash'] }}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="payment-option mb-3">
                                <label class="d-flex align-items-center mb-1">
                                    <input type="radio" name="payment_method_option" value="fawry" class="me-2">
                                    <strong><i class="fas fa-store me-2 text-warning"></i>Fawry</strong>
                                </label>
                                <div class="d-flex align-items-center ps-4">
                                    <span class="me-2 bg-light px-3 py-2 rounded border">{{ $instructions['payment_options']['fawry'] ?: 'Contact admin' }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary copy-btn" 
                                            onclick="copyToClipboard('{{ $instructions['payment_options']['fawry'] }}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="payment-option">
                                <label class="d-flex align-items-center mb-1">
                                    <input type="radio" name="payment_method_option" value="instapay" class="me-2">
                                    <strong><i class="fas fa-university me-2 text-primary"></i>Instapay</strong>
                                </label>
                                <div class="d-flex align-items-center ps-4">
                                    <span class="me-2 bg-light px-3 py-2 rounded border">{{ $instructions['payment_options']['instapay'] ?: 'Contact admin' }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary copy-btn" 
                                            onclick="copyToClipboard('{{ $instructions['payment_options']['instapay'] }}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="verification-section mt-4">
            <div class="card border-success mb-4">
                <div class="card-header bg-success bg-opacity-10">
                    <h5 class="mb-0 text-success"><i class="fas fa-check-circle me-2"></i>Verify Your Payment</h5>
                </div>
                <div class="card-body">
                    <p>After making your payment, please provide the transaction details below for instant verification:</p>
                    
                    <form id="self-verification-form" action="{{ route('payments.submit-proof', $payment->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="transaction_id" class="form-label">Transaction ID / Reference <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="transaction_id" name="transaction_id" required maxlength="50">
                            <div class="form-text">Enter the transaction ID or reference number you received after payment.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_method_used" class="form-label">Payment Method Used <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_method_used" name="payment_method_used" required>
                                <option value="" selected disabled>Select payment method</option>
                                <option value="vodafone_cash">Vodafone Cash</option>
                                <option value="fawry">Fawry</option>
                                <option value="instapay">Instapay</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_proof" class="form-label">Payment Screenshot <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept="image/*" required>
                            <div class="form-text">Upload a screenshot of your payment confirmation (max 5MB). This helps us verify your payment faster.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="500"></textarea>
                            <div class="form-text">Any additional information that might help us verify your payment.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" id="verify-payment-btn">
                                <i class="fas fa-shield-check me-2"></i>Verify Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sync the payment method radio buttons with the dropdown
    document.querySelectorAll('input[name="payment_method_option"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('payment_method_used').value = this.value;
        });
    });
    
    // Initialize the dropdown with the first selected radio button
    const checkedMethod = document.querySelector('input[name="payment_method_option"]:checked');
    if (checkedMethod) {
        document.getElementById('payment_method_used').value = checkedMethod.value;
    }
    
    // Form submission
    const form = document.getElementById('self-verification-form');
    const submitBtn = document.getElementById('verify-payment-btn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Disable button and show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying Payment...';
        
        // Get form data
        const formData = new FormData(form);
        
        // Submit via AJAX
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Replace form with success message
                const modalBody = document.querySelector('.modal-body');
                
                if (data.verified) {
                    // Payment was automatically verified
                    modalBody.innerHTML = `
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                            </div>
                            <h4 class="mb-3">Payment Verified Successfully!</h4>
                            <p class="mb-3">Your payment has been verified automatically and you now have access to the course.</p>
                            <p class="text-muted">You will be redirected to the course in a few seconds...</p>
                        </div>
                    `;
                } else {
                    // Payment needs manual verification
                    modalBody.innerHTML = `
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-paper-plane text-primary" style="font-size: 5rem;"></i>
                            </div>
                            <h4 class="mb-3">Payment Submission Received</h4>
                            <p class="mb-3">Your payment details have been submitted and are pending verification by our team.</p>
                            <p class="text-muted">We will process your verification shortly. You will be redirected in a few seconds...</p>
                        </div>
                    `;
                }
                
                // Redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = data.redirect || '/payments/history';
                }, 3000);
            } else {
                alert(data.message || 'Failed to verify payment. Please try again.');
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-shield-check me-2"></i>Verify Payment';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while verifying your payment. Please try again.');
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-shield-check me-2"></i>Verify Payment';
        });
    });
});

// Helper function to copy text to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(
        function() {
            // Show a temporary tooltip
            const tooltip = document.createElement('div');
            tooltip.innerHTML = 'Copied!';
            tooltip.style.position = 'fixed';
            tooltip.style.top = '50%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
            tooltip.style.padding = '8px 16px';
            tooltip.style.background = 'rgba(0,0,0,0.7)';
            tooltip.style.color = 'white';
            tooltip.style.borderRadius = '4px';
            tooltip.style.zIndex = '9999';
            
            document.body.appendChild(tooltip);
            
            setTimeout(() => {
                tooltip.remove();
            }, 1500);
        }
    );
}
</script> 