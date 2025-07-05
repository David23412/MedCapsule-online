@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Payment Management Dashboard</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#filterPanel">
                                <i class="fas fa-filter me-1"></i> Filters
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" id="bulkVerifyBtn" disabled>
                                <i class="fas fa-check me-1"></i> Verify Selected
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <!-- Stats Row -->
                    <div class="row mx-0 bg-light p-3 border-bottom">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body p-2 text-center">
                                    <h6 class="mb-0">Pending</h6>
                                    <h3 class="mb-0">{{ $stats['total_pending'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body p-2 text-center">
                                    <h6 class="mb-0">Completed</h6>
                                    <h3 class="mb-0">{{ $stats['total_completed'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body p-2 text-center">
                                    <h6 class="mb-0">Rejected</h6>
                                    <h3 class="mb-0">{{ $stats['total_rejected'] }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body p-2 text-center">
                                    <h6 class="mb-0">Total Revenue</h6>
                                    <h3 class="mb-0">{{ number_format($stats['total_amount'], 2) }} EGP</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body p-2 text-center">
                                    <h6 class="mb-0">Today's Revenue</h6>
                                    <h3 class="mb-0">{{ number_format($stats['today_amount'], 2) }} EGP</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Panel -->
                    <div class="collapse" id="filterPanel">
                        <div class="p-3 bg-light border-bottom">
                            <form method="GET" action="{{ route('admin.payment-history') }}" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label small">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="">All Statuses</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="pending_verification" {{ request('status') == 'pending_verification' ? 'selected' : '' }}>Pending Verification</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Reference</label>
                                    <input type="text" name="reference" class="form-control form-control-sm" value="{{ request('reference') }}" placeholder="Reference Number">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Transaction ID</label>
                                    <input type="text" name="transaction_id" class="form-control form-control-sm" value="{{ request('transaction_id') }}" placeholder="Transaction ID">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">From Date</label>
                                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">To Date</label>
                                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-sm me-2">Apply Filters</button>
                                    <a href="{{ route('admin.payment-history') }}" class="btn btn-secondary btn-sm">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Payments Table -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="30px">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                        </div>
                                    </th>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Course</th>
                                    <th>Ref Number</th>
                                    <th>Amount</th>
                                    <th>Transaction ID</th>
                                    <th>Payment Proof</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input payment-select" type="checkbox" value="{{ $payment->id }}" {{ $payment->status === 'completed' ? 'disabled' : '' }}>
                                        </div>
                                    </td>
                                    <td>{{ $payment->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if(isset($payment->user->profile_picture))
                                            <img src="{{ asset('storage/' . $payment->user->profile_picture) }}" class="me-2 rounded-circle" width="30" height="30" alt="User Avatar" onerror="this.src='{{ asset('images/default-avatar.png') }}'">
                                            @else
                                            <img src="{{ asset('images/default-avatar.png') }}" class="me-2 rounded-circle" width="30" height="30" alt="User Avatar">
                                            @endif
                                            <div>
                                                <div class="fw-bold">{{ $payment->user->name }}</div>
                                                <div class="small text-muted">{{ $payment->user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $payment->course->name }}</td>
                                    <td><code>{{ $payment->reference_number }}</code></td>
                                    <td>{{ number_format($payment->amount, 2) }} EGP</td>
                                    <td>
                                        @if(isset($payment->payment_data['transaction_id']))
                                        <code>{{ $payment->payment_data['transaction_id'] }}</code>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($payment->payment_data['proof_path']))
                                        <a href="{{ asset('storage/' . $payment->payment_data['proof_path']) }}" data-bs-toggle="modal" data-bs-target="#proofModal" class="btn btn-sm btn-info view-proof-btn" data-proof-url="{{ asset('storage/' . $payment->payment_data['proof_path']) }}" data-payment-id="{{ $payment->id }}">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        @else
                                        <span class="badge bg-secondary">No Proof</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @elseif($payment->status === 'pending_verification')
                                        <span class="badge bg-primary">Pending Verification</span>
                                        @elseif($payment->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                        @elseif($payment->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                        @elseif($payment->status === 'expired')
                                        <span class="badge bg-secondary">Expired</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $payment->created_at->format('Y-m-d') }}</div>
                                        <small class="text-muted">{{ $payment->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.payment-details', $payment->id) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                            
                                            @if($payment->status === 'pending_verification')
                                            <button type="button" class="btn btn-sm btn-outline-success verify-btn" data-payment-id="{{ $payment->id }}">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger reject-btn" data-payment-id="{{ $payment->id }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-3">No payments found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div>Showing {{ $payments->firstItem() ?? 0 }} to {{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} payments</div>
                    <div>{{ $payments->appends(request()->except('page'))->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Proof Modal -->
<div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="proofImage" class="img-fluid" alt="Payment Proof">
            </div>
            <div class="modal-footer">
                <a href="" id="downloadProof" class="btn btn-primary" download>Download Image</a>
                <button type="button" class="btn btn-success verify-from-modal" data-payment-id="">Verify Payment</button>
                <button type="button" class="btn btn-danger reject-from-modal" data-payment-id="">Reject Payment</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Verify Payment Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verify Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="verifyForm">
                    <div class="mb-3">
                        <label for="adminNotes" class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" id="adminNotes" rows="3" placeholder="Any notes about this verification"></textarea>
                    </div>
                    <input type="hidden" id="verifyPaymentId" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmVerify">Verify Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Payment Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <select class="form-select" id="rejectionReason" required>
                            <option value="">Select a reason</option>
                            <option value="Invalid payment proof">Invalid payment proof</option>
                            <option value="Incorrect amount">Incorrect amount</option>
                            <option value="Duplicate payment">Duplicate payment</option>
                            <option value="Payment not received">Payment not received</option>
                            <option value="Other">Other (specify in notes)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="rejectNotes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="rejectNotes" rows="3" placeholder="Additional details about the rejection"></textarea>
                    </div>
                    <input type="hidden" id="rejectPaymentId" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Reject Payment</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle payment proof modal
        const proofModal = document.getElementById('proofModal');
        const proofImage = document.getElementById('proofImage');
        const downloadProof = document.getElementById('downloadProof');
        const viewProofBtns = document.querySelectorAll('.view-proof-btn');
        
        viewProofBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const proofUrl = this.getAttribute('data-proof-url');
                const paymentId = this.getAttribute('data-payment-id');
                proofImage.src = proofUrl;
                downloadProof.href = proofUrl;
                document.querySelector('.verify-from-modal').setAttribute('data-payment-id', paymentId);
                document.querySelector('.reject-from-modal').setAttribute('data-payment-id', paymentId);
            });
        });
        
        // Handle verify payment
        const verifyModal = new bootstrap.Modal(document.getElementById('verifyModal'));
        const verifyButtons = document.querySelectorAll('.verify-btn, .verify-from-modal');
        const verifyPaymentIdInput = document.getElementById('verifyPaymentId');
        
        verifyButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const paymentId = this.getAttribute('data-payment-id');
                verifyPaymentIdInput.value = paymentId;
                verifyModal.show();
            });
        });
        
        // Handle confirm verify
        document.getElementById('confirmVerify').addEventListener('click', function() {
            const paymentId = verifyPaymentIdInput.value;
            const adminNotes = document.getElementById('adminNotes').value;
            
            fetch(`/admin/payments/${paymentId}/verify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    admin_notes: adminNotes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    verifyModal.hide();
                    alert('Payment verified successfully');
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to verify payment');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while verifying the payment');
            });
        });
        
        // Handle reject payment
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        const rejectButtons = document.querySelectorAll('.reject-btn, .reject-from-modal');
        const rejectPaymentIdInput = document.getElementById('rejectPaymentId');
        
        rejectButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const paymentId = this.getAttribute('data-payment-id');
                rejectPaymentIdInput.value = paymentId;
                rejectModal.show();
            });
        });
        
        // Handle confirm reject
        document.getElementById('confirmReject').addEventListener('click', function() {
            const paymentId = rejectPaymentIdInput.value;
            const reason = document.getElementById('rejectionReason').value;
            const adminNotes = document.getElementById('rejectNotes').value;
            
            if (!reason) {
                alert('Please select a rejection reason');
                return;
            }
            
            fetch(`/admin/payments/${paymentId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: reason,
                    admin_notes: adminNotes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    rejectModal.hide();
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
        });
        
        // Handle bulk verify
        const selectAll = document.getElementById('selectAll');
        const paymentCheckboxes = document.querySelectorAll('.payment-select');
        const bulkVerifyBtn = document.getElementById('bulkVerifyBtn');
        
        selectAll.addEventListener('change', function() {
            const isChecked = this.checked;
            
            paymentCheckboxes.forEach(checkbox => {
                if (!checkbox.disabled) {
                    checkbox.checked = isChecked;
                }
            });
            
            updateBulkVerifyButton();
        });
        
        paymentCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkVerifyButton);
        });
        
        function updateBulkVerifyButton() {
            const checkedCount = document.querySelectorAll('.payment-select:checked').length;
            bulkVerifyBtn.disabled = checkedCount === 0;
            bulkVerifyBtn.textContent = checkedCount > 0 ? `Verify Selected (${checkedCount})` : 'Verify Selected';
        }
        
        bulkVerifyBtn.addEventListener('click', function() {
            const selectedPayments = Array.from(document.querySelectorAll('.payment-select:checked')).map(checkbox => checkbox.value);
            
            if (selectedPayments.length === 0) {
                alert('Please select at least one payment to verify');
                return;
            }
            
            if (!confirm(`Are you sure you want to verify ${selectedPayments.length} payment(s)?`)) {
                return;
            }
            
            fetch('/admin/payments/bulk-verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    payment_ids: selectedPayments
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Payments verified successfully');
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to verify payments');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while verifying payments');
            });
        });
    });
</script>
@endsection 