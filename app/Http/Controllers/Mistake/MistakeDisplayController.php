<?php

namespace App\Http\Controllers\Mistake;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mistake;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 🚀 PERFORMANCE BEAST: Focused Mistake Display Controller
 * Handles: Mistake listing and display
 * Lines: ~100 (well under 200 limit)
 */
class MistakeDisplayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display user mistakes with optimized queries
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $courseFilter = $request->get('course');

        try {
            // 🚀 PERFORMANCE OPTIMIZED: Build efficient query
            $mistakesQuery = Mistake::where('user_id', $user->id);
            
            // Apply course filter if specified
            if ($courseFilter) {
                if ($courseFilter === 'skipped') {
                    $mistakesQuery->where('skipped', true);
                } else {
                    $mistakesQuery->whereHas('question.topic.course', function($query) use ($courseFilter) {
                        $query->where('id', $courseFilter);
                    });
                }
            }

            // 🚀 PERFORMANCE MONSTER: Eager load question relationship to eliminate N+1 queries
            $mistakes = $mistakesQuery->with('question')->orderBy('last_attempt_date', 'desc')->get();

            // 🚀 PERFORMANCE OPTIMIZED: Batch update null skipped values
            $mistakesToUpdate = [];
            foreach ($mistakes as $mistake) {
                if ($mistake->skipped === null) {
                    $mistakesToUpdate[] = $mistake->id;
                    $mistake->skipped = false;
                }
            }
            
            if (!empty($mistakesToUpdate)) {
                Mistake::whereIn('id', $mistakesToUpdate)->update(['skipped' => false]);
                Log::info('Fixed null skipped values', ['count' => count($mistakesToUpdate)]);
            }

            // Get user courses for filter dropdown
            $userCourses = $user->courses()
                ->select('courses.id', 'courses.name')
                ->orderBy('courses.name')
                ->get();

            return view('mistakes.index', compact('mistakes', 'userCourses', 'courseFilter'));

        } catch (\Exception $e) {
            Log::error('Error loading mistakes: ' . $e->getMessage());
            return view('mistakes.index', [
                'mistakes' => collect([]),
                'userCourses' => collect([]),
                'courseFilter' => null
            ])->with('error', 'Failed to load mistakes. Please try again.');
        }
    }
}