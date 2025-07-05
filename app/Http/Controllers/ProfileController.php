<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Mistake;
use Carbon\Carbon;
use App\Models\Topic;
use App\Models\User;
use App\Models\QuizAttempt;

class ProfileController extends Controller
{

    public function getProgress()
    {
        $user = Auth::user();
        return response()->json([
            'correct_answers_count' => $user->correct_answers_count ?? 0
        ]);
    }

    public function show()
    {
        $user = Auth::user();
        
        // Calculate accuracy from user's total attempts and correct answers
        $totalQuestions = $user->total_questions_attempted ?? 0;
        $totalCorrect = $user->correct_answers_count ?? 0;
        $accuracyRate = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 1) : 0;

        // Get the study streak value directly from the user model
        $studyStreak = $user->study_streak_days ?? 0;

        // Calculate study time breakdowns
        $studyTimeBreakdown = [
            'quiz' => QuizAttempt::where('user_id', $user->id)
                ->where(function($query) {
                    $query->where('quiz_type', 'topic')
                          ->orWhere('quiz_type', 'quiz')
                          ->orWhere('quiz_type', 'tutor');
                })
                ->where('topic_id', '!=', null)  // Ensure it's a topic quiz
                ->sum('duration_seconds'),
            'random_quiz' => QuizAttempt::where('user_id', $user->id)
                ->where('quiz_type', 'random')
                ->sum('duration_seconds'),
            'mistake_review' => QuizAttempt::where('user_id', $user->id)
                ->where(function($query) {
                    $query->where('quiz_type', 'mistake_review')
                          ->orWhere('source', 'mistakes')
                          ->orWhere(function($q) {
                              $q->where('quiz_type', 'review')
                                ->where('study_mode', 'review');
                          });
                })
                ->sum('duration_seconds')
        ];

        // Calculate total from all quiz types
        $studyTimeBreakdown['total'] = array_sum($studyTimeBreakdown);

        // Format study times without redundant labels
        $formattedStudyTimes = [
            'total' => $this->formatStudyTime($studyTimeBreakdown['total']),
            'quiz' => $this->formatStudyTime($studyTimeBreakdown['quiz']),
            'random_quiz' => $this->formatStudyTime($studyTimeBreakdown['random_quiz']),
            'mistake_review' => $this->formatStudyTime($studyTimeBreakdown['mistake_review'])
        ];

        // Get user's rank
        $userRank = null;
        if ($user->xp > 0) {
            $userRank = DB::table('users')
                ->whereRaw('users.total_questions_attempted > 0')
                ->where('xp', '>', $user->xp)
                ->count() + 1;
        }

        // Get performance data for the chart (from quiz_attempts)
        $performanceData = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->where('quiz_type', '!=', 'mistake_review')  // Exclude mistake review sessions
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->select(
                'created_at as date',
                DB::raw('COALESCE(percentage_grade, 0) as accuracy'),
                DB::raw('CASE 
                    WHEN quiz_type = "random" THEN "Random Quiz"
                    WHEN topic_id IS NULL THEN "Unknown Topic"
                    ELSE (SELECT name FROM topics WHERE id = quiz_attempts.topic_id)
                END as topic')
            )
            ->get()
            ->sortBy('date')
            ->values();

        // Format data for the chart
        $chartData = $performanceData->map(function($attempt) {
            return [
                'x' => Carbon::parse($attempt->date)->timestamp * 1000,
                'y' => round($attempt->accuracy, 1),
                'topic' => $attempt->topic ?? 'Unknown Topic',
                'date' => Carbon::parse($attempt->date)->format('M j, Y g:i A')
            ];
        });

        // Get weak topics (using Eloquent)
        $weakTopics = Topic::with(['course', 'userProgress' => function($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
        ->whereHas('userProgress', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->whereNotNull('percentage_grade')
                  ->where('percentage_grade', '<', 100);
        })
        ->orderByRaw('
            CASE 
                WHEN (SELECT percentage_grade FROM user_topic_progress 
                      WHERE user_id = ? AND topic_id = topics.id) <= 25 THEN 1
                WHEN (SELECT percentage_grade FROM user_topic_progress 
                      WHERE user_id = ? AND topic_id = topics.id) <= 49 THEN 2
                WHEN (SELECT percentage_grade FROM user_topic_progress 
                      WHERE user_id = ? AND topic_id = topics.id) <= 74 THEN 3
                ELSE 4
            END,
            (SELECT percentage_grade FROM user_topic_progress 
             WHERE user_id = ? AND topic_id = topics.id) ASC
        ', [$user->id, $user->id, $user->id, $user->id])
        ->get();
        
        // Calculate XP and get leaderboard
        // Get leaderboard using stored XP values
        $leaderboard = DB::table('users')
            ->select(
                'users.id',
                'users.name',
                'users.total_questions_attempted',
                'users.correct_answers_count',
                'users.xp'
            )
            ->whereRaw('users.total_questions_attempted > 0')
            ->orderBy('xp', 'desc')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return (object) [
                    'id' => $user->id,
                    'name' => $user->name,
                    'xp' => $user->xp ?? 0,
                    'level' => floor(($user->xp ?? 0) / 1000) + 1, // Every 1000 XP is a new level
                    'correct_answers' => $user->correct_answers_count
                ];
            });

        return view('profile', compact(
            'accuracyRate',
            'totalQuestions',
            'weakTopics',
            'leaderboard',
            'studyStreak',
            'chartData',
            'userRank',
            'formattedStudyTimes'
        ));
    }

    /**
     * Format study time from seconds to a human-readable string
     */
    private function formatStudyTime($seconds)
    {
        if (!$seconds) {
            return "0m";
        }
        
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0) {
            return sprintf("%dh %dm", $hours, $remainingMinutes);
        }
        
        return sprintf("%dm", $minutes);
    }

    /**
     * Handle profile picture upload
     */
    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $user = auth()->user();
            
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                
                \Log::info('Starting profile picture upload', [
                    'user_id' => $user->id,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize()
                ]);

                // Ensure file is valid
                if (!$file->isValid()) {
                    throw new \Exception('Invalid file upload');
                }

                // Create a unique filename
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $file->getClientOriginalName());
                
                \Log::info('Generated filename', ['filename' => $filename]);

                // Store the file in the public disk
                $path = $file->storeAs('profile_pictures', $filename, 'public');
                
                \Log::info('File stored', ['path' => $path]);

                if (!$path) {
                    throw new \Exception('Failed to store file');
                }

                // Verify file exists in storage
                $fullStoragePath = storage_path('app/public/' . $path);
                if (!file_exists($fullStoragePath)) {
                    throw new \Exception('File not found after upload: ' . $fullStoragePath);
                }

                // Update path to include storage prefix
                $fullPath = 'storage/' . $path;

                \Log::info('Updating user profile picture URL', [
                    'user_id' => $user->id,
                    'old_url' => $user->profile_picture_url,
                    'new_url' => $fullPath
                ]);

                // Update user's profile_picture_url with the full path
                $user->profile_picture_url = $fullPath;
                $user->save();

                \Log::info('Profile picture updated successfully', [
                    'user_id' => $user->id,
                    'path' => $fullPath
                ]);

                return redirect()->back()->with('success', 'Profile picture updated successfully!');
            }

            return redirect()->back()->with('error', 'No file uploaded.');
        } catch (\Exception $e) {
            \Log::error('Profile picture upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_has_file' => $request->hasFile('profile_picture'),
                'file_data' => $request->hasFile('profile_picture') ? [
                    'name' => $request->file('profile_picture')->getClientOriginalName(),
                    'size' => $request->file('profile_picture')->getSize(),
                    'mime' => $request->file('profile_picture')->getMimeType()
                ] : null
            ]);

            return redirect()->back()->with('error', 'Failed to update profile picture: ' . $e->getMessage());
        }
    }
}
