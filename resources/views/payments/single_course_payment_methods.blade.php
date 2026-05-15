<style>
    .payment-modal-shell {
        background: #fff;
        overflow: hidden;
        direction: rtl;
        text-align: right;
    }

    .payment-modal-shell .modal-header {
        border-bottom: 1px solid #edf1f5;
        padding: 18px 22px;
        flex-direction: row-reverse;
    }

    .payment-title {
        display: flex;
        align-items: center;
        gap: 9px;
        margin: 0;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 750;
    }

    .payment-title i {
        color: #64748b;
    }

    .payment-modal-shell .btn-close {
        margin: 0;
    }

    .payment-hero {
        padding: 0 0 18px;
        border-bottom: 1px solid #edf1f5;
    }

    .payment-hero-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 10px;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .payment-course-name {
        margin: 0;
        color: #0f172a;
        font-size: clamp(1.35rem, 3vw, 1.75rem);
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: 0;
    }

    .payment-course-copy {
        max-width: 34rem;
        margin: 8px 0 0;
        color: #475569;
        font-size: 0.92rem;
        line-height: 1.5;
    }

    .payment-price {
        display: inline-flex;
        align-items: baseline;
        gap: 6px;
        margin-top: 14px;
        padding: 8px 11px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        color: #0f172a;
        background: #f8fafc;
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1;
    }

    .payment-price span {
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 750;
    }

    .payment-benefits {
        display: grid;
        gap: 11px;
        margin: 18px 0;
    }

    .payment-benefit-item {
        display: grid;
        grid-template-columns: 24px minmax(0, 1fr);
        gap: 10px;
        align-items: start;
        color: #334155;
        font-size: 0.9rem;
        font-weight: 650;
        line-height: 1.4;
    }

    .payment-benefit-check {
        width: 19px;
        height: 19px;
        margin-top: 1px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dcfce7;
        color: #16a34a;
        font-size: 0.68rem;
        box-shadow: inset 0 0 0 1px #bbf7d0;
    }

    .instapay-card {
        padding: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
    }

    .instapay-heading {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }

    .instapay-icon {
        width: auto;
        height: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        color: #db2777;
        box-shadow: none;
        font-size: 1rem;
    }

    .instapay-heading h6 {
        margin: 0;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 800;
    }

    .instapay-heading p {
        margin: 2px 0 0;
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 550;
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
        padding: 12px 13px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #f8fafc;
        color: #0f172a;
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.94rem;
        font-weight: 750;
        letter-spacing: 0.02em;
        text-overflow: ellipsis;
        white-space: nowrap;
        direction: ltr;
        text-align: left;
    }

    .copy-btn {
        width: 44px;
        height: 44px;
        border: 1px solid #dbe3ef;
        border-radius: 10px;
        background: #ffffff;
        color: #475569;
    }

    .instapay-note {
        margin-top: 11px;
        color: #475569;
        font-size: 0.82rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .payment-form-card {
        margin-top: 16px;
    }

    .payment-form-card label {
        color: #0f172a;
        font-size: 0.86rem;
        font-weight: 750;
    }

    .payment-form-card .form-control {
        min-height: 50px;
        border-radius: 10px;
        border-color: #dbe3ef;
        font-weight: 650;
        direction: ltr;
        text-align: left;
    }

    .payment-submit {
        min-height: 52px;
        border: 0;
        border-radius: 10px;
        background: #0f172a;
        font-size: 0.96rem;
        font-weight: 800;
    }

    .payment-submit:hover,
    .payment-submit:focus {
        background: #1e293b;
    }

    .payment-modal-shell .modal-footer {
        border-top: 1px solid #edf1f5;
        padding: 12px 20px;
    }

    @media (max-width: 575px) {
        .payment-hero {
            padding-bottom: 16px;
        }
    }
</style>

<div class="payment-modal-shell">
    <div class="modal-header">
        <h5 class="payment-title" id="paymentModalLabel">
            افتح الكورس كامل
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body p-4">
        <div class="payment-hero">
            <div class="payment-hero-label">
                ترقية للوصول الكامل
            </div>
            <h3 class="payment-course-name">{{ $course['name'] }}</h3>
            <p class="payment-course-copy">
                كل حاجة محتاجها للكورس في مكان واحد، وبعد تأكيد الدفع بيتفتحلك المحتوى كامل على المنهج كله.
            </p>
            <div class="payment-price">
                {{ $course['price'] }}
                <span>{{ $course['currency'] }}</span>
            </div>
        </div>

        <div class="payment-benefits" aria-label="Upgrade benefits">
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>أكبر وأشمل QBank للكورس من كل المصادر: كويزات الكلية، فورماتيف، GD&apos;s، العملي، امتحانات سنين فاتت، وأسئلة معمولة مخصوص علشان مفيش Concept يعدّي من غير ما يتحل عليه</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>كل Concept في المحاضرة ليه حل عليه، حتى الحاجات اللي مش متغطية كويس في المصادر</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>الـ Data متقسمة حسب الـ Topics بتاعتك، وتعرف كل سؤال جاي من أنهي كويز أو سنة بالظبط</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>Quiz Mode وTutor Mode وRead Mode لكل Topic</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>مكتبة Visual Anatomy Atlas كاملة للـ Upper Limb وLower Limb وNeuroanatomy: Bones, Joints, Muscles بصور High Quality</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>الصور معمولة علشان تتخيل الـ Anatomical Relationships أسهل وتشوف الـ Structures بعينك بدل ما تحفظ كلام بس</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>Random Quiz Mode للتدريب العشوائي على المنهج كله</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>Search في كل أسئلة الكورس علشان توصل لأي سؤال أو مفهوم بسرعة</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>كل غلطة بتتسجل أوتوماتيك في Review Mistakes علشان ترجع تحلها تاني لحد ما تبقى صح</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>Learn Mistakes بيشرحلك أخطاءك ويحوّل نقاط ضعفك لمراجعة مركزة بدل ما تضيع منك</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>Spaced Repetition بيرجعلك الغلطات في الوقت الصح علشان تثبت معاك قبل الامتحان</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>متابعة أداءك في كل Topic: نسبة نجاحك، وقت دراستك، نقاط ضعفك، وتقدمك مع الوقت</span>
            </div>
            <div class="payment-benefit-item">
                <span class="payment-benefit-check"><i class="fas fa-check"></i></span>
                <span>Weak Topics في البروفايل بتوريك أنت ضعيف في أنهي Topics بالظبط وتقولك تحل على أنهي Topic دلوقتي</span>
            </div>
        </div>

        @php
            $configuredPaymentNumber = config('payment.instapay.number', config('payment.vodafone_cash.number'));
            $paymentNumber = preg_match('/^01[0125][0-9]{8}$/', (string) $configuredPaymentNumber)
                ? $configuredPaymentNumber
                : '01019070354';
        @endphp

        <div class="instapay-card">
            <div class="instapay-heading">
                <div class="instapay-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div>
                    <h6>الدفع عن طريق InstaPay أو Vodafone Cash</h6>
                    <p>حوّل على الرقم ده من InstaPay أو Vodafone Cash، وبعدها ابعت رقم العملية للتأكيد.</p>
                </div>
            </div>

            <div class="instapay-copy-row">
                <code class="instapay-handle">{{ $paymentNumber }}</code>
                <button type="button" class="copy-btn" data-clipboard="{{ $paymentNumber }}" aria-label="Copy payment number">
                    <i class="fas fa-copy"></i>
                </button>
            </div>

            <div class="instapay-note">
                <i class="fas fa-check-circle me-1"></i>
                بعد التحويل، حط رقم العملية هنا علشان نراجع الدفع ونفتحلك الكورس.
            </div>
        </div>

        <form id="coursePaymentForm" class="payment-form-card mb-0" method="POST" action="{{ route('payments.process', $course['id']) }}" data-course-id="{{ $course['id'] }}">
            @csrf
            <input type="hidden" id="paymentMethodField" name="payment_method" value="instapay">
            <input type="hidden" name="amount" value="{{ $course['price'] }}">

            <div class="mb-3">
                <label for="transactionId" class="form-label" id="transactionIdLabel">رقم عملية InstaPay</label>
                <input type="text" class="form-control form-control-lg" id="transactionId" name="transaction_id" placeholder="Example: IP12345678901234" required>
            </div>

            <button type="submit" class="btn btn-primary payment-submit w-100" id="submitBtn">
                تأكيد الدفع
            </button>
        </form>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-link text-muted text-decoration-none me-auto" data-bs-dismiss="modal">إلغاء</button>
    </div>
</div>
