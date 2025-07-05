<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class PaymentSettingsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the payment settings page.
     *
     * @return \Illuminate\View\View
     */
    public function showPaymentSettingsForm()
    {
        // Get current settings from config
        $settings = [
            'ocr' => [
                'enabled' => config('payment.ocr.enabled', true),
                'approval_threshold' => config('payment.ocr.approval_threshold', 7),
                'admin_review_threshold' => config('payment.ocr.admin_review_threshold', 4),
                'save_processed_images' => config('payment.ocr.save_processed_images', true),
                'enhance_image' => config('payment.ocr.enhance_image', true),
            ],
            'verification' => [
                'auto_approval_threshold' => config('payment.verification.auto_approval_threshold', 70),
                'expedited_review_threshold' => config('payment.verification.expedited_review_threshold', 40),
                'method_weights' => config('payment.verification.method_weights', [
                    'transaction_pattern' => 5,
                    'ocr' => 3,
                    'metadata' => 2
                ]),
            ],
            'storage' => [
                'enabled' => config('payment.storage.enabled', true),
                'compress_on_upload' => config('payment.storage.compress_on_upload', true),
                'compression_quality' => config('payment.storage.compression_quality', 70),
            ],
            'general' => [
                'reference_expiry_hours' => config('payment.reference_expiry_hours', 24),
                'enable_auto_verification' => config('payment.enable_auto_verification', true),
            ]
        ];

        // Get OCR status
        $ocrAvailable = $this->checkOcrAvailability();

        return view('admin.payments.settings', compact('settings', 'ocrAvailable'));
    }

    /**
     * Update payment settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePaymentSettings(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'ocr_enabled' => 'boolean',
            'auto_verification_enabled' => 'boolean',
            'ocr_approval_threshold' => 'integer|min:1|max:10',
            'ocr_admin_review_threshold' => 'integer|min:1|max:10',
            'auto_approval_threshold' => 'integer|min:1|max:100',
            'expedited_review_threshold' => 'integer|min:1|max:100',
        ]);

        try {
            // Update environment variables for dynamic settings
            $this->updateEnvironmentFile([
                'PAYMENT_OCR_ENABLED' => $request->ocr_enabled ? 'true' : 'false',
                'ENABLE_PAYMENT_AUTO_VERIFICATION' => $request->auto_verification_enabled ? 'true' : 'false',
                'PAYMENT_OCR_APPROVAL_THRESHOLD' => $request->ocr_approval_threshold,
                'PAYMENT_OCR_ADMIN_REVIEW_THRESHOLD' => $request->ocr_admin_review_threshold,
                'PAYMENT_VERIFICATION_THRESHOLD' => $request->auto_approval_threshold,
                'PAYMENT_EXPEDITED_REVIEW_THRESHOLD' => $request->expedited_review_threshold,
            ]);

            // Clear config cache to apply changes
            Artisan::call('config:clear');
            Cache::forget('payment_system_status');

            // Log the changes
            Log::info('Payment settings updated by admin', [
                'admin_id' => auth()->id(),
                'changes' => $validated
            ]);

            return redirect()->route('payment-settings.form')
                ->with('success', 'Payment settings updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating payment settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment-settings.form')
                ->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Check if OCR is available on the system.
     *
     * @return bool
     */
    private function checkOcrAvailability()
    {
        try {
            $output = null;
            $returnVar = null;
            exec('which tesseract 2>/dev/null', $output, $returnVar);
            
            return $returnVar === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update the environment file with new values.
     *
     * @param array $values
     * @return void
     */
    private function updateEnvironmentFile(array $values)
    {
        $envFile = app()->environmentFilePath();
        $envContents = file_get_contents($envFile);

        foreach ($values as $key => $value) {
            // First check if the key exists
            if (strpos($envContents, "{$key}=") !== false) {
                // Replace existing value
                $envContents = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $envContents
                );
            } else {
                // Add new value
                $envContents .= PHP_EOL . "{$key}={$value}";
            }
        }

        file_put_contents($envFile, $envContents);
    }
} 