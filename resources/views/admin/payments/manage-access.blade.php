@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="container py-4">
    <h2 class="mb-4">Payment Management</h2>
    
    <!-- JavaScript for handling payment actions -->
    <script>
    // Ensure Bootstrap is loaded
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap is not loaded!');
            return;
        }
        console.log('Bootstrap is loaded');
        
        // Pre-initialize the modal
        const verificationModal = new bootstrap.Modal(document.getElementById('verificationModal'));
    });

    function acceptPayment(paymentId) {
        console.log('Accept payment called for ID:', paymentId);
        
        // Show verification modal
        const modalElement = document.getElementById('verificationModal');
        if (!modalElement) {
            console.error('Verification modal not found!');
            return;
        }
        console.log('Modal element found');
        
        try {
            const modal = new bootstrap.Modal(modalElement);
            console.log('Modal instance created');
            modal.show();
            console.log('Modal shown');
            
            // Handle confirmation
            const confirmBtn = document.getElementById('confirmVerification');
            if (!confirmBtn) {
                console.error('Confirm button not found!');
                return;
            }
            console.log('Confirm button found');
            
            confirmBtn.onclick = function() {
                console.log('Confirm button clicked');
                const adminNotes = document.getElementById('adminNotes').value;
                console.log('Admin notes:', adminNotes);
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    console.error('CSRF token not found!');
                    return;
                }
                console.log('CSRF token found');
                
                // Send verification request
                console.log('Sending verification request to:', `/admin/payments/${paymentId}/verify`);
                fetch(`/admin/payments/${paymentId}/verify`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ admin_notes: adminNotes })
                })
                .then(response => {
                    console.log('Response received:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Data received:', data);
                    if (data.success) {
                        // Hide modal
                        modal.hide();
                        
                        // Show success message
                        alert('Payment verified successfully!');
                        
                        // Reload page to show updated status
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to verify payment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while verifying the payment');
                });
            };
        } catch (error) {
            console.error('Error creating/showing modal:', error);
            alert('An error occurred while showing the verification modal');
        }
    }

    function rejectPayment(paymentId) {
        const reason = prompt('Please provide a reason for rejecting this payment:');
        if (!reason) return; // User cancelled
        
        fetch(`/admin/payments/${paymentId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment rejected successfully');
                window.location.reload();
            } else {
                alert(data.message || 'Failed to reject payment');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while rejecting the payment');
        });
    }
    </script>

    <!-- Payment History Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Course</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>{{ $payment->user->name }}<br><small>{{ $payment->user->email }}</small></td>
                    <td>{{ $payment->course->name }}</td>
                    <td>{{ number_format($payment->amount, 2) }} EGP</td>
                    <td><span class="badge bg-secondary">{{ $payment->payment_method }}</span></td>
                    <td><span class="badge bg-{{ $payment->status == 'verified' ? 'success' : 'warning' }}">{{ $payment->status }}</span></td>
                    <td>{{ $payment->reference_number }}</td>
                    <td>{{ $payment->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                        @if($payment->status == 'pending_verification')
                        <button onclick="acceptPayment({{ $payment->id }})" class="btn btn-sm btn-success">
                            <i class="fas fa-check"></i> Accept
                        </button>
                        <button onclick="rejectPayment({{ $payment->id }})" class="btn btn-sm btn-danger">
                            <i class="fas fa-times"></i> Reject
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Verification Modal -->
    <div class="modal fade" id="verificationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Verify Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="adminNotes" class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" id="adminNotes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmVerification">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 