<div class="payment-modal-content">
    <div class="modal-header border-0 pb-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body px-4 pt-2">
        <div class="text-center mb-4">
            <h4 class="modal-title fw-bold">Premium Access</h4>
            <p class="text-muted">Unlock all features and content</p>
        </div>

        <div class="course-premium-card mb-4">
            <div class="d-flex align-items-center">
                <div class="course-image me-3">
                    <img src="{{ $course->image }}" alt="{{ $course->name }}" class="img-fluid rounded shadow-sm" style="max-width: 100px; object-fit: cover;" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCIgZmlsbD0ibm9uZSI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlOWVjZWYiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTRweCIgZmlsbD0iIzZjNzU3ZCI+Q291cnNlIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                </div>
                <div class="course-info">
                    <h5 class="course-title fw-bold mb-1">{{ $course->name }}</h5>
                    <div class="course-badges mb-2">
                        <span class="badge bg-primary">Premium</span>
                        @if($course->is_paid)
                        <span class="badge bg-success">{{ $course->formatted_price }}</span>
                        @endif
                    </div>
                    <p class="course-desc small text-muted mb-0">{{ Str::limit($course->description, 80) }}</p>
                </div>
            </div>
        </div>

        <div class="payment-benefits mb-4">
            <div class="d-flex align-items-center mb-3">
                <div class="benefit-icon me-3">
                    <i class="fas fa-unlock-alt text-primary fa-lg"></i>
                </div>
                <div class="benefit-text">
                    <h6 class="mb-0 fw-bold">Full Course Access</h6>
                    <p class="small text-muted mb-0">Immediate access to all materials</p>
                </div>
            </div>
            <div class="d-flex align-items-center mb-3">
                <div class="benefit-icon me-3">
                    <i class="fas fa-graduation-cap text-primary fa-lg"></i>
                </div>
                <div class="benefit-text">
                    <h6 class="mb-0 fw-bold">Premium Content</h6>
                    <p class="small text-muted mb-0">Expert-created study materials</p>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <div class="benefit-icon me-3">
                    <i class="fas fa-chart-line text-primary fa-lg"></i>
                </div>
                <div class="benefit-text">
                    <h6 class="mb-0 fw-bold">Progress Tracking</h6>
                    <p class="small text-muted mb-0">Track your learning journey</p>
                </div>
            </div>
        </div>

        <div class="payment-form">
            <h5 class="section-title fw-bold mb-3">Select Payment Method</h5>
            <form id="paymentMethodForm" data-course-id="{{ $course->id }}">
                @csrf
                <div class="payment-methods">
                    <div class="payment-method-option mb-3">
                        <input class="form-check-input visually-hidden" type="radio" name="payment_method" id="fawry" value="fawry" checked>
                        <label class="form-check-label payment-label d-flex align-items-center p-3 rounded border" for="fawry">
                            <div class="payment-radio me-3">
                                <div class="radio-indicator"></div>
                            </div>
                            <div class="payment-logo me-3">
                                <i class="fas fa-credit-card fa-2x text-primary"></i>
                            </div>
                            <div class="payment-info">
                                <h6 class="mb-0 fw-bold">Fawry</h6>
                                <p class="small text-muted mb-0">Pay online or at any Fawry outlet</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="payment-method-option mb-3">
                        <input class="form-check-input visually-hidden" type="radio" name="payment_method" id="vodafone_cash" value="vodafone_cash">
                        <label class="form-check-label payment-label d-flex align-items-center p-3 rounded border" for="vodafone_cash">
                            <div class="payment-radio me-3">
                                <div class="radio-indicator"></div>
                            </div>
                            <div class="payment-logo me-3">
                                <i class="fas fa-mobile-alt fa-2x text-danger"></i>
                            </div>
                            <div class="payment-info">
                                <h6 class="mb-0 fw-bold">Vodafone Cash</h6>
                                <p class="small text-muted mb-0">Pay using your Vodafone Cash wallet</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="payment-method-option">
                        <input class="form-check-input visually-hidden" type="radio" name="payment_method" id="manual_payment" value="manual_payment">
                        <label class="form-check-label payment-label d-flex align-items-center p-3 rounded border" for="manual_payment">
                            <div class="payment-radio me-3">
                                <div class="radio-indicator"></div>
                            </div>
                            <div class="payment-logo me-3">
                                <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                            </div>
                            <div class="payment-info">
                                <h6 class="mb-0 fw-bold">Self-Verification Payment</h6>
                                <p class="small text-muted mb-0">Pay directly & verify your transaction instantly</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="mt-3 small text-center">
                    <i class="fas fa-shield-alt text-primary me-1"></i>
                    All payment information is securely processed
                </div>
            </form>
        </div>
    </div>
    <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary px-4 py-2" id="proceedToPayment">
            <i class="fas fa-lock-open me-2"></i>Proceed to Payment
        </button>
    </div>
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
.payment-label {
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.payment-label:hover {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}
.payment-radio {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
}
.radio-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: white;
    transition: all 0.2s ease;
}
input[type="radio"]:checked + .payment-label {
    border-color: #0d6efd;
    background-color: #f0f7ff;
}
input[type="radio"]:checked + .payment-label .payment-radio {
    border-color: #0d6efd;
}
input[type="radio"]:checked + .payment-label .radio-indicator {
    background-color: #0d6efd;
}
.benefit-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #f0f7ff;
    display: flex;
    align-items: center;
    justify-content: center;
}
.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.2);
}
.btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
}
.section-title {
    color: #343a40;
    margin-top: 10px;
}
.modal-title {
    color: #212529;
    font-size: 1.5rem;
    letter-spacing: -0.02em;
}
</style>

<script>
    // Define simulation function globally first to ensure it's available
    window.simulatePaymentSuccess = function(paymentId) {
        console.log('Simulating payment success for ID:', paymentId);
        const csrfToken = document.querySelector("meta[name='csrf-token']")?.getAttribute("content");
        if (!csrfToken) { alert("CSRF token not found!"); return; }
        
        const simButton = document.querySelector("button[onclick^='simulatePaymentSuccess']");
        if (simButton) {
            simButton.innerHTML = "<span class='spinner-border spinner-border-sm' role='status'></span> Processing...";
            simButton.disabled = true;
        }
        
        fetch("/payments/" + paymentId + "/simulate-success", {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const statusAlert = document.querySelector(".alert");
                if (statusAlert) statusAlert.classList.replace("alert-info", "alert-success");
                
                const statusIcon = document.querySelector(".status-icon i");
                if (statusIcon) {
                    statusIcon.classList.remove("fa-spinner", "fa-spin");
                    statusIcon.classList.add("fa-check-circle");
                }
                
                const statusMessage = document.getElementById("status-message");
                if (statusMessage) statusMessage.innerHTML = "Payment successful! Redirecting...";
                
                setTimeout(() => { window.location.href = data.redirect; }, 2000);
            } else {
                alert("Payment simulation failed");
                if (simButton) {
                    simButton.innerHTML = "<i class='fas fa-vial me-2'></i>Simulate Success";
                    simButton.disabled = false;
                }
            }
        })
        .catch(error => {
            alert("Error: " + error.message);
            if (simButton) {
                simButton.innerHTML = "<i class='fas fa-vial me-2'></i>Simulate Success";
                simButton.disabled = false;
            }
        });
    };

    // Define both polling functions as placeholder functions
    // This prevents errors if the function is called before the specific modal is loaded
    if (typeof initVodafoneCashPolling === 'undefined') {
        window.initVodafoneCashPolling = function(paymentId) {
            console.log('Placeholder: Initializing Vodafone Cash polling for payment ID:', paymentId);
            // The actual implementation will be loaded with the Vodafone Cash modal
        };
    }

    if (typeof initFawryPolling === 'undefined') {
        window.initFawryPolling = function(paymentId) {
            console.log('Placeholder: Initializing Fawry polling for payment ID:', paymentId);
            // The actual implementation will be loaded with the Fawry modal
        };
    }

    // Modify the payment processing function to handle manual payments
    window.initializePaymentProcess = function(proceedButton, courseId, csrfToken) {
        if (!proceedButton) return;
        
        proceedButton.addEventListener('click', function() {
            // Disable the button to prevent multiple clicks
            proceedButton.disabled = true;
            proceedButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';

            // Get selected payment method
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            // Handle manual payment option differently
            if (paymentMethod === 'manual_payment') {
                fetch(`/payments/${courseId}/manual-options`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('#paymentModal .modal-content').innerHTML = data.html;
                    } else {
                        alert(data.message || 'Failed to load manual payment options.');
                        proceedButton.disabled = false;
                        proceedButton.innerHTML = '<i class="fas fa-lock-open me-2"></i>Proceed to Payment';
                    }
                })
                .catch(error => {
                    console.error('Error loading manual payment options:', error);
                    alert('An error occurred. Please try again.');
                    proceedButton.disabled = false;
                    proceedButton.innerHTML = '<i class="fas fa-lock-open me-2"></i>Proceed to Payment';
                });
                
                return;
            }
            
            // Regular payment processing for other methods
            fetch(`/payments/${courseId}/process`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    payment_method: paymentMethod
                })
            })
            .then(response => response.json())
            .then(data => {
                // ... existing code ...
            })
            .catch(error => {
                // ... existing code ...
            });
        });
    };
</script> 