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
    <!-- User is not enrolled, show request access button -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> You need to request access to this course
    </div>
    <form action="{{ route('courses.request-access', $course->slug) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-unlock-alt me-2"></i> Request Access
        </button>
    </form>
@endif 