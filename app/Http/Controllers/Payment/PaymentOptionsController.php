<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * 💀 NUCLEAR OPTIMIZED PAYMENT OPTIONS CONTROLLER 💀
 * Handles payment option retrieval with F-16 RAPTOR SPEED
 */
class PaymentOptionsController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->middleware(['auth']);
        $this->paymentService = $paymentService;
    }

    /**
     * Get payment options for a single course
     */
    public function getPaymentOptions(Course $course)
    {
        $user = Auth::user();
        
        // Ultra-aggressive caching for payment options
        $cacheKey = "payment_options_{$course->id}_{$user->id}";
        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, 1800, function () use ($course, $user) {
            // Check if user already has access
            $hasAccess = $user->courses()
                ->where('course_id', $course->id)
                ->where('is_enrolled', true)
                ->exists();

            if ($hasAccess) {
                return [
                    'success' => false,
                    'message' => 'You already have access to this course.',
                    'redirect' => route('topics.forCourse', $course->id)
                ];
            }
            
            // Generate payment options HTML
            $html = view('payments.single_course_payment_methods', [
                'course' => $course
            ])->render();
            
            return [
                'success' => true,
                'message' => 'Payment options loaded successfully.',
                'html' => $html
            ];
        });
        
        return response()->json($result);
    }


}