<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class PaymentReceiptValidator
{
    /**
     * Process the payment receipt and extract key information
     * 
     * @param string $imagePath
     * @param Payment $payment
     * @return array
     */
    public function validateReceipt($imagePath, Payment $payment)
    {
        try {
            // Get the full path to the image
            $fullImagePath = Storage::disk('public')->path($imagePath);
            
            // Preprocess the image for better OCR results
            $this->preprocessImage($fullImagePath);
            
            // Perform OCR on the image if Tesseract is available
            $text = $this->performOCR($fullImagePath);
            
            // Extract key information
            $result = $this->extractPaymentInfo($text, $payment);
            
            // Log the OCR results for analysis and improvement
            Log::info('OCR Payment Validation', [
                'payment_id' => $payment->id,
                'validation_score' => $result['score'],
                'auto_approved' => $result['is_valid'],
                'extracted_data' => $result['extracted_data']
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('OCR Payment Validation Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a default result when OCR fails
            return [
                'score' => 0,
                'max_score' => 10,
                'is_valid' => false,
                'extracted_data' => [],
                'confidence' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Preprocess the image to improve OCR accuracy
     * 
     * @param string $imagePath
     * @return void
     */
    private function preprocessImage($imagePath)
    {
        // Load the image
        $image = Image::make($imagePath);
        
        // Enhance the image for better OCR results
        $image->resize(1800, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })
        ->brightness(5)
        ->contrast(15)
        ->save($imagePath);
    }
    
    /**
     * Perform OCR on the image
     * 
     * @param string $imagePath
     * @return string
     */
    private function performOCR($imagePath)
    {
        // Check if Tesseract OCR is installed
        $tesseractInstalled = $this->isTesseractInstalled();
        
        if ($tesseractInstalled) {
            try {
                // Use Tesseract OCR
                $tesseract = new \thiagoalessio\TesseractOCR\TesseractOCR($imagePath);
                
                // Configure OCR (support both English and Arabic if available)
                try {
                    $tesseract->lang('eng', 'ara');
                } catch (\Exception $e) {
                    // If Arabic language data is not available, fallback to English
                    $tesseract->lang('eng');
                }
                
                // Run OCR
                return $tesseract->run();
            } catch (\Exception $e) {
                Log::error('Tesseract OCR Error', [
                    'error' => $e->getMessage(),
                    'image_path' => $imagePath
                ]);
                // Fallback to simple content extraction
                return $this->fallbackContentExtraction($imagePath);
            }
        } else {
            Log::warning('Tesseract OCR not installed. Using fallback method.');
            // Use a fallback method since Tesseract is not available
            return $this->fallbackContentExtraction($imagePath);
        }
    }
    
    /**
     * Fallback method for content extraction when Tesseract is not available
     * 
     * @param string $imagePath
     * @return string
     */
    private function fallbackContentExtraction($imagePath)
    {
        // This is a very basic fallback that uses image metadata and name
        // In a production environment, you might want to use a cloud OCR service as fallback
        
        $imageMeta = Image::make($imagePath)->exif();
        $filename = basename($imagePath);
        
        // Return any available metadata as text
        $text = "Filename: $filename\n";
        
        if (is_array($imageMeta)) {
            foreach ($imageMeta as $key => $value) {
                if (is_string($value)) {
                    $text .= "$key: $value\n";
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Check if Tesseract OCR is installed on the system
     * 
     * @return bool
     */
    private function isTesseractInstalled()
    {
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
        
        return $isAvailable;
    }
    
    /**
     * Extract and validate payment information from OCR text
     * 
     * @param string $text
     * @param Payment $payment
     * @return array
     */
    private function extractPaymentInfo($text, Payment $payment)
    {
        $score = 0;
        $maxScore = 10;
        $extracted = [
            'reference_number' => null,
            'amount' => null,
            'transaction_id' => null,
            'date' => null
        ];
        
        // Check for reference number (highest importance)
        if (strpos($text, $payment->reference_number) !== false) {
            $score += 4;
            $extracted['reference_number'] = $payment->reference_number;
        }
        
        // Check for payment amount (with some flexibility for formatting)
        $amountPattern = '/\b' . preg_quote((string)$payment->amount, '/') . '(?:\.\d{1,2})?\s*(EGP|LE|L\.E\.?|جنيه|جنيها?|مصري)?\b/i';
        if (preg_match($amountPattern, $text, $matches)) {
            $score += 3;
            $extracted['amount'] = $matches[0];
        }
        
        // Check for transaction ID based on payment method pattern
        $method = $payment->payment_data['payment_method_used'] ?? '';
        $patterns = config('payment.transaction_patterns', []);
        if (isset($patterns[$method]['pattern'])) {
            $pattern = '/' . $patterns[$method]['pattern'] . '/i';
            if (preg_match($pattern, $text, $matches)) {
                $score += 2;
                $extracted['transaction_id'] = $matches[0];
            }
        }
        
        // Look for date patterns in the text
        $datePattern = '/\d{1,2}[-\/\.]\d{1,2}[-\/\.]\d{2,4}|\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+\d{2,4}/i';
        if (preg_match($datePattern, $text, $matches)) {
            $score += 1;
            $extracted['date'] = $matches[0];
        }
        
        // Auto-approve if score meets threshold (configurable)
        $threshold = config('payment.ocr_approval_threshold', 7);
        $isValid = $score >= $threshold;
        
        return [
            'score' => $score,
            'max_score' => $maxScore,
            'is_valid' => $isValid,
            'extracted_data' => $extracted,
            'confidence' => ($score / $maxScore) * 100
        ];
    }
} 