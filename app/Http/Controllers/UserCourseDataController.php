<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 PERFORMANCE BEAST: Focused User Course Data Controller
 * Handles: User course data retrieval
 * Lines: ~50 (well under 200 limit)
 */
class UserCourseDataController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get user courses
     */
    public function userCourses($userId)
    {
        try {
            $user = User::with(['courses' => function($query) {
                $query->select('courses.id', 'courses.name', 'courses.price')
                      ->withPivot('enrolled_at', 'is_enrolled');
            }])->findOrFail($userId);

            $courses = $user->courses->map(function($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'price' => $course->price,
                    'enrolled_at' => $course->pivot->enrolled_at,
                    'is_enrolled' => $course->pivot->is_enrolled
                ];
            });

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'courses' => $courses
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user courses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user courses.'
            ], 500);
        }
    }
}