@extends('layouts.modal')

@section('modal_title')
    <i class="fas fa-shopping-cart"></i> Payment Details
@endsection

@section('modal_content')
<div class="payment-summary mb-4">
    <h5>Bundle Summary</h5>
    <div class="d-flex justify-content-between mb-2">
        <span>Original Price:</span>
        <span>{{ number_format($original_price, 2) }} EGP</span>
    </div>
    @if($is_bundle)
    <div class="d-flex justify-content-between mb-2 text-success">
        <span>Bundle Discount:</span>
        <span>-{{ number_format($discount_amount, 2) }} EGP</span>
    </div>
    @endif
    <div class="d-flex justify-content-between fw-bold">
        <span>Total:</span>
        <span>{{ number_format($total_price, 2) }} EGP</span>
    </div>
</div>

<div class="payment-methods">
    <h5>Select Payment Method</h5>
    <div class="list-group">
        <a href="#" class="list-group-item list-group-item-action payment-method" data-method="fawry">
            <div class="d-flex align-items-center">
                <img src="/images/fawry.png" alt="Fawry" class="payment-icon me-3">
                <div>
                    <h6 class="mb-1">Pay with Fawry</h6>
                    <p class="mb-0 text-muted">Get a code to pay at any Fawry outlet</p>
                </div>
            </div>
        </a>
        <a href="#" class="list-group-item list-group-item-action payment-method" data-method="card">
            <div class="d-flex align-items-center">
                <img src="/images/credit-card.png" alt="Credit Card" class="payment-icon me-3">
                <div>
                    <h6 class="mb-1">Credit/Debit Card</h6>
                    <p class="mb-0 text-muted">Pay securely with your card</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection

@section('modal_footer')
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
@endsection 