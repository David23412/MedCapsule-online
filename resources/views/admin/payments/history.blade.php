@extends('layouts.app')

@section('title', 'Payment Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-credit-card me-2"></i>Payment Management
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Course</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Transaction ID</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                <tr data-payment-id="{{ $payment->id }}">
                                    <td>
                                        <strong>{{ $payment->user->name }}</strong><br>
                                        <small class="text-muted">{{ $payment->user->email }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $payment->course->name }}</strong><br>
                                        <small class="text-muted">ID: {{ $payment->course->id }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $payment->amount }} EGP</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                    </td>
                                    <td>
                                        @if($payment->status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($payment->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($payment->status === 'rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        <code>{{ $payment->reference_number }}</code>
                                    </td>
                                    <td>
                                        {{ $payment->created_at->format('M d, Y H:i') }}
                                    </td>
                                    <td>
                                        @if($payment->status === 'pending')
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success accept-payment"
                                                    data-payment-id="{{ $payment->id }}"
                                                    data-bs-toggle="tooltip"
                                                    title="Accept Payment">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-danger reject-payment"
                                                    data-payment-id="{{ $payment->id }}"
                                                    data-bs-toggle="tooltip"
                                                    title="Reject Payment">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $payments->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    window.MANAGE_ACCESS_CONFIG = {csrfToken: @json(csrf_token())};
    window.loadPayments = () => window.location.reload();
    window.showNotification = (type, title, message) => alert(title + ': ' + (message || ''));
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.accept-payment').forEach(button => button.addEventListener('click', () => {
            if (confirm('Are you sure you want to accept this payment? The user will be enrolled in the course.')) acceptPayment(button.dataset.paymentId);
        }));
        document.querySelectorAll('.reject-payment').forEach(button => button.addEventListener('click', () => {
            if (confirm('Are you sure you want to reject this payment?')) rejectPayment(button.dataset.paymentId);
        }));
    });
</script>
<script src="{{ app(\App\Services\BladeAssetService::class)->url('js/admin-payment-actions.js') }}" defer></script>
@endpush
