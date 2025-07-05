# MedCapsule Payment Gateway Integration Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Account Setup](#account-setup)
3. [Database Setup](#database-setup)
4. [Fawry Express Checkout Integration](#fawry-express-checkout-integration)
5. [Vodafone Cash Integration](#vodafone-cash-integration)
6. [Common Components](#common-components)
7. [Frontend Implementation](#frontend-implementation)
8. [Webhook Configuration](#webhook-configuration)
9. [Error Handling and Monitoring](#error-handling-and-monitoring)
10. [Security](#security)
11. [Testing](#testing)
12. [Going Live](#going-live)
13. [Production Readiness Checklist](#production-readiness-checklist)
14. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before starting the integration, ensure you have:

1. **Merchant accounts** with both Fawry and Vodafone Cash (see [Account Setup](#account-setup))
2. **Environment variables** properly set up as defined in `config/payment.php`:
   ```
   # Fawry Configuration
   FAWRY_MERCHANT_CODE=your_merchant_code
   FAWRY_SECURITY_KEY=your_security_key
   FAWRY_RETURN_URL=https://your-domain.com/payments/fawry/callback
   FAWRY_SANDBOX_MODE=true  # Set to false for production

   # Vodafone Cash Configuration
   VODAFONE_CASH_MERCHANT_ID=your_merchant_id
   VODAFONE_CASH_API_KEY=your_api_key
   VODAFONE_CASH_CALLBACK_URL=https://your-domain.com/payments/vodafone-cash/callback
   VODAFONE_CASH_SANDBOX_MODE=true  # Set to false for production
   ```
3. **Laravel project** with database configured

---

## Account Setup

### Fawry Account Setup

1. **Merchant Registration**:
   - Contact Fawry's sales team via [their website](https://fawry.com/business/contact-us/) or call +20 2 2621 0700
   - Request to become a merchant partner
   - They will provide you with the necessary forms and contracts

2. **Contract Signing**:
   - Sign the merchant agreement with Fawry
   - Complete the required KYC (Know Your Customer) documentation
   - Provide your business registration documents, tax ID, and banking details

3. **Integration Access**:
   - Upon contract approval, Fawry will provide you with:
     - Merchant Code
     - Security Key for both sandbox and production environments
     - Fawry Dashboard credentials
     - API documentation access

4. **Dashboard Setup**:
   - Log in to the Fawry Merchant Dashboard
   - Configure your business profile
   - Set up your return URL and notification URLs (webhooks)
   - Determine which payment methods you want to accept (card payments, Fawry reference number, etc.)

---

## Fawry Express Checkout Integration

Fawry provides an Express Checkout integration, which is the fastest way to integrate with their payment gateway. This guide focuses on the **Checkout Button Integration (Self-Hosted option)**, which allows you to integrate Fawry payments directly within your website without redirecting users to external pages.

### 1. Import Fawry JavaScript and CSS Libraries

Add the following code to the `<head>` section of your checkout page:

#### For Development Environment (Staging):

```html
<!-- Import FawryPay CSS Library-->
<link rel="stylesheet" href="https://atfawry.fawrystaging.com/atfawry/plugin/assets/payments/css/fawrypay-payments.css">

<!-- Import FawryPay Staging JavaScript Library-->
<script type="text/javascript" src="https://atfawry.fawrystaging.com/atfawry/plugin/assets/payments/js/fawrypay-payments.js"></script>
```

#### For Production Environment:

```html
<!-- Import FawryPay CSS Library-->
<link rel="stylesheet" href="https://atfawry.fawrystaging.com/atfawry/plugin/assets/payments/css/fawrypay-payments.css">

<!-- Import FawryPay Production JavaScript Library -->
<script type="text/javascript" src="https://www.atfawry.com/atfawry/plugin/assets/payments/js/fawrypay-payments.js"></script>
```

### 2. Add the Fawry Checkout Button

Insert the following code in your checkout form where you want the payment button to appear:

```html
<!-- FawryPay Checkout Button -->
<input type="image" onclick="checkout();" src="https://www.atfawry.com/assets/img/FawryPayLogo.jpg" alt="pay-using-fawry" id="fawry-payment-btn"/>
```

### 3. Implement the Checkout Function

Create a JavaScript file or add the following JavaScript code to your page:

```javascript
// Define the checkout function that will be called when the button is clicked
function checkout() {
    const configuration = {
        locale: "en",  // default en
        mode: DISPLAY_MODE.POPUP,  // can be POPUP, INSIDE_PAGE, SIDE_PAGE, or SEPARATED
    };
    FawryPay.checkout(buildChargeRequest(), configuration);
}

// Build the charge request object with transaction details
function buildChargeRequest() {
    // Generate a unique merchant reference number for this transaction
    const merchantRefNum = 'MED-' + Date.now();
    
    // Get customer details from your form
    const customerName = document.getElementById('customer_name').value;
    const customerMobile = document.getElementById('customer_mobile').value;
    const customerEmail = document.getElementById('customer_email').value;
    
    // Get product details
    const courseId = document.getElementById('course_id').value;
    const courseTitle = document.getElementById('course_title').value;
    const coursePrice = parseFloat(document.getElementById('course_price').value);

    // Calculate signature
    const signatureString = '<?php echo config('payment.fawry.merchant_code'); ?>' + 
                           merchantRefNum + 
                           customerName + 
                           customerMobile + 
                           customerEmail + 
                           coursePrice.toFixed(2) + 
                           'EGP' + 
                           '<?php echo config('payment.fawry.security_key'); ?>';
    
    const signature = sha256(signatureString);
    
    // Build the charge request
    const chargeRequest = {
        merchantCode: '<?php echo config('payment.fawry.merchant_code'); ?>',
        merchantRefNum: merchantRefNum,
        customerMobile: customerMobile,
        customerEmail: customerEmail,
        customerName: customerName,
        customerProfileId: '<?php echo auth()->id(); ?>',
        paymentExpiry: (Date.now() + (2 * 24 * 60 * 60 * 1000)).toString(), // 2 days expiry
        chargeItems: [
            {
                itemId: courseId,
                description: courseTitle,
                price: coursePrice,
                quantity: 1,
                imageUrl: document.getElementById('course_image').src,
            }
        ],
        returnUrl: '<?php echo config('payment.fawry.return_url'); ?>',
        authCaptureModePayment: false,
        signature: signature
    };
    
    // Save transaction reference to the database
    saveTransactionReference(merchantRefNum);
    
    return chargeRequest;
}

// Save the transaction reference to your database
function saveTransactionReference(referenceNumber) {
    fetch('/api/payments/save-reference', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            reference_number: referenceNumber,
            course_id: document.getElementById('course_id').value,
            payment_method: 'fawry',
            amount: document.getElementById('course_price').value
        })
    })
    .then(response => response.json())
    .then(data => console.log('Transaction reference saved:', data))
    .catch(error => console.error('Error saving transaction reference:', error));
}

// SHA-256 hash function
function sha256(str) {
    // You can use a library like crypto-js or implement your own SHA-256 function
    // This is a placeholder - replace with actual SHA-256 implementation
    return CryptoJS.SHA256(str).toString();
}
```

### 4. Create a Backend API Route to Save Transaction References

Add the following route to your `routes/api.php`:

```php
Route::post('/payments/save-reference', [PaymentController::class, 'saveReference'])
    ->name('payment.save.reference')
    ->middleware('auth:api');
```

### 5. Create Controller Method to Save Payment References

Add the following method to your `PaymentController.php`:

```php
/**
 * Save payment reference
 */
public function saveReference(Request $request)
{
    $request->validate([
        'reference_number' => 'required|string|unique:payments,reference_number',
        'course_id' => 'required|exists:courses,id',
        'payment_method' => 'required|string',
        'amount' => 'required|numeric'
    ]);
    
    try {
        $payment = Payment::create([
            'user_id' => auth()->id(),
            'course_id' => $request->course_id,
            'reference_number' => $request->reference_number,
            'payment_method' => $request->payment_method,
            'amount' => $request->amount,
            'currency' => 'EGP',
            'status' => config('payment.statuses.pending')
        ]);
        
        return response()->json([
            'success' => true,
            'payment_id' => $payment->id
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to save payment reference', [
            'reference' => $request->reference_number,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Failed to save payment reference'
        ], 500);
    }
}
```

### 6. Handle Payment Callback

Create a controller method to handle callbacks from Fawry:

```php
/**
 * Handle Fawry callback
 */
public function fawryCallback(Request $request)
{
    Log::info('Fawry callback received', $request->all());
    
    try {
        // Extract parameters from callback
        $type = $request->input('type');
        $referenceNumber = $request->input('referenceNumber');
        $merchantRefNumber = $request->input('merchantRefNumber');
        $orderAmount = $request->input('orderAmount');
        $paymentAmount = $request->input('paymentAmount');
        $fawryFees = $request->input('fawryFees');
        $paymentMethod = $request->input('paymentMethod');
        $orderStatus = $request->input('orderStatus');
        $signature = $request->input('signature');
        $statusCode = $request->input('statusCode');
        
        // Verify callback signature
        $signatureString = ($referenceNumber ?? '') . 
                          $merchantRefNumber . 
                          $paymentAmount . 
                          $orderAmount . 
                          ($orderStatus ?? '') . 
                          ($paymentMethod ?? '') . 
                          ($fawryFees ?? '') . 
                          config('payment.fawry.security_key');
        
        $calculatedSignature = hash('sha256', $signatureString);
        
        // If signature doesn't match, reject the callback
        if ($signature !== $calculatedSignature) {
            Log::warning('Invalid Fawry callback signature', [
                'received' => $signature,
                'calculated' => $calculatedSignature
            ]);
            return redirect()->route('payment.failed')->with('error', 'Invalid payment verification');
        }
        
        // Find the payment by merchant reference number
        $payment = Payment::where('reference_number', $merchantRefNumber)->first();
        
        if (!$payment) {
            Log::warning('Fawry callback for unknown reference', [
                'merchantRefNumber' => $merchantRefNumber
            ]);
            return redirect()->route('payment.failed')->with('error', 'Unknown payment reference');
        }
        
        // Update payment status based on order status
        if ($orderStatus === 'PAID') {
            $payment->status = config('payment.statuses.completed');
            $payment->paid_at = now();
            $payment->payload = array_merge($payment->payload ?? [], [
                'fawry_reference' => $referenceNumber,
                'payment_method' => $paymentMethod,
                'fawry_fees' => $fawryFees
            ]);
            $payment->save();
            
            // Process successful payment (e.g., enroll user in course)
            // Add your business logic here
            
            return redirect()->route('payment.success', ['reference' => $payment->reference_number]);
        } else {
            $statusMap = [
                'EXPIRED' => config('payment.statuses.expired'),
                'FAILED' => config('payment.statuses.failed'),
                'REFUNDED' => 'refunded',
                'CANCELED' => 'canceled'
            ];
            
            $payment->status = $statusMap[$orderStatus] ?? config('payment.statuses.failed');
            $payment->payload = array_merge($payment->payload ?? [], [
                'fawry_reference' => $referenceNumber,
                'status_description' => $request->input('statusDescription')
            ]);
            $payment->save();
            
            return redirect()->route('payment.failed', ['reference' => $payment->reference_number]);
        }
    } catch (\Exception $e) {
        Log::error('Error processing Fawry callback', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->route('payment.failed')->with('error', 'Payment processing error');
    }
}
```

### 7. Add Callback Routes

```php
// Fawry callback route
Route::get('/payments/fawry/callback', [PaymentController::class, 'fawryCallback'])
    ->name('payment.fawry.callback');
```

### 8. Create Success and Failure Pages

Create view templates for success and failure scenarios:

#### Success Page (resources/views/payments/success.blade.php):
```php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">Payment Successful</div>

                <div class="card-body text-center">
                    <h3>Thank you for your payment!</h3>
                    <p>Your transaction has been completed successfully.</p>
                    
                    @if($payment)
                    <div class="payment-details mt-4">
                        <p><strong>Reference Number:</strong> {{ $payment->reference_number }}</p>
                        <p><strong>Amount:</strong> {{ $payment->amount }} {{ $payment->currency }}</p>
                        <p><strong>Date:</strong> {{ $payment->paid_at->format('F j, Y, g:i a') }}</p>
                    </div>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ route('courses.show', $payment->course_id) }}" class="btn btn-primary">Access Your Course</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### Failure Page (resources/views/payments/failed.blade.php):
```php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">Payment Failed</div>

                <div class="card-body text-center">
                    <h3>Sorry, your payment could not be processed</h3>
                    
                    @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                    @endif
                    
                    @if($payment)
                    <div class="payment-details mt-4">
                        <p><strong>Reference Number:</strong> {{ $payment->reference_number }}</p>
                        <p><strong>Status:</strong> {{ ucfirst($payment->status) }}</p>
                        @if(isset($payment->payload['status_description']))
                        <p><strong>Reason:</strong> {{ $payment->payload['status_description'] }}</p>
                        @endif
                    </div>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ url()->previous() }}" class="btn btn-primary">Try Again</a>
                        <a href="{{ route('contact') }}" class="btn btn-outline-secondary ml-2">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### 9. Add Display Modes to the Config

Add the following to your `config/payment.php` file:

```php
'fawry' => [
    'merchant_code' => env('FAWRY_MERCHANT_CODE'),
    'security_key' => env('FAWRY_SECURITY_KEY'),
    'return_url' => env('FAWRY_RETURN_URL'),
    'sandbox_mode' => env('FAWRY_SANDBOX_MODE', true),
    'display_modes' => [
        'POPUP' => 'POPUP',
        'INSIDE_PAGE' => 'INSIDE_PAGE',
        'SIDE_PAGE' => 'SIDE_PAGE',
        'SEPARATED' => 'SEPARATED'
    ]
],
```

### 10. Add Testing Instructions

For testing Fawry integration, use the following test cards in the sandbox environment:

| Card Type | Card Number        | CVV | Expiry Date | Card Holder | Result            |
|-----------|-------------------|-----|-------------|-------------|-------------------|
| Visa      | 4242 4242 4242 4242 | 123 | 05/25       | Fawry Test  | Successful payment |
| Visa      | 4263 9700 0000 5262 | 123 | 05/25       | Fawry Test  | Invalid expiry date|
| Visa      | 4222 0000 0672 4235 | 123 | 05/25       | Fawry Test  | Insufficient funds |

### 11. Handling Payment Response Details

The Fawry checkout response will include the following parameters:

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| type | String | Type of response | ChargeResponse |
| referenceNumber | String | FawryPay issued transaction reference number | 963455678 |
| merchantRefNumber | String | Your transaction reference number | 9990d0642040 |
| orderAmount | Decimal | Order amount | 20.00 |
| paymentAmount | Decimal | The paid amount | 20.00 |
| fawryFees | Decimal | The payment processing fees | 1.00 |
| paymentMethod | String | Payment method used | CARD, PayAtFawry, etc. |
| orderStatus | String | Order status | PAID, FAILED, etc. |
| paymentTime | Integer | Payment timestamp | 1607879720568 |
| signature | String | Response signature | b0ef178e2f06b215b18cfc7d82fb5 |
| statusCode | String | Response status code | 200 |
| statusDescription | String | Response status description | Operation done successfully |

For successful payments, the response will contain all parameters. For failed payments, it will only include the status code and description.

---

## Vodafone Cash Integration

// ... rest of the code ...

### Create Payment Failed Jobs Table

For handling failed payment retries:

```bash
php artisan make:migration create_payment_failed_jobs_table
```

Update the migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentFailedJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->string('exception');
            $table->text('failed_reason');
            $table->timestamp('failed_at')->useCurrent();
            $table->timestamp('retry_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->boolean('resolved')->default(false);
            $table->timestamps();
            
            $table->index(['resolved', 'retry_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_failed_jobs');
    }
}
```

### Run Migrations

Execute the migrations:

```bash
php artisan migrate
```

---

## Fawry Integration

Fawry integration uses the Express Checkout method with a redirection link to the Fawry payment page.

### 1. Create Payment Service Class

Create a `FawryPaymentService.php` class in your services directory:

```php
<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentFailedJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FawryPaymentService
{
    /**
     * Generate a payment URL for Fawry
     * 
     * @param Payment $payment
     * @return string The payment URL
     */
    public function generatePaymentUrl(Payment $payment)
    {
        $params = [
            'merchantCode' => config('payment.fawry.merchant_code'),
            'merchantRefNum' => $payment->reference_number,
            'customerName' => $payment->user->name,
            'customerEmail' => $payment->user->email,
            'customerMobile' => $payment->user->phone,
            'amount' => $payment->amount,
            'currencyCode' => $payment->currency,
            'description' => "Payment for " . $payment->course->title,
            'chargeItems' => [
                [
                    'itemId' => $payment->course->id,
                    'description' => $payment->course->title,
                    'price' => $payment->amount,
                    'quantity' => 1
                ]
            ],
            'returnUrl' => config('payment.fawry.return_url'),
            'paymentExpiry' => now()->addDays(2)->timestamp
        ];
        
        // Generate signature
        $signature = $this->generateSignature($params);
        $params['signature'] = $signature;
        
        try {
            DB::beginTransaction();
            
            $response = Http::timeout(30)->post(config('payment.fawry.api_url'), $params);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                // Check for Fawry-specific error codes
                if (isset($responseData['statusCode']) && $responseData['statusCode'] != 200) {
                    $errorMessage = $responseData['statusDescription'] ?? 'Unknown Fawry error';
                    Log::error('Fawry API error', [
                        'status_code' => $responseData['statusCode'],
                        'message' => $errorMessage,
                        'payment_id' => $payment->id,
                        'response' => array_except($responseData, ['signature']) // Exclude sensitive data
                    ]);
                    
                    $this->recordFailedPayment($payment, 'API Error', $errorMessage);
                    DB::commit();
                    
                    throw new \Exception("Fawry payment error: {$errorMessage}");
                }
                
                // Extract payment URL from response
                $paymentUrl = $responseData['paymentURL'] ?? $responseData['paymentUrl'] ?? $responseData['redirectUrl'] ?? null;
                
                if (!$paymentUrl) {
                    Log::error('Fawry payment URL not found in response', [
                        'payment_id' => $payment->id,
                        'response' => array_except($responseData, ['signature']) // Exclude sensitive data
                    ]);
                    
                    $this->recordFailedPayment($payment, 'Missing URL', 'Payment URL not found in response');
                    DB::commit();
                    
                    throw new \Exception('Payment URL not found in Fawry response');
                }
                
                // Save reference number from Fawry if available
                if (isset($responseData['referenceNumber'])) {
                    $payment->payload = array_merge($payment->payload ?? [], [
                        'fawry_reference' => $responseData['referenceNumber']
                    ]);
                }
                
                // Save response data to payment record
                $payment->payload = array_merge($payment->payload ?? [], [
                    'fawry_request' => array_except($params, ['signature']), // Exclude sensitive data
                    'fawry_response' => array_except($responseData, ['signature']) // Exclude sensitive data
                ]);
                $payment->save();
                
                DB::commit();
                
                return $paymentUrl;
            } else {
                $errorBody = $response->body();
                Log::error('Fawry payment request failed', [
                    'status_code' => $response->status(),
                    'response' => $errorBody,
                    'payment_id' => $payment->id
                ]);
                
                $this->recordFailedPayment($payment, 'HTTP Error', "HTTP {$response->status()}: {$errorBody}");
                DB::commit();
                
                throw new \Exception("Failed to generate Fawry payment URL: HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Fawry payment exception', [
                'message' => $e->getMessage(),
                'payment_id' => $payment->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Record the failure outside of the transaction
            $this->recordFailedPayment($payment, get_class($e), $e->getMessage());
            
            throw $e;
        }
    }
    
    /**
     * Generate the request signature
     * 
     * @param array $params
     * @return string
     */
    protected function generateSignature(array $params)
    {
        // Fawry requires a specific order for signature generation
        $message = $params['merchantCode'] . 
                   $params['merchantRefNum'] . 
                   $params['customerName'] . 
                   $params['customerMobile'] . 
                   $params['customerEmail'] . 
                   $params['amount'] . 
                   $params['currencyCode'] . 
                   config('payment.fawry.security_key');
        
        return hash('sha256', $message);
    }
}

                   $params['timestamp'] . 
                   config('payment.vodafone_cash.api_key');
        
        return hash_hmac('sha256', $message, config('payment.vodafone_cash.api_key'));
    }
    
    /**
     * Process callback from Vodafone Cash
     * 
     * @param array $data
     * @return bool
     */
    public function processCallback(array $data)
    {
        Log::info('Received Vodafone Cash callback', array_except($data, ['signature']));
        
        // Verify callback has all required fields
        $requiredFields = [
            'merchantId', 
            'orderId', 
            'amount', 
            'transactionId',
            'status', 
            'signature'
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                Log::warning("Missing required field in Vodafone Cash callback: {$field}", [
                    'data' => array_except($data, ['signature'])
                ]);
                return false;
            }
        }
        
        // Verify signature first
        if (!$this->verifyCallbackSignature($data)) {
            Log::warning('Vodafone Cash callback signature verification failed', [
                'data' => array_except($data, ['signature'])
            ]);
            return false;
        }
        
        try {
            DB::beginTransaction();
            
            // Find payment by reference number
            $payment = Payment::where('reference_number', $data['orderId'])->first();
            if (!$payment) {
                Log::warning('Vodafone Cash callback received for unknown payment', [
                    'reference_number' => $data['orderId']
                ]);
                DB::rollBack();
                return false;
            }
            
            // Update payment status
            $statusMap = [
                'SUCCESS' => config('payment.statuses.completed'),
                'FAILED' => config('payment.statuses.failed'),
                'EXPIRED' => config('payment.statuses.expired'),
                'CANCELLED' => 'canceled',
                'PENDING' => config('payment.statuses.pending')
            ];
            
            $newStatus = $statusMap[$data['status']] ?? $payment->status;
            
            // Only update if status is changing (avoid processing duplicates)
            if ($payment->status !== $newStatus) {
                $payment->status = $newStatus;
                
                // If payment is completed, set the paid_at timestamp
                if ($newStatus === config('payment.statuses.completed')) {
                    $payment->paid_at = now();
                }
                
                $payment->payload = array_merge($payment->payload ?? [], [
                    'vodafone_callback' => array_except($data, ['signature']),
                    'vodafone_transaction_id' => $data['transactionId']
                ]);
                
                $payment->save();
                
                // Process successful payments
                if ($payment->status === config('payment.statuses.completed')) {
                    $this->processSuccessfulPayment($payment);
                }
                
                Log::info('Payment status updated from Vodafone Cash callback', [
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference_number,
                    'old_status' => $payment->getOriginal('status'),
                    'new_status' => $newStatus
                ]);
            } else {
                Log::info('Duplicate Vodafone Cash callback received, status unchanged', [
                    'payment_id' => $payment->id,
                    'status' => $payment->status
                ]);
            }
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error processing Vodafone Cash callback', [
                'message' => $e->getMessage(),
                'reference_number' => $data['orderId'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Verify callback signature
     * 
     * @param array $data
     * @return bool
     */
    protected function verifyCallbackSignature(array $data)
    {
        $providedSignature = $data['signature'];
        
        // Recreate message for verification
        $message = $data['merchantId'] . 
                   $data['orderId'] . 
                   $data['amount'] . 
                   $data['status'] . 
                   config('payment.vodafone_cash.api_key');
        
        $calculatedSignature = hash_hmac('sha256', $message, config('payment.vodafone_cash.api_key'));
        
        return hash_equals($calculatedSignature, $providedSignature);
    }
    
    /**
     * Query payment status from Vodafone Cash API
     * 
     * @param Payment $payment
     * @return array Payment status details
     */
    public function checkPaymentStatus(Payment $payment)
    {
        $params = [
            'merchantId' => config('payment.vodafone_cash.merchant_id'),
            'orderId' => $payment->reference_number,
            'timestamp' => now()->timestamp
        ];
        
        // Generate signature
        $signature = hash_hmac('sha256', 
            $params['merchantId'] . $params['orderId'] . $params['timestamp'] . config('payment.vodafone_cash.api_key'), 
            config('payment.vodafone_cash.api_key')
        );
        $params['signature'] = $signature;
        
        $queryUrl = str_replace('/request', '/status', config('payment.vodafone_cash.api_url'));
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('payment.vodafone_cash.api_key')
            ])->get($queryUrl, $params);
            
            if ($response->successful()) {
                $statusData = $response->json();
                
                // Update payment record with latest status
                if (isset($statusData['status'])) {
                    $statusMap = [
                        'SUCCESS' => config('payment.statuses.completed'),
                        'FAILED' => config('payment.statuses.failed'),
                        'EXPIRED' => config('payment.statuses.expired'),
                        'CANCELLED' => 'canceled',
                        'PENDING' => config('payment.statuses.pending')
                    ];
                    
                    $newStatus = $statusMap[$statusData['status']] ?? $payment->status;
                    
                    if ($payment->status !== $newStatus) {
                        $payment->status = $newStatus;
                        
                        if ($newStatus === config('payment.statuses.completed') && !$payment->paid_at) {
                            $payment->paid_at = now();
                        }
                        
                        $payment->payload = array_merge($payment->payload ?? [], [
                            'vodafone_status_check' => array_except($statusData, ['signature'])
                        ]);
                        
                        $payment->save();
                        
                        if ($newStatus === config('payment.statuses.completed')) {
                            $this->processSuccessfulPayment($payment);
                        }
                    }
                }
                
                return $statusData;
            }
            
            return ['error' => 'Failed to get status from Vodafone Cash'];
        } catch (\Exception $e) {
            Log::error('Error checking Vodafone Cash payment status', [
                'payment_id' => $payment->id,
                'message' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Process successful payment (e.g., enrolling user in course)
     * 
     * @param Payment $payment
     * @return void
     */
    protected function processSuccessfulPayment(Payment $payment)
    {
        try {
            // Implement your business logic here
            // Example: Enroll user in course
            
            // Log successful processing
            Log::info('Successfully processed Vodafone Cash payment', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'course_id' => $payment->course_id,
                'amount' => $payment->amount
            ]);
            
            // Send email notification to user
            // \Mail::to($payment->user->email)->send(new \App\Mail\PaymentSuccessful($payment));
            
        } catch (\Exception $e) {
            Log::error('Failed to process successful Vodafone Cash payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            // You may want to create a job to retry this processing
            throw $e;
        }
    }
    
    /**
     * Record a failed payment attempt
     * 
     * @param Payment $payment
     * @param string $exceptionType
     * @param string $reason
     * @return void
     */
    protected function recordFailedPayment(Payment $payment, string $exceptionType, string $reason)
    {
        try {
            $payment->status = config('payment.statuses.failed');
            $payment->save();
            
            PaymentFailedJob::create([
                'payment_id' => $payment->id,
                'exception' => $exceptionType,
                'failed_reason' => $reason,
                'retry_at' => now()->addMinutes(30), // Schedule a retry in 30 minutes
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record Vodafone Cash payment failure', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Retry failed payments
     * 
     * @return void
     */
    public function retryFailedPayments()
    {
        $failedJobs = PaymentFailedJob::where('resolved', false)
            ->where('retry_count', '<', 3) // Maximum 3 retry attempts
            ->where('retry_at', '<=', now())
            ->with('payment')
            ->whereHas('payment', function($query) {
                $query->where('payment_method', 'vodafone_cash');
            })
            ->get();
            
        foreach ($failedJobs as $job) {
            try {
                if (!$job->payment) {
                    $job->resolved = true;
                    $job->save();
                    continue;
                }
                
                // Generate a new reference number and create a new payment
                $newPayment = $job->payment->replicate();
                $newPayment->reference_number = $job->payment->reference_number . '-R' . ($job->retry_count + 1);
                $newPayment->status = config('payment.statuses.pending');
                $newPayment->payload = ['retry_of' => $job->payment_id];
                $newPayment->save();
                
                // Generate a new payment code
                $paymentCode = $this->generatePaymentCode($newPayment);
                
                // Mark as superseded
                $job->payment->status = 'superseded';
                $job->payment->payload = array_merge($job->payment->payload ?? [], [
                    'superseded_by' => $newPayment->id
                ]);
                $job->payment->save();
                
                // Mark job as resolved
                $job->resolved = true;
                $job->save();
                
                Log::info('Successfully retried failed Vodafone Cash payment', [
                    'original_payment_id' => $job->payment_id,
                    'new_payment_id' => $newPayment->id
                ]);
                
            } catch (\Exception $e) {
                $job->retry_count += 1;
                $job->retry_at = now()->addHours($job->retry_count * 2); // Exponential backoff
                $job->save();
                
                Log::error('Failed to retry Vodafone Cash payment', [
                    'job_id' => $job->id,
                    'payment_id' => $job->payment_id,
                    'error' => $e->getMessage(),
                    'retry_count' => $job->retry_count
                ]);
            }
        }
    }
}
```

### 2. Create Controller Methods

Add these methods to your `PaymentController.php`:

```php
/**
 * Generate Vodafone Cash payment code
 */
public function generateVodafoneCashCode(Payment $payment)
{
    try {
        // Verify the payment belongs to the current user
        if ($payment->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to payment');
        }
        
        // Only allow redirect for pending payments
        if ($payment->status !== config('payment.statuses.pending')) {
            return redirect()->route('payments.show', $payment)
                ->with('error', 'This payment cannot be processed again.');
        }
        
        $vodafoneService = new VodafoneCashService();
        $paymentCode = $vodafoneService->generatePaymentCode($payment);
        
        return view('payments.vodafone_code', [
            'payment' => $payment,
            'paymentCode' => $paymentCode
        ]);
    } catch (\Exception $e) {
        Log::error('Error generating Vodafone Cash code', [
            'payment_id' => $payment->id,
            'error' => $e->getMessage()
        ]);
        
        return redirect()->route('payments.show', $payment)
            ->with('error', 'Unable to process your payment at this time. Please try again later.');
    }
}

/**
 * Handle Vodafone Cash callback
 */
public function vodafoneCashCallback(Request $request)
{
    Log::info('Vodafone Cash callback received', $request->all());
    
    try {
        $vodafoneService = new VodafoneCashService();
        $success = $vodafoneService->processCallback($request->all());
        
        // This is a server-to-server callback, so just return a simple response
        return response()->json([
            'success' => $success
        ]);
    } catch (\Exception $e) {
        Log::error('Error processing Vodafone Cash callback', [
            'error' => $e->getMessage(),
            'data' => $request->all()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Internal server error'
        ], 500);
    }
}

/**
 * Check Vodafone Cash payment status
 */
public function checkVodafoneCashStatus(Payment $payment)
{
    try {
        // Verify the payment belongs to the current user
        if ($payment->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to payment');
        }
        
        $vodafoneService = new VodafoneCashService();
        $statusData = $vodafoneService->checkPaymentStatus($payment);
        
        return response()->json([
            'status' => $payment->status,
            'details' => $statusData
        ]);
    } catch (\Exception $e) {
        Log::error('Error checking Vodafone Cash status', [
            'payment_id' => $payment->id,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'error' => 'Failed to check payment status'
        ], 500);
    }
}
```

### 3. Define Routes

```php
// Vodafone Cash Routes
Route::get('/payments/vodafone-cash/{payment}', [PaymentController::class, 'generateVodafoneCashCode'])
    ->name('payment.vodafone.code')
    ->middleware('auth');

// Server-to-server callback
Route::post('/payments/vodafone-cash/callback', [PaymentController::class, 'vodafoneCashCallback'])
    ->name('payment.vodafone.callback')
    ->middleware('payment.webhook');

// Check payment status
Route::get('/payments/vodafone-cash/{payment}/status', [PaymentController::class, 'checkVodafoneCashStatus'])
    ->name('payment.vodafone.status')
    ->middleware('auth');
```

---

## Common Components

### Payment Model

Create a `Payment.php` model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;
    
    protected $guarded = [];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
        'paid_at' => 'datetime'
    ];
    
    /**
     * Payment status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELED = 'canceled';
    const STATUS_SUPERSEDED = 'superseded';
    
    /**
     * Payment method constants
     */
    const METHOD_FAWRY = 'fawry';
    const METHOD_VODAFONE_CASH = 'vodafone_cash';
    
    /**
     * Get the user that owns the payment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the course that the payment is for
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
    
    /**
     * Get the failed jobs for this payment
     */
    public function failedJobs()
    {
        return $this->hasMany(PaymentFailedJob::class);
    }
    
    /**
     * Check if the payment is pending
     * 
     * @return bool
     */
    public function isPending()
    {
        return $this->status === config('payment.statuses.pending');
    }
    
    /**
     * Check if the payment is completed
     * 
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === config('payment.statuses.completed');
    }
    
    /**
     * Check if the payment has failed
     * 
     * @return bool
     */
    public function hasFailed()
    {
        return $this->status === config('payment.statuses.failed');
    }
    
    /**
     * Create a new payment request
     * 
     * @param User $user
     * @param Course $course
     * @param string $paymentMethod
     * @return Payment
     */
    public static function createRequest(User $user, Course $course, string $paymentMethod)
    {
        try {
            // Generate a unique reference number with prefix based on payment method
            $prefix = $paymentMethod === self::METHOD_FAWRY ? 'FWY' : 'VDC';
            $referenceNumber = $prefix . '-' . Str::upper(Str::random(4)) . '-' . now()->timestamp;
            
            // Create payment record
            $payment = self::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $course->price,
                'currency' => 'EGP',
                'reference_number' => $referenceNumber,
                'payment_method' => $paymentMethod,
                'status' => config('payment.statuses.pending')
            ]);
            
            // Log transaction start
            \Log::info('Payment request created', [
                'payment_id' => $payment->id,
                'reference_number' => $payment->reference_number,
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method
            ]);
            
            return $payment;
        } catch (\Exception $e) {
            \Log::error('Failed to create payment request', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

### Payment Failed Job Model

Create a `PaymentFailedJob.php` model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentFailedJob extends Model
{
    protected $guarded = [];
    
    /**
     * Get the payment that owns the job
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
```

### Payment Controller Common Methods

Update your `PaymentController.php` with these common methods:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Payment;
use App\Services\FawryPaymentService;
use App\Services\VodafoneCashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Initialize a new payment
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'payment_method' => 'required|in:fawry,vodafone_cash'
        ]);
        
        try {
            DB::beginTransaction();
            
            $user = auth()->user();
            $course = Course::findOrFail($request->course_id);
            
            // Check if the user already has a pending payment for this course
            $pendingPayment = Payment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', config('payment.statuses.pending'))
                ->first();
                
            if ($pendingPayment) {
                // If the payment method is the same, reuse the existing payment
                if ($pendingPayment->payment_method === $request->payment_method) {
                    DB::commit();
                    
                    // Redirect based on payment method
                    if ($pendingPayment->payment_method === Payment::METHOD_FAWRY) {
                        return redirect()->route('payment.fawry.redirect', $pendingPayment);
                    } else {
                        return redirect()->route('payment.vodafone.code', $pendingPayment);
                    }
                }
                
                // If changing payment method, mark the old one as superseded
                $pendingPayment->status = 'superseded';
                $pendingPayment->save();
            }
            
            // Create payment record
            $payment = Payment::createRequest($user, $course, $request->payment_method);
            
            DB::commit();
            
            // Redirect based on payment method
            if ($payment->payment_method === Payment::METHOD_FAWRY) {
                return redirect()->route('payment.fawry.redirect', $payment);
            } else {
                return redirect()->route('payment.vodafone.code', $payment);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment initiation failed', [
                'user_id' => auth()->id(),
                'course_id' => $request->course_id,
                'payment_method' => $request->payment_method,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Unable to process your payment request. Please try again later.');
        }
    }

    /**
     * Display payment success page
     */
    public function success(Request $request)
    {
        $reference = $request->reference;
        
        $payment = null;
        if ($reference) {
            $payment = Payment::where('reference_number', $reference)->first();
        }
        
        return view('payments.success', [
            'payment' => $payment
        ]);
    }

    /**
     * Display payment failure page
     */
    public function failed(Request $request)
    {
        $reference = $request->reference;
        
        $payment = null;
        if ($reference) {
            $payment = Payment::where('reference_number', $reference)->first();
        }
        
        return view('payments.failed', [
            'payment' => $payment
        ]);
    }

    /**
     * Check payment status
     */
    public function checkStatus(Payment $payment)
    {
        try {
            // Verify the payment belongs to the current user
            if ($payment->user_id !== auth()->id()) {
                abort(403, 'Unauthorized access to payment');
            }
            
            // Call the appropriate service to check status
            if ($payment->payment_method === Payment::METHOD_FAWRY) {
                $fawryService = new FawryPaymentService();
                $statusData = $fawryService->checkPaymentStatus($payment);
            } else {
                $vodafoneService = new VodafoneCashService();
                $statusData = $vodafoneService->checkPaymentStatus($payment);
            }
            
            return response()->json([
                'status' => $payment->status,
                'details' => $statusData
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking payment status', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to check payment status'
            ], 500);
        }
    }
    
    /**
     * Show payment details page
     */
    public function show(Payment $payment)
    {
        // Verify the payment belongs to the current user
        if ($payment->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to payment');
        }
        
        return view('payments.show', [
            'payment' => $payment
        ]);
    }
    
    /**
     * List user payments
     */
    public function index()
    {
        $payments = Payment::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('payments.index', [
            'payments' => $payments
        ]);
    }
    
    /**
     * Retry a failed payment
     */
    public function retry(Payment $payment)
    {
        try {
            // Verify the payment belongs to the current user
            if ($payment->user_id !== auth()->id()) {
                abort(403, 'Unauthorized access to payment');
            }
            
            // Only allow retry for failed payments
            if ($payment->status !== config('payment.statuses.failed')) {
                return redirect()->route('payments.show', $payment)
                    ->with('error', 'Only failed payments can be retried.');
            }
            
            DB::beginTransaction();
            
            // Create a new payment
            $newPayment = $payment->replicate();
            $newPayment->reference_number = str_replace(['-R1', '-R2', '-R3'], '', $payment->reference_number) . '-R' . now()->timestamp;
            $newPayment->status = config('payment.statuses.pending');
            $newPayment->payload = ['retry_of' => $payment->id];
            $newPayment->save();
            
            // Mark original as superseded
            $payment->status = 'superseded';
            $payment->payload = array_merge($payment->payload ?? [], [
                'superseded_by' => $newPayment->id
            ]);
            $payment->save();
            
            DB::commit();
            
            // Redirect based on payment method
            if ($newPayment->payment_method === Payment::METHOD_FAWRY) {
                return redirect()->route('payment.fawry.redirect', $newPayment);
            } else {
                return redirect()->route('payment.vodafone.code', $newPayment);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment retry failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('payments.show', $payment)
                ->with('error', 'Unable to retry payment. Please try again later.');
        }
    }
}
```

### Payment Service Provider

Create a service provider to register payment services:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FawryPaymentService;
use App\Services\VodafoneCashService;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FawryPaymentService::class, function ($app) {
            return new FawryPaymentService();
        });
        
        $this->app->singleton(VodafoneCashService::class, function ($app) {
            return new VodafoneCashService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
```

### Payment Webhook Middleware

Create a middleware to verify payment webhooks:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyPaymentWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // In production, you should verify the webhook source by checking IP addresses
        // or implementing additional security measures
        
        if (app()->environment('production')) {
            // Example: Verify the request comes from Fawry or Vodafone IP ranges
            $allowedIps = config('payment.webhook_allowed_ips', []);
            $clientIp = $request->ip();
            
            if (!in_array($clientIp, $allowedIps)) {
                Log::warning('Unauthorized webhook access attempt', [
                    'ip' => $clientIp,
                    'route' => $request->path()
                ]);
                
                abort(403, 'Unauthorized');
            }
        }
        
        return $next($request);
    }
}
```

### Additional Routes

```php
<?php

use App\Http\Controllers\PaymentController;

// Common payment routes
Route::middleware(['auth'])->group(function () {
    Route::post('/payments/initiate', [PaymentController::class, 'initiatePayment'])->name('payment.initiate');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/retry', [PaymentController::class, 'retry'])->name('payments.retry');
    Route::get('/payments/{payment}/status', [PaymentController::class, 'checkStatus'])->name('payment.status');
});

// Public routes for callbacks and success/failure pages
Route::get('/payments/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payments/failed', [PaymentController::class, 'failed'])->name('payment.failed');

// Register the middleware in app/Http/Kernel.php
// 'payment.webhook' => \App\Http\Middleware\VerifyPaymentWebhook::class,
```

### Payment Console Commands

Create a command to handle retry logic:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FawryPaymentService;
use App\Services\VodafoneCashService;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class RetryFailedPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:retry-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed payments that are eligible for retry';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to retry failed payments...');
        
        try {
            $fawryService = new FawryPaymentService();
            $fawryService->retryFailedPayments();
            
            $vodafoneService = new VodafoneCashService();
            $vodafoneService->retryFailedPayments();
            
            $this->info('Completed retrying failed payments.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error retrying failed payments: ' . $e->getMessage());
            Log::error('Payment retry command failed', [
                'error' => $e->getMessage()
            ]);
            
            return 1;
        }
    }
}
```

### Scheduled Task

Add the following to your `app/Console/Kernel.php` file:

```php
/**
 * Define the application's command schedule.
 *
 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
 * @return void
 */
protected function schedule(Schedule $schedule)
{
    // Retry failed payments every hour
    $schedule->command('payments:retry-failed')->hourly();
    
    // Check pending payments that might have been missed by webhooks
    $schedule->command('payments:check-pending')->everyThirtyMinutes();
}
```

Create a command to check pending payments:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FawryPaymentService;
use App\Services\VodafoneCashService;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckPendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check status of pending payments that might have been missed by webhooks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to check pending payments...');
        
        try {
            // Get pending payments that are at least 10 minutes old but less than 2 days old
            $pendingPayments = Payment::where('status', config('payment.statuses.pending'))
                ->where('created_at', '<=', Carbon::now()->subMinutes(10))
                ->where('created_at', '>=', Carbon::now()->subDays(2))
                ->get();
                
            $this->info("Found {$pendingPayments->count()} pending payments to check.");
            
            $fawryService = new FawryPaymentService();
            $vodafoneService = new VodafoneCashService();
            
            foreach ($pendingPayments as $payment) {
                $this->line("Checking payment {$payment->id} ({$payment->reference_number})...");
                
                if ($payment->payment_method === Payment::METHOD_FAWRY) {
                    $fawryService->checkPaymentStatus($payment);
                } else {
                    $vodafoneService->checkPaymentStatus($payment);
                }
            }
            
            $this->info('Completed checking pending payments.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error checking pending payments: ' . $e->getMessage());
            Log::error('Payment check command failed', [
                'error' => $e->getMessage()
            ]);
            
            return 1;
        }
    }
}
```