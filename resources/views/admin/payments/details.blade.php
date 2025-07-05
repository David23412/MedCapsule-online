@extends('layouts.admin')

@section('title', 'Payment Details')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center pb-0">
                    <h5 class="mb-0">Payment Details</h5>
                    <div>
                        <a href="{{ route('admin.payment-history') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Payments
                        </a>
                        
                        @if($payment->status === 'pending_verification')
                        <button class="btn btn-sm btn-success ms-2" id="verify-payment-btn">
                            <i class="fas fa-check me-1"></i> Verify Payment
                        </button>
                        <button class="btn btn-sm btn-danger ms-2" id="reject-payment-btn">
                            <i class="fas fa-times me-1"></i> Reject Payment
                        </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light py-3">
                                    <h6 class="mb-0 fw-bold">Payment Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th style="width: 150px;">Reference Number</th>
                                            <td>
                                                <code class="bg-light px-2 py-1">{{ $payment->reference_number }}</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Amount</th>
                                            <td><strong class="text-primary">{{ number_format($payment->amount, 2) }} EGP</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                @if($payment->status === 'completed')
                                                <span class="badge bg-success">Completed</span>
                                                @elseif($payment->status === 'pending_verification')
                                                <span class="badge bg-warning">Pending Verification</span>
                                                @elseif($payment->status === 'pending')
                                                <span class="badge bg-info">Pending Payment</span>
                                                @elseif($payment->status === 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                                @elseif($payment->status === 'expired')
                                                <span class="badge bg-secondary">Expired</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Payment Method</th>
                                            <td>
                                                @php
                                                    $methodUsed = $payment->payment_data['payment_method_used'] ?? $payment->payment_method ?? 'Unknown';
                                                    $methodNames = [
                                                        'vodafone_cash' => 'Vodafone Cash',
                                                        'fawry' => 'Fawry',
                                                        'instapay' => 'Instapay',
                                                        'manual_payment' => 'Manual Payment'
                                                    ];
                                                @endphp
                                                {{ $methodNames[$methodUsed] ?? $methodUsed }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <td>
                                                @if(isset($payment->payment_data['transaction_id']))
                                                <code class="bg-light px-2 py-1">{{ $payment->payment_data['transaction_id'] }}</code>
                                                @else
                                                <span class="text-muted">Not submitted yet</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td>{{ $payment->created_at->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                        @if($payment->status === 'completed' && $payment->paid_at)
                                        <tr>
                                            <th>Paid At</th>
                                            <td>{{ $payment->paid_at->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                        @endif
                                        @if(isset($payment->payment_data['submission_date']))
                                        <tr>
                                            <th>Submitted At</th>
                                            <td>{{ \Carbon\Carbon::parse($payment->payment_data['submission_date'])->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                        @endif
                                        @if(isset($payment->payment_data['payment_date']))
                                        <tr>
                                            <th>Payment Date</th>
                                            <td>{{ $payment->payment_data['payment_date'] }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card border-0 shadow-sm mt-4">
                                <div class="card-header bg-light py-3">
                                    <h6 class="mb-0 fw-bold">Course & User Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="mb-1">Course</h6>
                                        <p class="mb-0">
                                            <a href="{{ route('admin.courses.edit', $payment->course_id) }}" class="fw-bold text-decoration-none">
                                                {{ $payment->course->name }}
                                            </a>
                                        </p>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">User</h6>
                                        <p class="mb-0">
                                            <a href="{{ route('admin.users.show', $payment->user_id) }}" class="fw-bold text-decoration-none">
                                                {{ $payment->user->name }} ({{ $payment->user->email }})
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light py-3">
                                    <h6 class="mb-0 fw-bold">Payment Proof</h6>
                                </div>
                                <div class="card-body">
                                    @if($hasProofImage)
                                    <div class="text-center mb-3">
                                        <img src="{{ $proofUrl }}" class="img-fluid img-thumbnail" style="max-height: 400px;" alt="Payment Proof">
                                    </div>
                                    <div class="d-grid">
                                        <a href="{{ $proofUrl }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-external-link-alt me-1"></i> View Full Size
                                        </a>
                                    </div>
                                    @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No payment proof image has been submitted yet.
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            @if(isset($payment->payment_data['ocr_validation']))
                            <div class="card border-0 shadow-sm mt-4">
                                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">OCR Validation Results</h6>
                                    <span class="badge {{ $payment->payment_data['ocr_validation']['is_valid'] ? 'bg-success' : 'bg-warning' }}">
                                        {{ $payment->payment_data['ocr_validation']['is_valid'] ? 'Auto-Verified' : 'Manual Review Needed' }}
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="fw-bold mb-2">Confidence Score: 
                                            <span class="text-{{ $payment->payment_data['ocr_validation']['confidence'] >= 70 ? 'success' : ($payment->payment_data['ocr_validation']['confidence'] >= 40 ? 'warning' : 'danger') }}">
                                                {{ round($payment->payment_data['ocr_validation']['confidence']) }}%
                                            </span>
                                        </h6>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-{{ $payment->payment_data['ocr_validation']['confidence'] >= 70 ? 'success' : ($payment->payment_data['ocr_validation']['confidence'] >= 40 ? 'warning' : 'danger') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $payment->payment_data['ocr_validation']['confidence'] }}%;" 
                                                 aria-valuenow="{{ $payment->payment_data['ocr_validation']['confidence'] }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted">Score: {{ $payment->payment_data['ocr_validation']['score'] }}/{{ $payment->payment_data['ocr_validation']['max_score'] }}</small>
                                    </div>
                                    
                                    <h6 class="fw-bold mb-2">Extracted Information</h6>
                                    <table class="table table-sm table-bordered">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Field</th>
                                                <th>Extracted Value</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Reference Number</td>
                                                <td>
                                                    @if($payment->payment_data['ocr_validation']['extracted_data']['reference_number'])
                                                    <code>{{ $payment->payment_data['ocr_validation']['extracted_data']['reference_number'] }}</code>
                                                    @else
                                                    <span class="text-muted">Not found</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($payment->payment_data['ocr_validation']['extracted_data']['reference_number'])
                                                    <i class="fas fa-check-circle text-success"></i>
                                                    @else
                                                    <i class="fas fa-times-circle text-danger"></i>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Amount</td>
                                                <td>
                                                    @if($payment->payment_data['ocr_validation']['extracted_data']['amount'])
                                                    <code>{{ $payment->payment_data['ocr_validation']['extracted_data']['amount'] }}</code>
                                                    @else
                                                    <span class="text-muted">Not found</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($payment->payment_data['ocr_validation']['extracted_data']['amount'])
                                                    <i class="fas fa-check-circle text-success"></i>
                                                    @else
                                                    <i class="fas fa-times-circle text-danger"></i>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Transaction ID</td>
                                                <td>
                                                    @if($payment->payment_data['ocr_validation']['extracted_data']['transaction_id'])
                                                    <code>{{ $payment->payment_data['ocr_validation']['extracted_data']['transaction_id'] }}</code>
                                                    @else
                                                    <span class="text-muted">Not found</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($payment->payment_data['ocr_validation']['extracted_data']['transaction_id'])
                                                    <i class="fas fa-check-circle text-success"></i>
                                                    @else
                                                    <i class="fas fa-times-circle text-danger"></i>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Date</td>
                                                <td>
                                                    @if($payment->payment_data['ocr_validation']['extracted_data']['date'])
                                                    <code>{{ $payment->payment_data['ocr_validation']['extracted_data']['date'] }}</code>
                                                    @else
                                                    <span class="text-muted">Not found</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($payment->payment_data['ocr_validation']['extracted_data']['date'])
                                                    <i class="fas fa-check-circle text-success"></i>
                                                    @else
                                                    <i class="fas fa-times-circle text-danger"></i>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    @if(isset($payment->payment_data['ocr_validation']['error']))
                                    <div class="alert alert-danger mt-3 mb-0">
                                        <strong>OCR Error:</strong> {{ $payment->payment_data['ocr_validation']['error'] }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                            
                            <div class="card border-0 shadow-sm mt-4">
                                <div class="card-header bg-light py-3">
                                    <h6 class="mb-0 fw-bold">Additional Data</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        @if(isset($payment->payment_data['submission_ip']))
                                        <tr>
                                            <th>IP Address</th>
                                            <td>{{ $payment->payment_data['submission_ip'] }}</td>
                                        </tr>
                                        @endif
                                        
                                        @if(isset($payment->payment_data['notes']))
                                        <tr>
                                            <th>User Notes</th>
                                            <td>{{ $payment->payment_data['notes'] ?: 'No notes provided' }}</td>
                                        </tr>
                                        @endif
                                        
                                        @if(isset($payment->payment_data['admin_notes']))
                                        <tr>
                                            <th>Admin Notes</th>
                                            <td>{{ $payment->payment_data['admin_notes'] }}</td>
                                        </tr>
                                        @endif
                                        
                                        @if(isset($payment->payment_data['ocr_admin_note']))
                                        <tr>
                                            <th>OCR Note</th>
                                            <td>{{ $payment->payment_data['ocr_admin_note'] }}</td>
                                        </tr>
                                        @endif
                                        
                                        @if(isset($payment->payment_data['verified_by']))
                                        <tr>
                                            <th>Verified By</th>
                                            <td>
                                                @if($payment->payment_data['verified_by'] === 'system')
                                                <span class="badge bg-info">System (Transaction ID Pattern)</span>
                                                @elseif($payment->payment_data['verified_by'] === 'system_ocr')
                                                <span class="badge bg-info">System (OCR Verification)</span>
                                                @else
                                                <span class="badge bg-primary">Admin (ID: {{ $payment->payment_data['verified_by'] }})</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                        
                                        @if(isset($payment->payment_data['verified_at']))
                                        <tr>
                                            <th>Verified At</th>
                                            <td>{{ \Carbon\Carbon::parse($payment->payment_data['verified_at'])->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                        @endif
                                        
                                        @if(isset($payment->payment_data['rejected_by']))
                                        <tr>
                                            <th>Rejected By</th>
                                            <td>Admin (ID: {{ $payment->payment_data['rejected_by'] }})</td>
                                        </tr>
                                        @endif
                                        
                                        @if(isset($payment->payment_data['rejection_reason']))
                                        <tr>
                                            <th>Rejection Reason</th>
                                            <td>{{ $payment->payment_data['rejection_reason'] }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verifyPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verify Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="verify-payment-form">
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Add any notes about this verification"></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will mark the payment as completed and grant the user access to the course.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirm-verify-btn">Verify Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reject-payment-form">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason</label>
                        <select class="form-select" id="rejection_reason" name="reason" required>
                            <option value="">Select a reason</option>
                            <option value="Invalid payment proof">Invalid payment proof</option>
                            <option value="Transaction ID not found">Transaction ID not found</option>
                            <option value="Reference number mismatch">Reference number mismatch</option>
                            <option value="Amount mismatch">Amount mismatch</option>
                            <option value="Suspected fraud">Suspected fraud</option>
                            <option value="Other">Other (specify in notes)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="rejection_notes" class="form-label">Admin Notes (Optional)</label>
                        <textarea class="form-control" id="rejection_notes" name="admin_notes" rows="3" placeholder="Add any notes about this rejection"></textarea>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will reject the payment. The user will need to submit a new payment.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-reject-btn">Reject Payment</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Verify payment button
        $('#verify-payment-btn').on('click', function() {
            $('#verifyPaymentModal').modal('show');
        });
        
        // Confirm verification
        $('#confirm-verify-btn').on('click', function() {
            const adminNotes = $('#admin_notes').val();
            
            $.ajax({
                url: '{{ route("admin.payments.verify", $payment->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    admin_notes: adminNotes
                },
                beforeSend: function() {
                    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Payment verified successfully');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        toastr.error(response.message || 'An error occurred');
                        $(this).prop('disabled', false).html('Verify Payment');
                    }
                },
                error: function() {
                    toastr.error('An error occurred while verifying the payment');
                    $(this).prop('disabled', false).html('Verify Payment');
                }
            });
        });
        
        // Reject payment button
        $('#reject-payment-btn').on('click', function() {
            $('#rejectPaymentModal').modal('show');
        });
        
        // Confirm rejection
        $('#confirm-reject-btn').on('click', function() {
            const reason = $('#rejection_reason').val();
            const adminNotes = $('#rejection_notes').val();
            
            if (!reason) {
                toastr.error('Please select a rejection reason');
                return;
            }
            
            $.ajax({
                url: '{{ route("admin.payments.reject", $payment->id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    reason: reason,
                    admin_notes: adminNotes
                },
                beforeSend: function() {
                    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Payment rejected successfully');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        toastr.error(response.message || 'An error occurred');
                        $(this).prop('disabled', false).html('Reject Payment');
                    }
                },
                error: function() {
                    toastr.error('An error occurred while rejecting the payment');
                    $(this).prop('disabled', false).html('Reject Payment');
                }
            });
        });
    });
</script>
@endsection 