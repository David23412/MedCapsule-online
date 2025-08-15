<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    */
    
    // Default currency
    'default_currency' => env('PAYMENT_CURRENCY', 'EGP'),
    
    // Payment methods
    'payment_methods' => [
        'vodafone_cash' => [
            'enabled' => env('PAYMENT_VODAFONE_ENABLED', true),
            'number' => env('PAYMENT_VODAFONE_NUMBER', '01012345678'),
            'name' => env('PAYMENT_VODAFONE_NAME', 'MedCapsule Account'),
        ],
        'fawry' => [
            'enabled' => env('PAYMENT_FAWRY_ENABLED', true),
            'number' => env('PAYMENT_FAWRY_NUMBER', 'FAWRY-ACCOUNT'),
            'reference' => env('PAYMENT_FAWRY_REFERENCE', 'MEDCAPSULE'),
        ],
        'instapay' => [
            'enabled' => env('PAYMENT_INSTAPAY_ENABLED', true),
            'username' => env('PAYMENT_INSTAPAY_USERNAME', '@medcapsule'),
        ],
    ],
    
    // Payment verification
    'auto_verification' => [
        'enabled' => env('PAYMENT_AUTO_VERIFY', false),
        'transaction_patterns' => [
            'vodafone_cash' => '/^VC\d{10,}$/',
            'fawry' => '/^FW\d{8,}$/',
            'instapay' => '/^IP\d{10,}$/',
        ],
    ],
    
    // Payment expiry
    'reference_expiry_hours' => env('PAYMENT_EXPIRY_HOURS', 24),
    'cleanup_expired_payments' => env('PAYMENT_CLEANUP_EXPIRED', true),
    
    // Notifications
    'notify_admin_on_payment' => env('PAYMENT_NOTIFY_ADMIN', true),
    'notify_user_on_verification' => env('PAYMENT_NOTIFY_USER', true),
];