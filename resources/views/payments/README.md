# Payment System Templates

This directory contains all the Blade templates related to the payment system. The naming convention follows a clear pattern to make the purpose of each file immediately obvious.

## File Naming Convention

We've established a consistent naming pattern to make it easy to understand the purpose of each file:

1. **Scope prefix**: `single_course_` or `bundle_` - Indicates if the template is for individual course payments or bundle payments
2. **Function**: `payment_methods` - Indicates the template is for selecting payment methods

## Template Files

### Single Course Payment Files

- `single_course_payment_methods.blade.php`: Modal with payment method selection and processing options for a single course

### Bundle Payment Files

- `bundle_payment_methods.blade.php`: Modal with payment method selection options for course bundles
- `bundle_payment_course_selection.blade.php`: Modal for displaying selected courses and processing manual payments for course bundles

## Flow Overview

1. User clicks on a payment button in the courses page
2. Payment method selection modal appears directly (`single_course_payment_methods.blade.php` or `bundle_payment_methods.blade.php`)
3. For single courses, the payment is processed directly from the payment methods modal
4. For bundles, after selecting a payment method, the course selection and payment modal appears (`bundle_payment_course_selection.blade.php`)

## Usage Notes

- All templates follow a responsive design pattern and are optimized for mobile devices
- Auto-verification is available for certain payment methods
- Payment receipt upload functionality is included in the payment method templates
- Reference numbers are automatically included in the backend processing without requiring users to manually include them 