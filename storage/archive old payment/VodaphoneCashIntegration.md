Who is this guide for?
This guide is for developers, solution architects, and business professionals looking to:

Integrate Vodafone APIs into their applications.
Order and go-live with Vodafone's API based products and services.
Manage existing connectivity or get support for Vodafone's APIs.
What you will learn
In this guide you'll learn:

How to register and create a free developer account that unlocks access to test and live environments.
Find APIs in the catalogue, Try them out, create a sandbox app and use our Postman collections.
How to get access to live services and retrieve secure credentials for the production environment.
Get support on your development and engage with our DevRel team.
Explore our site
The Vodafone Developer Marketplace offers a suite of APIs, tools, and resources. Key features include:

Solutions: The Solutions section connects you with the Vodafone API portfolios, featuring benefits and case studies where our APIs can provide solutions to real-world problems. Explore how they can address your business challenges and bring your ideas to life.

APIs: The APIs section covers our available API products, each listed against their solution portfolio. You can explore our full API catalogue to find the product best suited to your needs. You can access our Postman collection from the Toolkit submenu if you want to test them out.

For each API, you will find:

A detailed description, including its usage and potential use cases.
Instructions for testing and using the API with Postman.
Open API (Swagger) reference documentation for seamless integration.
The countries where the API is available.
Whether the API is fully Live or only available in a Sandbox beta environment.
The solution category it belongs to (e.g., Identity, Boost, etc.).
Developer Relations: Access our DevRel Space and blog posts for insights, tutorials, and thought leadership.

Support: The Support page is your go-to resource for resolving issues and finding answers quickly. It features a Contact Us section, where you can contact us directly for assistance via the Developer Interest Form. Additionally, the FAQs and Troubleshooting section provides filterable solutions to common questions and issues, helping you quickly find answers and get back on track.

1. Account Registration
You can try out the APIs in the Vodafone Developer Portal without registering or logging in. However, we recommend registering to receive a free developer account. This will allow you to access the sandbox environment, and enable your organisation to request production access either directly through the portal or by speaking to the Vodafone team.

2. Validation
Your company must first be validated to request production access and create a team profile. Upon logging in, a validation form will automatically appear as a pop-up until completion. If you don't want access to production (for now), you can opt out of validation. You can add Company Details later. 

Note
The Company Name and Primary Country of Operation cannot be changed after submitting the form.
Please use your corporate email address as part of the registration process to streamline the validation process.
Enter your Company’s Name.
Select the Primary Country of Operation from the drop-down.
You can mark the checkbox to receive news, updates, and offers from Vodafone Business.
Click Submit.
After submitting the information, you will be notified of the validation status via email within 5 business days. You can check the company’s validation status in your profile. Until validation, it will be in Pending status. After successful validation, the status will be Approved.

Where our team has not been able to validate your company information, you will receive an email requesting additional information. If your company has not been successfully validated, you will still be able to view the API documentation and use the sandbox, but you will not be able to request access to the live APIs. Contact us via the Support menu if your company validation has been incorrectly Rejected.

3. Creating a Sandbox App
To begin testing Vodafone APIs in the sandbox environment, you need to create a sandbox application on the Vodafone Developer Marketplace. Sandbox applications allow you to test API functionality and generate the credentials required to use Vodafone's Postman collections. These credentials, including API keys, are essential for exploring and integrating APIs in a safe, pre-production environment. Once you’re comfortable with the APIs, you can move forward with developing your application for production.

Go to the My Apps page, then click Add Sandbox app.
Enter the following details:
Name: Provide a name for your app.
Callback URL: Enter your app’s callback URL.
Description: Provide a brief description of your app.
Select the APIs you want to use by marking the checkbox.
Click Add app.
Once you successfully created an app, it will appear on your My Apps page. From there, you can:

Create new apps.
View credentials, edit, or delete existing apps.
Add or revoke assigned keys.
4. Accessing your API Keys
Click on the View icon next to your app on the My Apps page.


 
On the page that appears, you can see details about your app and your credentials. For each credential, you can:
Access the Consumer Key and Consumer Secret by clicking Show. 
See when the credential was created (under Issued) and their expiration date (under Expires).
View the credential’s status (under Key Status).
View the APIs associated with the credential.


Next Steps
Now that you’ve set up your account and created your first sandbox application, you’re ready to explore more advanced features of the Vodafone Developer Portal. Here’s what you can do next:

Manage your sandbox apps by viewing, editing, deleting, generating new credentials, revoking old ones, and monitoring performance with analytics tools.
Test and use Postman collections by accessing Vodafone’s pre-prepared examples in the sandbox environment and setting up credentials to streamline your development process.
Transition to the production environment by completing the required steps to move your application from sandbox to production and serve real users.
Optional Steps
Edit Company Details: Update or add more information about your company in your profile.
Resetting Your Password: Learn how to recover your account if you’ve lost your password.
Error Codes and Troubleshooting: Familiarize yourself with common API error codes and troubleshooting tips to resolve issues quickly.

When you move to the production environment, we want to ensure everything is in place for you. You'll need new credentials for the production environment, and there are a few steps you'll need to carry out.

Commercial Terms apply
Contact your Vodafone Account Manager to discuss the commercial agreement for the APIs you want to use.

About the process
Below is the process for moving to the production environment:

Carry out your integration testing.
You must test to ensure that connectivity works correctly and transactions can be completed.
When you've completed your integration testing, email developermarketplace@vodafone.com. Tell us:
You want to move to the production environment
Your go-live dates
Vodafone's Developer Marketplace team will run a few checks and then provide you with access to a production environment:
We'll send you the prod environment keys
We'll also send you a customer service guide. This explains how we deliver service levels that meet your expectations.
When Vodafone approves your setup, we'll send you an email telling you that the production service is ready for you to use.

I see you're looking at the available APIs for your Vodafone Cash integration sandbox app. Based on your location in Egypt and the Medcapsule payment requirements, I'll help you select the appropriate API and document the integration process.

For a payment integration in Egypt, none of the currently displayed APIs in the screenshot are directly related to Vodafone Cash payments. The APIs shown (CAMARA-LOCATION, OPERATEAPI-ONBOARDING, SMSMESSAGING, etc.) are for different purposes.

For Vodafone Cash integration, you would typically need a payment or mobile money API, which doesn't appear to be listed in your screenshot. Let me document the proper integration process based on the information available:

# Vodafone Cash Integration for Medcapsule in Egypt

## 1. API Selection and Account Setup

Since you're in Egypt and using Vodafone Cash, you'll need to:

1. **Contact Vodafone Egypt Business Team**: For proper Vodafone Cash B2B integration in Egypt, you should contact the Vodafone Egypt Business team directly as they may have a specific API not shown in the general Vodafone Developer Portal.

2. **Request Merchant Account**: Apply for a merchant account specifically for Vodafone Cash Egypt.

## 2. Environment Setup

### Sandbox Environment
1. **Callback URL Configuration**: 
   - You've correctly set up `http://localhost:8000/payments/vodafone-cash/callback` for local development
   - This should match your `.env` file's `VODAFONE_CASH_CALLBACK_URL` setting

2. **API Credentials**:
   - After receiving merchant credentials, add to your `.env` file:
   ```
   VODAFONE_CASH_MERCHANT_ID=your_merchant_id
   VODAFONE_CASH_API_KEY=your_api_key
   VODAFONE_CASH_SANDBOX_MODE=true
   ```

## 3. Implementation Details for Egypt

### Payment Flow Specifics for Egypt

1. **Customer Initiation**:
   - Egyptian customers will need to dial *9# on their Vodafone lines
   - Select "Pay Bills" or "Payments" option
   - Enter the merchant code provided by Vodafone Egypt
   - Enter the payment amount
   - Confirm with Vodafone Cash PIN

2. **Merchant Side Implementation**:
   - Generate a unique reference number for each transaction
   - Create a database record using the `Payment` model
   - When payment completes, Vodafone will send a notification to your callback URL

### Update VodafoneCashService Class

```php
<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentFailedJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VodafoneCashService
{
    /**
     * Generate a payment code for Vodafone Cash Egypt
     * 
     * @param Payment $payment
     * @return string Payment code to display to user
     */
    public function generatePaymentCode(Payment $payment)
    {
        try {
            $params = [
                'merchantId' => config('payment.vodafone_cash.merchant_id'),
                'orderId' => $payment->reference_number,
                'amount' => $payment->amount,
                'customerMsisdn' => $payment->user->phone, // Egyptian mobile number
                'merchantRedirectUrl' => config('payment.vodafone_cash.callback_url'),
                'timestamp' => now()->timestamp,
                'description' => "Payment for " . $payment->course->title,
                'locale' => 'ar', // Arabic for Egypt
                'currency' => 'EGP', // Egyptian Pound
            ];
            
            // Generate signature
            $signature = $this->generateSignature($params);
            $params['signature'] = $signature;
            
            // Log the request for debugging
            Log::info('Generating Vodafone Cash payment code', [
                'payment_id' => $payment->id,
                'params' => array_except($params, ['signature'])
            ]);
            
            // For Egypt, we need to return instructions to the user rather than a code
            $instructions = $this->generateEgyptInstructions($payment);
            
            // Save request data to payment record
            $payment->payload = array_merge($payment->payload ?? [], [
                'vodafone_request' => array_except($params, ['signature']),
                'instructions_generated_at' => now()
            ]);
            $payment->save();
            
            return $instructions;
        } catch (\Exception $e) {
            Log::error('Error generating Vodafone Cash code', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            
            $this->recordFailedPayment($payment, get_class($e), $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate payment instructions for Egypt
     * 
     * @param Payment $payment
     * @return array Instructions array
     */
    protected function generateEgyptInstructions(Payment $payment)
    {
        $merchantCode = config('payment.vodafone_cash.merchant_code');
        
        return [
            'steps' => [
                '1. Dial *9# from your Vodafone mobile number',
                '2. Select "Pay Bills" or "Payments" option',
                "3. Enter merchant code: {$merchantCode}",
                "4. Enter the amount: {$payment->amount} EGP",
                '5. Confirm with your Vodafone Cash PIN',
                "6. Keep the confirmation code for your reference"
            ],
            'reference' => $payment->reference_number,
            'amount' => $payment->amount,
            'expires_at' => now()->addDays(1)->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate signature for request
     */
    protected function generateSignature(array $params)
    {
        // Signature format specific to Vodafone Egypt
        $message = $params['merchantId'] . 
                   $params['orderId'] . 
                   $params['amount'] . 
                   $params['timestamp'] . 
                   config('payment.vodafone_cash.api_key');
        
        return hash_hmac('sha256', $message, config('payment.vodafone_cash.api_key'));
    }
    
    // Other methods as defined in the original implementation guide
}
```

### Frontend Implementation for Egypt

Create a Blade template `resources/views/payments/vodafone_code.blade.php`:

```php
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">Vodafone Cash Payment</div>

                <div class="card-body">
                    <h4 class="text-center mb-4">Complete Your Payment with Vodafone Cash</h4>
                    
                    <div class="payment-details mb-4">
                        <p><strong>Reference Number:</strong> {{ $payment->reference_number }}</p>
                        <p><strong>Amount:</strong> {{ $payment->amount }} {{ $payment->currency }}</p>
                        <p><strong>Course:</strong> {{ $payment->course->title }}</p>
                        <p><strong>Expires:</strong> {{ $paymentCode['expires_at'] }}</p>
                    </div>
                    
                    <div class="payment-instructions">
                        <h5>Follow these steps to complete your payment:</h5>
                        <ol class="list-group">
                            @foreach($paymentCode['steps'] as $step)
                                <li class="list-group-item">{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <p class="text-muted">After completing payment, please wait for confirmation.</p>
                        <button class="btn btn-outline-primary" id="check-status">Check Payment Status</button>
                    </div>
                    
                    <div class="alert alert-info mt-4" role="alert">
                        Having trouble? Contact our support team at support@medcapsule.com
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // JavaScript to periodically check payment status
    document.addEventListener('DOMContentLoaded', function() {
        let checkButton = document.getElementById('check-status');
        let checkUrl = '{{ route("payment.vodafone.status", $payment) }}';
        
        // Check status when button is clicked
        checkButton.addEventListener('click', function() {
            checkPaymentStatus();
        });
        
        // Also check automatically every 30 seconds
        let interval = setInterval(checkPaymentStatus, 30000);
        
        function checkPaymentStatus() {
            fetch(checkUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'completed') {
                        clearInterval(interval);
                        window.location.href = '{{ route("payment.success", ["reference" => $payment->reference_number]) }}';
                    } else if (data.status === 'failed' || data.status === 'expired') {
                        clearInterval(interval);
                        window.location.href = '{{ route("payment.failed", ["reference" => $payment->reference_number]) }}';
                    }
                })
                .catch(error => console.error('Error checking payment status:', error));
        }
    });
</script>
@endpush
@endsection
```

## 4. Configuration Updates

Update your `config/payment.php` file to include Egypt-specific settings:

```php
'vodafone_cash' => [
    'merchant_id' => env('VODAFONE_CASH_MERCHANT_ID'),
    'merchant_code' => env('VODAFONE_CASH_MERCHANT_CODE'), // Egypt-specific merchant code
    'api_key' => env('VODAFONE_CASH_API_KEY'),
    'callback_url' => env('VODAFONE_CASH_CALLBACK_URL'),
    'sandbox_mode' => env('VODAFONE_CASH_SANDBOX_MODE', true),
    'api_url' => env('VODAFONE_CASH_SANDBOX_MODE', true) 
        ? 'https://vodafone-test.example.com/api/payment' // Replace with actual sandbox URL for Egypt
        : 'https://vodafone-live.example.com/api/payment', // Replace with actual production URL for Egypt
    'country' => 'EG', // Egypt country code
    'currency' => 'EGP', // Egyptian Pound
],
```

## 5. Testing in Egypt

For testing in Egypt:

1. **Test numbers**: Request test mobile numbers from Vodafone Egypt that can be used in the sandbox environment
2. **Test amounts**: Use small amounts (e.g., 1-5 EGP) for testing
3. **Test flows**: Test both successful and failed payment scenarios
4. **Webhook testing**: Use your local URL with ngrok for initial testing, then move to a staging server

## 6. Production Considerations for Egypt

1. **SSL Certificate**: Ensure your production server has a valid SSL certificate
2. **IP Whitelisting**: Vodafone Egypt may require whitelisting your server IPs
3. **MCC Code**: Ensure your Merchant Category Code is properly configured for Egypt
4. **Compliance**: Ensure compliance with Central Bank of Egypt regulations for digital payments
5. **Arabic Support**: Test your UI with Arabic language support

---

Note: Since I don't see a specific Vodafone Cash API in the options listed in your screenshot, you should contact Vodafone Egypt directly to get the proper API credentials and documentation for integration. The implementation details above are based on standard mobile money implementations but may need adjustment based on Vodafone Egypt's specific requirements.
