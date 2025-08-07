<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use App\Services\QuizService;
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
        $this->middleware(['auth']);
    }

    /**
     * Display the payment settings page.
     */
    public function showPaymentSettingsForm()
    {
        // Ultra-aggressive caching for payment settings with eager loading
        $cacheKey = QuizCacheService::generateCacheKey('payment_settings', [
            'user_id' => auth()->id(),
            'timestamp' => now()->format('Y-m-d-H')
        ]);
        
        $data = Cache::remember($cacheKey, 3600, function () {
            // Load user with payment-related relationships
            $user = auth()->user()->load(['payments.course:id,name,price',
                'courses:id,name,price',
                'courses.payments:id,course_id,user_id,status'// Get current settings from config
            $settings = [
            'ocr' => [
                'enabled' => config('payment.ocr.enabled', true),
                'approval_threshold' => config('payment.ocr.approval_threshold', 7),
                'admin_review_threshold' => config('payment.ocr.admin_review_threshold', 4),
                'save_processed_images' => config('payment.ocr.save_processed_images', true),
                'enhance_image' => config('payment.ocr.enhance_image', true)],
            'verification' => [
                'auto_approval_threshold' => config('payment.verification.auto_approval_threshold', 70),
                'expedited_review_threshold' => config('payment.verification.expedited_review_threshold', 40),
                'method_weights' => config('payment.verification.method_weights', [
                    'transaction_pattern' => 5,
                    'ocr' => 3,
                    'metadata' => 2
                ])],
            'storage' => [
                'enabled' => config('payment.storage.enabled', true),
                'compress_on_upload' => config('payment.storage.compress_on_upload', true),
                'compression_quality' => config('payment.storage.compression_quality', 70)],
            'general' => [
                'reference_expiry_hours' => config('payment.reference_expiry_hours', 24),
                'enable_auto_verification' => config('payment.enable_auto_verification', true)]
        ];

            return [
                'settings' => $settings,
                'ocrAvailable' => $this->checkOcrAvailability()
            ];
        });

        return view('admin.payments.settings', [
            'settings' => $data['settings'],
            'ocrAvailable' => $data['ocrAvailable']
        ]);
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
            // TODO: Add validation rules
        ]);

        try {
            // Update environment variables for dynamic settings
            $this->updateEnvironmentFile([
                'PAYMENT_OCR_ENABLED' => $request->ocr_enabled ? 'true' : 'false',
                'ENABLE_PAYMENT_AUTO_VERIFICATION' => $request->auto_verification_enabled ? 'true' : 'false',
                'PAYMENT_OCR_APPROVAL_THRESHOLD' => $request->ocr_approval_threshold,
                'PAYMENT_OCR_ADMIN_REVIEW_THRESHOLD' => $request->ocr_admin_review_threshold,
                'PAYMENT_VERIFICATION_THRESHOLD' => $request->auto_approval_threshold,
                'PAYMENT_EXPEDITED_REVIEW_THRESHOLD' => $request->expedited_review_threshold]);

            // Clear config cache to apply changes
            Artisan::call('config:clear');
            Cache::forget('payment_system_status');

            // Log the changes
            Log::info('Payment settings updated by admin', [
                'admin_id' => auth()->id(),
                'changes' => $validated
            ]);

            return redirect()->route('payment-settings.form')
                ->with('success', 'Payment settings updated successfully');}

    /**
     * Check if OCR is available on the system.
     *
     * @return bool
     */
    private function checkOcrAvailability()
    {
        try {
            // Check if tesseract is available
            $tesseractPath = '/usr/bin/tesseract';
            $isAvailable = file_exists($tesseractPath) && is_executable($tesseractPath);
            
            if (!$isAvailable) {
                // Try common alternative paths
                $alternativePaths = [
                    '/usr/local/bin/tesseract',
                    '/opt/homebrew/bin/tesseract',
                    '/usr/bin/tesseract-ocr'
                ];
                
                foreach ($alternativePaths as $path) {
                    if (file_exists($path) && is_executable($path)) {
                        $tesseractPath = $path;
                        $isAvailable = true;
                        break;
                    }
                }
            }

            return $isAvailable;}

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