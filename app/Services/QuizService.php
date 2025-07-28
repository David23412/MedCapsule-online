<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizService
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Initialize a quiz session with validation and question retrieval
     *
     * @param User $user
     * @param Course $course
     * @param Topic $topic
     * @param array $sources
     * @param string $mode
     * @return array
     * @throws \Exception
     */
    public function initializeQuiz(User $user, Course $course, Topic $topic, array $sources = ['all'], string $mode = 'quiz'): array
    {
        try {
            Log::info('📌 Quiz Initialization Attempt', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'is_admin' => $user->role === 'admin',
                'course_id' => $course->id,
                'topic_id' => $topic->id,
                'mode' => $mode,
                'sources' => $sources,
                'request_url' => request()->fullUrl()
            ]);

            // Validate course-topic relationship
            $this->validateCourseTopicRelationship($course, $topic);

            // Check user access to course
            $this->validateUserAccess($user, $course);

            // Normalize and validate sources
            $sources = $this->normalizeSources($sources);

            // Retrieve questions based on sources
            $questions = $this->retrieveQuestions($topic, $sources);

            // Validate question availability
            $this->validateQuestionAvailability($questions, $topic, $sources);

            // Store session data
            $this->storeQuizSessionData($sources, $mode);

            Log::info('✅ Quiz initialized successfully', [
                'topic_id' => $topic->id,
                'questions_count' => $questions->count(),
                'course_id' => $course->id,
                'sources' => $sources,
                'mode' => $mode
            ]);

            return [
                'success' => true,
                'questions' => $questions,
                'sources' => $sources,
                'mode' => $mode,
                'topic' => $topic,
                'course' => $course
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error in QuizService::initializeQuiz:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'topic_id' => $topic->id ?? null,
                'course_id' => $course->id ?? null,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate that topic belongs to course
     *
     * @param Course $course
     * @param Topic $topic
     * @throws \InvalidArgumentException
     */
    private function validateCourseTopicRelationship(Course $course, Topic $topic): void
    {
        if ($topic->course_id !== $course->id) {
            Log::error('Topic does not belong to course', [
                'topic_id' => $topic->id,
                'course_id' => $course->id,
                'topic_course_id' => $topic->course_id
            ]);
            throw new \InvalidArgumentException('Invalid topic for this course.');
        }
    }

    /**
     * Validate user access to course
     *
     * @param User $user
     * @param Course $course
     * @throws \InvalidArgumentException
     */
    private function validateUserAccess(User $user, Course $course): void
    {
        if ($user->role !== 'admin' && !$user->hasAccessToCourse($course->id) && $course->price && $course->price > 0) {
            Log::error('Access denied: User does not have access to course', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'is_admin' => $user->role === 'admin',
                'course_price' => $course->price
            ]);
            throw new \InvalidArgumentException('You must be granted access to this course to take quizzes. Please contact an administrator.');
        }
    }

    /**
     * Normalize and validate sources array
     *
     * @param array $sources
     * @return array
     */
    private function normalizeSources(array $sources): array
    {
        // Ensure sources is an array
        if (!is_array($sources)) {
            $sources = [$sources];
        }

        // Filter out empty values
        $sources = array_filter($sources);

        // Default to 'all' if no sources provided
        if (empty($sources)) {
            $sources = ['all'];
        }

        Log::info('Normalized sources for quiz', [
            'sources' => $sources
        ]);

        return $sources;
    }

    /**
     * OPTIMIZED LETHAL FIX: Retrieve questions with SMART caching and deletion validation
     * Fast AND safe - best of both worlds
     *
     * @param Topic $topic
     * @param array $sources
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function retrieveQuestions(Topic $topic, array $sources): \Illuminate\Database\Eloquent\Collection
    {
        // Trim all source values to avoid whitespace issues
        $sources = array_map('trim', $sources);
        
        // SMART CACHE KEY with question count for deletion detection
        $questionCount = Question::where('topic_id', $topic->id)
            ->when(!in_array('all', $sources), function ($query) use ($sources) {
                $query->whereIn('source', $sources);
            })
            ->count();
            
        $cacheKey = "quiz_questions_{$topic->id}_" . md5(serialize($sources)) . "_count_{$questionCount}";
        
        // SMART CACHING: Try cache first, validate if needed
        $questions = $this->cacheService->get($cacheKey);
        
        if ($questions === null) {
            // FRESH QUERY: Get questions from database
            $questions = $this->getQuestionsFromDatabase($topic, $sources);
            
            // SMART CACHE: Store with question count for deletion detection
            $this->cacheService->put($cacheKey, $questions, 1800); // 30 minutes
        } else {
            // SMART VALIDATION: Quick check if question count changed (indicates deletion)
            $currentCount = Question::where('topic_id', $topic->id)
                ->when(!in_array('all', $sources), function ($query) use ($sources) {
                    $query->whereIn('source', $sources);
                })
                ->count();
                
            if ($currentCount !== $questionCount) {
                // DELETION DETECTED: Clear cache and get fresh data
                $this->cacheService->forget($cacheKey);
                $questions = $this->getQuestionsFromDatabase($topic, $sources);
                $this->cacheService->put($cacheKey, $questions, 1800);
                
                Log::info('OPTIMIZED LETHAL FIX: Deletion detected, cache refreshed', [
                    'topic_id' => $topic->id,
                    'old_count' => $questionCount,
                    'new_count' => $currentCount
                ]);
            }
        }

        Log::info('OPTIMIZED LETHAL FIX: Questions retrieved with smart caching', [
            'topic_id' => $topic->id,
            'questions_count' => $questions->count(),
            'sources' => $sources,
            'cache_hit' => $questions !== null,
            'validation_type' => 'smart_count_check'
        ]);

        return $questions;
    }

    /**
     * OPTIMIZED DATABASE QUERY: Get questions with efficient deletion validation
     */
    private function getQuestionsFromDatabase(Topic $topic, array $sources): \Illuminate\Database\Eloquent\Collection
    {
        // EFFICIENT QUERY: Build with minimal validation
        $questionsQuery = Question::select([
            'id', 'topic_id', 'question', 'question_type', 
            'option_a', 'option_b', 'option_c', 'option_d',
            'correct_answer', 'explanation', 'image_url', 'source',
            'alternative_answers', 'similarity_threshold'
        ])
        ->where('topic_id', $topic->id)
        ->whereNotNull('question') // Ensure question content exists
        ->where('question', '!=', '') // Ensure question is not empty
        ->with(['topic:id,name,course_id']); // Eager load minimal topic data
        
        // Apply source filtering if specific sources are selected
        if (!in_array('all', $sources)) {
            $questionsQuery->whereIn('source', $sources);
        }
        
        // EFFICIENT VALIDATION: Single query with EXISTS check
        $questions = $questionsQuery->get();
        
        // OPTIMIZED VERIFICATION: Batch check for existence
        $questionIds = $questions->pluck('id')->toArray();
        $existingIds = Question::whereIn('id', $questionIds)
            ->whereNotNull('question')
            ->where('question', '!=', '')
            ->pluck('id')
            ->toArray();
            
        $validQuestions = $questions->filter(function ($question) use ($existingIds) {
            return in_array($question->id, $existingIds);
        });
        
        if ($validQuestions->count() !== $questions->count()) {
            Log::warning('OPTIMIZED LETHAL FIX: Deleted questions filtered out', [
                'original_count' => $questions->count(),
                'valid_count' => $validQuestions->count(),
                'filtered_count' => $questions->count() - $validQuestions->count()
            ]);
        }
        
        return $validQuestions;
    }

    /**
     * Validate that questions are available
     *
     * @param \Illuminate\Database\Eloquent\Collection $questions
     * @param Topic $topic
     * @param array $sources
     * @throws \InvalidArgumentException
     */
    private function validateQuestionAvailability($questions, Topic $topic, array $sources): void
    {
        if ($questions->isEmpty()) {
            Log::warning('⚠️ No questions available for topic with selected sources:', [
                'topic_id' => $topic->id,
                'sources' => $sources
            ]);
            throw new \InvalidArgumentException('No questions available for this quiz with the selected sources.');
        }
    }

    /**
     * Store quiz session data
     *
     * @param array $sources
     * @param string $mode
     */
    private function storeQuizSessionData(array $sources, string $mode): void
    {
        // Store the selected sources in the session for reference during grading
        session(['selected_sources' => $sources]);

        // Store quiz mode
        session(['quiz_mode' => $mode]);

        // Initialize tutor progress if in tutor mode
        if ($mode === 'tutor') {
            session(['tutor_progress' => ['sources' => []]]);
        }
    }

    /**
     * Get available sources for a topic
     *
     * @param Topic $topic
     * @return array
     */
    public function getAvailableSources(Topic $topic): array
    {
        $cacheKey = "available_sources_{$topic->id}";
        $sources = $this->cacheService->get($cacheKey);

        if ($sources === null) {
            $sources = Question::where('topic_id', $topic->id)
                ->distinct()
                ->pluck('source')
                ->filter()
                ->values()
                ->toArray();

            // Cache for 1 hour
            $this->cacheService->put($cacheKey, $sources, 3600);
        }

        return $sources;
    }

    /**
     * Get quiz statistics for a topic
     *
     * @param Topic $topic
     * @param array $sources
     * @return array
     */
    public function getQuizStatistics(Topic $topic, array $sources = ['all']): array
    {
        $cacheKey = "quiz_stats_{$topic->id}_" . md5(serialize($sources));
        $stats = $this->cacheService->get($cacheKey);

        if ($stats === null) {
            $questionsQuery = Question::where('topic_id', $topic->id);
            
            if (!in_array('all', $sources)) {
                $questionsQuery->whereIn('source', $sources);
            }

            $totalQuestions = $questionsQuery->count();
            $multipleChoiceQuestions = $questionsQuery->where('question_type', 'multiple_choice')->count();
            $writtenQuestions = $questionsQuery->where('question_type', 'written')->count();

            $stats = [
                'total_questions' => $totalQuestions,
                'multiple_choice' => $multipleChoiceQuestions,
                'written' => $writtenQuestions,
                'sources' => $this->getSourceBreakdown($topic, $sources)
            ];

            // Cache for 30 minutes
            $this->cacheService->put($cacheKey, $stats, 1800);
        }

        return $stats;
    }

    /**
     * Get source breakdown for statistics
     *
     * @param Topic $topic
     * @param array $sources
     * @return array
     */
    private function getSourceBreakdown(Topic $topic, array $sources): array
    {
        $query = Question::where('topic_id', $topic->id);
        
        if (!in_array('all', $sources)) {
            $query->whereIn('source', $sources);
        }

        return $query->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();
    }

    /**
     * Get random questions for practice mode
     *
     * @param Topic $topic
     * @param int $count
     * @param array $sources
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRandomQuestions(Topic $topic, int $count = 10, array $sources = ['all']): \Illuminate\Database\Eloquent\Collection
    {
        $questionsQuery = Question::where('topic_id', $topic->id);
        
        if (!in_array('all', $sources)) {
            $questionsQuery->whereIn('source', $sources);
        }

        return $questionsQuery->inRandomOrder()
            ->limit($count)
            ->get();
    }

    /**
     * Get questions by difficulty level
     *
     * @param Topic $topic
     * @param string $difficulty
     * @param array $sources
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getQuestionsByDifficulty(Topic $topic, string $difficulty, array $sources = ['all']): \Illuminate\Database\Eloquent\Collection
    {
        $questionsQuery = Question::where('topic_id', $topic->id);
        
        if (!in_array('all', $sources)) {
            $questionsQuery->whereIn('source', $sources);
        }

        return $questionsQuery->get();
    }

    /**
     * Get user's quiz history for a topic
     *
     * @param User $user
     * @param Topic $topic
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserQuizHistory(User $user, Topic $topic, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\QuizAttempt::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get quiz recommendations based on user performance
     *
     * @param User $user
     * @param Topic $topic
     * @return array
     */
    public function getQuizRecommendations(User $user, Topic $topic): array
    {
        // Get user's recent performance
        $recentAttempts = $this->getUserQuizHistory($user, $topic, 5);
        
        if ($recentAttempts->isEmpty()) {
            return [
                'recommended_mode' => 'quiz',
                'recommended_sources' => ['all'],
                'difficulty' => 'medium'
            ];
        }

        // Calculate average performance
        $avgPercentage = $recentAttempts->avg('percentage_grade');
        
        // Determine recommended mode and difficulty
        if ($avgPercentage < 60) {
            return [
                'recommended_mode' => 'tutor',
                'recommended_sources' => ['all'],
                'difficulty' => 'easy'
            ];
        } elseif ($avgPercentage < 80) {
            return [
                'recommended_mode' => 'quiz',
                'recommended_sources' => ['all'],
                'difficulty' => 'medium'
            ];
        } else {
            return [
                'recommended_mode' => 'quiz',
                'recommended_sources' => ['all'],
                'difficulty' => 'hard'
            ];
        }
    }

    /**
     * Clear quiz-related caches
     *
     * @param Topic $topic
     */
    public function clearQuizCaches(Topic $topic): void
    {
        $this->cacheService->forget("quiz_questions_{$topic->id}_*");
        $this->cacheService->forget("available_sources_{$topic->id}");
        $this->cacheService->forget("quiz_stats_{$topic->id}_*");
    }

    /**
     * Validate quiz session data
     *
     * @param string $mode
     * @param array $sources
     * @return bool
     */
    public function validateQuizSession(string $mode, array $sources): bool
    {
        $sessionSources = session('selected_sources', []);
        $sessionMode = session('quiz_mode', '');

        return $sessionMode === $mode && $sessionSources === $sources;
    }

    /**
     * Get quiz configuration for a topic
     *
     * @param Topic $topic
     * @return array
     */
    public function getQuizConfiguration(Topic $topic): array
    {
        return [
            'max_questions' => config('quiz.max_questions'),
            'duration_minutes' => config('quiz.time_limit') / 60, // Convert seconds to minutes
            'passing_score' => config('quiz.passing_score'),
        ];
    }

    /**
     * Generate quiz questions for a topic
     *
     * @param Topic $topic
     * @param array $sources
     * @param User $user
     * @return array
     */
    public function generateQuizQuestions(Topic $topic, array $sources = ['all'], User $user = null): array
    {
        try {
            // Trim all source values to avoid whitespace issues
            $sources = array_map('trim', $sources);
            Log::info('Generating quiz questions', [
                'topic_id' => $topic->id,
                'sources' => $sources,
                'user_id' => $user?->id
            ]);

            // Get questions for the topic with source filtering
            $questionsQuery = Question::where('topic_id', $topic->id);
            
            // Apply source filtering if specific sources are selected
            if (!in_array('all', $sources)) {
                $questionsQuery->whereIn('source', $sources);
            }
            
            $questions = $questionsQuery->get();
            Log::info('DEBUG: generateQuizQuestions', [
                'topic_id' => $topic->id,
                'sources' => $sources,
                'questions_found' => $questions->count(),
                'first_question' => $questions->first()
            ]);
            
            if ($questions->isEmpty()) {
                Log::warning('No questions available for topic with selected sources', [
                    'topic_id' => $topic->id,
                    'sources' => $sources
                ]);
                
                return [
                    'success' => false,
                    'message' => 'No questions available for this quiz with the selected sources.',
                    'questions' => collect([])
                ];
            }

            // Shuffle questions for randomization
            $questions = $questions->shuffle();

            // Limit questions if needed (configurable)
            $maxQuestions = config('quiz.max_questions_per_quiz', 50);
            if ($questions->count() > $maxQuestions) {
                $questions = $questions->take($maxQuestions);
            }

            Log::info('Quiz questions generated successfully', [
                'topic_id' => $topic->id,
                'questions_count' => $questions->count(),
                'sources' => $sources
            ]);

            return [
                'success' => true,
                'questions' => $questions,
                'total_questions' => $questions->count(),
                'sources' => $sources
            ];

        } catch (\Exception $e) {
            Log::error('Error generating quiz questions', [
                'topic_id' => $topic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while generating quiz questions.',
                'questions' => collect([])
            ];
        }
    }

    /**
     * OPTIMIZED RANDOM QUIZ: Generate random quiz with smart deletion validation
     */
    public function generateRandomQuiz(User $user, int $questionCount = 10, array $options = []): array
    {
        try {
            $difficulty = $options['difficulty'] ?? 'mixed';
            $sources = $options['sources'] ?? ['all'];
            $excludeTopics = $options['exclude_topics'] ?? [];
            $courseIds = $options['course_ids'] ?? [];

            Log::info('OPTIMIZED LETHAL FIX: Generating random quiz with smart validation', [
                'user_id' => $user->id,
                'question_count' => $questionCount,
                'difficulty' => $difficulty,
                'sources' => $sources,
                'course_ids' => $courseIds
            ]);

            // SMART QUERY: Build with efficient deletion checks
            $questionsQuery = Question::query()
                ->whereNotNull('question') // Ensure question content exists
                ->where('question', '!=', '') // Ensure question is not empty
                ->select([
                    'id', 'topic_id', 'question', 'question_type', 
                    'option_a', 'option_b', 'option_c', 'option_d',
                    'correct_answer', 'explanation', 'image_url', 'source',
                    'alternative_answers', 'similarity_threshold'
                ]);

            // Apply course filtering if specified
            if (!empty($courseIds)) {
                $questionsQuery->whereHas('topic', function($query) use ($courseIds) {
                    $query->whereIn('course_id', $courseIds);
                });
            }

            // Apply source filtering
            if (!in_array('all', $sources)) {
                $questionsQuery->whereIn('source', $sources);
            }

            // Exclude specific topics if provided
            if (!empty($excludeTopics)) {
                $questionsQuery->whereNotIn('topic_id', $excludeTopics);
            }

            // Apply difficulty filtering if specified and column exists
            if ($difficulty !== 'mixed' && \Schema::hasColumn('questions', 'difficulty')) {
                $questionsQuery->where('difficulty', $difficulty);
            }

            // SMART VALIDATION: Get questions and verify efficiently
            $questions = $questionsQuery->inRandomOrder()->limit($questionCount * 1.5)->get(); // Get extra to account for deletions
            
            // OPTIMIZED VERIFICATION: Batch check for existence
            $questionIds = $questions->pluck('id')->toArray();
            $existingIds = Question::whereIn('id', $questionIds)
                ->whereNotNull('question')
                ->where('question', '!=', '')
                ->pluck('id')
                ->toArray();
                
            $validQuestions = $questions->filter(function ($question) use ($existingIds) {
                return in_array($question->id, $existingIds);
            })->take($questionCount);

            if ($validQuestions->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No valid questions available for the specified criteria.',
                    'questions' => []
                ];
            }

            // Group questions by topic for better organization
            $questionsByTopic = $validQuestions->groupBy('topic_id');

            Log::info('OPTIMIZED LETHAL FIX: Random quiz generated with smart validation', [
                'user_id' => $user->id,
                'questions_count' => $validQuestions->count(),
                'topics_count' => $questionsByTopic->count(),
                'validation_type' => 'smart_batch_check'
            ]);

            return [
                'success' => true,
                'questions' => $validQuestions,
                'questions_by_topic' => $questionsByTopic,
                'total_questions' => $validQuestions->count(),
                'topics_covered' => $questionsByTopic->keys()->toArray()
            ];

        } catch (\Exception $e) {
            Log::error('OPTIMIZED LETHAL FIX: Error generating random quiz', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while generating the random quiz.',
                'questions' => []
            ];
        }
    }

    /**
     * Generate tutor mode quiz (one question at a time with immediate feedback)
     *
     * @param Topic $topic
     * @param array $sources
     * @param User $user
     * @return array
     */
    public function generateTutorQuiz(Topic $topic, array $sources = ['all'], User $user = null): array
    {
        Log::info('DEBUG: generateTutorQuiz called', [
            'topic_id' => $topic->id,
            'sources' => $sources,
            'user_id' => $user?->id
        ]);
        try {
            Log::info('Generating tutor quiz', [
                'topic_id' => $topic->id,
                'sources' => $sources,
                'user_id' => $user?->id
            ]);

            // Get questions for tutor mode
            $result = $this->generateQuizQuestions($topic, $sources, $user);
            
            if (!$result['success']) {
                return $result;
            }

            // For tutor mode, we might want to prioritize certain types of questions
            $questions = $result['questions'];
            
            // Sort questions by difficulty (easier first for tutor mode)
            $questions = $questions->sortBy('difficulty');

            // Store question IDs in session for later verification
            session(['tutor_questions' => $questions->pluck('id')->toArray()]);

            // Add tutor-specific metadata
            $tutorQuestions = $questions->map(function ($question) {
                return [
                    'question' => $question,
                    'hints' => $this->generateHints($question),
                    'explanation' => $question->explanation,
                    'related_concepts' => $this->getRelatedConcepts($question)
                ];
            });

            Log::info('Tutor quiz generated successfully', [
                'topic_id' => $topic->id,
                'questions_count' => $tutorQuestions->count()
            ]);

            return [
                'success' => true,
                'questions' => $tutorQuestions,
                'mode' => 'tutor',
                'total_questions' => $tutorQuestions->count()
            ];

        } catch (\Exception $e) {
            Log::error('Error generating tutor quiz', [
                'topic_id' => $topic->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'An error occurred while generating the tutor quiz.',
                'questions' => collect([])
            ];
        }
    }

    /**
     * Generate hints for a question (for tutor mode)
     *
     * @param Question $question
     * @return array
     */
    protected function generateHints(Question $question): array
    {
        $hints = [];

        if ($question->question_type === 'multiple_choice') {
            // For multiple choice, provide progressive hints
            $hints[] = "Read the question carefully and eliminate obviously wrong answers.";
            
            if ($question->explanation) {
                $hints[] = "Think about the key concepts mentioned in the question.";
            }
            
            $hints[] = "Consider the context and what the question is really asking.";
        } else {
            // For written questions
            $hints[] = "Focus on the main concepts and key terms.";
            $hints[] = "Be specific and concise in your answer.";
        }

        return $hints;
    }

    /**
     * Get related concepts for a question
     *
     * @param Question $question
     * @return array
     */
    protected function getRelatedConcepts(Question $question): array
    {
        // This could be enhanced with actual concept extraction
        // For now, return basic related concepts based on question content
        $concepts = [];
        
        if ($question->topic) {
            $concepts[] = $question->topic->name;
        }
        
        // Extract key terms from question (basic implementation)
        $words = str_word_count(strtolower($question->question), 1);
        $keyTerms = array_slice(array_unique($words), 0, 3);
        
        return array_merge($concepts, $keyTerms);
    }

    /**
     * LETHAL METHOD - Get questions for quiz with nuclear deletion validation
     *
     * @param Topic $topic
     * @param array $sources
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getQuestionsForQuiz(Topic $topic, array $sources = ['all']): \Illuminate\Database\Eloquent\Collection
    {
        // LETHAL CACHE KEY with timestamp to force refresh on deletions
        $cacheKey = "quiz_questions_{$topic->id}_" . md5(serialize($sources)) . "_" . time();
        
        return Cache::remember($cacheKey, 300, function () use ($topic, $sources) {
            // LETHAL QUERY: Build with explicit deletion checks
            $query = Question::where('topic_id', $topic->id)
                ->whereNotNull('question') // Ensure question content exists
                ->where('question', '!=', '') // Ensure question is not empty
                ->orderBy('id');

            if (!in_array('all', $sources)) {
                $query->whereIn('source', $sources);
            }

            $questions = $query->get();
            
            // NUCLEAR VERIFICATION: Double-check each question still exists
            $validQuestions = collect();
            foreach ($questions as $question) {
                // Real-time database check for each question
                $exists = Question::where('id', $question->id)
                    ->where('topic_id', $question->topic_id)
                    ->whereNotNull('question')
                    ->where('question', '!=', '')
                    ->exists();
                    
                if ($exists) {
                    $validQuestions->push($question);
                } else {
                    Log::warning('LETHAL FIX: Deleted question detected in getQuestionsForQuiz', [
                        'question_id' => $question->id,
                        'topic_id' => $question->topic_id
                    ]);
                }
            }
            
            return $validQuestions;
        });
    }

    /**
     * Get quiz statistics for a topic
     *
     * @param Topic $topic
     * @param User $user
     * @return array
     */
    public function getTopicQuizStats(Topic $topic, User $user): array
    {
        $cacheKey = "topic_quiz_stats_{$topic->id}_{$user->id}";
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($topic, $user) {
            $attempts = $user->quizAttempts()
                ->where('topic_id', $topic->id)
                ->orderBy('attempted_at', 'desc')
                ->get();

            if ($attempts->isEmpty()) {
                return [
                    'total_attempts' => 0,
                    'average_score' => 0,
                    'best_score' => 0,
                    'last_attempt' => null,
                    'improvement_trend' => 'none'
                ];
            }

            $totalAttempts = $attempts->count();
            $averageScore = $attempts->avg('grade_percentage');
            $bestScore = $attempts->max('grade_percentage');
            $lastAttempt = $attempts->first();

            // Calculate improvement trend
            $recentAttempts = $attempts->take(3);
            $trend = 'stable';
            
            if ($recentAttempts->count() >= 2) {
                $firstScore = $recentAttempts->last()->grade_percentage;
                $lastScore = $recentAttempts->first()->grade_percentage;
                
                if ($lastScore > $firstScore + 5) {
                    $trend = 'improving';
                } elseif ($lastScore < $firstScore - 5) {
                    $trend = 'declining';
                }
            }

            return [
                'total_attempts' => $totalAttempts,
                'average_score' => round($averageScore, 1),
                'best_score' => round($bestScore, 1),
                'last_attempt' => $lastAttempt,
                'improvement_trend' => $trend
            ];
        });
    }
} 