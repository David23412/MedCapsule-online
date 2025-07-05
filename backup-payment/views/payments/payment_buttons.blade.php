@if (auth()->check())
    @if (Auth::user()->is_admin)
        <a href="{{ route('topics.forCourse', $course) }}" class="action-btn continue">
            <i class="fas fa-play"></i>
            Continue Learning
        </a>
    @elseif (Auth::user()->hasAccessToCourse($course->id))
        <a href="{{ route('topics.forCourse', $course) }}" class="action-btn continue">
            <i class="fas fa-play"></i>
            Continue Learning
        </a>
    @else
        <button type="button" class="action-btn enroll show-payment-modal" data-course-id="{{ $course->id }}">
            <i class="fas fa-unlock"></i>
            Pay to Access ({{ $course->formatted_price }})
        </button>
    @endif
@else
    <a href="{{ route('login') }}" class="action-btn enroll">
        <i class="fas fa-lock"></i>
        Sign in to Access
    </a>
@endif

@once
<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal content will be loaded dynamically -->
            <div class="modal-body text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading payment options...</p>
            </div>
        </div>
    </div>
</div>

<!-- Payment JS -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize payment modal
        const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
        
        // Add click event to all payment buttons
        document.querySelectorAll('.show-payment-modal').forEach(button => {
            button.addEventListener('click', function() {
                const courseId = this.getAttribute('data-course-id');
                
                // Show modal with loading state
                paymentModal.show();
                
                // Fetch payment options
                fetch(`/payments/${courseId}/options`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update modal content
                            document.querySelector('#paymentModal .modal-content').innerHTML = data.html;
                        } else {
                            // Show error message
                            alert(data.message);
                            
                            // Redirect if needed
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                paymentModal.hide();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        paymentModal.hide();
                    });
            });
        });
    });
</script>
@endonce 