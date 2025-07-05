<div class="modal-header py-2">
    <h5 class="modal-title" id="paymentModalLabel">
        <i class="fas fa-shopping-cart me-2"></i>Bundle Payment
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-3">
    <!-- Bundle Summary Box -->
    <div class="card mb-3 border shadow-sm">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0">Bundle Summary ({{ $bundle['count'] }} courses)</h6>
        </div>
        <div class="card-body p-2">
            <div class="d-flex flex-wrap">
                @foreach($bundle['courses'] as $course)
                    <div class="me-3 mb-1 d-flex align-items-center">
                        <span class="me-2">{{ $course->name }}</span>
                        <span class="badge bg-primary rounded-pill">{{ $course->formatted_price ?? '200 EGP' }}</span>
                    </div>
                @endforeach
            </div>
            
            <div class="price-summary mt-2 border-top pt-2">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="small text-muted">Original: {{ $bundle['original_price'] }} EGP</div>
                        <div class="small text-success">Discount: -{{ $bundle['discount_amount'] }} EGP</div>
                    </div>
                    <div class="col-auto">
                        <div class="h5 mb-0 text-primary">Total: {{ $bundle['total_price'] }} EGP</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Options -->
    @if(Auth::user()->is_admin)
        <div class="text-muted small mb-2">
            <i class="fas fa-info-circle me-1"></i> Admin: All payment methods visible
        </div>
    @endif
    
    <!-- Payment Method Tabs -->
    <ul class="nav nav-tabs nav-fill mb-0" id="paymentMethodTabs" role="tablist">
        @if(config('payment.vodafone_cash.enabled', true) || Auth::user()->is_admin)
            <li class="nav-item" role="presentation">
                <button class="nav-link active payment-tab-vodafone" id="vodafone-tab" data-bs-toggle="tab" data-bs-target="#vodafone-panel" type="button" role="tab">
                    <i class="fas fa-mobile-alt me-1"></i>Vodafone Cash
                    @if(Auth::user()->is_admin && !config('payment.vodafone_cash.enabled', true))
                        <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">Disabled</span>
                    @endif
                </button>
            </li>
        @endif
        
        @if(config('payment.fawry.enabled', true) || Auth::user()->is_admin)
            <li class="nav-item" role="presentation">
                <button class="nav-link payment-tab-fawry" id="fawry-tab" data-bs-toggle="tab" data-bs-target="#fawry-panel" type="button" role="tab">
                    <i class="fas fa-store me-1"></i>Fawry
                    @if(Auth::user()->is_admin && !config('payment.fawry.enabled', true))
                        <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65rem;">Disabled</span>
                    @endif
                </button>
            </li>
        @endif
        
        <li class="nav-item" role="presentation">
            <button class="nav-link payment-tab-bank" id="bank-tab" data-bs-toggle="tab" data-bs-target="#bank-panel" type="button" role="tab">
                <i class="fas fa-university me-1"></i>Bank Transfer
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content border border-top-0 rounded-bottom p-3 mb-3" id="paymentMethodContent">
        <!-- Vodafone Cash Panel -->
        <div class="tab-pane fade show active" id="vodafone-panel" role="tabpanel">
            <div class="d-flex align-items-center">
                <div class="text-danger me-2"><i class="fas fa-mobile-alt fa-lg"></i></div>
                <div class="fw-bold">Send {{ $bundle['total_price'] }} EGP to:</div>
            </div>
            <div class="d-flex align-items-center my-2">
                <code class="bg-white py-1 px-2 rounded border flex-grow-1 me-2">{{ config('payment.vodafone_cash.number', '01XXXXXXXXX') }}</code>
                <button class="btn btn-sm text-secondary copy-btn" data-clipboard="{{ config('payment.vodafone_cash.number', '01XXXXXXXXX') }}">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="small text-success">
                <i class="fas fa-check-circle me-1"></i>Auto-verify with <code>VC</code> followed by your transaction number
            </div>
        </div>
        
        <!-- Fawry Panel -->
        <div class="tab-pane fade" id="fawry-panel" role="tabpanel">
            <div class="d-flex align-items-center">
                <div class="text-warning me-2"><i class="fas fa-store fa-lg"></i></div>
                <div class="fw-bold">Pay {{ $bundle['total_price'] }} EGP to:</div>
            </div>
            <div class="d-flex align-items-center my-2">
                <code class="bg-white py-1 px-2 rounded border flex-grow-1 me-2">{{ config('payment.fawry.number', 'FAWRY-XXXXXXXXX') }}</code>
                <button class="btn btn-sm text-secondary copy-btn" data-clipboard="{{ config('payment.fawry.number', 'FAWRY-XXXXXXXXX') }}">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="small text-success">
                <i class="fas fa-check-circle me-1"></i>Auto-verify with <code>FWY</code> followed by your transaction number
            </div>
        </div>
        
        <!-- Bank Transfer Panel -->
        <div class="tab-pane fade" id="bank-panel" role="tabpanel">
            <div class="d-flex align-items-center">
                <div style="color: #c13584;" class="me-2"><i class="fas fa-university fa-lg"></i></div>
                <div class="fw-bold">Transfer {{ $bundle['total_price'] }} EGP to:</div>
            </div>
            <div class="d-flex align-items-center my-2">
                <code class="bg-white py-1 px-2 rounded border flex-grow-1 me-2">{{ config('payment.bank.number', 'BANK-XXXXXXXXX') }}</code>
                <button class="btn btn-sm text-secondary copy-btn" data-clipboard="{{ config('payment.bank.number', 'BANK-XXXXXXXXX') }}">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="small text-success">
                <i class="fas fa-check-circle me-1"></i>Auto-verify with <code>BNK</code> followed by your transaction number
            </div>
        </div>
    </div>
    
    <!-- Transaction Form -->
    <form id="bundlePaymentForm" class="mb-0">
        <div class="input-group mb-3">
            <span class="input-group-text bg-light text-muted" id="transaction-prefix">VC</span>
            <input type="text" class="form-control" id="transactionId" required placeholder="Enter your transaction number" aria-label="Transaction ID">
        </div>
        
        <!-- Receipt Screenshot Upload -->
        <div class="mb-3">
            <label for="receiptImage" class="form-label small text-muted">
                <i class="fas fa-image me-1"></i>Receipt Screenshot (optional)
            </label>
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
        <input type="hidden" id="bundleReferenceNumber" name="reference_code" value="BUNDLE-{{ strtoupper(substr(md5(Auth::id() . json_encode($bundle['course_ids'])), 0, 8)) }}">
        <button type="submit" class="btn btn-primary w-100" id="submitPaymentBtn">
            <i class="fas fa-check-circle me-1"></i>Submit Payment
        </button>
    </form>
</div>
<div class="modal-footer p-2">
    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>

<style>
    .nav-tabs .nav-link {
        padding: 0.5rem;
        border-radius: 0;
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .payment-tab-vodafone {
        color: #e53935;
    }
    
    .payment-tab-vodafone:hover, .payment-tab-vodafone.active {
        background-color: #ffebee !important;
        color: #c62828 !important;
        border-color: #ffcdd2 !important;
    }
    
    .payment-tab-fawry {
        color: #f57c00;
    }
    
    .payment-tab-fawry:hover, .payment-tab-fawry.active {
        background-color: #fff8e1 !important;
        color: #ef6c00 !important;
        border-color: #ffe082 !important;
    }
    
    .payment-tab-bank {
        color: #c13584;
    }
    
    .payment-tab-bank:hover, .payment-tab-bank.active {
        background-color: rgba(193, 53, 132, 0.1) !important;
        color: #833ab4 !important;
        border-color: rgba(193, 53, 132, 0.3) !important;
    }
    
    code {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.9rem;
    }
    
    .card-body .badge {
        font-weight: 500;
    }
    
    #imagePreviewContainer {
        transition: all 0.3s ease;
    }
</style>

<script>
    window.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const transactionPrefix = document.getElementById('transaction-prefix');
        const paymentMethodInput = document.getElementById('paymentMethod');
        const bundleReference = 'BUNDLE-{{ strtoupper(substr(md5(Auth::id() . json_encode($bundle["course_ids"])), 0, 8)) }}';
        
        // Set up payment method tab switching
        const paymentTabs = document.querySelectorAll('#paymentMethodTabs .nav-link');
        let currentPrefix = 'VC';
        
        paymentTabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (event) {
                const targetId = event.target.getAttribute('data-bs-target');
                let method = 'vodafone_cash';
                
                if (targetId === '#fawry-panel') {
                    method = 'fawry';
                    currentPrefix = 'FWY';
                    transactionPrefix.textContent = 'FWY';
                } else if (targetId === '#bank-panel') {
                    method = 'bank_transfer';
                    currentPrefix = 'BNK';
                    transactionPrefix.textContent = 'BNK';
                } else {
                    currentPrefix = 'VC';
                    transactionPrefix.textContent = 'VC';
                }
                
                paymentMethodInput.value = method;
            });
        });
        
        // Handle form submission
        const form = document.getElementById('bundlePaymentForm');
        const submitBtn = document.getElementById('submitPaymentBtn');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            submitBtn.disabled = true;
            
            // Get the transaction ID and automatically add the prefix
            let transactionId = document.getElementById('transactionId').value.trim();
            
            // If user didn't add the prefix, add it
            if (!transactionId.startsWith('VC') && !transactionId.startsWith('FWY') && !transactionId.startsWith('BNK')) {
                transactionId = currentPrefix + transactionId;
            }
            
            // Create FormData to handle file upload
            const formData = new FormData();
            
            // Add form fields to FormData
            formData.append('courses', JSON.stringify(@json($bundle['course_ids'])));
            formData.append('original_price', {{ $bundle['original_price'] }});
            formData.append('discount_amount', {{ $bundle['discount_amount'] }});
            formData.append('total_price', {{ $bundle['total_price'] }});
            formData.append('transaction_id', transactionId);
            formData.append('payment_method', paymentMethodInput.value);
            formData.append('bundle_id', bundleReference);
            formData.append('reference_number', bundleReference);
            
            // Add receipt image if selected
            if (receiptImage.files[0]) {
                formData.append('receipt_image', receiptImage.files[0]);
            }
            
            fetch('/payments/bundle/submit-proof', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.ok ? response.json() : Promise.reject('Network error: ' + response.status))
            .then(data => {
                if (data.success) {
                    const modalContent = document.querySelector('.modal-content');
                    
                    if (data.auto_verified) {
                        modalContent.innerHTML = `
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title"><i class="fas fa-check-circle me-1"></i>Payment Auto-Verified!</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center p-4">
                                <div class="mb-3 text-success"><i class="fas fa-bolt fa-2x"></i> <i class="fas fa-check-circle fa-2x"></i></div>
                                <h5>Success!</h5>
                                <p>Your payment has been automatically verified!</p>
                                <div class="d-flex justify-content-center mt-2">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    <span>Redirecting...</span>
                                </div>
                            </div>
                        `;
                        setTimeout(() => window.location.href = '/courses', 2000);
                    } else {
                        modalContent.innerHTML = `
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title"><i class="fas fa-check-circle me-1"></i>Payment Submitted</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center p-4">
                                <div class="mb-3 text-success"><i class="fas fa-check-circle fa-2x"></i></div>
                                <h5>Thank You!</h5>
                                <p>Your bundle payment has been submitted successfully.</p>
                                <a href="/courses" class="btn btn-primary"><i class="fas fa-home me-1"></i>Back to Courses</a>
                            </div>
                        `;
                    }
                } else {
                    alert(data.message || 'Something went wrong. Please try again.');
                    submitBtn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Submit Payment';
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
                submitBtn.innerHTML = '<i class="fas fa-check-circle me-1"></i>Submit Payment';
                submitBtn.disabled = false;
            });
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
        
        // Copy button functionality
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', function() {
                navigator.clipboard.writeText(this.getAttribute('data-clipboard'))
                    .then(() => {
                        this.classList.add('btn-success');
                        this.classList.remove('text-secondary');
                        const originalContent = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check"></i>';
                        setTimeout(() => {
                            this.classList.remove('btn-success');
                            this.classList.add('text-secondary');
                            this.innerHTML = originalContent;
                        }, 1500);
                    })
                    .catch(err => console.error('Failed to copy:', err));
            });
        });
    });
</script> 