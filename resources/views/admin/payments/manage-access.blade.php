@extends('layouts.app')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="user-id" content="{{ auth()->id() }}">
<meta name="user-role" content="{{ auth()->user()->role ?? '' }}">
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
                
                // Check if user is authenticated
                if (!document.querySelector('meta[name="user-id"]')) {
                    console.error('User not authenticated');
                    alert('You must be logged in to verify payments. Please log in and try again.');
                    // Redirect to login page
                    window.location.href = '/login';
                    return;
                }

                // Check if user is admin
                const userRole = document.querySelector('meta[name="user-role"]')?.getAttribute('content');
                if (userRole !== 'admin') {
                    console.error('User is not admin');
                    alert('You must be an admin to verify payments. Please contact an administrator.');
                    return;
                }

                // Send verification request
                console.log('Sending verification request to:', `/admin/payments/${paymentId}/verify`);
                console.log('Request payload:', { admin_notes: adminNotes });
                console.log('CSRF token:', csrfToken.getAttribute('content'));
                console.log('User role:', userRole);
                
                // Add retry mechanism
                const makeRequest = (retryCount = 0) => {
                    const maxRetries = 3;
                    
                    fetch(`/admin/payments/verify/${paymentId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ admin_notes: adminNotes }),
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        console.log('Response received:', response);
                        console.log('Response status:', response.status);
                        
                        if (!response.ok) {
                            console.error('Response not ok:', response.status, response.statusText);
                            
                            if (response.status === 404) {
                                throw new Error('Route not found. Please check if you are logged in as admin.');
                            } else if (response.status === 419) {
                                throw new Error('CSRF token expired. Please refresh the page and try again.');
                            } else if (response.status === 403) {
                                throw new Error('Access denied. You must be an admin to verify payments.');
                            } else if (response.status === 401) {
                                throw new Error('Not authenticated. Please log in and try again.');
                            } else {
                                throw new Error(`Server error: ${response.status} ${response.statusText}`);
                            }
                        }
                        
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        
                        if (data.success) {
                            // Show success message
                            showNotification('Payment verified successfully!', 'success');
                            
                            // Update the UI
                            const row = document.querySelector(`[data-payment-id="${paymentId}"]`);
                            if (row) {
                                const statusCell = row.querySelector('.payment-status');
                                if (statusCell) {
                                    statusCell.textContent = 'Completed';
                                    statusCell.className = 'payment-status text-success';
                                }
                                
                                // Disable the verify button
                                const verifyBtn = row.querySelector('.verify-payment-btn');
                                if (verifyBtn) {
                                    verifyBtn.disabled = true;
                                    verifyBtn.textContent = 'Verified';
                                    verifyBtn.className = 'btn btn-success btn-sm verify-payment-btn';
                                }
                            }
                            
                            // Close modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('verifyPaymentModal'));
                            if (modal) {
                                modal.hide();
                            }
                        } else {
                            throw new Error(data.message || 'Payment verification failed');
                        }
                    })
                    .catch(error => {
                        console.error('Error accepting payment:', error);
                        
                        if (retryCount < maxRetries && error.message.includes('network')) {
                            console.log(`Retrying request (${retryCount + 1}/${maxRetries})...`);
                            setTimeout(() => makeRequest(retryCount + 1), 1000 * (retryCount + 1));
                        } else {
                            showNotification(`Error accepting payment: ${error.message}`, 'error');
                        }
                    });
                };
                
                makeRequest();
            };
        } catch (error) {
            console.error('Error creating/showing modal:', error);
            alert('An error occurred while showing the verification modal');
        }
    }

    function rejectPayment(paymentId) {
        const reason = prompt('Please provide a reason for rejecting this payment:');
        if (!reason) return; // User cancelled
        
        fetch(`/admin/payments/reject/${paymentId}`, {
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
                <tr data-payment-id="{{ $payment->id }}">
                    <td>{{ $payment->id }}</td>
                    <td>{{ $payment->user->name }}<br><small>{{ $payment->user->email }}</small></td>
                    <td>{{ $payment->course->name }}</td>
                    <td>{{ number_format($payment->amount, 2) }} EGP</td>
                    <td><span class="badge bg-secondary">{{ $payment->payment_method }}</span></td>
                    <td class="payment-status">{{ $payment->status }}</td>
                    <td>{{ $payment->reference_number }}</td>
                    <td>{{ $payment->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>
                        @if($payment->status == 'pending')
                        <button onclick="acceptPayment({{ $payment->id }})" class="btn btn-sm btn-success verify-payment-btn">
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
