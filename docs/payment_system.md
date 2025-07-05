# MedCapsule Payment System Documentation

This document provides a comprehensive overview of the MedCapsule manual payment system, explaining how it works, its security features, and the end-to-end flow.

## Table of Contents

1. [Overview](#overview)
2. [Key Components](#key-components)
3. [Payment Flow](#payment-flow)
4. [Security Features](#security-features)
5. [Verification Process](#verification-process)
6. [Technical Implementation](#technical-implementation)
7. [Admin Interface](#admin-interface)
8. [Advanced Verification System](#advanced-verification-system)
9. [Best Practices](#best-practices)

## Overview

The MedCapsule payment system enables users to purchase course access through manual payment methods like Vodafone Cash, Fawry, and Instapay. The system generates unique reference numbers for each transaction, validates payment proofs, and automatically enrolls users in courses upon verification.

## Key Components

1. **Payment Reference Number**: A unique identifier generated for each payment attempt, following the format `MC[user+course hash][timestamp][random string]` (e.g., `MCA3B21666123456ABCDEFGH`)

2. **Transaction ID**: A standardized identifier from the payment provider that users must submit (e.g., `VC1234567890` for Vodafone Cash)

3. **Payment Proof**: A screenshot or image of the payment receipt that users upload as evidence of payment

4. **Verification System**: Multi-layer verification processes to validate payments both automatically and manually

## Payment Flow

### User Journey

1. **Course Selection**: User chooses a course they want to purchase
2. **Payment Initiation**: System generates a new payment record with a unique reference number
3. **Payment Instructions**: User receives payment details (account numbers) and the unique reference number
4. **External Payment**: User makes the payment using their chosen method outside the platform
5. **Proof Submission**: User uploads proof of payment with transaction details
6. **Verification**: System attempts auto-verification through multiple methods; if unsuccessful, admin verification is required
7. **Access Granted**: Upon verification, user is automatically enrolled in the course

### System Process

1. **Reference Generation**: When a user initiates payment, any previous pending payments are invalidated, and a new reference number is generated
2. **Expiration Control**: Payment references expire after 24 hours (configurable)
3. **Multi-Layer Verification**: The system employs multiple verification methods:
   - Pattern-based transaction ID validation
   - Image-based receipt analysis (when OCR is enabled)
   - Reference number and amount verification
4. **Admin Verification**: If auto-verification fails, administrators review and manually verify payments
5. **Course Enrollment**: Upon verification, users are automatically enrolled in the purchased course

## Security Features

- **Unique Reference Numbers**: Each payment attempt generates a cryptographically secure reference incorporating user ID, course ID, timestamp, and random elements
- **Transaction ID Validation**: Pattern matching ensures transaction IDs follow expected formats for each payment method
- **Image Analysis**: Optional OCR-based analysis to extract and verify key information from payment receipts
- **IP Tracking**: Records IP addresses and user agents when payment proofs are submitted
- **Payment Expiration**: References expire after a configurable time period to prevent reuse
- **Access Control**: Multiple verification checks ensure only authorized users can access course content
- **Admin Verification**: Multi-step verification process for administrators with notes and audit trails

## Verification Process

### Auto-Verification System

The system uses a multi-layered approach to automatically verify payments:

#### Layer 1: Transaction ID Pattern Validation
- Vodafone Cash: `VC` followed by 10-12 digits (e.g., `VC1234567890`)
- Fawry: `FWY` followed by 12-15 digits (e.g., `FWY123456789012`)
- Instapay: `IP` followed by 14-16 digits (e.g., `IP12345678901234`)

#### Layer 2: Receipt Image Analysis (when enabled)
- Extracts text from receipt images
- Verifies presence of reference number (highest priority)
- Validates payment amount
- Identifies transaction ID 
- Confirms payment date

#### Layer 3: Composite Scoring System
- Each validation element contributes to a confidence score
- Payments achieving a threshold score are automatically approved
- Partial matches are flagged for expedited admin review
- Full history of verification attempts is maintained

### Manual Verification

If auto-verification fails, administrators can:
1. View payment details including the uploaded proof image
2. See results from automated verification attempts
3. Verify payments individually or in bulk
4. Reject payments with categorized rejection reasons
5. Add notes for record-keeping

## Technical Implementation

### Key Models

- `Payment`: Stores payment records, status, and reference numbers
- `Course`: Contains course information and pricing
- `User`: Manages user accounts and course enrollments
- `PaymentReceiptValidator`: Handles receipt image validation

### Database Structure

The payment system uses the following key fields:

- `payments` table:
  - `id`: Primary key
  - `user_id`: Foreign key to users table
  - `course_id`: Foreign key to courses table
  - `reference_number`: Unique payment reference
  - `amount`: Payment amount
  - `payment_method`: Method of payment
  - `status`: Current status (pending, pending_verification, completed, rejected, expired)
  - `payment_data`: JSON field containing transaction details, verification history, and validation results
  - `paid_at`: Timestamp when payment was verified

### Configuration Options

The system is highly configurable through the `config/payment.php` file:

```php
// Payment provider details
'vodafone_cash' => [
    'number' => env('VODAFONE_CASH_NUMBER', '01XXXXXXXXX'),
    'name' => env('VODAFONE_CASH_NAME', 'MedCapsule'),
],

// Security settings
'reference_expiry_hours' => env('PAYMENT_REFERENCE_EXPIRY_HOURS', 24),
'enable_auto_verification' => env('ENABLE_PAYMENT_AUTO_VERIFICATION', true),

// Transaction patterns for verification
'transaction_patterns' => [
    'vodafone_cash' => [
        'pattern' => '^VC\d{10,12}$',
        'example' => 'VC1234567890'
    ],
    // Other payment methods...
],

// OCR Configuration (optional)
'ocr' => [
    'enabled' => env('PAYMENT_OCR_ENABLED', true),
    'approval_threshold' => env('PAYMENT_OCR_APPROVAL_THRESHOLD', 7),
    'admin_review_threshold' => env('PAYMENT_OCR_ADMIN_REVIEW_THRESHOLD', 4),
],
```

## Admin Interface

### Payment Management Dashboard

Administrators have access to a comprehensive dashboard that displays:

1. **Payment Statistics**: 
   - Total pending verifications
   - Completed payments
   - Rejected payments
   - Total revenue
   - Daily revenue

2. **Filtering Options**:
   - By status (pending, completed, rejected, expired)
   - By reference number
   - By transaction ID
   - By date range
   - By user

3. **Bulk Actions**:
   - Verify multiple payments at once
   - Export payment records

4. **Detailed Views**:
   - Payment proof images
   - Transaction details
   - User information
   - Course details
   - Verification history
   - Automated verification results

## Advanced Verification System

### Multi-Layer Verification Architecture

The MedCapsule payment system employs a sophisticated verification architecture that doesn't rely solely on OCR but leverages multiple complementary approaches:

1. **Transaction ID Pattern Recognition**: The primary verification method matches transaction IDs against predefined patterns for each payment method. This is highly reliable and works locally without external dependencies.

2. **Receipt Image Analysis**: When OCR is available, the system extracts information from payment receipts to validate:
   - Reference numbers (highest priority, 4 points)
   - Payment amounts (3 points)
   - Transaction IDs (2 points)
   - Payment dates (1 point)

3. **Confidence Scoring**: Each verification method contributes to a confidence score:
   - High confidence (≥70%): Automatic approval
   - Medium confidence (40-69%): Expedited admin review with validation hints
   - Low confidence (<40%): Standard admin review

4. **Fallback Mechanisms**: If OCR is unavailable or fails, the system gracefully falls back to transaction ID pattern validation, ensuring the system always works.

5. **Audit Trail**: Each verification attempt, successful or not, is logged with detailed information for security auditing and process improvement.

### Admin Review Optimization

For payments requiring manual review, the admin interface is optimized to minimize verification time:

1. **Visual Verification Aids**: Payment proofs are displayed alongside extracted data, with mismatches highlighted.

2. **Confidence Indicators**: Color-coded indicators show the system's confidence in each detected field.

3. **Verification History**: Complete history of automated verification attempts and their results.

4. **Bulk Processing**: Similar payments can be grouped and processed together based on common attributes like user, course, or payment method.

5. **Keyboard Shortcuts**: Streamlined verification shortcuts reduce admin handling time.

## Best Practices

### For Users

1. **Include Reference Number**: Always include the provided reference number in the payment description
2. **Clear Screenshots**: Submit clear, unedited screenshots of payment receipts showing all relevant information
3. **Correct Payment Method**: Use the exact payment account details provided in the instructions
4. **Verify Information**: Double-check the amount and reference number before making payment

### For Administrators

1. **Cross-Check Information**: Always verify that the payment amount matches the course price
2. **Validate References**: Ensure the reference number in the payment proof matches the system record
3. **Check Transaction IDs**: Verify transaction IDs against payment provider patterns
4. **Review Image Authenticity**: Look for signs of image manipulation in payment proofs
5. **Monitor Patterns**: Watch for unusual payment patterns from the same user or IP address
6. **Verify Payment Data**: Pay attention to system-extracted data and verification scores

---

This documentation is maintained by the MedCapsule development team. For questions or updates, please contact the system administrator. 