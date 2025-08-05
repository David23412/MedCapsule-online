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

    /**
     * Get payment options for a bundle of courses
     */
    public function getBundlePaymentOptions(Request $request)
    {
        try {
            $validated = $request->validate([
                'courses' => 'required|array|min:1',
                'courses.*' => 'integer|exists:courses,id'
            ]);

            $user = Auth::user();
            $courseIds = $validated['courses'];
            
            // Get courses with optimized query, excluding free courses
            $courses = Course::select('id', 'name', 'price')
                ->whereIn('id', $courseIds)
                ->where('price', '>', 0) // Exclude free courses
                ->get();

            // Check for existing access
            $enrolledCourseIds = $user->courses()
                ->whereIn('course_id', $courseIds)
                ->where('is_enrolled', true)
                ->pluck('course_id')
                ->toArray();

            $availableCourses = $courses->whereNotIn('id', $enrolledCourseIds);
            
            if ($availableCourses->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have access to all selected courses.'
                ]);
            }

            // Calculate total price with rounding to nearest .99 for each course
            $totalPrice = $availableCourses->sum(function($course) {
                return floor($course->price) + 0.99;
            });
            
            // Generate bundle payment HTML
            $html = view('payments.bundle_payment_methods', [
                'courses' => $availableCourses,
                'totalPrice' => $totalPrice
            ])->render();
            
            return response()->json([
                'success' => true,
                'message' => 'Bundle payment options loaded successfully.',
                'html' => $html,
                'total_price' => $totalPrice
            ]);
            
        } catch (\Exception $e) {
            Log::error('Bundle payment options error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'courses' => $request->input('courses', [])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load bundle payment options. Please try again.'
            ], 500);
        }
    }
}