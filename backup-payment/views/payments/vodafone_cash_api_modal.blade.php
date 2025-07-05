<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">
            <i class="fas fa-mobile-alt me-2 text-primary"></i>
            Vodafone Cash Payment {{ isset($instructions['debug_code']) ? '(Debug Mode)' : '' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body">
        @if(isset($instructions['debug_code']))
        <div class="alert alert-warning" role="alert">
            <div class="d-flex align-items-center">
                <div class="status-icon me-3">
                    <i class="fas fa-bug fa-2x"></i>
                </div>
                <div>
                    <h5 class="mb-1">Debug Mode Active</h5>
                    <p id="status-message" class="mb-0">
                        This is a simulated payment for testing purposes only. No actual payment will be processed.
                    </p>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-info" role="alert">
            <div class="d-flex align-items-center">
                <div class="status-icon me-3">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
                <div>
                    <h5 class="mb-1">Payment Pending</h5>
                    <p id="status-message" class="mb-0">
                        Please complete your payment using Vodafone Cash following the instructions below.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Payment Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Course:</strong></p>
                        <p class="mb-0">{{ $course->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Amount:</strong></p>
                        <p class="mb-0">{{ number_format($payment->amount, 2) }} EGP</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Reference Number:</strong></p>
                        <p class="mb-0">{{ $payment->reference_number }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Expires:</strong></p>
                        <p class="mb-0">{{ \Carbon\Carbon::parse($expiresAt)->format('F j, Y, g:i a') }}</p>
                    </div>
                </div>
                @if(isset($instructions['debug_code']))
                <div class="row mt-3">
                    <div class="col-12">
                        <p class="mb-1"><strong>Debug Code:</strong></p>
                        <p class="mb-0 text-danger">{{ $instructions['debug_code'] }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Payment Instructions</h5>
            </div>
            <div class="card-body">
                <ol class="list-group list-group-numbered mb-0">
                    @foreach($instructions['steps'] as $step)
                        <li class="list-group-item">{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">What happens next?</h5>
            </div>
            <div class="card-body">
                <p>After completing your payment:</p>
                <ol>
                    <li>We'll receive a notification from Vodafone Cash</li>
                    <li>Your payment will be verified automatically</li>
                    <li>You'll get immediate access to your course</li>
                    <li>You'll receive a confirmation email with payment details</li>
                </ol>
                <p class="mb-0">If you encounter any issues, please contact our support team with your reference number.</p>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        @if(config('app.env') === 'local' || config('app.env') === 'development' || config('payment.vodafone_cash.debug_mode') || isset($instructions['debug_code']))
            <button type="button" class="btn btn-success" onclick="simulatePaymentSuccess('{{ $payment->id }}')">
                <i class="fas fa-vial me-2"></i>Simulate Success
            </button>
        @endif
    </div>
</div> 