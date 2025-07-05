<div class="modal-header">
    <h5 class="modal-title" id="paymentModalLabel">
        <i class="fas fa-shopping-cart me-2"></i>Complete Your Bundle Purchase
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="bundle-summary mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Bundle Summary ({{ $bundle['count'] }} courses)</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="mb-2">Selected Courses:</h6>
                    <ul class="list-group">
                        @foreach($bundle['courses'] as $course)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $course->name }}
                                <span class="badge bg-primary rounded-pill">{{ $course->formatted_price ?? '200 EGP' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                <div class="price-summary p-3 bg-light rounded">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Original Price:</span>
                        <span>{{ $bundle['original_price'] }} EGP</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Bundle Discount:</span>
                        <span>- {{ $bundle['discount_amount'] }} EGP</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total:</span>
                        <span>{{ $bundle['total_price'] }} EGP</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="payment-instructions mb-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Payment Instructions</h5>
            </div>
            <div class="card-body">
                <p>Send <strong>{{ $bundle['total_price'] }} EGP</strong> to one of these payment methods:</p>
                
                <div class="alert alert-info mb-3 small">
                    <div class="d-flex">
                        <div class="me-2 text-primary">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <strong>Auto-Verification Available!</strong> Your payment may be verified instantly if you provide a valid transaction ID.
                        </div>
                    </div>
                </div>

                <!-- Payment Methods Tabs -->
                <div class="mb-3">
                    <h6 class="fw-bold mb-2 small text-uppercase text-muted">Select Payment Method</h6>
                    <div class="nav payment-nav mb-2" id="payment-method-tab" role="tablist">
                        <button class="payment-tab payment-tab-vodafone active" data-bs-toggle="pill" data-bs-target="#vodafone-cash-bundle" type="button" role="tab" aria-selected="true">
                            <i class="fas fa-mobile-alt me-2"></i>Vodafone Cash
                        </button>
                        <button class="payment-tab payment-tab-fawry" data-bs-toggle="pill" data-bs-target="#fawry-bundle" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-store me-2"></i>Fawry
                        </button>
                        <button class="payment-tab payment-tab-instapay" data-bs-toggle="pill" data-bs-target="#bank-bundle" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-university me-2"></i>Bank Transfer
                        </button>
                    </div>
                    
                    <div class="tab-content p-2 border rounded bg-light">
                        <div class="tab-pane fade show active" id="vodafone-cash-bundle" role="tabpanel">
                            <div class="d-flex align-items-start">
                                <div class="me-3 text-danger">
                                    <i class="fas fa-mobile-alt fa-lg"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <ol class="ps-3 small mb-2">
                                        <li>Send <strong>{{ $bundle['total_price'] }} EGP</strong> to this wallet:</li>
                                    </ol>
                                    <div class="d-flex align-items-center mb-2">
                                        <code class="bg-white px-2 py-1 rounded border flex-grow-1 me-2">{{ config('payment.vodafone_cash.number', '01XXXXXXXXX') }}</code>
                                        <button class="btn btn-sm text-secondary copy-btn" data-clipboard="{{ config('payment.vodafone_cash.number', '01XXXXXXXXX') }}" title="Copy wallet number">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="small text-success">
                                        <i class="fas fa-check-circle me-1"></i>Auto-verify with <code>VC</code> followed by your transaction number
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="fawry-bundle" role="tabpanel">
                            <div class="d-flex align-items-start">
                                <div class="me-3 text-warning">
                                    <i class="fas fa-store fa-lg"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <ol class="ps-3 small mb-2">
                                        <li>Pay <strong>{{ $bundle['total_price'] }} EGP</strong> to this account:</li>
                                    </ol>
                                    <div class="d-flex align-items-center mb-2">
                                        <code class="bg-white px-2 py-1 rounded border flex-grow-1 me-2">{{ config('payment.fawry.number', 'FAWRY-XXXXXXXXX') }}</code>
                                        <button class="btn btn-sm text-secondary copy-btn" data-clipboard="{{ config('payment.fawry.number', 'FAWRY-XXXXXXXXX') }}" title="Copy account number">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="small text-success">
                                        <i class="fas fa-check-circle me-1"></i>Auto-verify with <code>FWY</code> followed by your transaction number
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="bank-bundle" role="tabpanel">
                            <div class="d-flex align-items-start">
                                <div class="me-3 text-instapay">
                                    <i class="fas fa-university fa-lg"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <ol class="ps-3 small mb-2">
                                        <li>Transfer <strong>{{ $bundle['total_price'] }} EGP</strong> to this account:</li>
                                    </ol>
                                    <div class="d-flex align-items-center mb-2">
                                        <code class="bg-white px-2 py-1 rounded border flex-grow-1 me-2">{{ config('payment.bank.number', 'BANK-XXXXXXXXX') }}</code>
                                        <button class="btn btn-sm text-secondary copy-btn" data-clipboard="{{ config('payment.bank.number', 'BANK-XXXXXXXXX') }}" title="Copy account">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="small text-success">
                                        <i class="fas fa-check-circle me-1"></i>Auto-verify with <code>BNK</code> followed by your transaction number
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="transaction-form">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Submit Payment</h5>
            </div>
            <div class="card-body">
                <form id="bundlePaymentForm">
                    <div class="mb-3">
                        <label for="transactionId" class="form-label">Transaction ID <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted small payment-method-prefix" id="transaction-prefix">VC</span>
                            <input type="text" class="form-control" id="transactionId" required placeholder="Enter your transaction number">
                        </div>
                        <div class="form-text">You'll receive this after making the payment</div>
                    </div>
                    
                    <!-- Receipt Screenshot Upload -->
                    <div class="mb-3">
                        <label for="receiptImage" class="form-label">Receipt Screenshot <span class="text-muted">(optional)</span></label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="receiptImage" accept="image/*" aria-label="Upload receipt screenshot">
                            <button class="btn btn-outline-secondary" type="button" id="clearImageBtn" disabled>
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="form-text">Upload a screenshot of your payment confirmation (max 5MB)</div>
                        
                        <!-- Image Preview -->
                        <div id="imagePreviewContainer" class="mt-2 d-none">
                            <div class="position-relative border rounded p-2 bg-light">
                                <img id="imagePreview" class="img-fluid rounded" style="max-height: 150px" alt="Receipt preview">
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="paymentMethod" name="payment_method" value="vodafone_cash">
                    <input type="hidden" id="bundleReferenceNumber" name="reference_code" value="{{ $payment->reference_number }}">
                    
                    <button type="submit" class="btn btn-primary w-100" id="submitPaymentBtn">
                        <i class="fas fa-check-circle me-2"></i>Submit Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .payment-nav {
        display: flex;
        gap: 0.5rem;
        width: 100%;
    }

    .payment-tab {
        flex: 1;
        text-align: center;
        padding: 0.5rem;
        border-radius: 8px;
        border: none;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .payment-tab-vodafone {
        background-color: #ffebee;
        color: #e53935;
        border: 1px solid rgba(229, 57, 53, 0.3);
    }

    .payment-tab-vodafone:hover, .payment-tab-vodafone.active {
        background-color: #ffcdd2;
        color: #c62828;
        box-shadow: 0 2px 4px rgba(198, 40, 40, 0.1);
        transform: translateY(-1px);
    }

    .payment-tab-fawry {
        background-color: #fff8e1;
        color: #f57c00;
        border: 1px solid rgba(245, 124, 0, 0.3);
    }

    .payment-tab-fawry:hover, .payment-tab-fawry.active {
        background-color: #ffe082;
        color: #ef6c00;
        box-shadow: 0 2px 4px rgba(239, 108, 0, 0.1);
        transform: translateY(-1px);
    }

    .payment-tab-instapay {
        background: linear-gradient(135deg, rgba(131, 58, 180, 0.05), rgba(225, 48, 108, 0.05));
        color: #c13584;
        border: 1px solid rgba(193, 53, 132, 0.3);
    }

    .payment-tab-instapay:hover, .payment-tab-instapay.active {
        background: linear-gradient(135deg, rgba(131, 58, 180, 0.1), rgba(225, 48, 108, 0.1));
        color: #833ab4;
        box-shadow: 0 2px 4px rgba(131, 58, 180, 0.1);
        transform: translateY(-1px);
    }

    .text-instapay {
        background: -webkit-linear-gradient(45deg, #833ab4, #fd1d1d, #fcb045);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .payment-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .payment-number {
        font-family: monospace;
        letter-spacing: 0.5px;
        font-size: 1.1rem;
    }
    
    .copy-btn {
        padding: 0.25rem 0.5rem;
    }
    
    .copy-btn:hover {
        background-color: #f8f9fa;
    }
    
    .copy-btn.copied {
        background-color: #28a745;
        color: white;
        border-color: #28a745;
    }

    code {
        font-size: 0.85rem;
    }
    
    #imagePreviewContainer {
        transition: all 0.3s ease;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Copy buttons functionality
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', function() {
                const textToCopy = this.getAttribute('data-clipboard');
                navigator.clipboard.writeText(textToCopy).then(() => {
                    // Visual feedback
                    this.classList.add('copied');
                    const originalContent = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    
                    setTimeout(() => {
                        this.classList.remove('copied');
                        this.innerHTML = originalContent;
                    }, 1500);
                });
            });
        });
        
        // Update hidden payment method field based on selected tab
        const paymentTabs = document.querySelectorAll('#payment-method-tab button');
        const transactionPrefix = document.getElementById('transaction-prefix');
        let currentPrefix = 'VC';
        
        // Set initial format
        updateTransactionFormat('vodafone_cash');
        
        paymentTabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                const targetId = event.target.getAttribute('data-bs-target');
                let method = 'vodafone_cash';
                
                if (targetId === '#fawry-bundle') {
                    method = 'fawry';
                    currentPrefix = 'FWY';
                } else if (targetId === '#bank-bundle') {
                    method = 'bank_transfer';
                    currentPrefix = 'BNK';
                } else {
                    currentPrefix = 'VC';
                }
                
                document.getElementById('paymentMethod').value = method;
                updateTransactionFormat(method);
            });
        });
        
        function updateTransactionFormat(method) {
            let prefix = '';
            
            if (method === 'vodafone_cash') {
                prefix = 'VC';
                document.getElementById('transactionId').placeholder = 'Enter your transaction number';
            } else if (method === 'fawry') {
                prefix = 'FWY';
                document.getElementById('transactionId').placeholder = 'Enter your transaction number';
            } else if (method === 'bank_transfer') {
                prefix = 'BNK';
                document.getElementById('transactionId').placeholder = 'Enter your transaction number';
            }
            
            transactionPrefix.textContent = prefix;
        }
        
        // Auto-add prefix to transaction ID if not already present
        const transactionIdInput = document.getElementById('transactionId');
        transactionIdInput.addEventListener('blur', function() {
            const method = document.getElementById('paymentMethod').value;
            let requiredPrefix = '';
            
            if (method === 'vodafone_cash') {
                requiredPrefix = 'VC';
            } else if (method === 'fawry') {
                requiredPrefix = 'FWY';
            } else if (method === 'bank_transfer') {
                requiredPrefix = 'BNK';
            }
            
            // Only add prefix if value doesn't already start with it
            if (transactionIdInput.value && !transactionIdInput.value.startsWith(requiredPrefix)) {
                transactionIdInput.value = requiredPrefix + transactionIdInput.value;
            }
        });
        
        // Image preview functionality
        const receiptImage = document.getElementById('receiptImage');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const clearImageBtn = document.getElementById('clearImageBtn');
        
        receiptImage.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                // Check file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File is too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.classList.remove('d-none');
                    clearImageBtn.disabled = false;
                }
                
                reader.readAsDataURL(file);
            } else {
                imagePreviewContainer.classList.add('d-none');
                clearImageBtn.disabled = true;
            }
        });
        
        clearImageBtn.addEventListener('click', function() {
            receiptImage.value = '';
            imagePreviewContainer.classList.add('d-none');
            this.disabled = true;
        });
        
        // Handle form submission
        const form = document.getElementById('bundlePaymentForm');
        const submitBtn = document.getElementById('submitPaymentBtn');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            submitBtn.disabled = true;
            
            // Create FormData to handle file upload
            const formData = new FormData();
            
            // Get the transaction ID and automatically add the prefix if needed
            let transactionId = document.getElementById('transactionId').value.trim();
            if (!transactionId.startsWith('VC') && !transactionId.startsWith('FWY') && !transactionId.startsWith('BNK')) {
                transactionId = currentPrefix + transactionId;
            }
            
            // Add form fields to FormData
            formData.append('transaction_id', transactionId);
            formData.append('payment_method', document.getElementById('paymentMethod').value);
            formData.append('bundle_id', '{{ $bundle['id'] }}');
            formData.append('reference_number', '{{ $payment->reference_number }}');
            
            // Add receipt image if selected
            if (receiptImage.files[0]) {
                formData.append('receipt_image', receiptImage.files[0]);
            }
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            // Submit payment proof
            fetch('/payments/bundle/submit-proof', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success message
                    const paymentModal = document.getElementById('paymentModal');
                    
                    // Update modal content with success message
                    paymentModal.querySelector('.modal-content').innerHTML = `
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-check-circle me-2"></i>Payment Submitted
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center p-5">
                            <div class="display-1 text-success mb-4">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4>Thank You!</h4>
                            <p class="mb-4">Your bundle payment has been submitted successfully. We'll verify it shortly and grant you access to all the courses in your bundle.</p>
                            <a href="/courses" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Back to Courses
                            </a>
                        </div>
                    `;
                    
                    // If auto-verified, show different message
                    if (data.verified) {
                        paymentModal.querySelector('.modal-content').innerHTML = `
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-check-circle me-2"></i>Payment Auto-Verified!
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center p-5">
                                <div class="display-1 text-success mb-3">
                                    <i class="fas fa-bolt"></i>
                                    <i class="fas fa-check-circle ms-2"></i>
                                </div>
                                <h4>Success!</h4>
                                <p class="mb-4">Your payment has been automatically verified! You now have access to all courses in your bundle.</p>
                                <div class="d-flex justify-content-center mt-3">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    <span>Redirecting to courses...</span>
                                </div>
                            </div>
                        `;
                        
                        // Redirect after 3 seconds
                        setTimeout(() => {
                            window.location.href = '/courses';
                        }, 3000);
                    }
                } else {
                    // Error message
                    alert(data.message || 'Something went wrong. Please try again.');
                    
                    // Reset button state
                    submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Submit Payment';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error submitting payment proof:', error);
                alert('An error occurred. Please try again later.');
                
                // Reset button state
                submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Submit Payment';
                submitBtn.disabled = false;
            });
        });
    });
</script> 