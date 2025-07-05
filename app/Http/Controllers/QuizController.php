<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Mistake;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;
use App\Models\StudySession;
use App\Models\QuizAttempt;
use App\Models\UserTopicProgress;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\QuestionAttempt;
use Carbon\Carbon;
use App\Services\CacheService;
use App\Services\UserStatsService;

class QuizController extends Controller 
{
    protected $cacheService;
    protected $userStatsService;

    public function __construct(CacheService $cacheService, UserStatsService $userStatsService)
    {
        $this->middleware('auth');
        $this->cacheService = $cacheService;
        $this->userStatsService = $userStatsService;
    }

    /**
     * ✅ Show the quiz for a topic.
     */
    public function startQuiz(Course $course, Topic $topic)
    {
        try {
            $user = auth()->user();
            
            Log::info('📌 Quiz Start Attempt', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'is_admin' => $user->is_admin,
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'request_url' => request()->fullUrl()
            ]);

            // Verify that the topic belongs to the course
            if ($topic->course_id !== $course->id) {
                Log::error('Topic does not belong to course', [
                    'topic_id' => $topic->id,
                    'course_id' => $course->id,
                    'topic_course_id' => $topic->course_id
                ]);
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', 'Invalid topic for this course.');
            }

            // Check if user has access to the course (admin, enrolled, or free course)
            if (!$user->is_admin && !$user->hasAccessToCourse($course->id) && $course->is_paid) {
                Log::error('Access denied: User does not have access to course', [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'is_admin' => $user->is_admin,
                    'course_is_paid' => $course->is_paid
                ]);
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', 'You must be granted access to this course to take quizzes. Please contact an administrator.');
            }

            // Get the selected sources from the request
            $sources = request()->input('sources', ['all']);
            
            // Ensure sources is an array
            if (!is_array($sources)) {
                $sources = [$sources];
            }
            
            Log::info('Selected sources for quiz', [
                'sources' => $sources,
                'topic_id' => $topic->id
            ]);

            // Get questions for the topic with source filtering
            $questionsQuery = Question::where('topic_id', $topic->id);
            
            // Apply source filtering if specific sources are selected
            if (!in_array('all', $sources)) {
                $questionsQuery->whereIn('source', $sources);
            }
            
            $questions = $questionsQuery->get();
            
            Log::info('Questions retrieved', [
                'topic_id' => $topic->id,
                'questions_count' => $questions->count(),
                'sources' => $sources
            ]);

            if ($questions->isEmpty()) {
                Log::warning('⚠️ No questions available for topic with selected sources:', [
                    'topic_id' => $topic->id,
                    'sources' => $sources
                ]);
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', 'No questions available for this quiz with the selected sources.');
            }

            Log::info('✅ Starting quiz', [
                'topic_id' => $topic->id,
                'questions_count' => $questions->count(),
                'course_id' => $course->id,
                'sources' => $sources
            ]);

            // Store the selected sources in the session for reference during grading
            session(['selected_sources' => $sources]);

            return view('solve_quiz', [
                'topic' => $topic,
                'questions' => $questions,
                'course' => $course,
                'selected_sources' => $sources,
                'mode' => 'quiz'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error in startQuiz:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'topic_id' => $topic->id ?? null,
                'course_id' => $course->id ?? null,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('topics.forCourse', ['course' => $course->id])
                ->with('error', 'An error occurred while loading the quiz. Please try again.');
        }
    }

    /**
     * ✅ Grade quiz and track mistakes
     */
    public function gradeQuiz(Request $request, Course $course, Topic $topic)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $answers = $request->input('answers', []);
            $duration = $request->input('time_taken', 0);
            $mode = $request->input('mode', 'quiz');

            // Log the received mode for debugging
            Log::info('Quiz mode received:', [
                'mode' => $mode,
                'user_id' => $user->id,
                'topic_id' => $topic->id
            ]);

            // Validate inputs
            if (empty($answers)) {
                throw new \InvalidArgumentException('No answers provided');
            }
            if (!is_numeric($duration) || $duration < 0) {
                throw new \InvalidArgumentException('Invalid duration');
            }
            
            // Default to 'quiz' mode if not valid
            if (!in_array($mode, ['quiz', 'tutor'])) {
                Log::warning('Invalid mode received, defaulting to quiz mode', [
                    'received_mode' => $mode,
                    'user_id' => $user->id,
                    'topic_id' => $topic->id
                ]);
                $mode = 'quiz';
            }

            // Get all questions that were answered
            $questions = Question::whereIn('id', array_keys($answers))->get();
            
            // Verify all questions belong to this topic
            if ($questions->where('topic_id', '!=', $topic->id)->count() > 0) {
                throw new \InvalidArgumentException('Invalid questions for this topic');
            }

            // Initialize counters with validation
            $score = 0;
            $totalQuestions = $questions->count();
            if ($totalQuestions === 0) {
                throw new \InvalidArgumentException('No valid questions found');
            }
            
            $sourceScores = [];
            $sourceTotals = [];
            $questionData = [];
            
            // Process each answer with validation
            foreach ($questions as $question) {
                $submittedAnswer = $answers[$question->id] ?? null;
                if ($submittedAnswer === null) {
                    throw new \InvalidArgumentException("Missing answer for question {$question->id}");
                }

                $isCorrect = $question->isAnswerCorrect($submittedAnswer);
                $source = $question->source ?: 'General';
                
                // Initialize source counters if not exists
                if (!isset($sourceScores[$source])) {
                    $sourceScores[$source] = 0;
                    $sourceTotals[$source] = 0;
                }
                $sourceTotals[$source]++;
                
                // Track question data
                $questionData[] = [
                    'question_id' => $question->id,
                    'user_answer' => $submittedAnswer,
                    'is_correct' => $isCorrect
                ];
                
                if ($isCorrect) {
                    $score++;
                    $sourceScores[$source]++;
                } else {
                    // Create or update mistake record
                    Mistake::updateOrCreate(
                        ['user_id' => $user->id, 'question_id' => $question->id],
                        [
                            'submitted_answer' => $submittedAnswer,
                            'question_text' => $question->question,
                            'correct_answer' => $question->correct_answer,
                            'image_path' => $question->image_url,
                            'last_attempt_date' => now()
                        ]
                    );
                }
            }

            // Calculate percentage grade with validation
            $percentageGrade = $totalQuestions > 0 ? min(100, ($score / $totalQuestions) * 100) : 0;

            // Get total questions in topic for overall progress
            $totalTopicQuestions = Question::where('topic_id', $topic->id)->count();
            
            // Calculate overall progress based on total questions in topic
            $overallProgress = $totalTopicQuestions > 0 ? min(100, ($score / $totalTopicQuestions) * 100) : 0;

            // Update UserTopicProgress
            UserTopicProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'topic_id' => $topic->id
                ],
                [
                    'percentage_grade' => $overallProgress,
                    'last_attempt_date' => now()
                ]
            );

            // Group questions and results by source
            $sourceData = [];
            foreach ($questions as $question) {
                $source = $question->source ?: 'General';
                if (!isset($sourceData[$source])) {
                    $sourceData[$source] = [
                        'score' => 0,
                        'total' => 0,
                        'question_data' => []
                    ];
                }
                
                $isCorrect = $question->isAnswerCorrect($answers[$question->id] ?? null);
                $sourceData[$source]['total']++;
                if ($isCorrect) {
                    $sourceData[$source]['score']++;
                }
                
                $sourceData[$source]['question_data'][] = [
                    'question_id' => $question->id,
                    'user_answer' => $answers[$question->id] ?? null,
                    'is_correct' => $isCorrect
                ];
            }

            // Create a quiz attempt for each source while maintaining the same session time
            $totalQuestionsAcrossSources = array_sum(array_column($sourceData, 'total'));
            $totalCorrectAcrossSources = array_sum(array_column($sourceData, 'score'));
            foreach ($sourceData as $source => $data) {
                $sourcePercentage = $data['total'] > 0 ? min(100, ($data['score'] / $data['total']) * 100) : 0;
                
                // Calculate proportional duration based on number of questions from this source
                $sourceDuration = $totalQuestionsAcrossSources > 0 
                    ? round(($data['total'] / $totalQuestionsAcrossSources) * $duration) 
                    : 0;
                
                QuizAttempt::create([
                    'user_id' => $user->id,
                    'topic_id' => $topic->id,
                    'quiz_type' => 'normal',
                    'study_mode' => $mode,
                    'source' => $source,
                    'score' => $data['score'],
                    'total_questions' => $data['total'],
                    'percentage_grade' => $sourcePercentage,
                    'duration_seconds' => $sourceDuration, // Record proportional duration for each source
                    'question_data' => $data['question_data']
                ]);
            }
            // Update user stats after all attempts
            $this->userStatsService->updateAfterQuizAttempt($user, $totalQuestionsAcrossSources, $totalCorrectAcrossSources, $duration);

            // Calculate XP and check for level up
            $this->calculateAndAwardXP($user, $score, $duration, $percentageGrade);

            // Clear relevant caches
            $this->cacheService->clearUserTopicProgressCache($user->id, $topic->id);
            $this->cacheService->clearUserMistakesCache($user->id, $topic->id);

            DB::commit();

            // Get updated progress for all sources
            $sourceProgress = [];
            foreach ($sourceData as $source => $data) {
                $totalSourceQuestions = Question::where('topic_id', $topic->id)
                    ->where('source', $source)
                    ->count();
                
                $sourceProgress[$source] = [
                    'score' => $data['score'],
                    'total' => $data['total'],
                    'total_questions_available' => $totalSourceQuestions,
                    'percentage' => $data['total'] > 0 ? min(100, ($data['score'] / $data['total']) * 100) : 0
                ];
            }

            return response()->json([
                'success' => true,
                'score' => $score,
                'total' => $totalQuestions,
                'percentage' => $percentageGrade,
                'encouragement' => $this->getEncouragementMessage($percentageGrade),
                'source_progress' => $sourceProgress,
                'correct_answers' => $questions->mapWithKeys(function ($question) use ($answers) {
                    return [$question->id => $question->correct_answer];
                })
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error in gradeQuiz:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'topic_id' => $topic->id ?? null,
                'course_id' => $course->id ?? null,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'An error occurred while grading the quiz.'], 500);
        }
    }

    /**
     * ✅ Show mistakes (incorrect answers).
     */
    public function showMistakes(Request $request)
    {
        try {
            $userId = auth()->id();
            $courseFilter = $request->input('course');
            
            Log::info('Loading mistakes for user', ['user_id' => $userId, 'course_filter' => $courseFilter]);

            // Get all courses for the filter dropdown
            $courses = DB::table('courses')
                ->join('topics', 'courses.id', '=', 'topics.course_id')
                ->join('questions', 'topics.id', '=', 'questions.topic_id')
                ->join('mistakes', 'questions.id', '=', 'mistakes.question_id')
                ->where('mistakes.user_id', $userId)
                ->select('courses.id', 'courses.name')
                ->distinct()
                ->get();

            // Build the query for mistakes - only show actual mistakes (records that exist in the mistakes table)
            $mistakesQuery = Mistake::where('user_id', $userId)
                ->with(['question' => function($query) {
                    $query->select('id', 'question', 'topic_id', 'image_url', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'explanation');
                    $query->with(['topic' => function($query) {
                        $query->select('id', 'name', 'course_id');
                        $query->with(['course' => function($query) {
                            $query->select('id', 'name');
                        }]);
                    }]);
                }]);
            
            // Apply course filter if specified
            if ($courseFilter) {
                $mistakesQuery->whereHas('question.topic.course', function($query) use ($courseFilter) {
                    $query->where('id', $courseFilter);
                });
            }

            $mistakes = $mistakesQuery->orderBy('last_attempt_date', 'desc')->get();

            Log::info('Found mistakes', [
                'count' => $mistakes->count(),
                'courses_count' => $courses->count(),
                'user_id' => $userId
            ]);

            return view('mistakes', [
                'mistakes' => $mistakes,
                'courses' => $courses,
                'courseFilter' => $courseFilter,
                'hasMistakes' => $mistakes->isNotEmpty()
            ]);
        } catch (\Exception $e) {
            Log::error('Error in showMistakes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('mistakes', [
                'mistakes' => collect([]), // Empty collection instead of array
                'courses' => collect([]),
                'courseFilter' => null,
                'hasMistakes' => false
            ]);
        }
    }

    private function getEncouragementMessage($percentageGrade)
    {
        if ($percentageGrade >= 90) {
            return "Outstanding! You've done great with this source! 🌟 Remember, to fully master this topic, you'll need to practice with all available sources.";
        } elseif ($percentageGrade >= 80) {
            return "Great job! You're doing really well! 🎉 Keep practicing with different sources to increase your overall topic mastery.";
        } elseif ($percentageGrade >= 70) {
            return "Good work! Keep practicing to improve further! 💪";
        } elseif ($percentageGrade >= 60) {
            return "You're making progress! Review the mistakes and try again! 📚";
        } else {
            return "Don't give up! Review the material and keep practicing! 💪";
        }
    }

    /**
     * Check if the user has leveled up and send notification if needed
     */
    private function checkForLevelUp(User $user): void
    {
        try {
            // Get the current level after updates
            $currentLevel = $user->getCurrentLevel();
            
            // Calculate what level the user was at before the new XP was added
            // This should be based on the XP before this quiz attempt
            $previousXP = max(0, ($user->xp ?? 0) - $user->earnXP($user->correct_answers_count, $user->total_questions_attempted));
            $previousLevel = floor($previousXP / 1000) + 1;
            
            // If the level increased, create a level up notification
            if ($currentLevel > $previousLevel) {
                $xpForNextLevel = $user->getXPForNextLevel();
                
                // Create level up notification
                app(NotificationService::class)->createLevelUpNotification(
                    $user,
                    $currentLevel,
                    $xpForNextLevel
                );
                
                Log::info('User leveled up', [
                    'user_id' => $user->id,
                    'from_level' => $previousLevel,
                    'to_level' => $currentLevel,
                    'xp_for_next' => $xpForNextLevel
                ]);
            }
        } catch (\Exception $e) {
            // Never let a notification failure affect the quiz experience
            Log::error('Failed to process level up notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Start a quiz in tutor mode
     */
    public function startTutorQuiz(Course $course, Topic $topic)
    {
        try {
            $user = auth()->user();
            
            if (!$user->is_admin && !$user->hasAccessToCourse($course->id) && $course->is_paid) {
                Log::error('Access denied: User does not have access to course', [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'is_admin' => $user->is_admin,
                    'course_is_paid' => $course->is_paid
                ]);
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', 'You must be granted access to this course to take quizzes.');
            }

            // Get the selected sources from the request
            $sources = request()->input('sources', ['all']);
            
            // Ensure sources is an array
            if (!is_array($sources)) {
                $sources = [$sources];
            }
            
            Log::info('Selected sources for tutor mode', [
                'sources' => $sources,
                'topic_id' => $topic->id
            ]);

            // Get questions for the topic with source filtering
            $questionsQuery = Question::where('topic_id', $topic->id);
            
            // Apply source filtering if specific sources are selected
            if (!in_array('all', $sources)) {
                $questionsQuery->whereIn('source', $sources);
            }
            
            $questions = $questionsQuery->get();
            
            if ($questions->isEmpty()) {
                Log::warning('⚠️ No questions available for topic with selected sources:', [
                    'topic_id' => $topic->id,
                    'sources' => $sources
                ]);
                return redirect()->route('topics.forCourse', ['course' => $course->id])
                    ->with('error', 'No questions available for this quiz with the selected sources.');
            }

            Log::info('✅ Starting tutor mode quiz', [
                'topic_id' => $topic->id,
                'questions_count' => $questions->count(),
                'course_id' => $course->id,
                'sources' => $sources
            ]);

            // Store the selected sources in the session for reference during grading
            session(['selected_sources' => $sources]);

            return view('tutor_mode', [
                'topic' => $topic,
                'questions' => $questions,
                'course' => $course,
                'selected_sources' => $sources
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error in startTutorQuiz:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'topic_id' => $topic->id ?? null,
                'course_id' => $course->id ?? null,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('topics.forCourse', ['course' => $course->id])
                ->with('error', 'An error occurred while loading the quiz. Please try again.');
        }
    }

    /**
     * Check a single answer in tutor mode
     */
    public function checkTutorAnswer(Request $request, Question $question)
    {
        try {
            $user = auth()->user();
            
            // Enhanced logging for debugging HTTPS/ngrok issues
            Log::info('Checking tutor answer - Environment Details', [
                'question_id' => $question->id,
                'user_id' => $user->id,
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_host' => $request->getHost(),
                'request_scheme' => $request->getScheme(),
                'is_secure' => $request->secure(),
                'headers' => [
                    'x-forwarded-proto' => $request->header('X-Forwarded-Proto'),
                    'x-forwarded-host' => $request->header('X-Forwarded-Host'),
                    'user-agent' => $request->header('User-Agent'),
                    'origin' => $request->header('Origin'),
                    'referer' => $request->header('Referer'),
                ],
                'request_data' => $request->all(),
                'session_id' => session()->getId(),
                'csrf_token' => csrf_token()
            ]);

            // CSRF protection is handled by Laravel's VerifyCsrfToken middleware
            // No need for explicit validation here

            $answer = $request->input('answer');
            if (empty($answer)) {
                Log::error('Empty answer provided', [
                    'question_id' => $question->id,
                    'user_id' => $user->id,
                    'request_data' => $request->all()
                ]);
                throw new \Exception('Answer is required');
            }

            // Check if the answer is correct using the Question model's method
            $isCorrect = $question->isAnswerCorrect($answer);
            
            Log::info('Answer evaluation result', [
                'question_id' => $question->id,
                'user_id' => $user->id,
                'answer' => $answer,
                'is_correct' => $isCorrect,
                'correct_answer' => $question->correct_answer
            ]);
            
            // Track mistake if answer is incorrect
            if (!$isCorrect) {
                try {
                    Mistake::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'question_id' => $question->id
                        ],
                        [
                            'submitted_answer' => $answer,
                            'question_text' => $question->question,
                            'correct_answer' => $question->correct_answer,
                            'image_path' => $question->image_url,
                            'correct_streak' => 0,
                            'last_attempt_date' => now()
                        ]
                    );
                    
                    Log::info('Mistake recorded successfully', [
                        'question_id' => $question->id,
                        'user_id' => $user->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to record mistake', [
                        'question_id' => $question->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue execution even if mistake recording fails
                }
            }
            
            // Get the topic
            $topic = $question->topic;
            if (!$topic) {
                Log::error('Question has no associated topic', [
                    'question_id' => $question->id,
                    'user_id' => $user->id
                ]);
                throw new \Exception('Question has no associated topic');
            }

            // Get or create session data for tracking progress by source
            $tutorProgress = session()->get('tutor_progress', [
                'sources' => []
            ]);

            // Initialize source progress if not exists
            $source = $question->source ?: 'General';
            if (!isset($tutorProgress['sources'][$source])) {
                $tutorProgress['sources'][$source] = [
                    'correct' => 0,
                    'total' => 0,
                    'completed_questions' => [],
                    'start_time' => time() // Track start time per source
                ];
            }

            // Only update progress if this question hasn't been completed yet
            if (!in_array($question->id, $tutorProgress['sources'][$source]['completed_questions'])) {
                $tutorProgress['sources'][$source]['total']++;
                if ($isCorrect) {
                    $tutorProgress['sources'][$source]['correct']++;
                }
                $tutorProgress['sources'][$source]['completed_questions'][] = $question->id;
            }

            // Store updated progress in session
            session(['tutor_progress' => $tutorProgress]);

            // Debug logging
            Log::info('Tutor progress update', [
                'question_id' => $question->id,
                'source' => $source,
                'completed_questions' => $tutorProgress['sources'][$source]['completed_questions'],
                'total' => $tutorProgress['sources'][$source]['total'],
                'correct' => $tutorProgress['sources'][$source]['correct']
            ]);

            // Calculate progress percentage for the source
            $totalQuestionsInSource = Question::where('topic_id', $topic->id)
                ->where('source', $source)
                ->count();

            $completedQuestionsInSource = count(array_unique($tutorProgress['sources'][$source]['completed_questions']));
            $progressPercentage = $totalQuestionsInSource > 0 ? ($completedQuestionsInSource / $totalQuestionsInSource) * 100 : 0;

            // Debug logging for progress calculation
            Log::info('Progress calculation', [
                'question_id' => $question->id,
                'source' => $source,
                'completed_questions' => $completedQuestionsInSource,
                'total_questions_in_source' => $totalQuestionsInSource,
                'progress_percentage' => $progressPercentage,
                'session_data' => $tutorProgress['sources'][$source]
            ]);

            // If this was the last question in the source, create the final attempt record
            $remainingQuestions = Question::where('topic_id', $topic->id)
                ->where('source', $source)
                ->whereNotIn('id', $tutorProgress['sources'][$source]['completed_questions'])
                ->count();

            if ($remainingQuestions === 0) {
                try {
                    // Calculate source-specific duration
                    $sourceDuration = time() - $tutorProgress['sources'][$source]['start_time'];
                    
                    // Create question data array with proper structure
                    $questionData = [];
                    foreach (array_unique($tutorProgress['sources'][$source]['completed_questions']) as $questionId) {
                        $questionData[] = [
                            'question_id' => $questionId,
                            'user_answer' => null, // We don't track individual answers in tutor mode
                            'is_correct' => true // Assume correct for completed questions
                        ];
                    }
                    
                    // Create the final quiz attempt for this source
                    $attempt = QuizAttempt::create([
                        'user_id' => $user->id,
                        'topic_id' => $topic->id,
                        'score' => $tutorProgress['sources'][$source]['correct'],
                        'total_questions' => $tutorProgress['sources'][$source]['total'],
                        'percentage_grade' => ($tutorProgress['sources'][$source]['correct'] / $tutorProgress['sources'][$source]['total']) * 100,
                        'duration_seconds' => $sourceDuration,
                        'quiz_type' => 'tutor',
                        'study_mode' => 'tutor',
                        'source' => $source,
                        'question_data' => $questionData
                    ]);
                    // Update user stats after tutor quiz attempt
                    $this->userStatsService->updateAfterQuizAttempt(
                        $user,
                        $tutorProgress['sources'][$source]['total'],
                        $tutorProgress['sources'][$source]['correct'],
                        $sourceDuration
                    );

                    Log::info('Quiz attempt created successfully', [
                        'attempt_id' => $attempt->id,
                        'user_id' => $user->id,
                        'topic_id' => $topic->id,
                        'source' => $source
                    ]);

                    // Remove this source's progress from session
                    unset($tutorProgress['sources'][$source]);
                    if (empty($tutorProgress['sources'])) {
                        session()->forget('tutor_progress');
                    } else {
                        session(['tutor_progress' => $tutorProgress]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to create quiz attempt', [
                        'question_id' => $question->id,
                        'user_id' => $user->id,
                        'topic_id' => $topic->id,
                        'source' => $source,
                        'error' => $e->getMessage()
                    ]);
                    // Continue execution even if quiz attempt creation fails
                }
            }

            $response = [
                'success' => true,
                'is_correct' => $isCorrect,
                'correct_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
                'source_progress' => [
                    'completed' => $completedQuestionsInSource,
                    'total' => $totalQuestionsInSource,
                    'percentage' => round($progressPercentage, 2)
                ]
            ];

            Log::info('Tutor answer check completed successfully', [
                'question_id' => $question->id,
                'user_id' => $user->id,
                'response' => $response
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error in checkTutorAnswer:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'question_id' => $question->id ?? null,
                'user_id' => auth()->id(),
                'request_url' => request()->fullUrl(),
                'request_method' => request()->method(),
                'request_data' => request()->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while checking your answer. Please try again.',
                'debug_info' => app()->environment('local') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    private function calculateAndAwardXP(User $user, int $score, int $duration, float $percentageGrade): void
    {
        // Calculate XP based on score and time
        $baseXP = $score * 10; // 10 XP per correct answer
        $timeBonus = max(0, 50 - floor($duration / 60)); // Up to 50 bonus XP for fast completion
        $accuracyBonus = floor($percentageGrade / 10) * 5; // Up to 50 bonus XP for high accuracy
        $earnedXP = $baseXP + $timeBonus + $accuracyBonus;

        Log::info('XP Calculation:', [
            'score' => $score,
            'baseXP' => $baseXP,
            'duration' => $duration,
            'timeBonus' => $timeBonus,
            'percentageGrade' => $percentageGrade,
            'accuracyBonus' => $accuracyBonus,
            'totalXP' => $earnedXP
        ]);

        // Update user's XP
        $user->increment('xp', $earnedXP);

        // Check for level up
        $this->checkForLevelUp($user);
    }
}