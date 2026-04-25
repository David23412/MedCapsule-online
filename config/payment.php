<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the configuration options for the payment system.
    |
    */

    'provider' => env('PAYMENT_PROVIDER', 'manual'),

    'currency' => env('PAYMENT_CURRENCY', 'EGP'),

    'paymob' => [
        'enabled' => env('PAYMOB_ENABLED', false),
        'mode' => env('PAYMOB_MODE', 'test'),
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com'),
        'public_key' => env('PAYMOB_PUBLIC_KEY'),
        'secret_key' => env('PAYMOB_SECRET_KEY'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'card_integration_id' => env('PAYMOB_CARD_INTEGRATION_ID'),
        'wallet_integration_id' => env('PAYMOB_WALLET_INTEGRATION_ID'),
        'iframe_id' => env('PAYMOB_IFRAME_ID'),
    ],

    // Manual payment options
    'vodafone_cash' => [
        'enabled' => env('VODAFONE_CASH_ENABLED', true),
        'number' => env('VODAFONE_CASH_NUMBER', '01XXXXXXXXX'),
        'name' => env('VODAFONE_CASH_NAME', 'MedCapsule'),
    ],
    
    'fawry' => [
        'enabled' => env('FAWRY_ENABLED', true),
        'number' => env('FAWRY_NUMBER', 'FAWRY-ACCOUNT-NUMBER'),
        'name' => env('FAWRY_NAME', 'MedCapsule'),
    ],
    
    'instapay' => [
        'enabled' => env('INSTAPAY_ENABLED', true),
        'number' => env('INSTAPAY_NUMBER', env('INSTAPAY_USERNAME', 'INSTAPAY-USERNAME')),
        'username' => env('INSTAPAY_USERNAME', 'INSTAPAY-USERNAME'),
        'name' => env('INSTAPAY_NAME', 'MedCapsule'),
    ],

    'bank_transfer' => [
        'enabled' => env('BANK_TRANSFER_ENABLED', true),
        'bank_name' => env('BANK_TRANSFER_BANK_NAME', 'Your Bank'),
        'account_name' => env('BANK_TRANSFER_ACCOUNT_NAME', 'MedCapsule'),
        'iban' => env('BANK_TRANSFER_IBAN', 'EGXXXXXXXXXXXXXXXXXXXXXXXXX'),
        'account_number' => env('BANK_TRANSFER_ACCOUNT_NUMBER'),
        'swift' => env('BANK_TRANSFER_SWIFT'),
        'branch' => env('BANK_TRANSFER_BRANCH'),
        'instructions' => env('BANK_TRANSFER_INSTRUCTIONS', 'Use your full name or order reference in the transfer note, then submit the transfer reference below.'),
    ],
    
    // Payment options
    'reference_expiry_hours' => env('PAYMENT_REFERENCE_EXPIRY_HOURS', 24),
    
    // Auto-verification settings
    'enable_auto_verification' => env('ENABLE_PAYMENT_AUTO_VERIFICATION', true),
    
    // Transaction patterns for verification
    'transaction_patterns' => [
        'vodafone_cash' => [
            'pattern' => '^VC\d{10,12}$',
            'example' => 'VC1234567890'
        ],
        'fawry' => [
            'pattern' => '^FWY\d{12,15}$',
            'example' => 'FWY123456789012'
        ],
        'instapay' => [
            'pattern' => '^IP\d{14,16}$',
            'example' => 'IP12345678901234'
        ]
    ],
    
    // Admin notification settings
    'notify_admin_on_new_payment' => env('NOTIFY_ADMIN_ON_NEW_PAYMENT', true),
    'admin_email' => env('PAYMENT_ADMIN_EMAIL', 'admin@example.com'),
    
    // OCR Configuration
    'ocr' => [
        'enabled' => env('PAYMENT_OCR_ENABLED', true),
        'approval_threshold' => env('PAYMENT_OCR_APPROVAL_THRESHOLD', 7),
        'admin_review_threshold' => env('PAYMENT_OCR_ADMIN_REVIEW_THRESHOLD', 4),
        'save_processed_images' => env('PAYMENT_OCR_SAVE_PROCESSED_IMAGES', true),
        'enhance_image' => env('PAYMENT_OCR_ENHANCE_IMAGE', true),
    ],
    
    // Advanced Verification Settings
    'verification' => [
        // Overall confidence threshold for auto-approval (0-100)
        'auto_approval_threshold' => env('PAYMENT_VERIFICATION_THRESHOLD', 70),
        
        // Expedited review threshold (40-69)
        'expedited_review_threshold' => env('PAYMENT_EXPEDITED_REVIEW_THRESHOLD', 40),
        
        // Weight of each verification method (must add up to 10)
        'method_weights' => [
            'transaction_pattern' => 5,   // 50% of score from transaction ID pattern
            'ocr' => 3,                  // 30% of score from OCR (when enabled)
            'metadata' => 2              // 20% of score from payment metadata
        ],
        
        // Time window for payment submission after creation (in hours)
        'expected_submission_window' => env('PAYMENT_EXPECTED_SUBMISSION_WINDOW', 3),
        
        // Add additional verification features (each can be enabled/disabled)
        'features' => [
            'user_history' => env('PAYMENT_VERIFY_USER_HISTORY', true),    // Check user payment history
            'submission_timing' => env('PAYMENT_VERIFY_TIMING', true),      // Check submission timing
            'price_matching' => env('PAYMENT_VERIFY_PRICE_MATCH', true)     // Verify course price matches payment
        ]
    ],
    
    // Storage Management Settings
    'storage' => [
        // Enable/disable storage management features
        'enabled' => env('PAYMENT_STORAGE_MANAGEMENT_ENABLED', true),
        
        // Compress images on upload (for files over 1MB)
        'compress_on_upload' => env('PAYMENT_COMPRESS_ON_UPLOAD', true),
        'upload_compression_quality' => env('PAYMENT_UPLOAD_COMPRESSION_QUALITY', 85),
        'upload_compression_threshold' => env('PAYMENT_UPLOAD_COMPRESSION_THRESHOLD', 1024), // Size in KB
        
        // Retention periods (in days)
        'original_retention_days' => env('PAYMENT_ORIGINAL_RETENTION_DAYS', 60),
        'compressed_retention_days' => env('PAYMENT_COMPRESSED_RETENTION_DAYS', 90),
        'archived_retention_days' => env('PAYMENT_ARCHIVED_RETENTION_DAYS', 365),
        
        // Compression settings
        'compression_quality' => env('PAYMENT_COMPRESSION_QUALITY', 70),
        'thumbnail_width' => env('PAYMENT_THUMBNAIL_WIDTH', 300),
        
        // Processing limits
        'cleanup_batch_size' => env('PAYMENT_CLEANUP_BATCH_SIZE', 100),
    ],
]; 
