<style>
    .payment-modal-shell {
        background: #fff;
        overflow: hidden;
    }

    .payment-modal-shell .modal-header {
        border-bottom: 1px solid #eef2f7;
        padding: 16px 20px;
    }

    .payment-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #111827;
        font-size: 1.08rem;
        font-weight: 800;
    }

    .payment-title i {
        color: #2563eb;
    }

    .payment-hero {
        position: relative;
        padding: 22px;
        border-radius: 14px;
        background:
            radial-gradient(circle at top right, rgba(236, 72, 153, 0.24), transparent 34%),
            linear-gradient(135deg, #2563eb 0%, #7c3aed 58%, #db2777 100%);
        color: #fff;
        box-shadow: 0 18px 40px rgba(37, 99, 235, 0.22);
    }

    .payment-hero-label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .payment-course-name {
        margin: 14px 0 8px;
        font-size: clamp(1.45rem, 3vw, 2rem);
        font-weight: 900;
        line-height: 1.05;
    }

    .payment-course-copy {
        max-width: 30rem;
        margin: 0;
        color: rgba(255, 255, 255, 0.86);
        font-size: 0.95rem;
        line-height: 1.45;
    }

    .payment-price {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-top: 18px;
        font-size: 2rem;
        font-weight: 900;
        line-height: 1;
    }

    .payment-price span {
        font-size: 0.95rem;
        font-weight: 800;
        opacity: 0.88;
    }

    .payment-value-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin: 14px 0 18px;
    }

    .payment-value-item {
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
        color: #334155;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .payment-value-item i {
        display: block;
        margin-bottom: 8px;
        color: #2563eb;
        font-size: 1rem;
    }

    .instapay-card {
        padding: 18px;
        border: 1px solid rgba(219, 39, 119, 0.2);
        border-radius: 14px;
        background: linear-gradient(180deg, #fff7fb 0%, #ffffff 100%);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }

    .instapay-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }

    .instapay-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #db2777;
        color: #fff;
        box-shadow: 0 10px 18px rgba(219, 39, 119, 0.22);
    }

    .instapay-heading h6 {
        margin: 0;
        color: #111827;
        font-size: 1rem;
        font-weight: 900;
    }

    .instapay-heading p {
        margin: 2px 0 0;
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .instapay-copy-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 44px;
        gap: 10px;
        align-items: center;
    }

    .instapay-handle {
        min-width: 0;
        overflow: hidden;
        padding: 13px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        color: #be185d;
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 1rem;
        font-weight: 900;
        letter-spacing: 0.02em;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .copy-btn {
        width: 44px;
        height: 44px;
        border: 0;
        border-radius: 10px;
        background: #eef2ff;
        color: #2563eb;
    }

    .instapay-note {
        margin-top: 12px;
        color: #15803d;
        font-size: 0.86rem;
        font-weight: 700;
        line-height: 1.4;
    }

    .payment-form-card {
        margin-top: 16px;
    }

    .payment-form-card label {
        color: #111827;
        font-weight: 800;
    }

    .payment-form-card .form-control {
        min-height: 50px;
        border-radius: 10px;
        border-color: #dbe3ef;
        font-weight: 700;
    }

    .payment-submit {
        min-height: 52px;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #2563eb, #db2777);
        font-size: 1rem;
        font-weight: 900;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.24);
    }

    .payment-submit:hover,
    .payment-submit:focus {
        background: linear-gradient(135deg, #1d4ed8, #be185d);
    }

    .payment-modal-shell .modal-footer {
        border-top: 1px solid #eef2f7;
        padding: 12px 20px;
    }

    @media (max-width: 575px) {
        .payment-value-grid {
            grid-template-columns: 1fr;
        }

        .payment-hero {
            padding: 18px;
        }
    }
</style>

<div class="payment-modal-shell">
    <div class="modal-header">
        <h5 class="payment-title" id="paymentModalLabel">
            <i class="fas fa-bolt"></i>
            Unlock Course
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body p-4">
        <div class="payment-hero">
            <div class="payment-hero-label">
                <i class="fas fa-crown"></i>
                Instant access after approval
            </div>
            <h3 class="payment-course-name">{{ $course['name'] }}</h3>
            <p class="payment-course-copy">
                Get the full learning track, protected video lessons, topic practice, and progress tracking in one focused course space.
            </p>
            <div class="payment-price">
                {{ $course['price'] }}
                <span>{{ $course['currency'] }}</span>
            </div>
        </div>

        <div class="payment-value-grid">
            <div class="payment-value-item">
                <i class="fas fa-play-circle"></i>
                Full topic video access
            </div>
            <div class="payment-value-item">
                <i class="fas fa-chart-line"></i>
                Progress tracking
            </div>
            <div class="payment-value-item">
                <i class="fas fa-shield-alt"></i>
                Protected study session
            </div>
        </div>

        <div class="instapay-card">
            <div class="instapay-heading">
                <div class="instapay-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div>
                    <h6>Pay with InstaPay</h6>
                    <p>Use InstaPay or any supported wallet transfer.</p>
                </div>
            </div>

            <div class="instapay-copy-row">
                <code class="instapay-handle">{{ config('payment.instapay.number', 'INSTAPAY-USERNAME') }}</code>
                <button type="button" class="copy-btn" data-clipboard="{{ config('payment.instapay.number', 'INSTAPAY-USERNAME') }}" aria-label="Copy InstaPay account">
                    <i class="fas fa-copy"></i>
                </button>
            </div>

            <div class="instapay-note">
                <i class="fas fa-check-circle me-1"></i>
                After transfer, paste the reference number below so we can verify and unlock your course.
            </div>
        </div>

        <form id="coursePaymentForm" class="payment-form-card mb-0" method="POST" action="{{ route('payments.process', $course['id']) }}" data-course-id="{{ $course['id'] }}">
            @csrf
            <input type="hidden" id="paymentMethodField" name="payment_method" value="instapay">
            <input type="hidden" name="amount" value="{{ $course['price'] }}">

            <div class="mb-3">
                <label for="transactionId" class="form-label" id="transactionIdLabel">InstaPay Reference Number</label>
                <input type="text" class="form-control form-control-lg" id="transactionId" name="transaction_id" placeholder="Example: IP12345678901234" required>
            </div>

            <button type="submit" class="btn btn-primary payment-submit w-100" id="submitBtn">
                Submit Payment
            </button>
        </form>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-link text-muted text-decoration-none ms-auto" data-bs-dismiss="modal">Cancel</button>
    </div>
</div>
