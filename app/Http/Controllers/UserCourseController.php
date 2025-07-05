<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserCourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->is_admin) {
                return redirect()->route('home')->with('error', 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        if (!auth()->user()->is_admin) {
            return redirect()->route('courses.index')->with('error', 'Unauthorized access');
        }
        
        $users = User::orderBy('name')->get();
        $courses = Course::orderBy('name')->get();
        
        // Get user statistics
        $userStats = [
            'total_users' => User::count(),
            'active_users' => User::where('last_active_at', '>=', now()->subDays(7))->count(),
            'admin_users' => User::where('is_admin', true)->count(),
            'total_enrollments' => DB::table('course_user')->count(),
        ];
        
        // Get latest registered users
        $latestUsers = User::select('id', 'name', 'email', 'created_at as registered_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name, 
                    'email' => $user->email,
                    'registered_at' => $user->registered_at,
                    'courses_count' => $user->courses()->count(),
                ];
            });
        
        // Get course enrollment statistics
        $courseStats = DB::table('courses')
            ->select('courses.name', DB::raw('COUNT(course_user.id) as enrolled_count'))
            ->leftJoin('course_user', 'courses.id', '=', 'course_user.course_id')
            ->groupBy('courses.name')
            ->orderBy('enrolled_count', 'desc')
            ->get();
        
        // Get recent enrollments for history
        $enrolledUsers = DB::table('course_user')
            ->select(
                'users.email', 
                'courses.name as course_name', 
                'course_user.created_at',
                'course_user.enrollment_status'
            )
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->orderBy('course_user.created_at', 'desc')
            ->limit(30)
            ->get();
        
        // Get payment settings data for the settings tab
        $paymentSettings = [
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
        
        // Check OCR availability
        $ocrAvailable = $this->checkOcrAvailability();
        
        return view('admin.manage-access', compact(
            'users', 
            'courses', 
            'userStats', 
            'latestUsers', 
            'courseStats', 
            'enrolledUsers',
            'paymentSettings',
            'ocrAvailable'
        ));
    }

    public function searchUsers(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }
        $query = $request->input('query');
        return User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return $this->mapUserSearchResults($user);
            });
    }

    public function enroll(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'action' => 'required|in:enroll,unenroll'
        ]);

        $user = User::findOrFail($request->user_id);
        $course = Course::findOrFail($request->course_id);

        if ($request->action === 'enroll') {
            $user->enrollInCourse($course->id);
            $message = 'Access granted successfully';
        } else {
            $user->unenrollFromCourse($course->id);
            $message = 'Access removed successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'enrolled_courses' => $user->courses()->pluck('course_id'),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'course_name' => $course->name,
                'enrolled_at' => now()->diffForHumans()
            ]
        ]);
    }

    public function toggleAdmin(Request $request)
    {
        if (!auth()->user()->is_admin) {
            abort(403);
        }
        $request->validate(['user_id' => 'required|exists:users,id']);
        $user = User::findOrFail($request->user_id);

        // Preventing removal of admin status from the last admin
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove admin status from the last admin user.'
            ], 400);
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_admin ? 'Admin privileges granted' : 'Admin privileges revoked',
            'is_admin' => $user->is_admin
        ]);
    }

    // Helper methods for optimized queries and logic

    private function getRecentEnrollments()
    {
        return DB::table('course_user')
            ->join('users', 'users.id', '=', 'course_user.user_id')
            ->join('courses', 'courses.id', '=', 'course_user.course_id')
            ->select('users.name', 'users.email', 'courses.name as course_name', 'course_user.created_at')
            ->orderBy('course_user.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'user' => [
                        'name' => $enrollment->name,
                        'email' => $enrollment->email
                    ],
                    'enrolled_at' => Carbon::parse($enrollment->created_at)->diffForHumans(),
                    'courses' => [$enrollment->course_name]
                ];
            });
    }

    private function getUserStats()
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('last_active_at', '>=', now()->subDays(7))->count(),
            'admin_users' => User::where('is_admin', true)->count(),
            'total_enrollments' => DB::table('course_user')->count()
        ];
    }

    private function getLatestUsers()
    {
        return User::select('email', 'created_at as registered_at')
            ->withCount('courses')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($user) {
                return [
                    'email' => $user->email,
                    'registered_at' => $user->registered_at,
                    'courses_count' => $user->courses_count
                ];
            });
    }

    private function getCourseStats()
    {
        return Course::select('id', 'name')
            ->withCount(['topics', 'users'])
            ->get()
            ->map(function($course) {
                return [
                    'name' => $course->name,
                    'enrolled_count' => $course->users_count
                ];
            });
    }

    private function getEnrolledUsers()
    {
        return DB::table('course_user')
            ->join('users', 'users.id', '=', 'course_user.user_id')
            ->join('courses', 'courses.id', '=', 'course_user.course_id')
            ->select('users.email', 'courses.name as course_name', 'course_user.created_at', 'course_user.enrollment_status')
            ->orderBy('course_user.created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function mapUserSearchResults($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'enrolled_courses' => $user->courses()->pluck('course_id'),
            'is_admin' => $user->is_admin
        ];
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
     * Update payment system settings
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
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Cache::forget('payment_system_status');

            // Log the changes
            \Illuminate\Support\Facades\Log::info('Payment settings updated by admin', [
                'admin_id' => auth()->id(),
                'changes' => $validated
            ]);

            return redirect()->route('admin.user-course', ['tab' => 'payment-settings'])
                ->with('success', 'Payment settings updated successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating payment settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.user-course', ['tab' => 'payment-settings'])
                ->with('error', 'Failed to update settings: ' . $e->getMessage());
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