<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-money-bill-wave me-2"></i>Manual Payment Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Please complete your payment using one of the methods below and then submit your payment details.
        </div>
        
        <h5 class="mb-1">Payment Reference: <span class="text-primary">{{ $instructions['reference_number'] }}</span></h5>
        <p class="small mb-3">
            <i class="fas fa-exclamation-circle me-1 text-warning"></i>
            Include this reference number in your payment description to help us identify your payment.
        </p>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span><strong>Course:</strong> {{ $instructions['course_name'] }}</span>
            <span><strong>Amount:</strong> {{ number_format($instructions['amount'], 2) }} EGP</span>
        </div>
        
        <h5 class="mt-4 mb-3">Payment Steps:</h5>
        <ol class="mb-4">
            <li class="mb-2">Choose your preferred payment method from the options below</li>
            <li class="mb-2">Send the exact amount ({{ number_format($instructions['amount'], 2) }} EGP) to the provided account</li>
            <li class="mb-2">Include the payment reference in your transaction description if possible</li>
            <li class="mb-2">Take a screenshot of your payment confirmation</li>
            <li>Return to this page and enter your transaction details below</li>
        </ol>
        
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Payment Options</h5>
            </div>
            <div class="card-body">
                <div class="payment-option mb-3">
                    <label class="d-flex align-items-center mb-1">
                        <input type="radio" name="payment_method_option" value="vodafone_cash" checked class="me-2">
                        <strong><i class="fas fa-mobile-alt me-2 text-danger"></i>Vodafone Cash</strong>
                    </label>
                    <p class="mb-0">Wallet Number: <strong>{{ $instructions['payment_options']['vodafone_cash'] ?: 'Contact admin' }}</strong></p>
                </div>
                <div class="payment-option mb-3">
                    <label class="d-flex align-items-center mb-1">
                        <input type="radio" name="payment_method_option" value="fawry" class="me-2">
                        <strong><i class="fas fa-store me-2 text-warning"></i>Fawry</strong>
                    </label>
                    <p class="mb-0">Account Number: <strong>{{ $instructions['payment_options']['fawry'] ?: 'Contact admin' }}</strong></p>
                </div>
                <div class="payment-option">
                    <label class="d-flex align-items-center mb-1">
                        <input type="radio" name="payment_method_option" value="instapay" class="me-2">
                        <strong><i class="fas fa-university me-2 text-primary"></i>Instapay</strong>
                    </label>
                    <p class="mb-0">Account: <strong>{{ $instructions['payment_options']['instapay'] ?: 'Contact admin' }}</strong></p>
                </div>
            </div>
        </div>
        
        <h5 class="mt-2 mb-3">Submit Payment Proof</h5>
        <form id="payment-proof-form" action="{{ route('payments.submit-proof', $payment->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="transaction_id" class="form-label">Transaction ID <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="transaction_id" name="transaction_id" required maxlength="50">
                <div class="form-text">Enter the transaction ID or reference number from your payment.</div>
            </div>
            
            <div class="mb-3">
                <label for="payment_method_used" class="form-label">Payment Method Used <span class="text-danger">*</span></label>
                <select class="form-select" id="payment_method_used" name="payment_method_used" required>
                    <option value="" selected disabled>Select payment method</option>
                    <option value="vodafone_cash">Vodafone Cash</option>
                    <option value="fawry">Fawry</option>
                    <option value="instapay">Instapay</option>
                </select>
                <div class="form-text">Select the payment method you used.</div>
            </div>
            
            <div class="mb-3">
                <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                <div class="form-text">Select the date when you made the payment.</div>
            </div>
            
            <div class="mb-3">
                <label for="payment_proof" class="form-label">Payment Screenshot <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept="image/*" required>
                <div class="form-text">Upload a screenshot of your payment confirmation (max 5MB). This is required for verification.</div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Additional Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="500"></textarea>
                <div class="form-text">Any additional information that might help us verify your payment.</div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" id="submit-payment-btn">
                    <i class="fas fa-paper-plane me-2"></i>Submit Payment Proof
                </button>
            </div>
        </form>
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
    
    const form = document.getElementById('payment-proof-form');
    const submitBtn = document.getElementById('submit-payment-btn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Disable button and show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
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
                modalBody.innerHTML = `
                    <div class="text-center py-4">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="mb-1">Payment Proof Submitted Successfully!</h5>
                        ${data.verified ? 
                            `<p class="alert alert-success mt-3">Your payment has been verified automatically. You now have access to the course!</p>` : 
                            `<p class="mb-3">Your payment proof has been submitted and is pending verification by our team.</p>
                            <p>You will be redirected to the payment history page in a few seconds...</p>`
                        }
                    </div>
                `;
                
                // Redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = data.redirect || '/payments/history';
                }, 3000);
            } else {
                alert(data.message || 'Failed to submit payment proof. Please try again.');
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Payment Proof';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting your payment proof. Please try again.');
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Payment Proof';
        });
    });
});
</script> 