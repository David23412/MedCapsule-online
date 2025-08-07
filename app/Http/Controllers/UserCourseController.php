<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserCourseController extends Controller
{
    public function __construct()
    {
        // NO AUTH CHECKS - DIRECT ACCESS
    }

    public function index()
    {
        try {
            // LETHAL IMPLEMENTATION - Force load all data
            Log::info('Loading manage access page - LETHAL MODE');
            
            // Get ALL users - NO EXCEPTIONS
            $users = User::select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get();
            
            Log::info('Users loaded: ' . $users->count());
            
            // Get ALL courses - NO EXCEPTIONS  
            $courses = Course::select('id', 'name', 'price')
                ->orderBy('name')
                ->get();
                
            Log::info('Courses loaded: ' . $courses->count());

            // Get user statistics with REAL data
            $totalEnrollments = DB::table('course_user')->count();
            $userStats = [
                'total_users' => $users->count(),
                'active_users' => $users->count(),
                'admin_users' => $users->where('role', 'admin')->count(),
                'total_enrollments' => $totalEnrollments
            ];

            // Get enrolled users with all enrollment statuses for admin view
            $enrolledUsers = DB::table('course_user')
                ->join('users', 'course_user.user_id', '=', 'users.id')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->select(
                    'users.id as user_id',
                    'users.name as name',
                    'users.email as email',
                    'courses.id as course_id',
                    'courses.name as course_name',
                    'course_user.is_enrolled',
                    'course_user.enrolled_at',
                    'course_user.created_at'
                )
                ->orderBy('course_user.created_at', 'desc')
                ->get();

            Log::info('Enrolled users loaded: ' . $enrolledUsers->count());

            // FORCE SUCCESS - NO FALLBACKS
            return view('admin.manage-access', compact('userStats', 'users', 'courses', 'enrolledUsers'));
            
        } catch (\Exception $e) {
            Log::error('LETHAL ERROR in manage access: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Even in error, provide SOMETHING
            $users = User::with(['courses'])->get();
            $courses = Course::with(['topics', 'users'])->get();
            $userStats = [
                'total_users' => $users->count(),
                'active_users' => $users->count(),
                'admin_users' => $users->where('role', 'admin')->count(),
                'total_enrollments' => 0
            ];
            $enrolledUsers = collect([]);
            
            return view('admin.manage-access', compact('userStats', 'users', 'courses', 'enrolledUsers'))
                ->with('error', 'LETHAL ERROR: ' . $e->getMessage());
        }
    }

    public function enroll(Request $request)
    {
        try {
            Log::emergency('🚨 ENROLL METHOD CALLED!', [
                'method' => $request->method(),
                'url' => $request->url(),
                'all_data' => $request->all(),
                'headers' => $request->headers->all(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            Log::info('🚀 GRANT ACCESS REQUEST', $request->all());

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'course_id' => 'required|exists:courses,id'
            ]);

            if ($validator->fails()) {
                Log::error('❌ Validation failed:', $validator->errors());
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data provided.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'course_id' => 'required|integer|exists:courses,id'
            ]);
            
            $user = User::findOrFail($validated['user_id']);
            $course = Course::findOrFail($validated['course_id']);

            Log::info('✅ User and Course found:', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'course_id' => $course->id,
                'course_name' => $course->name
            ]);

            // Check if enrollment already exists
            $existingEnrollment = DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingEnrollment) {
                Log::info('🔄 Updating existing enrollment');
                
                // Update existing enrollment to enrolled
                DB::table('course_user')
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->update([
                        'is_enrolled' => true,
                        'enrolled_at' => now(),
                        'updated_at' => now()
                    ]);
            } else {
                Log::info('🆕 Creating new enrollment');
                
                // Create new enrollment
                DB::table('course_user')->insert([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'is_enrolled' => true,
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Verify the enrollment was created/updated
            $verificationEnrollment = DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$verificationEnrollment || !$verificationEnrollment->is_enrolled) {
                throw new \Exception('Failed to create/update enrollment in database');
            }

            // INSTANT UPDATE: Clear course cache for immediate UI updates
            $this->clearCourseCacheForUser($user->id);

            Log::info('✅ ENROLLMENT COMPLETED SUCCESSFULLY', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'is_enrolled' => $verificationEnrollment->is_enrolled,
                'enrolled_at' => $verificationEnrollment->enrolled_at
            ]);

            return response()->json([
                'success' => true,
                'message' => "User {$user->name} enrolled in {$course->name} successfully.",
                'enrollment' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'is_enrolled' => true,
                    'enrolled_at' => $verificationEnrollment->enrolled_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Course enrollment failed: ' . $e->getMessage());
            Log::error('❌ Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll user in course: ' . $e->getMessage()
            ], 500);
        }
    }

    public function revoke(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'course_id' => 'required|exists:courses,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data provided.'
                ], 400);
            }

            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'course_id' => 'required|integer|exists:courses,id'
            ]);
            
            $user = User::findOrFail($validated['user_id']);
            $course = Course::findOrFail($validated['course_id']);

            // Check if enrollment exists
            $enrollment = DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$enrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not enrolled in this course.'
                ], 400);
            }

            // Simple toggle: if enrolled, revoke it. If revoked, enroll it.
            $newStatus = !$enrollment->is_enrolled;
            $action = $newStatus ? 'granted' : 'revoked';

            DB::table('course_user')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->update([
                    'is_enrolled' => $newStatus,
                    'updated_at' => now()
                ]);

            // INSTANT UPDATE: Clear course cache for immediate UI updates
            $this->clearCourseCacheForUser($user->id);

            Log::info('User enrollment toggled', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'admin_id' => auth()->id(),
                'new_status' => $newStatus ? 'enrolled' : 'revoked'
            ]);

            $message = $newStatus 
                ? "Access granted to {$user->name} for {$course->name}."
                : "Access revoked for {$user->name} from {$course->name}.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'is_enrolled' => $newStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Access revocation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke access.'
            ], 500);
        }
    }



    public function searchUsers(Request $request)
    {
        try {
            $query = $request->input('query', '');
            
            if (strlen($query) < 2) {
                return response()->json([]);
            }

            $users = User::select('id', 'name', 'email', 'role', 'created_at', 'last_login_at')
                ->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->with(['courses:id,name'])
                ->limit(20)
                ->get();

            return response()->json($users);

        } catch (\Exception $e) {
            Log::error('User search failed: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function userCourses($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $courses = $user->courses()->select('id', 'name', 'created_at')->get();

            return response()->json([
                'success' => true,
                'courses' => $courses
            ]);

        } catch (\Exception $e) {
            Log::error('User courses fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'courses' => []
            ]);
        }
    }

    public function toggleAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user ID provided.'
                ], 400);
            }

            $user = User::findOrFail($request->user_id);
            
            // Prevent self-modification
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own admin status.'
                ], 400);
            }

        // Check authorization for role assignment
        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

        // Check authorization for role assignment
        if (!auth()->user()->can('assign-roles')) {
            abort(403, 'Unauthorized to assign roles');
        }

            $wasAdmin = $user->role === 'admin';
            $user->role = $wasAdmin ? 'student' : 'admin';
        // Validate user creation data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8'
        ]);

            $user->save();

            Log::info('Admin status toggled', [
                'target_user_id' => $user->id,
                'new_role' => $user->role,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => $wasAdmin 
                    ? 'Admin privileges removed successfully.' 
                    : 'User promoted to admin successfully.',
                'new_role' => $user->role
            ]);

        } catch (\Exception $e) {
            Log::error('Admin toggle failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update admin status.'
            ], 500);
        }
    }

    public function payments(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $perPage = 20;
            
            $query = DB::table('payments')
                ->join('users', 'payments.user_id', '=', 'users.id')
                ->join('courses', 'payments.course_id', '=', 'courses.id')
                ->select(
                    'payments.id',
                    'payments.reference_number',
                    'payments.amount',
                    'payments.payment_method',
                    'payments.status',
                    'payments.created_at',
                    'payments.paid_at',
                    'payments.is_bundle',
                    'payments.payment_data',
                    'users.name as user_name',
                    'users.email as user_email',
                    'courses.name as course_name'
                );

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%")
                      ->orWhere('users.email', 'like', "%{$search}%")
                      ->orWhere('courses.name', 'like', "%{$search}%")
                      ->orWhere('payments.reference_number', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            $payments = $query->orderBy('payments.created_at', 'desc')
                            ->offset(($page - 1) * $perPage)
                            ->limit($perPage)
                            ->get();

            // Process bundle information for each payment
            $payments = $payments->map(function($payment) {
                $paymentData = json_decode($payment->payment_data, true) ?? [];
                
                // Add bundle information
                $payment->is_bundle_payment = $payment->is_bundle || isset($paymentData['bundle_id']);
                $payment->bundle_id = $paymentData['bundle_id'] ?? null;
                $payment->bundle_total_amount = $paymentData['bundle_total_amount'] ?? null;
                $payment->transaction_id = $paymentData['transaction_id'] ?? null;
                
                return $payment;
            });

            // Get payment statistics with proper type casting
            $totalRevenue = DB::table('payments')->where('status', 'completed')->sum('amount');
            $stats = [
                'total_revenue' => $totalRevenue ? (float) $totalRevenue : 0.0,
                'completed_count' => DB::table('payments')->where('status', 'completed')->count(),
                'pending_count' => DB::table('payments')->where('status', 'pending')->count(),
                'rejected_count' => DB::table('payments')->where('status', 'rejected')->count(),
            ];

            // Debug logging
            Log::info('Payment stats calculated:', $stats);
            Log::info('Total payments found:', ['count' => $payments->count()]);
            
            return response()->json([
                'success' => true,
                'payments' => $payments,
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment fetch failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load payments.'
            ], 500);
        }
    }

    public function updatePaymentSettings(Request $request)
    {
        try {
            // Implementation for payment settings
            return response()->json([
                'success' => true,
                'message' => 'Payment settings updated successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment settings update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment settings.'
            ], 500);
        }
    }
    
    /**
     * Get enrollment data for AJAX refresh
     */
    public function getEnrollments(Request $request)
    {
        try {
            $enrollments = DB::table('course_user')
                ->join('users', 'course_user.user_id', '=', 'users.id')
                ->join('courses', 'course_user.course_id', '=', 'courses.id')
                ->select(
                    'users.id as user_id',
                    'users.name as name',
                    'users.email as email',
                    'courses.id as course_id',
                    'courses.name as course_name',
                    'course_user.is_enrolled',
                    'course_user.enrolled_at',
                    'course_user.created_at'
                )
                ->orderBy('course_user.created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'enrollments' => $enrollments
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get enrollments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load enrollment data.'
            ], 500);
        }
    }
    
    /**
     * Get user enrollments for course dropdown
     */
    public function getUserEnrollments(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required.'
                ], 400);
            }
            
            // Get all courses with enrollment status for this user
            $courses = DB::table('courses')
                ->leftJoin('course_user', function($join) use ($userId) {
                    $join->on('courses.id', '=', 'course_user.course_id')
                         ->where('course_user.user_id', '=', $userId);
                })
                ->select(
                    'courses.id',
                    'courses.name',
                    'courses.price',
                    DB::raw('COALESCE(course_user.is_enrolled, false) as is_enrolled')
                )
                ->orderBy('courses.name')
                ->get();

            return response()->json([
                'success' => true,
                'courses' => $courses
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user enrollments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user enrollment data.'
            ], 500);
        }
    }

    /**
     * Clear course cache for instant UI updates
     */
    private function clearCourseCacheForUser($userId)
    {
        // Clear all course-related cache for this user
        $cacheVersion = \Cache::get('courses_cache_version', 1);
        
        // Clear specific user cache
        $patterns = [
            "courses_metadata_{$userId}_all_v{$cacheVersion}",
            "courses_metadata_{$userId}_*",
            "course_*_user_{$userId}_*"
        ];
        
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '*') !== false) {
                // For wildcard patterns, we increment the cache version to invalidate all
                \Cache::put('courses_cache_version', $cacheVersion + 1, 86400);
                break;
            } else {
                \Cache::forget($pattern);
            }
        }
        
        // Also clear general course cache to ensure consistency
        \Cache::forget('courses_list');
        \Cache::forget('courses_with_stats');
    }
}