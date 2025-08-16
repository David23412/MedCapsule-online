<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Support\State\UserProgressService;
use App\Services\ProfileDataService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

/**
 * 🚀 PERFORMANCE BEAST: Focused Profile Display Controller
 * Handles: Profile display and progress data
 * Lines: ~100 (well under 200 limit)
 */
class ProfileDisplayController extends Controller
{
    private ProfileDataService $profileDataService;

    public function __construct(ProfileDataService $profileDataService)
    {
        $this->middleware('auth');
        $this->profileDataService = $profileDataService;
    }

    /**
     * Get user progress data
     */
    public function getProgress()
    {
        $user = Auth::user();
        
        // SSOT: Use UserProgressService for all progress data
        $progressService = app(UserProgressService::class);
        $progress = $progressService->getUserProgress($user->id);
        
        return response()->json([
            'correct_answers_count' => $progress['total_correct_answers'],
            'total_questions_attempted' => $progress['total_questions_attempted'],
            'accuracy_rate' => $progress['accuracy_percentage']
        ]);
    }

    /**
     * Show user profile - Using original optimized logic
     */
    public function show()
    {
        $user = Auth::user();
        
        // RUTHLESS OPTIMIZATION: More responsive caching with hourly granularity
        $cacheKey = "user_profile_optimized_{$user->id}_" . now()->format('Y-m-d-H');
        
        $profileData = Cache::remember($cacheKey, 3600, function () use ($user) {
            // SSOT: Get progress data from UserProgressService
            $userProgressService = app(\App\Support\State\UserProgressService::class);
            $progress = $userProgressService->getUserProgress($user->id);
            
            $totalQuestions = $progress['total_questions_attempted'];
            $totalCorrect = $progress['total_correct_answers'];
            $accuracyRate = $progress['accuracy_percentage'];
            $studyStreak = $progress['study_streak_days'];

            // OPTIMIZED: Single query for study time calculation
            $studyTimeBreakdown = $this->profileDataService->calculateStudyTimeBreakdown($user);

            // Format study times without redundant labels
            $formattedStudyTimes = [
                'total' => $this->profileDataService->formatStudyTime($studyTimeBreakdown['total']),
                'quiz' => $this->profileDataService->formatStudyTime($studyTimeBreakdown['quiz']),
                'tutor' => $this->profileDataService->formatStudyTime($studyTimeBreakdown['tutor']),
                'random_quiz' => $this->profileDataService->formatStudyTime($studyTimeBreakdown['random_quiz']),
                'mistake_review' => $this->profileDataService->formatStudyTime($studyTimeBreakdown['mistake_review'])
            ];

            return compact('accuracyRate', 'totalQuestions', 'studyStreak', 'formattedStudyTimes');
        });

        // Extract cached data
        extract($profileData);

        // SSOT: Use UserProgressService for XP and ranking
        $progressService = app(UserProgressService::class);
        $userProgress = $progressService->getUserProgress($user->id);
        
        // Get user rank
        $userRank = $this->profileDataService->getUserRank($user, $userProgress['xp']);

        // Get performance data for charts
        $performanceData = $this->profileDataService->getOptimizedPerformanceData($user);
        
        // Format data for the chart
        $chartData = $performanceData->map(function($attempt) {
            return [
                'x' => \Carbon\Carbon::parse($attempt->date)->timestamp * 1000,
                'y' => round($attempt->accuracy, 1),
                'topic' => $attempt->topic ?? 'Unknown Topic',
                'date' => \Carbon\Carbon::parse($attempt->date)->format('M j, Y g:i A')
            ];
        });

        // Get weak topics data
        $weakTopics = $this->profileDataService->getWeakTopicsData($user);
        
        // Get leaderboard data
        $leaderboard = $this->profileDataService->getLeaderboardData();

        return view('profile', compact(
            'accuracyRate',
            'totalQuestions',
            'weakTopics',
            'leaderboard',
            'studyStreak',
            'chartData',
            'userRank',
            'formattedStudyTimes',
            'userProgress'
        ));
    }

}