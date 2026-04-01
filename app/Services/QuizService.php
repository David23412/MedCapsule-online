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
use App\Services\UltraFastQueryCache;

class QuizService
{
    protected $cacheService;

    /** @var bool|null Cached result of Schema::hasColumn('questions', 'difficulty') */
    private static ?bool $hasDifficultyColumn = null;

    public function __construct(Core\UltraConsolidatedCacheService $cacheService)
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
            // Validate course-topic relationship
            $this->validateCourseTopicRelationship($course, $topic);

            // Check user access to course
            $this->validateUserAccess($user, $course);

            // Preview users can only open the first configured topics.
            $this->validateTopicPreviewAccess($user, $topic);

            // Normalize and validate sources
            $sources = $this->normalizeSources($sources);

            // Retrieve questions based on sources
            $questions = $this->retrieveQuestions($topic, $sources);

            // Validate question availability
            $this->validateQuestionAvailability($questions, $topic, $sources);

            // Store session data
            $this->storeQuizSessionData($sources, $mode);

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
        if (!$user->isAdmin() && !$user->hasAccessToCourse($course->id)) {
            Log::error('Access denied: User does not have access to course', [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'is_admin' => $user->isAdmin(),
                'course_price' => $course->price
            ]);

            if ($user->requiresSurveyUnlockForCourse($course)) {
                throw new \InvalidArgumentException('Free course survey unlock is required before accessing this course.');
            }

            throw new \InvalidArgumentException('You must be granted access to this course to take quizzes. Please contact an administrator.');
        }
    }

    private function validateTopicPreviewAccess(User $user, Topic $topic): void
    {
        if ($user->isAdmin() || !$user->isTopicPreviewLocked($topic)) {
            return;
        }

        Log::warning('Preview access denied: topic is outside preview window', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'course_id' => $topic->course_id,
            'display_order' => $topic->display_order,
        ]);

        throw new \InvalidArgumentException('This topic is outside the free preview. Unlock the full course to continue.');
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

        return $sources;
    }

    /**
     * Retrieve questions with SMART caching and deletion validation
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

        $cacheKey = "topic:{$topic->id}:questions:" . md5(serialize($sources));

        $questions = $this->cacheService->remember($cacheKey, function () use ($topic, $sources) {
            return $this->getQuestionsFromDatabase($topic, $sources);
        }, 1800); // 30 minutes

        return $questions;
    }

    /**
     * OPTIMIZED DATABASE QUERY: Get questions with efficient deletion validation
     */
    private function getQuestionsFromDatabase(Topic $topic, array $sources): \Illuminate\Database\Eloquent\Collection
    {
        $query = Question::select([
            'questions.id', 'questions.topic_id', 'questions.question', 'questions.question_type',
            'questions.option_a', 'questions.option_b', 'questions.option_c', 'questions.option_d',
            'questions.correct_answer', 'questions.explanation', 'questions.image_url', 'questions.source',
            'questions.alternative_answers', 'questions.exam_appearances_count', 'questions.extra_topic'
        ])
        ->whereNotNull('questions.question') // Ensure question content exists
        ->where('questions.question', '!=', '') // Ensure question is not empty
        ->with(['topic:id,name,course_id']) // Eager load minimal topic data
        ->when(!in_array('all', $sources), function ($q) use ($sources) {
            $q->whereIn('questions.source', $sources);
        });

        // Add topic filtering logic based on exam vs lecture
        $isExamTopic = $topic->is_exam;

        if ($isExamTopic) {
            // For exam topics, only pull questions whose main topic is this exam
            $query->where('questions.topic_id', $topic->id);
        } else {
            // For lecture topics, pull main topic questions + extra_topic column (comma-separated string)
            $query->where(function($q) use ($topic) {
                $q->where('questions.topic_id', $topic->id)
                  ->orWhereRaw('FIND_IN_SET(?, REPLACE(questions.extra_topic, " ", "")) > 0', [$topic->id]);
            });
        }

        return $query->get();
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
        $cacheKey = "topic:{$topic->id}:available_sources";
        
        return $this->cacheService->remember($cacheKey, function () use ($topic) {
            return Question::where(function($q) use ($topic) {
                    $q->where('questions.topic_id', $topic->id)
                      ->orWhereRaw('FIND_IN_SET(?, REPLACE(questions.extra_topic, " ", "")) > 0', [$topic->id]);
                })
                ->distinct()
                ->pluck('source')
                ->filter()
                ->values()
                ->toArray();
        }, 3600); // Cache for 1 hour
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
        $cacheKey = "topic:{$topic->id}:*";
        
        return $this->cacheService->remember($cacheKey, function () use ($topic, $sources) {
            $questionsQuery = Question::where(function($q) use ($topic) {
                $q->where('questions.topic_id', $topic->id)
                  ->orWhereRaw('FIND_IN_SET(?, REPLACE(questions.extra_topic, " ", "")) > 0', [$topic->id]);
            });
            
            if (!in_array('all', $sources)) {
                $questionsQuery->whereIn('source', $sources);
            }

            $totalQuestions = $questionsQuery->count();
            $multipleChoiceQuestions = (clone $questionsQuery)->where('question_type', 'multiple_choice')->count();
            $writtenQuestions = (clone $questionsQuery)->where('question_type', 'written')->count();

            return [
                'total_questions' => $totalQuestions,
                'multiple_choice' => $multipleChoiceQuestions,
                'written' => $writtenQuestions,
                'sources' => $this->getSourceBreakdown($topic, $sources)
            ];
        }, 1800); // Cache for 30 minutes
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
        $query = Question::where(function($q) use ($topic) {
            $q->where('questions.topic_id', $topic->id)
              ->orWhereRaw('FIND_IN_SET(?, REPLACE(questions.extra_topic, " ", "")) > 0', [$topic->id]);
        });
        
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
        $questionsQuery = Question::query()->where(function($q) use ($topic) {
            $q->where('questions.topic_id', $topic->id)
              ->orWhereRaw('FIND_IN_SET(?, REPLACE(questions.extra_topic, " ", "")) > 0', [$topic->id]);
        });

        if (!in_array('all', $sources)) {
            $questionsQuery->whereIn('source', $sources);
        }

        $selectedIds = $this->sampleQuestionIds($questionsQuery, $count);
        if ($selectedIds === []) {
            return collect();
        }

        $idOrderMap = array_flip($selectedIds);

        return Question::query()
            ->whereIn('id', $selectedIds)
            ->get()
            ->sortBy(function ($question) use ($idOrderMap) {
                return $idOrderMap[$question->id] ?? PHP_INT_MAX;
            })
            ->values();
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
        $questionsQuery = Question::where(function($q) use ($topic) {
            $q->where('questions.topic_id', $topic->id)
              ->orWhereRaw('FIND_IN_SET(?, REPLACE(questions.extra_topic, " ", "")) > 0', [$topic->id]);
        });
        
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
        $this->cacheService->forget("topic:{$topic->id}:questions:*");
        $this->cacheService->forget("topic:{$topic->id}:available_sources");
        $this->cacheService->forget("topic:{$topic->id}:quiz_stats_*");
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
    public function generateQuizQuestions(Topic $topic, array $sources = ['all'], ?User $user = null): array
    {
        try {
            // Trim all source values to avoid whitespace issues
            $sources = array_map('trim', $sources);
            
            // Get questions for the topic with source filtering
            $questionsQuery = Question::where(function($q) use ($topic) {
                $q->where('questions.topic_id', $topic->id)
                  ->orWhereRaw('FIND_IN_SET(?, REPLACE(questions.extra_topic, " ", "")) > 0', [$topic->id]);
            });
            
            // Apply source filtering if specific sources are selected
            if (!in_array('all', $sources)) {
                $questionsQuery->whereIn('source', $sources);
            }
            
            $questions = $questionsQuery->get();

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

            // SECURITY FIX: Validate user access to specified courses.
            // Random quiz must only run on fully unlocked courses to prevent
            // preview users from bypassing topic-lock progression.
            // Bulk-fetch access levels in a single query to avoid N+1.
            if (!empty($courseIds)) {
                $intCourseIds = array_map('intval', $courseIds);
                $accessLevels = $user->getCourseAccessLevels($intCourseIds);
                $inaccessibleCourses = array_filter($intCourseIds, function($courseId) use ($accessLevels) {
                    return ($accessLevels[$courseId] ?? null) !== User::COURSE_ACCESS_FULL;
                });

                if (!empty($inaccessibleCourses)) {
                    Log::warning('User attempted to access random quiz with inaccessible courses', [
                        'user_id' => $user->id,
                        'inaccessible_courses' => array_values($inaccessibleCourses),
                        'all_requested_courses' => $courseIds
                    ]);

                    return [
                        'success' => false,
                        'message' => 'You do not have access to one or more of the requested courses.',
                        'questions' => []
                    ];
                }
            }

            // If no specific courses requested, use only accessible courses
            if (empty($courseIds)) {
                $userCourses = $user->courses()->pluck('courses.id')->toArray();
                $accessLevels = $user->getCourseAccessLevels(array_map('intval', $userCourses));
                $userCourses = array_values(array_filter(
                    $userCourses,
                    fn ($courseId) => ($accessLevels[(int) $courseId] ?? null) === User::COURSE_ACCESS_FULL
                ));

                if (empty($userCourses)) {
                    Log::warning('User has no accessible courses for random quiz', [
                        'user_id' => $user->id
                    ]);

                    return [
                        'success' => false,
                        'message' => 'You are not enrolled in any courses. Please enroll in a course first.',
                        'questions' => []
                    ];
                }
                $courseIds = $userCourses;
            }

            // SMART QUERY: Build with efficient deletion checks (including linked questions and extra topics)
            $questionsQuery = Question::query()
                ->where(function($q) use ($courseIds) {
                    // Include questions from topics in the specified courses
                    $q->whereHas('topic', function($query) use ($courseIds) {
                        $query->whereIn('course_id', $courseIds);
                    })
                    // Also include questions with extra topics from those courses
                    ->orWhereExists(function ($sub) use ($courseIds) {
                        $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('topics')
                            ->whereRaw('FIND_IN_SET(topics.id, REPLACE(questions.extra_topic, " ", "")) > 0')
                            ->whereIn('topics.course_id', $courseIds);
                    });
                })
                ->whereNotNull('question') // Ensure question content exists
                ->where('question', '!=', '') // Ensure question is not empty
                ->select([
                    'id', 'topic_id', 'question', 'question_type',
                    'option_a', 'option_b', 'option_c', 'option_d',
                    'correct_answer', 'explanation', 'image_url', 'source',
                    'alternative_answers'
                ]);

            // Apply source filtering
            if (!in_array('all', $sources)) {
                $questionsQuery->whereIn('source', $sources);
            }

            // Exclude specific topics if provided
            if (!empty($excludeTopics)) {
                $questionsQuery->whereNotIn('topic_id', $excludeTopics);
            }

            // Apply difficulty filtering if specified and column exists
            if ($difficulty !== 'mixed' && $this->questionTableHasDifficultyColumn()) {
                $questionsQuery->where('difficulty', $difficulty);
            }

            $selectedIds = $this->sampleQuestionIds($questionsQuery, $questionCount);

            if ($selectedIds === []) {
                return [
                    'success' => false,
                    'message' => 'No valid questions available for the specified criteria.',
                    'questions' => []
                ];
            }

            $idOrderMap = array_flip($selectedIds);

            $validQuestions = Question::query()
                ->select([
                    'id', 'topic_id', 'question', 'question_type',
                    'option_a', 'option_b', 'option_c', 'option_d',
                    'correct_answer', 'explanation', 'image_url', 'source',
                    'alternative_answers'
                ])
                ->whereIn('id', $selectedIds)
                ->get()
                ->sortBy(function ($question) use ($idOrderMap) {
                    return $idOrderMap[$question->id] ?? PHP_INT_MAX;
                })
                ->values();

            if ($validQuestions->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No valid questions available for the specified criteria.',
                    'questions' => []
                ];
            }

            // Group questions by topic for better organization
            $questionsByTopic = $validQuestions->groupBy('topic_id');

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

    private function sampleQuestionIds($query, int $limit): array
    {
        $stats = (clone $query)
            ->selectRaw('MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as total')
            ->first();

        $total = (int) ($stats->total ?? 0);
        if ($total === 0) {
            return [];
        }

        if ($total <= $limit) {
            return (clone $query)
                ->orderBy('id')
                ->pluck('id')
                ->all();
        }

        $minId = (int) $stats->min_id;
        $maxId = (int) $stats->max_id;
        $startId = random_int($minId, $maxId);

        $selectedIds = (clone $query)
            ->where('id', '>=', $startId)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        if (count($selectedIds) < $limit) {
            $selectedIds = array_merge(
                $selectedIds,
                (clone $query)
                    ->where('id', '<', $startId)
                    ->orderBy('id')
                    ->limit($limit - count($selectedIds))
                    ->pluck('id')
                    ->all()
            );
        }

        return array_values(array_unique($selectedIds));
    }

    /**
     * Generate tutor mode quiz (one question at a time with immediate feedback)
     *
     * @param Topic $topic
     * @param array $sources
     * @param User $user
     * @return array
     */
    public function generateTutorQuiz(Topic $topic, array $sources = ['all'], ?User $user = null): array
    {
        try {
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
        $cacheKey = "topic:{$topic->id}:questions_lethal:" . md5(serialize($sources));
        
        return $this->cacheService->remember($cacheKey, 300, function () use ($topic, $sources) {
            // LETHAL QUERY: Build with explicit deletion checks
            $query = Question::where(function($q) use ($topic) {
                    $q->where('topic_id', $topic->id)
                      ->orWhereRaw('FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$topic->id]);
                })
                ->whereNotNull('question') // Ensure question content exists
                ->where('question', '!=', '') // Ensure question is not empty
                ->orderBy('id');

            if (!in_array('all', $sources)) {
                $query->whereIn('source', $sources);
            }

            $questions = $query->get();
            
            // NUCLEAR VERIFICATION: Filter out deleted questions while maintaining Eloquent Collection
            $validQuestions = $questions->filter(function ($question) use ($topic) {
                // Real-time database check for each question
                    $exists = Question::where('id', $question->id)
                        ->where(function($q) use ($topic, $question) {
                            $q->where('topic_id', $question->topic_id)
                              ->orWhereRaw('FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$topic->id]);
                        })
                    ->whereNotNull('question')
                    ->where('question', '!=', '')
                    ->exists();
                    
                if (!$exists) {
                    Log::warning('LETHAL FIX: Deleted question detected in getQuestionsForQuiz', [
                        'question_id' => $question->id,
                        'topic_id' => $question->topic_id
                    ]);
                }
                
                return $exists;
            });
            
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
        $cacheKey = "topic:{$topic->id}:user_stats:{$user->id}";
        
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

    // ===== CONSOLIDATED QUIZ FEATURES =====

    /**
     * PERFORMANCE TRACKING (from QuizPerformanceService)
     */
    private static $performanceMetrics = [];

    /**
     * Start performance tracking for an operation
     */
    public static function startTracking(string $operation): string
    {
        $trackingId = uniqid($operation . '_', true);
        
        self::$performanceMetrics[$trackingId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'queries_before' => self::getQueryCount()
        ];
        
        return $trackingId;
    }

    /**
     * End performance tracking and log results
     */
    public static function endTracking(string $trackingId, array $additionalData = []): array
    {
        if (!isset(self::$performanceMetrics[$trackingId])) {
            return [];
        }

        $metrics = self::$performanceMetrics[$trackingId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $queriesAfter = self::getQueryCount();

        $performance = [
            'operation' => $metrics['operation'],
            'duration_ms' => round(($endTime - $metrics['start_time']) * 1000, 2),
            'memory_used_mb' => round(($endMemory - $metrics['start_memory']) / 1024 / 1024, 2),
            'queries_executed' => $queriesAfter - $metrics['queries_before']
        ];

        // Clean up
        unset(self::$performanceMetrics[$trackingId]);

        return $performance;
    }

    /**
     * Get current query count
     */
    private static function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * MEMORY EFFICIENT OPERATIONS (from MemoryEfficientQuizService)
     */
    private const CHUNK_SIZE = 100;
    private const MAX_MEMORY_USAGE = 128 * 1024 * 1024; // 128MB
    
    /**
     * Get questions using lazy loading for memory efficiency
     */
    public function getQuestionsLazy(Topic $topic, array $sources = ['all'], ?int $limit = null)
    {
        $query = $this->buildQuestionsQuery($topic, $sources);
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->lazy(self::CHUNK_SIZE);
    }

    /**
     * Process questions in chunks to manage memory
     */
    public function processQuestionsInChunks(Topic $topic, array $sources, callable $processor, ?int $chunkSize = null): array
    {
        $chunkSize = $chunkSize ?? self::CHUNK_SIZE;
        $results = [];
        $processedCount = 0;
        
        $query = $this->buildQuestionsQuery($topic, $sources);
        
        $query->chunk($chunkSize, function ($questions) use ($processor, &$results, &$processedCount) {
            // Check memory usage before processing
            if (memory_get_usage(true) > self::MAX_MEMORY_USAGE) {
                Log::warning('Memory usage approaching limit', [
                    'current_usage' => memory_get_usage(true),
                    'limit' => self::MAX_MEMORY_USAGE
                ]);
                return false; // Stop processing
            }
            
            $chunkResults = $processor($questions);
            if (is_array($chunkResults)) {
                $results = array_merge($results, $chunkResults);
            }
            
            $processedCount += $questions->count();
        });
        
        return [
            'results' => $results,
            'processed_count' => $processedCount,
            'memory_peak' => memory_get_peak_usage(true)
        ];
    }

    /**
     * Build optimized questions query
     */
    private function buildQuestionsQuery(Topic $topic, array $sources)
    {
        $query = Question::select('id', 'question', 'options', 'correct_answer', 'explanation', 'source')
            ->where(function($q) use ($topic) {
                $q->where('topic_id', $topic->id)
                  ->orWhereRaw('FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$topic->id]);
            });
            
        if (!in_array('all', $sources)) {
            $query->whereIn('source', $sources);
        }
        
        return $query;
    }

    /**
     * Check whether the questions table has a 'difficulty' column.
     * Result is cached in a static property so SHOW COLUMNS is only issued once
     * per PHP-FPM worker lifetime.
     */
    private function questionTableHasDifficultyColumn(): bool
    {
        if (self::$hasDifficultyColumn === null) {
            self::$hasDifficultyColumn = \Schema::hasColumn('questions', 'difficulty');
        }

        return self::$hasDifficultyColumn;
    }
}
