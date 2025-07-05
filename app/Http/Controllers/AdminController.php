<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Manually enroll a user in a course from the admin panel
     */
    public function manualEnrollment(Request $request, User $user, Course $course)
    {
        try {
            // Check if the user already has access
            if ($user->hasAccessToCourse($course->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has access to this course'
                ]);
            }

            // Enroll the user
            $user->courses()->syncWithoutDetaching([$course->id]);

            // Log the enrollment
            Log::info('Manual enrollment by admin', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'course_id' => $course->id,
                'course_name' => $course->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User has been enrolled successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Manual enrollment failed', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Enrollment failed: ' . $e->getMessage()
            ], 500);
        }
    }
} 