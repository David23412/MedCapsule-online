# Payment Integration with Fawry and Vodafone Cash

## Overview

This document outlines how to integrate Fawry and Vodafone Cash payment gateways into MedCapsule's course enrollment system. The integration will allow students to automatically gain access to courses after completing a payment, replacing the current manual enrollment process.

## Current System Analysis

Currently, MedCapsule handles course enrollment in the following way:

1. Users can see available courses but need to "Request Access" to enroll
2. There's no automated payment system - enrollment is handled manually by administrators
3. The `CourseController@enroll` method facilitates enrollment, but requires admin approval
4. The enrollment relationship is managed through the `course_user` pivot table

## Proposed Payment Integration

### High-Level Architecture

```
User (Request Access) → Payment Gateway (Fawry/Vodafone Cash) → Webhook/Callback → Automatic Enrollment
```

### Database Changes Required

1. Create a new `payments` table to track payment transactions:

```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('course_id')->constrained();
    $table->string('reference_number')->unique();
    $table->decimal('amount', 10, 2);
    $table->string('payment_method'); // 'fawry', 'vodafone_cash', etc.
    $table->string('status'); // 'pending', 'completed', 'failed', 'expired'
    $table->json('payment_data')->nullable(); // Store gateway-specific data
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

2. Add payment-related fields to the `courses` table:

```php
Schema::table('courses', function (Blueprint $table) {
    $table->boolean('is_paid')->default(false);
    $table->decimal('price', 10, 2)->nullable();
    $table->string('currency')->default('EGP');
});
```

## Implementation Steps

### 1. Setting Up Fawry Integration

#### Dependencies

Add the Fawry PHP SDK to your project:

```bash
composer require fawry/fawry-php-sdk
```

> Note: If an official SDK isn't available, you'll need to implement the API integration manually.

#### Configuration

Add Fawry configuration to your `.env` file:

```
FAWRY_MERCHANT_CODE=your_merchant_code
FAWRY_SECURITY_KEY=your_security_key
FAWRY_RETURN_URL=https://your-app.com/payments/fawry/callback
FAWRY_SANDBOX_MODE=true  # Set to false in production
```

Create a configuration file at `config/payment.php`:

```php
return [
    'fawry' => [
        'merchant_code' => env('FAWRY_MERCHANT_CODE'),
        'security_key' => env('FAWRY_SECURITY_KEY'),
        'return_url' => env('FAWRY_RETURN_URL'),
        'sandbox_mode' => env('FAWRY_SANDBOX_MODE', true),
    ],
    'vodafone_cash' => [
        'api_key' => env('VODAFONE_CASH_API_KEY'),
        'merchant_id' => env('VODAFONE_CASH_MERCHANT_ID'),
        'callback_url' => env('VODAFONE_CASH_CALLBACK_URL'),
    ],
];
```

### 2. Setting Up Vodafone Cash Integration

#### Configuration

Add Vodafone Cash API details to your `.env` file:

```
VODAFONE_CASH_API_KEY=your_api_key
VODAFONE_CASH_MERCHANT_ID=your_merchant_id
VODAFONE_CASH_CALLBACK_URL=https://your-app.com/payments/vodafone-cash/callback
```

### 3. Create Payment Models and Controllers

#### Payment Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'reference_number',
        'amount',
        'payment_method',
        'status',
        'payment_data',
        'paid_at',
    ];

    protected $casts = [
        'payment_data' => 'json',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function markAsPaid()
    {
        $this->status = 'completed';
        $this->paid_at = now();
        $this->save();

        // Enroll the user in the course
        $this->user->enrollInCourse($this->course_id);

        // Create a notification
        app(NotificationService::class)->createPaymentSuccessNotification(
            $this->user,
            $this->course->name,
            $this->amount
        );

        return $this;
    }
}
```

#### Payment Service

```php
<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Create a new payment request for a course enrollment
     */
    public function createPaymentRequest(User $user, Course $course, string $paymentMethod)
    {
        $referenceNumber = $this->generateReferenceNumber();
        
        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'reference_number' => $referenceNumber,
            'amount' => $course->price,
            'payment_method' => $paymentMethod,
            'status' => 'pending',
        ]);

        return $payment;
    }

    /**
     * Generate a payment URL for Fawry
     */
    public function generateFawryPaymentUrl(Payment $payment)
    {
        // Here you would use the Fawry SDK to generate a payment URL
        $fawryParams = [
            'merchantCode' => config('payment.fawry.merchant_code'),
            'merchantRefNum' => $payment->reference_number,
            'customerProfileId' => $payment->user_id,
            'customerName' => $payment->user->name,
            'customerEmail' => $payment->user->email,
            'customerMobile' => $payment->user->phone ?? '',
            'amount' => $payment->amount,
            'currencyCode' => 'EGP',
            'description' => 'Enrollment in course: ' . $payment->course->name,
            'chargeItems' => [
                [
                    'itemId' => $payment->course_id,
                    'description' => $payment->course->name,
                    'price' => $payment->amount,
                    'quantity' => 1,
                ]
            ],
            'returnUrl' => config('payment.fawry.return_url'),
        ];

        // This would actually use the Fawry SDK
        // For demo purposes, we're just returning a placeholder
        $paymentUrl = "https://fawry.com/payment?ref=" . $payment->reference_number;
        
        // Store the payment data for future reference
        $payment->payment_data = ['payment_url' => $paymentUrl];
        $payment->save();
        
        return $paymentUrl;
    }

    /**
     * Generate a payment code for Vodafone Cash
     */
    public function generateVodafoneCashCode(Payment $payment)
    {
        // Here you would use the Vodafone Cash API to generate a payment code
        // This is a simplified example
        $code = strtoupper(Str::random(8));
        
        // Update the payment with the generated code
        $payment->payment_data = ['payment_code' => $code];
        $payment->save();
        
        return $code;
    }

    /**
     * Process a payment callback from Fawry
     */
    public function processFawryCallback($data)
    {
        $referenceNumber = $data['merchantRefNumber'] ?? null;
        
        if (!$referenceNumber) {
            return false;
        }
        
        $payment = Payment::where('reference_number', $referenceNumber)
            ->where('status', 'pending')
            ->first();
            
        if (!$payment) {
            return false;
        }
        
        $status = $data['paymentStatus'] ?? '';
        
        if ($status === 'PAID') {
            return $payment->markAsPaid();
        } elseif ($status === 'FAILED') {
            $payment->status = 'failed';
            $payment->save();
        } elseif ($status === 'EXPIRED') {
            $payment->status = 'expired';
            $payment->save();
        }
        
        return $payment;
    }

    /**
     * Process a payment callback from Vodafone Cash
     */
    public function processVodafoneCashCallback($data)
    {
        $referenceNumber = $data['reference'] ?? null;
        
        if (!$referenceNumber) {
            return false;
        }
        
        $payment = Payment::where('reference_number', $referenceNumber)
            ->where('status', 'pending')
            ->first();
            
        if (!$payment) {
            return false;
        }
        
        $status = $data['status'] ?? '';
        
        if ($status === 'SUCCESS') {
            return $payment->markAsPaid();
        } else {
            $payment->status = 'failed';
            $payment->save();
        }
        
        return $payment;
    }

    /**
     * Generate a unique reference number for the payment
     */
    private function generateReferenceNumber()
    {
        return 'MC' . time() . strtoupper(Str::random(6));
    }
}
```

#### Payment Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        $this->middleware('auth');
    }

    /**
     * Display the payment options for a course
     */
    public function showPaymentOptions(Course $course)
    {
        if (auth()->user()->hasAccessToCourse($course->id)) {
            return redirect()->route('topics.forCourse', $course->id)
                ->with('info', 'You already have access to this course.');
        }
        
        return view('payments.options', compact('course'));
    }

    /**
     * Process a payment request and redirect to the payment gateway
     */
    public function processPayment(Request $request, Course $course)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:fawry,vodafone_cash',
        ]);

        $user = auth()->user();
        $paymentMethod = $validated['payment_method'];
        
        // Create a payment record
        $payment = $this->paymentService->createPaymentRequest($user, $course, $paymentMethod);
        
        if ($paymentMethod === 'fawry') {
            // Generate Fawry payment URL
            $paymentUrl = $this->paymentService->generateFawryPaymentUrl($payment);
            
            // Redirect to Fawry payment page
            return redirect($paymentUrl);
        } elseif ($paymentMethod === 'vodafone_cash') {
            // Generate Vodafone Cash payment code
            $paymentCode = $this->paymentService->generateVodafoneCashCode($payment);
            
            // Show the payment code to the user
            return view('payments.vodafone_cash', compact('payment', 'paymentCode', 'course'));
        }
        
        return redirect()->back()->with('error', 'Invalid payment method.');
    }

    /**
     * Handle Fawry payment callback
     */
    public function fawryCallback(Request $request)
    {
        Log::info('Fawry callback received', $request->all());
        
        $payment = $this->paymentService->processFawryCallback($request->all());
        
        if ($payment && $payment->status === 'completed') {
            return redirect()->route('topics.forCourse', $payment->course_id)
                ->with('success', 'Payment successful! You now have access to the course.');
        }
        
        return redirect()->route('courses.index')
            ->with('error', 'Payment processing failed. Please try again or contact support.');
    }

    /**
     * Handle Vodafone Cash payment callback
     */
    public function vodafoneCashCallback(Request $request)
    {
        Log::info('Vodafone Cash callback received', $request->all());
        
        $payment = $this->paymentService->processVodafoneCashCallback($request->all());
        
        if ($payment && $payment->status === 'completed') {
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false]);
    }

    /**
     * Check the status of a Vodafone Cash payment
     */
    public function checkVodafoneCashStatus(Payment $payment)
    {
        if ($payment->status === 'completed') {
            return response()->json([
                'status' => 'completed',
                'redirect' => route('topics.forCourse', $payment->course_id)
            ]);
        }
        
        return response()->json(['status' => $payment->status]);
    }

    /**
     * Display payment history for the authenticated user
     */
    public function history()
    {
        $payments = auth()->user()->payments()->latest()->paginate(10);
        return view('payments.history', compact('payments'));
    }
}
```

### 4. Create Payment Views

#### Payment Options View (resources/views/payments/options.blade.php)

```html
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Payment for {{ $course->name }}</h3>
                </div>
                <div class="card-body">
                    <div class="course-details mb-4">
                        <div class="d-flex align-items-center">
                            <div class="course-image me-3">
                                <img src="{{ $course->image }}" alt="{{ $course->name }}" class="img-fluid" style="max-width: 120px;">
                            </div>
                            <div class="course-info">
                                <h4>{{ $course->name }}</h4>
                                <p class="text-muted">{{ $course->description }}</p>
                                <h5 class="price">{{$course->price}} EGP</h5>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('payments.process', $course) }}" method="POST">
                        @csrf
                        <div class="form-group mb-4">
                            <label class="form-label">Select Payment Method</label>
                            
                            <div class="payment-methods">
                                <div class="form-check payment-method-option">
                                    <input class="form-check-input" type="radio" name="payment_method" id="fawry" value="fawry" checked>
                                    <label class="form-check-label" for="fawry">
                                        <img src="{{ asset('images/fawry-logo.png') }}" alt="Fawry" height="30">
                                        Fawry
                                    </label>
                                </div>
                                
                                <div class="form-check payment-method-option mt-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="vodafone_cash" value="vodafone_cash">
                                    <label class="form-check-label" for="vodafone_cash">
                                        <img src="{{ asset('images/vodafone-cash-logo.png') }}" alt="Vodafone Cash" height="30">
                                        Vodafone Cash
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Proceed to Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### Vodafone Cash Payment View (resources/views/payments/vodafone_cash.blade.php)

```html
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Vodafone Cash Payment</h3>
                </div>
                <div class="card-body text-center">
                    <div class="payment-code-container mb-4">
                        <h4>Your Payment Code</h4>
                        <div class="payment-code">
                            <h1 class="display-4 code">{{ $paymentCode }}</h1>
                            <button class="btn btn-sm btn-outline-secondary copy-btn" onclick="copyPaymentCode()">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    
                    <div class="payment-instructions mb-4">
                        <h5>How to Pay:</h5>
                        <ol class="text-start">
                            <li>Dial *9# from your Vodafone number</li>
                            <li>Select "Pay" from the menu</li>
                            <li>Enter the merchant code: <strong>{{ config('payment.vodafone_cash.merchant_id') }}</strong></li>
                            <li>Enter the amount: <strong>{{ $course->price }} EGP</strong></li>
                            <li>Enter the payment code shown above</li>
                            <li>Confirm your payment</li>
                        </ol>
                    </div>
                    
                    <div class="payment-details mb-4">
                        <div class="d-flex justify-content-between">
                            <div>Course:</div>
                            <div><strong>{{ $course->name }}</strong></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div>Amount:</div>
                            <div><strong>{{ $course->price }} EGP</strong></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div>Reference:</div>
                            <div><strong>{{ $payment->reference_number }}</strong></div>
                        </div>
                    </div>
                    
                    <div class="payment-status mb-4">
                        <div class="alert alert-info">
                            <div id="status-message">
                                <i class="fas fa-spinner fa-spin me-2"></i>
                                Waiting for your payment...
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary">Cancel and Return to Courses</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function copyPaymentCode() {
        const code = '{{ $paymentCode }}';
        navigator.clipboard.writeText(code).then(() => {
            const copyBtn = document.querySelector('.copy-btn');
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy';
            }, 2000);
        });
    }
    
    // Check payment status every 5 seconds
    const checkPaymentStatus = () => {
        fetch('{{ route('payments.check-status', $payment) }}')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'completed') {
                    document.getElementById('status-message').innerHTML = 
                        '<i class="fas fa-check-circle text-success me-2"></i>' +
                        'Payment successful! Redirecting to your course...';
                    
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else if (data.status === 'failed') {
                    document.getElementById('status-message').innerHTML = 
                        '<i class="fas fa-times-circle text-danger me-2"></i>' +
                        'Payment failed. Please try again.';
                } else if (data.status === 'expired') {
                    document.getElementById('status-message').innerHTML = 
                        '<i class="fas fa-exclamation-circle text-warning me-2"></i>' +
                        'Payment expired. Please try again.';
                }
            });
    };
    
    // Check status initially and then every 5 seconds
    checkPaymentStatus();
    setInterval(checkPaymentStatus, 5000);
</script>
@endpush
@endsection
```

### 5. Update Routes

Add the following routes to your `routes/web.php` file:

```php
// Payment routes
Route::middleware('auth')->group(function () {
    // Payment options and processing
    Route::get('/courses/{course}/payment', [PaymentController::class, 'showPaymentOptions'])
        ->name('payments.options');
    Route::post('/courses/{course}/payment', [PaymentController::class, 'processPayment'])
        ->name('payments.process');
    
    // Payment callbacks and status checks
    Route::get('/payments/history', [PaymentController::class, 'history'])
        ->name('payments.history');
    Route::get('/payments/{payment}/check-status', [PaymentController::class, 'checkVodafoneCashStatus'])
        ->name('payments.check-status');
});

// Payment gateway callbacks (no authentication required)
Route::post('/payments/fawry/callback', [PaymentController::class, 'fawryCallback'])
    ->name('payments.fawry.callback')
    ->withoutMiddleware(['web', 'auth']);
Route::post('/payments/vodafone-cash/callback', [PaymentController::class, 'vodafoneCashCallback'])
    ->name('payments.vodafone-cash.callback')
    ->withoutMiddleware(['web', 'auth']);
```

### 6. Update UI

Modify the course request access button in your views:

```php
@if (auth()->check())
    @if (Auth::user()->is_admin)
        <a href="{{ route('topics.forCourse', $course) }}" class="action-btn continue">
            <i class="fas fa-play"></i>
            Continue Learning
        </a>
    @elseif ($course->is_enrolled)
        <a href="{{ route('topics.forCourse', $course) }}" class="action-btn continue">
            <i class="fas fa-play"></i>
            Continue Learning
        </a>
    @else
        <a href="{{ route('payments.options', $course) }}" class="action-btn enroll">
            <i class="fas fa-unlock"></i>
            Pay to Access ({{ $course->price }} EGP)
        </a>
    @endif
@else
    <a href="{{ route('login') }}" class="action-btn enroll">
        <i class="fas fa-lock"></i>
        Sign in to Access
    </a>
@endif
```

## Pros and Cons of API Integration

### Pros

1. **Automation**: Students can gain immediate access to courses after payment without manual intervention
2. **Improved User Experience**: Streamlined enrollment process with instant confirmation
3. **Reduced Administrative Burden**: No need for manual verification and enrollment
4. **Improved Tracking**: Better financial reporting and enrollment analytics
5. **Scalability**: Can handle a much larger number of students (100-200+) efficiently
6. **Payment Flexibility**: Support for multiple payment methods (Fawry, Vodafone Cash)
7. **Higher Conversion Rate**: Reducing friction in the payment process can lead to more enrollments

### Cons

1. **Implementation Complexity**: Requires significant development effort and testing
2. **Maintenance Overhead**: Need to maintain payment integrations and handle errors
3. **Security Concerns**: Payment processing requires additional security measures
4. **API Dependency**: Reliance on third-party services that may change or be unavailable
5. **Testing Challenges**: Payment flows need thorough testing in sandbox environments
6. **Transaction Fees**: Payment processors charge fees for each transaction
7. **Technical Support**: May require specific expertise to debug payment issues

## Implementation Complexity

The implementation is moderately complex, involving:

1. **Database Changes**: Adding payment-related tables and columns
2. **API Integration**: Connecting to Fawry and Vodafone Cash APIs
3. **Payment Flow**: Creating a seamless payment process
4. **Error Handling**: Managing failed payments and edge cases
5. **Security**: Ensuring secure handling of payment information
6. **User Interface**: Designing clear payment options and status pages

For your team, the estimated effort would be:
- **Backend Development**: 2-3 weeks
- **Frontend Implementation**: 1-2 weeks
- **Testing**: 1-2 weeks
- **Total**: 4-7 weeks of development time

## Alternative Options

If full API integration seems too complex initially, consider these alternatives:

1. **Semi-Automated Solution**: Generate payment codes that students can use, then manually verify and batch-enroll students once per day
2. **Third-Party Payment Processors**: Use established services that handle much of the payment complexity
3. **Phased Implementation**: Start with a simpler integration and enhance it over time

## Next Steps

1. Decide on the preferred payment providers (Fawry, Vodafone Cash, or others)
2. Request API documentation and credentials from the payment providers
3. Set up sandbox testing environments
4. Begin with database schema changes
5. Implement payment flows one provider at a time
6. Thoroughly test each payment method
7. Deploy with monitoring in place

## Conclusion

Integrating Fawry and Vodafone Cash payments into MedCapsule will significantly improve the course enrollment process, especially for handling larger numbers of students. While it requires initial development effort, the long-term benefits in terms of automation, scalability, and user experience make it a worthwhile investment. 