<?php

namespace App\Services;

use App\Models\User;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\UserTopicProgress;
use App\Services\Core\UltraConsolidatedCacheService;
use App\Services\UserStatsService;
use App\Services\NotificationService;
use App\Services\WrittenAnswerEvaluationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GradingService
{
    // Quiz validation constants
    private const MAX_ANSWER_LENGTH = 2000;
    private const MAX_TOTAL_LENGTH = 20000;
    
    // XP calculation constants  
    private const XP_PER_CORRECT_ANSWER = 10;
    private const MAX_TIME_BONUS_XP = 50;
    private const MAX_ACCURACY_BONUS_XP = 50;
    private const ACCURACY_BONUS_DIVISOR = 10;
    private const ACCURACY_BONUS_MULTIPLIER = 5;
    
    // Level calculation constants
    private const XP_PER_LEVEL = 1000;
    
    protected $cacheService;
    protected $userStatsService;
    protected $notificationService;
    protected $writtenAnswerEvaluationService;

    public function __construct(
        UltraConsolidatedCacheService $cacheService,
        UserStatsService $userStatsService,
        NotificationService $notificationService,
        WrittenAnswerEvaluationService $writtenAnswerEvaluationService
    ) {
        $this->cacheService = $cacheService;
        $this->userStatsService = $userStatsService;
        $this->notificationService = $notificationService;
        $this->writtenAnswerEvaluationService = $writtenAnswerEvaluationService;
    }

    /**
     * Grade a quiz and return comprehensive results
     *
     * @param User $user
     * @param array $answers
     * @param int $duration
     * @param string $mode
     * @param Topic $topic
     * @param Course $course
     * @return array
     * @throws \Exception
     */
    public function gradeQuiz(User $user, array $answers, int $duration, string $mode, Topic $topic, Course $course, ?int $assignmentId = null): array
    {
        DB::beginTransaction();
        try {
            // Validate inputs
            $this->validateQuizInputs($answers, $duration, $mode);

            // Get questions and validate they belong to the topic
            $questions = $this->getAndValidateQuestions($answers);

            // Process quiz based on mode and get grading results
            $gradingResult = $this->processQuizByMode($user, $questions, $answers, $topic, $mode);

            // Check if this is an assignment quiz
            $isAssignment = $this->isAssignmentQuiz($user, $topic, $course, $assignmentId);

            // Create quiz attempts based on mode
            $this->createQuizAttemptsByMode($user, $gradingResult, $duration, $mode, $topic, $isAssignment, $assignmentId);

            // Update user statistics and rewards
            $xpResult = $this->updateUserStatsAndRewards($user, $gradingResult, $duration);

            // Handle topic progress updates
            $this->handleProgressUpdate($user, $topic, $gradingResult, $mode, $assignmentId);

            // Clear relevant caches
            $this->cacheService->smartInvalidate('user_progress', $user->id, ['topic_id' => $topic->id]);

            DB::commit();

            // Get updated overall topic progress after quiz completion
            $overallTopicProgress = QuizAttempt::getTopicProgress($user->id, $topic->id, $mode);

            // Prepare response
            return $this->prepareGradingResponse($gradingResult, $questions, $answers, $xpResult, $overallTopicProgress);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error in GradingService::gradeQuiz:', [
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
     * Process quiz answers based on mode
     *
     * @param User $user
     * @param mixed $questions
     * @param array $answers
     * @param Topic $topic
     * @param string $mode
     * @return array
     */
    private function processQuizByMode(User $user, $questions, array $answers, Topic $topic, string $mode): array
    {
        // LETHAL FIX: Use appropriate processing method based on mode
        if ($mode === 'tutor') {
            return $this->processTutorAnswers($user, $questions, $answers, $topic);
        } else {
            return $this->processAnswers($user, $questions, $answers, $topic);
        }
    }

    /**
     * Create quiz attempts based on mode
     *
     * @param User $user
     * @param array $gradingResult
     * @param int $duration
     * @param string $mode
     * @param Topic $topic
     * @param bool $isAssignment
     * @param int|null $assignmentId
     */
    private function createQuizAttemptsByMode(User $user, array $gradingResult, int $duration, string $mode, Topic $topic, bool $isAssignment, ?int $assignmentId): void
    {
        // Create quiz attempts - handle random mode differently
        if ($mode === 'random') {
            $this->createRandomQuizAttempt($user, $gradingResult, $duration, $mode, $topic, $isAssignment, $assignmentId);
        } else { // Both tutor and quiz modes use createQuizAttempts
            // For tutor mode, sourceData from processTutorAnswers contains the breakdown
            // For quiz mode, sourceData from processAnswers contains the breakdown
            $this->createQuizAttempts($user, $gradingResult['sourceData'], $duration, $mode, $topic, $isAssignment, $assignmentId);
        }
    }

    /**
     * Update user statistics and calculate rewards
     *
     * @param User $user
     * @param array $gradingResult
     * @param int $duration
     * @return array
     */
    private function updateUserStatsAndRewards(User $user, array $gradingResult, int $duration): array
    {
        // Update user statistics
        $this->userStatsService->updateAfterQuizAttempt(
            $user,
            $gradingResult['totalQuestions'],
            $gradingResult['totalCorrect'],
            $duration
        );

        // Calculate and award XP
        $xpResult = $this->calculateAndAwardXP($user, $gradingResult['score'], $duration, $gradingResult['percentageGrade']);

        // Update study streak for ALL quiz types (including first quiz)
        $this->updateStudyStreak($user);

        return $xpResult;
    }

    /**
     * Handle topic progress updates based on quiz type
     *
     * @param User $user
     * @param Topic $topic
     * @param array $gradingResult
     * @param string $mode
     * @param int|null $assignmentId
     */
    private function handleProgressUpdate(User $user, Topic $topic, array $gradingResult, string $mode, ?int $assignmentId): void
    {
        // Update topic progress ONLY for regular quizzes, NOT assignments or tutor mode
        if (!$assignmentId && $mode === 'quiz') {
            $this->updateTopicProgress($user, $topic, $gradingResult['score'], $gradingResult['totalQuestions']);
        } else {
            // Assignment completion or tutor mode - don't update regular topic progress
            Log::info('Non-regular quiz completed - skipping regular topic progress update', [
                'assignment_id' => $assignmentId,
                'mode' => $mode,
                'user_id' => $user->id,
                'topic_id' => $topic->id
            ]);
        }
    }

    /**
     * Validate quiz inputs
     *
     * @param array $answers
     * @param int $duration
     * @param string $mode
     * @throws \InvalidArgumentException
     */
    private function validateQuizInputs(array $answers, int $duration, string $mode): void
    {
        // Validate answer payload size (but allow empty answers)
        $totalLength = 0;

        foreach ($answers as $qid => $answer) {
            // Skip null/empty answers (they're allowed)
            if ($answer === null || $answer === '') {
                continue;
            }
            
            if (is_string($answer) && mb_strlen($answer) > self::MAX_ANSWER_LENGTH) {
                throw new \InvalidArgumentException("Answer for question {$qid} exceeds maximum allowed length of " . self::MAX_ANSWER_LENGTH . " characters.");
            }
            if (is_string($answer)) {
                $totalLength += mb_strlen($answer);
            }
        }

        if ($totalLength > self::MAX_TOTAL_LENGTH) {
            throw new \InvalidArgumentException("Total answer payload exceeds maximum allowed length of " . self::MAX_TOTAL_LENGTH . " characters.");
        }

        // Allow empty answers array (user might not answer anything)
        if (!is_array($answers)) {
            throw new \InvalidArgumentException('Answers must be provided as an array');
        }

        if (!is_numeric($duration) || $duration < 0) {
            throw new \InvalidArgumentException('Duration must be a non-negative number');
        }

        // Default to 'quiz' mode if not valid
        if (!in_array($mode, ['quiz', 'tutor', 'random'])) {
            Log::warning('Invalid mode received, defaulting to quiz mode', [
                'received_mode' => $mode
            ]);
            $mode = 'quiz';
        }
    }

    /**
     * Get and validate questions with optimized queries
     *
     * @param array $answers
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getAndValidateQuestions(array $answers): \Illuminate\Database\Eloquent\Collection
    {
        $questionIds = array_keys($answers);
        
        // OPTIMIZED QUERY: Eager load relationships and select only needed fields
        $questions = Question::select([
                'id', 'question', 'question_type', 'correct_answer', 
                'option_a', 'option_b', 'option_c', 'option_d', 'topic_id', 'source'
            ])
            ->with([
                'topic:id,name,course_id',
                'topic.course:id,name'
            ])
            ->whereIn('id', $questionIds)
            ->get();
        
        // VALIDATION: Ensure all questions exist and are accessible
        if ($questions->count() !== count($questionIds)) {
            $foundIds = $questions->pluck('id')->toArray();
            $missingIds = array_diff($questionIds, $foundIds);
            throw new \InvalidArgumentException("Questions not found: " . implode(', ', $missingIds) . ". Please check question IDs and try again.");
        }
        
        return $questions;
    }

    /**
     * Process answers and calculate scores
     *
     * @param User $user
     * @param \Illuminate\Database\Eloquent\Collection $questions
     * @param array $answers
     * @param Topic $topic
     * @return array
     */
    private function processAnswers(User $user, $questions, array $answers, Topic $topic): array
    {
        $score = 0;
        $totalQuestions = $questions->count();
        $sourceData = [];
        $mistakes = [];

        // 🚀 ULTRA-OPTIMIZED SINGLE-PASS PROCESSING
        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            $originalSubmittedAnswer = $submittedAnswer;
            
            // Handle unanswered questions
            if ($submittedAnswer === null || $submittedAnswer === '') {
                $submittedAnswer = 'unanswered';
                $isCorrect = false;
            } else {
                $isCorrect = $this->evaluateAnswer($question, $submittedAnswer);
            }
            
            $source = $question->source ?: 'General';

            // Initialize source data if not exists
            if (!isset($sourceData[$source])) {
                $sourceData[$source] = [
                    'score' => 0,
                    'total' => 0,
                    'question_data' => []
                ];
            }

            $sourceData[$source]['total']++;
            if ($isCorrect) {
                $score++;
                $sourceData[$source]['score']++;
            } else {
                // 🚀 IMMEDIATE MISTAKE RECORDING - NO BATCH OVERHEAD
                $actualAnswer = $originalSubmittedAnswer !== null && $originalSubmittedAnswer !== '' 
                    ? $originalSubmittedAnswer 
                    : 'unanswered';
                

                    
                \App\Models\Mistake::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'question_id' => $question->id
                    ],
                    [
                        'submitted_answer' => $actualAnswer,
                        'question_text' => $question->question,
                        'correct_answer' => $question->correct_answer,
                        'last_attempt_date' => now()
                    ]
                );
                
                $mistakes[] = $question->id;
            }

            $sourceData[$source]['question_data'][] = [
                'question_id' => $question->id,
                'user_answer' => $submittedAnswer,
                'is_correct' => $isCorrect
            ];
        }


        $percentageGrade = $totalQuestions > 0 ? min(100, ($score / $totalQuestions) * 100) : 0;
        $totalCorrect = $score;

        // 🔥 INSTANT CACHE CLEAR - Clear cache if any mistakes were recorded
        if (!empty($mistakes)) {
            \App\Http\Controllers\Quiz\QuizMistakeController::clearUserMistakeCaches($user->id);
        }

        return [
            'score' => $score,
            'totalQuestions' => $totalQuestions,
            'percentageGrade' => $percentageGrade,
            'sourceData' => $sourceData,
            'mistakes' => $mistakes,
            'totalCorrect' => $totalCorrect
        ];
    }

    /**
     * Evaluate if an answer is correct
     *
     * @param Question $question
     * @param string $submittedAnswer
     * @return bool
     */
    private function evaluateAnswer(Question $question, string $submittedAnswer): bool
    {
        if ($question->question_type === 'written') {
            return $this->evaluateWrittenAnswer($question, $submittedAnswer);
        }

        return $question->isAnswerCorrect($submittedAnswer);
    }

    /**
     * ULTRA EFFICIENT: Process tutor mode answers with optimized evaluation
     *
     * @param User $user
     * @param $questions
     * @param array $answers
     * @param Topic $topic
     * @return array
     */
    private function processTutorAnswers(User $user, $questions, array $answers, Topic $topic): array
    {
        // 🚀 ULTRA EFFICIENT: Single-pass evaluation with immediate processing
        $score = 0;
        $totalQuestions = $questions->count();
        $sourceData = [];
        $mistakes = [];
        $evaluations = [];

        // Group questions by source for efficient processing
        $questionsBySource = $questions->groupBy(function($question) {
            return $question->source ?: 'General';
        });

        // 🚀 SINGLE EVALUATION LOOP - MAXIMUM EFFICIENCY
        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            $originalSubmittedAnswer = $submittedAnswer;
            
            // Handle unanswered questions efficiently
            if ($submittedAnswer === null || $submittedAnswer === '') {
                $submittedAnswer = 'unanswered';
                $isCorrect = false;
            } else {
                $isCorrect = $this->evaluateAnswer($question, $submittedAnswer);
            }
            
            // Store evaluation for immediate reuse
            $evaluations[$question->id] = [
                'is_correct' => $isCorrect,
                'submitted_answer' => $submittedAnswer,
                'original_answer' => $originalSubmittedAnswer
            ];
            
            // Increment score immediately
            if ($isCorrect) {
                $score++;
            }
        }

        // 🚀 ULTRA EFFICIENT: Process sources using cached evaluations
        foreach ($questionsBySource as $source => $sourceQuestions) {
            $sourceCorrect = 0;
            $sourceTotal = $sourceQuestions->count();
            
            // Initialize source data efficiently
            $sourceData[$source] = [
                'score' => 0,
                'total' => $sourceTotal,
                'question_data' => []
            ];

            // Process each question in this source
            foreach ($sourceQuestions as $question) {
                $evaluation = $evaluations[$question->id]; // Use pre-computed evaluation
                $isCorrect = $evaluation['is_correct'];
                $submittedAnswer = $evaluation['submitted_answer'];
                $originalSubmittedAnswer = $evaluation['original_answer'];
                
                if ($isCorrect) {
                    $sourceCorrect++;
                } else {
                    // 🚀 EFFICIENT MISTAKE RECORDING - BATCH OPTIMIZED
                    $actualAnswer = $originalSubmittedAnswer !== null && $originalSubmittedAnswer !== '' 
                        ? $originalSubmittedAnswer 
                        : 'unanswered';
                    
                    // Use updateOrCreate for efficiency
                    \App\Models\Mistake::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'question_id' => $question->id
                        ],
                        [
                            'submitted_answer' => $actualAnswer,
                            'question_text' => $question->question,
                            'correct_answer' => $question->correct_answer,
                            'image_path' => $question->image_url,
                            'last_attempt_date' => now()
                        ]
                    );
                    
                    $mistakes[] = $question->id;
                }

                // Build question data efficiently
                $sourceData[$source]['question_data'][] = [
                    'question_id' => $question->id,
                    'user_answer' => $submittedAnswer,
                    'is_correct' => $isCorrect
                ];
            }
            
            // Set the final score for this source
            $sourceData[$source]['score'] = $sourceCorrect;
        }

        $percentageGrade = $totalQuestions > 0 ? min(100, ($score / $totalQuestions) * 100) : 0;

        Log::info('✅ TUTOR MODE: Processed tutor answers', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'total_score' => $score,
            'total_questions' => $totalQuestions,
            'percentage' => $percentageGrade,
            'source_data' => array_map(function($data) {
                return ['score' => $data['score'], 'total' => $data['total']];
            }, $sourceData)
        ]);

        // 🔥 INSTANT CACHE CLEAR - Clear cache if any mistakes were recorded
        if (!empty($mistakes)) {
            \App\Http\Controllers\Quiz\QuizMistakeController::clearUserMistakeCaches($user->id);
        }

        return [
            'score' => $score,
            'totalQuestions' => $totalQuestions,
            'percentageGrade' => $percentageGrade,
            'sourceData' => $sourceData,
            'mistakes' => $mistakes,
            'totalCorrect' => $score
        ];
    }

    /**
     * Evaluate written answer using AI service
     *
     * @param Question $question
     * @param string $submittedAnswer
     * @return bool
     */
    private function evaluateWrittenAnswer(Question $question, string $submittedAnswer): bool
    {
        try {
            $evaluation = $this->writtenAnswerEvaluationService->evaluateAnswer(
                $submittedAnswer,
                $question->correct_answer,
                [], // alternativeAnswers parameter
                0.7 // similarityThreshold parameter
            );

            // Use the correct key from WrittenAnswerEvaluationService response
            return $evaluation['isCorrect'] ?? false;
        } catch (\Exception $e) {
            Log::error('Written answer evaluation failed', [
                'question_id' => $question->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to exact match if evaluation fails
            return strtolower(trim($submittedAnswer)) === strtolower(trim($question->correct_answer));
        }
    }

    /**
     * Check if this quiz is being solved for an assignment based on assignment_id parameter
     *
     * @param User $user
     * @param Topic $topic
     * @param Course $course
     * @param int|null $assignmentId
     * @return bool
     */
    private function isAssignmentQuiz(User $user, Topic $topic, Course $course, ?int $assignmentId = null): bool
    {
        // CRITICAL FIX: Only return true if assignment_id is explicitly provided
        // Don't assume it's an assignment just because assignments exist for the topic
        // Check if assignment exists and is accessible (status is computed dynamically)
        if ($assignmentId === null) {
            return false;
        }
        
        $assignment = \App\Models\Assignment::where('id', $assignmentId)
            ->where('topic_id', $topic->id)
            ->where('course_id', $course->id)
            ->first();
            
        if (!$assignment) {
            return false;
        }
        
        // Check if the assignment is currently active for this specific user.
        // The status is computed dynamically, so we don't need to check for overdue/expired here.
        $status = $assignment->getStatusForUser($user);
        return $status === \App\Models\Assignment::STATUS_ACTIVE;
    }



    /**
     * ULTRA EFFICIENT: Create quiz attempts with attempt_id grouping for chart optimization
     *
     * @param User $user
     * @param array $sourceData
     * @param int $duration
     * @param string $mode
     * @param Topic $topic
     * @param bool $isAssignment
     * @param int|null $assignmentId
     */
    private function createQuizAttempts(User $user, array $sourceData, int $duration, string $mode, Topic $topic, bool $isAssignment = false, ?int $assignmentId = null): void
    {
        Log::info('🔥 DEBUG: createQuizAttempts called', [
            'user_id' => $user->id,
            'source_count' => count($sourceData),
            'mode' => $mode,
            'topic_id' => $topic->id,
            'is_random' => ($mode === 'random'),
            'is_tutor' => ($mode === 'tutor'),
            'source_data_keys' => array_keys($sourceData),
            'transaction_active' => DB::transactionLevel()
        ]);

        if (empty($sourceData)) {
            Log::error('⚠️ DEBUG: No sourceData provided to createQuizAttempts!');
            return;
        }
        $numSources = count($sourceData);
        $durationPerSource = $numSources > 0 ? (int) round($duration / $numSources) : $duration;

        // Determine quiz type based on assignment status (tutor mode uses 'normal' quiz_type)
        $quizType = $isAssignment ? 'assignment' : 'normal';

        // 🚀 CRITICAL FIX: Generate SINGLE attempt_id for ALL sources in this quiz
        // This ensures multiple sources from same quiz = 1 chart point
        $attemptId = \App\Models\QuizAttempt::generateAttemptId($user->id, $topic->id);

        // 🚀 ULTRA EFFICIENT: Create attempts with shared attempt_id
        $attemptsCreated = 0;
        foreach ($sourceData as $source => $data) {
            $score = $data['score'];
            $totalQuestions = $data['total'];
            $percentage = $totalQuestions > 0 ? min(100, ($score / $totalQuestions) * 100) : 0;

            // Use createWithProgress to ensure attempt_id is auto-generated if not provided
            $attemptData = [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'assignment_id' => $assignmentId,
                'quiz_type' => $quizType,
                'study_mode' => $mode,
                'source' => $source,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'percentage_grade' => $percentage,
                'duration_seconds' => $durationPerSource,
                'question_data' => $data['question_data'],
                'correct_answers' => $score,
                'attempt_id' => $attemptId // 🚀 CRITICAL: Same ID for all sources in this quiz
            ];

            \App\Models\QuizAttempt::createWithProgress($attemptData);
            $attemptsCreated++;
        }

        Log::info('🚀 ULTRA EFFICIENT: Created quiz attempts with attempt_id grouping', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'mode' => $mode,
            'attempt_id' => $attemptId,
            'sources_grouped' => $attemptsCreated,
            'chart_points' => 1 // Multiple sources = 1 chart point ✅
        ]);
    }

    /**
     * Create a single quiz attempt for random quizzes (no source) with attempt_id
     *
     * @param User $user
     * @param array $gradingResult
     * @param int $duration
     * @param string $mode
     * @param Topic $topic
     * @param bool $isAssignment
     * @param int|null $assignmentId
     */
    private function createRandomQuizAttempt(User $user, array $gradingResult, int $duration, string $mode, Topic $topic, bool $isAssignment = false, ?int $assignmentId = null): void
    {
        // Determine quiz type based on assignment status
        $quizType = $isAssignment ? 'assignment' : 'random'; // Use 'random' for better profile detection

        // Generate attempt_id for random quiz (single attempt = 1 chart point)
        $attemptId = \App\Models\QuizAttempt::generateAttemptId($user->id, $topic->id);

        // Consolidate all question data from all sources
        $allQuestionData = [];
        foreach ($gradingResult['sourceData'] as $sourceData) {
            $allQuestionData = array_merge($allQuestionData, $sourceData['question_data']);
        }

        // Use createWithProgress to ensure attempt_id is set properly
        $attemptData = [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'assignment_id' => $assignmentId,
            'quiz_type' => $quizType,
            'study_mode' => $mode,
            'source' => null, // Random quizzes have no specific source
            'score' => $gradingResult['score'],
            'total_questions' => $gradingResult['totalQuestions'],
            'percentage_grade' => $gradingResult['percentageGrade'],
            'duration_seconds' => $duration,
            'question_data' => $allQuestionData,
            'correct_answers' => $gradingResult['score'],
            'attempt_id' => $attemptId // 🚀 CRITICAL: Set attempt_id for chart grouping
        ];

        \App\Models\QuizAttempt::createWithProgress($attemptData);

        Log::info('🚀 RANDOM QUIZ: Created attempt with attempt_id', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'attempt_id' => $attemptId,
            'quiz_type' => $quizType,
            'study_mode' => $mode,
            'chart_points' => 1 // Random quiz = 1 chart point ✅
        ]);
    }

    /**
     * Calculate and award XP to user
     *
     * @param User $user
     * @param int $score
     * @param int $duration
     * @param float $percentageGrade
     * @return array
     */
    private function calculateAndAwardXP(User $user, int $score, int $duration, float $percentageGrade): array
    {
        // Calculate XP based on score and time
        $baseXP = $score * self::XP_PER_CORRECT_ANSWER; // XP per correct answer
        $timeBonus = max(0, self::MAX_TIME_BONUS_XP - floor($duration / 60)); // Up to max time bonus XP for fast completion
        $accuracyBonus = floor($percentageGrade / self::ACCURACY_BONUS_DIVISOR) * self::ACCURACY_BONUS_MULTIPLIER; // Up to max accuracy bonus XP for high accuracy
        $earnedXP = $baseXP + $timeBonus + $accuracyBonus;

        Log::info('XP Calculation:', [
            'user_id' => $user->id,
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
        $levelUpResult = $this->checkForLevelUp($user);

        return [
            'earnedXP' => $earnedXP,
            'levelUp' => $levelUpResult['levelUp'],
            'newLevel' => $levelUpResult['newLevel']
        ];
    }

    /**
     * Check if user leveled up and handle notification
     *
     * @param User $user
     * @return array
     */
    private function checkForLevelUp(User $user): array
    {
        $currentLevel = $user->getCurrentLevel();
        $newLevel = floor($user->xp / self::XP_PER_LEVEL) + 1;

        if ($newLevel > $currentLevel) {
            // Create level up notification
            $this->notificationService->createLevelUpNotification($user, $newLevel, $user->xp);

            Log::info('User leveled up', [
                'user_id' => $user->id,
                'old_level' => $currentLevel,
                'new_level' => $newLevel,
                'xp' => $user->xp
            ]);

            return [
                'levelUp' => true,
                'newLevel' => $newLevel
            ];
        }

        return [
            'levelUp' => false,
            'newLevel' => $currentLevel
        ];
    }

    /**
     * Update topic progress for user
     *
     * @param User $user
     * @param Topic $topic
     * @param int $score
     * @param int $totalQuestions
     */
    private function updateTopicProgress(User $user, Topic $topic, int $score, int $totalQuestions): void
    {
        // Clear cache using the same smart invalidation approach for consistency
        $this->cacheService->smartInvalidate('user_progress', $user->id, ['topic_id' => $topic->id]);
        
        // Use QuizAttempt::getTopicProgress to get the true overall progress
        $progress = \App\Models\QuizAttempt::getTopicProgress($user->id, $topic->id, 'quiz');
        $overallPercentage = $progress['overall']['percentage'] ?? 0;

        \App\Models\UserTopicProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'topic_id' => $topic->id
            ],
            [
                'percentage_grade' => $overallPercentage,
                'last_attempt_date' => now()
            ]
        );
    }

    /**
     * Prepare grading response
     *
     * @param array $gradingResult
     * @param \Illuminate\Database\Eloquent\Collection $questions
     * @param array $answers
     * @param array $xpResult
     * @param array $overallTopicProgress
     * @return array
     */
    private function prepareGradingResponse(array $gradingResult, $questions, array $answers, array $xpResult, array $overallTopicProgress): array
    {
        // Get updated progress for all sources
        $sourceProgress = [];
        
        // OPTIMIZATION: Get all source question counts in a single query to avoid N+1 problem
        $topicId = $questions->first()->topic_id;
        $sourceCounts = Question::where('topic_id', $topicId)
            ->selectRaw('source, COUNT(*) as total_questions')
            ->groupBy('source')
            ->pluck('total_questions', 'source')
            ->toArray();

        foreach ($gradingResult['sourceData'] as $source => $data) {
            $totalSourceQuestions = $sourceCounts[$source] ?? 0;

            $sourceProgress[$source] = [
                'score' => $data['score'],
                'total' => $data['total'],
                'total_questions_available' => $totalSourceQuestions,
                'percentage' => $data['total'] > 0 ? min(100, ($data['score'] / $data['total']) * 100) : 0
            ];
        }

        // Get overall topic progress for sources
        $overallSourceProgress = [];
        if (isset($overallTopicProgress['sources'])) {
            foreach ($overallTopicProgress['sources'] as $source => $data) {
                $overallSourceProgress[$source] = [
                    'score' => $data['score'],
                    'total' => $data['total'],
                    'percentage' => $data['percentage']
                ];
            }
        }

        // Build incorrect_answers map for review mode
        $incorrectAnswers = [];
        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            $isCorrect = $this->evaluateAnswer($question, $submittedAnswer ?? '');
            
            if (!$isCorrect) {
                $incorrectAnswers[$question->id] = [
                    'submitted_answer' => $submittedAnswer ?? 'unanswered',
                    'correct_answer' => $question->correct_answer
                ];
            }
        }

        return [
            'success' => true,
            'score' => $gradingResult['score'],
            'totalQuestions' => $gradingResult['totalQuestions'],
            'percentageGrade' => $gradingResult['percentageGrade'],
            'encouragement' => $this->getEncouragementMessage($gradingResult['percentageGrade']),
            'source_progress' => $sourceProgress, // Current quiz attempt progress
            'overall_topic_progress' => $overallTopicProgress, // Overall progress including all attempts
            'overall_source_progress' => $overallSourceProgress, // Overall progress by source
            'correct_answers' => $questions->mapWithKeys(function ($question) {
                return [$question->id => $question->correct_answer];
            })->toArray(),
            'incorrect_answers' => $incorrectAnswers, // For review mode - shows which answers were wrong
            'xp_earned' => $xpResult['earnedXP'],
            'level_up' => $xpResult['levelUp'],
            'new_level' => $xpResult['newLevel'],
            'mistakes' => $gradingResult['mistakes']
        ];
    }

    /**
     * Get encouragement message based on percentage grade
     *
     * @param float $percentageGrade
     * @return string
     */
    private function getEncouragementMessage(float $percentageGrade): string
    {
        if ($percentageGrade >= 100) {
            return "Excellent work! You're mastering this material! 🌟";
        } elseif ($percentageGrade >= 85) {
            return "Great job! You're making excellent progress! 🎉";
        } elseif ($percentageGrade >= 75) {
            return "Good work! Keep practicing to improve further! 💪";
        } elseif ($percentageGrade >= 65) {
            return "Not bad! Review the mistakes and try again! 📚";
        } else {
            return "Don't worry! Learning takes time. Review and practice more! 🔄";
        }
    }

    /**
     * Grade a single tutor answer
     *
     * @param User $user
     * @param Question $question
     * @param string $answer
     * @return array
     */
    public function gradeTutorAnswer(User $user, Question $question, string $answer): array
    {
        try {
            Log::info('Tutor answer check', [
                'user_id' => $user->id,
                'question_id' => $question->id,
                'topic_id' => $question->topic_id,
                'source' => $question->source,
                'answer' => $answer
            ]);
            $isCorrect = $this->evaluateAnswer($question, $answer);
            if (!$isCorrect) {

                \App\Models\Mistake::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'question_id' => $question->id
                    ],
                    [
                        'submitted_answer' => $answer,
                        'question_text' => $question->question,
                        'correct_answer' => $question->correct_answer,
                        'image_path' => $question->image_url,
                        'last_attempt_date' => now()
                    ]
                );
                
                // 🔥 INSTANT CACHE CLEAR - Mistakes show up immediately!
                \App\Http\Controllers\Quiz\QuizMistakeController::clearUserMistakeCaches($user->id);
            }
            $topic = $question->topic;
            if (!$topic) {
                throw new \Exception('Question has no associated topic');
            }
            $this->updateTutorProgress($user, $question, $topic, $isCorrect);
            $this->checkAndCreateTutorAttempt($user, $question, $topic);
            $message = $isCorrect ? 'Correct!' : 'Incorrect. Try again!';
            return [
                'success' => true,
                'is_correct' => $isCorrect,
                'message' => $message,
                'correct_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
                'source_progress' => $this->getTutorSourceProgress($user, $topic, $question->source)
            ];
        } catch (\Exception $e) {
            Log::error('Error in GradingService::gradeTutorAnswer:', [
                'error' => $e->getMessage(),
                'question_id' => $question->id,
                'user_id' => $user->id
            ]);
            throw $e;
        }
    }

    /**
     * Update tutor progress in session
     *
     * @param User $user
     * @param Question $question
     * @param Topic $topic
     * @param bool $isCorrect
     */
    private function updateTutorProgress(User $user, Question $question, Topic $topic, bool $isCorrect): void
    {
        $tutorProgress = session()->get('tutor_progress', ['sources' => []]);
        $source = $question->source ?: 'General';

        if (!isset($tutorProgress['sources'][$source])) {
            $tutorProgress['sources'][$source] = [
                'correct' => 0,
                'total' => 0,
                'completed_questions' => [],
                'start_time' => time()
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

        session(['tutor_progress' => $tutorProgress]);
    }

    /**
     * Check if source is complete and create tutor attempt
     *
     * @param User $user
     * @param Question $question
     * @param Topic $topic
     */
    private function checkAndCreateTutorAttempt(User $user, Question $question, Topic $topic): void
    {
        $tutorProgress = session()->get('tutor_progress', ['sources' => []]);
        $source = $question->source ?: 'General';
        if (!isset($tutorProgress['sources'][$source])) {
            return;
        }
        $remainingQuestions = Question::where('topic_id', $topic->id)
            ->where('source', $source)
            ->whereNotIn('id', $tutorProgress['sources'][$source]['completed_questions'])
            ->count();
        // Fallback: if this is the last question for the source, always create the attempt
        if ($remainingQuestions === 0 || $this->isLastQuestionForSource($question, $topic, $source)) {
            Log::info('Creating tutor attempt (all questions completed for source)', [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'source' => $source
            ]);
            $this->createTutorAttempt($user, $topic, $source, $tutorProgress['sources'][$source]);
            unset($tutorProgress['sources'][$source]);
            if (empty($tutorProgress['sources'])) {
                session()->forget('tutor_progress');
            } else {
                session(['tutor_progress' => $tutorProgress]);
            }
        }
    }

    // Helper to check if this is the last question for the source
    private function isLastQuestionForSource(Question $question, Topic $topic, string $source): bool
    {
        // OPTIMIZATION: Use count instead of plucking all IDs for better performance
        $totalQuestions = Question::where('topic_id', $topic->id)
            ->where('source', $source)
            ->count();
            
        $remainingQuestions = Question::where('topic_id', $topic->id)
            ->where('source', $source)
            ->where('id', '!=', $question->id)
            ->count();
            
        return $totalQuestions > 0 && $remainingQuestions === 0;
    }

    /**
     * Create tutor attempt record with attempt_id for chart optimization
     *
     * @param User $user
     * @param Topic $topic
     * @param string $source
     * @param array $sourceProgress
     */
    private function createTutorAttempt(User $user, Topic $topic, string $source, array $sourceProgress): void
    {
        try {
            Log::info('Tutor quiz attempt creation', [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'source' => $source,
                'progress' => $sourceProgress
            ]);

            $sourceDuration = time() - $sourceProgress['start_time'];
            $percentageGrade = ($sourceProgress['correct'] / max(1, $sourceProgress['total'])) * 100;

            // Generate attempt_id for tutor attempt (single source = 1 chart point)
            $attemptId = \App\Models\QuizAttempt::generateAttemptId($user->id, $topic->id);

            $questionData = [];
            foreach (array_unique($sourceProgress['completed_questions']) as $questionId) {
                $questionData[] = [
                    'question_id' => $questionId,
                    'user_answer' => null,
                    'is_correct' => true
                ];
            }

            // Use createWithProgress to ensure attempt_id is set properly
            $attemptData = [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'assignment_id' => null, // Tutor mode doesn't use assignments
                'quiz_type' => 'normal',
                'study_mode' => 'tutor',
                'source' => $source,
                'score' => $sourceProgress['correct'],
                'total_questions' => $sourceProgress['total'],
                'percentage_grade' => $percentageGrade,
                'duration_seconds' => $sourceDuration,
                'question_data' => $questionData,
                'correct_answers' => $sourceProgress['correct'],
                'attempt_id' => $attemptId // 🚀 CRITICAL: Set attempt_id for chart grouping
            ];

            $attempt = \App\Models\QuizAttempt::createWithProgress($attemptData);

            $this->userStatsService->updateAfterQuizAttempt(
                $user,
                $sourceProgress['total'],
                $sourceProgress['correct'],
                $sourceDuration
            );

            Log::info('Tutor quiz attempt created successfully with attempt_id', [
                'attempt_id' => $attemptId,
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'source' => $source,
                'chart_points' => 1 // Tutor attempt = 1 chart point ✅
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create tutor quiz attempt', [
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'source' => $source,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get tutor source progress
     *
     * @param User $user
     * @param Topic $topic
     * @param string $source
     * @return array
     */
    private function getTutorSourceProgress(User $user, Topic $topic, string $source): array
    {
        // LETHAL FIX: Eliminated session dependency - now uses database exclusively
        $source = $source ?: 'General';

        // Get the latest tutor attempt for this source
        $latestAttempt = QuizAttempt::where([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'study_mode' => 'tutor',
            'source' => $source
        ])->orderBy('created_at', 'desc')->first();

        $totalQuestionsInSource = Question::where('topic_id', $topic->id)
            ->where('source', $source)
            ->count();

        if (!$latestAttempt) {
            return ['completed' => 0, 'total' => $totalQuestionsInSource, 'percentage' => 0];
        }

        return [
            'completed' => $latestAttempt->score,
            'total' => $totalQuestionsInSource,
            'percentage' => round($latestAttempt->percentage_grade, 2)
        ];
    }



    /**
     * Update study streak for user after quiz completion
     *
     * @param User $user
     */
    private function updateStudyStreak(User $user): void
    {
        try {
            // Store original streak for notification comparison
            $originalStreak = $user->study_streak_days ?? 0;

            // Update the streak using the User model method
            $newStreak = $user->updateStudyStreak();

            Log::info('Study streak updated after quiz', [
                'user_id' => $user->id,
                'original_streak' => $originalStreak,
                'new_streak' => $newStreak,
                'streak_increased' => $newStreak > $originalStreak
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update study streak', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
