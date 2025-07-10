@if(isset($debug) && !empty($debug))
    <div class="alert alert-info">
        <h5>Debug Info (Admin Only)</h5>
        <pre>{{ json_encode($debug, JSON_PRETTY_PRINT) }}</pre>
    </div>
@endif

@if($isEnrolled)
    <!-- User is enrolled, show access content -->
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i> You have access to this course
    </div>
    <!-- Show course content here -->
@else
    @if($course->hasUserPendingRequest())
    <!-- User has pending request, show pending status -->
    <div class="alert alert-warning">
        <i class="fas fa-clock me-2"></i> Your payment is being processed. Please wait for admin approval.
    </div>
    <button type="button" class="btn btn-warning" disabled>
        <i class="fas fa-spinner fa-spin me-2"></i> Payment Pending
    </button>
@else
    <!-- User is not enrolled, show payment options -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> This course requires payment for access
    </div>
    <a href="{{ secure_url(route('payments.options', $course->id, false)) }}" class="btn btn-primary">
        <i class="fas fa-credit-card me-2"></i> Pay to Access
    </a>
@endif
@endif 