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
use App\Services\ProgressCacheInvalidator;
use App\Services\UserStatsService;
use App\Services\NotificationService;
use App\Services\WrittenAnswerEvaluationService;
use App\Services\Learning\UserMistakeMasteryService;
use App\Services\TutorSourceProgressService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GradingService
{
    // Quiz validation constants
    private const MAX_ANSWER_LENGTH = 2000;
    private const MAX_TOTAL_LENGTH = 20000;
    
    // XP calculation constants
    private const XP_PER_CORRECT_QUIZ = 10;        // Quiz/random mode: 10 XP per correct answer
    private const XP_PER_CORRECT_TUTOR = 5;         // Tutor mode: 5 XP per correct (conservative)
    private const ACCURACY_BONUS_THRESHOLD = 80;     // Accuracy % required to earn bonus
    private const MAX_ACCURACY_BONUS_XP = 50;        // Max bonus XP for high accuracy
    
    // Level calculation constants
    private const XP_PER_LEVEL = 1000;
    
    protected $cacheService;
    protected $progressCacheInvalidator;
    protected $userStatsService;
    protected $notificationService;
    protected $writtenAnswerEvaluationService;
    protected $userMistakeMasteryService;
    protected TutorSourceProgressService $tutorSourceProgressService;

    public function __construct(
        UltraConsolidatedCacheService $cacheService,
        ProgressCacheInvalidator $progressCacheInvalidator,
        UserStatsService $userStatsService,
        NotificationService $notificationService,
        WrittenAnswerEvaluationService $writtenAnswerEvaluationService,
        TutorSourceProgressService $tutorSourceProgressService,
        ?UserMistakeMasteryService $userMistakeMasteryService = null
    ) {
        $this->cacheService = $cacheService;
        $this->progressCacheInvalidator = $progressCacheInvalidator;
        $this->userStatsService = $userStatsService;
        $this->notificationService = $notificationService;
        $this->writtenAnswerEvaluationService = $writtenAnswerEvaluationService;
        $this->tutorSourceProgressService = $tutorSourceProgressService;
        $this->userMistakeMasteryService = $userMistakeMasteryService ?? app(UserMistakeMasteryService::class);
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
        // Enforce canonical modes to ensure attempts match planner task visibility gates.
        $mode = in_array((string) $mode, ['quiz', 'tutor', 'random'], true) ? (string) $mode : 'quiz';

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
            $xpResult = $this->updateUserStatsAndRewards($user, $gradingResult, $duration, $mode);

            // Recompute topic progress from the attempts created above, not a warm cached snapshot.
            $this->progressCacheInvalidator->invalidateUserTopicProgress($user->id, $topic->id, $mode, false);

            // Get updated overall topic progress after quiz completion - OPTIMIZATION: Get once, reuse
            $overallTopicProgress = QuizAttempt::getTopicProgress($user->id, $topic->id, $mode);

            // Handle topic progress updates - OPTIMIZATION: Pass progress to avoid re-query
            $this->handleProgressUpdate($user, $topic, $gradingResult, $mode, $assignmentId, $overallTopicProgress);

            // Clear relevant caches through canonical helper
            $this->progressCacheInvalidator->invalidateUserTopicProgress($user->id, $topic->id, null, true);

            DB::commit();

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
            return $this->processTutorAnswers($user, $questions, $answers, $topic, $mode);
        } else {
            return $this->processAnswers($user, $questions, $answers, $topic, $mode);
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
     * @param string $mode
     * @return array
     */
    private function updateUserStatsAndRewards(User $user, array $gradingResult, int $duration, string $mode = 'quiz'): array
    {
        // Calculate XP based on quiz performance and mode
        $earnedXP = $this->calculateQuizXp(
            (int) ($gradingResult['totalCorrect'] ?? 0),
            (int) ($gradingResult['totalQuestions'] ?? 0),
            $mode
        );

        // Update user statistics
        $this->userStatsService->updateAfterQuizAttempt(
            $user,
            $gradingResult['totalQuestions'],
            $gradingResult['totalCorrect'],
            $duration,
            $earnedXP
        );

        // Handle level-up checks/notifications 
        $xpResult = $this->finalizeXpAndLevel($user, $earnedXP);

        // Study streak is already updated atomically inside UserStatsService::updateAfterQuizAttempt().
        
        return $xpResult;
    }

    /**
     * Calculate XP earned from a quiz based on correct answers, total questions, and mode.
     *
     * Quiz/Random mode: 10 XP per correct answer + accuracy bonus (up to 50 XP at ≥80%).
     * Tutor mode: 5 XP per correct answer (more conservative — rewards learning over speed).
     *
     * @param int $correctAnswers
     * @param int $totalQuestions
     * @param string $mode
     * @return int
     */
    private function calculateQuizXp(int $correctAnswers, int $totalQuestions, string $mode): int
    {
        if ($correctAnswers <= 0) {
            return 0;
        }

        // Base XP: per-correct-answer rate depends on mode
        $perCorrect = ($mode === 'tutor') ? self::XP_PER_CORRECT_TUTOR : self::XP_PER_CORRECT_QUIZ;
        $baseXp = $correctAnswers * $perCorrect;

        // Accuracy bonus only for quiz/random (not tutor — tutor is per-question, not batch-scored)
        $accuracyBonus = 0;
        if ($mode !== 'tutor' && $totalQuestions > 0) {
            $accuracy = ($correctAnswers / $totalQuestions) * 100;
            if ($accuracy >= self::ACCURACY_BONUS_THRESHOLD) {
                // Scale bonus linearly: 80% → 0, 100% → MAX_ACCURACY_BONUS_XP
                $bonusFraction = ($accuracy - self::ACCURACY_BONUS_THRESHOLD) / (100 - self::ACCURACY_BONUS_THRESHOLD);
                $accuracyBonus = (int) round($bonusFraction * self::MAX_ACCURACY_BONUS_XP);
            }
        }

        return $baseXp + $accuracyBonus;
    }

    /**
     * Check if answer was correct based on existing grading results (optimization helper)
     *
     * @param int $questionId
     * @param array $gradingResult
     * @return bool
     */
    private function isAnswerCorrectInGradingResult(int $questionId, array $gradingResult): bool
    {
        foreach ($gradingResult['sourceData'] as $sourceData) {
            foreach ($sourceData['question_data'] as $questionData) {
                if ((int)$questionData['question_id'] === $questionId) {
                    return (bool)$questionData['is_correct'];
                }
            }
        }
        
        return false;
    }

    /**
     * Handle topic progress updates based on quiz type
     *
     * @param User $user
     * @param Topic $topic
     * @param array $gradingResult
     * @param string $mode
     * @param int|null $assignmentId
     * @param array|null $overallTopicProgress - OPTIMIZATION: Pass progress to avoid re-query
     */
    private function handleProgressUpdate(User $user, Topic $topic, array $gradingResult, string $mode, ?int $assignmentId, ?array $overallTopicProgress = null): void
    {
        // Update topic progress ONLY for regular quizzes, NOT assignments or tutor mode
        if (!$assignmentId && $mode === 'quiz') {
            $this->updateTopicProgress($user, $topic, $gradingResult['score'], $gradingResult['totalQuestions'], $overallTopicProgress);
        } else {
            // Assignment completion or tutor mode - don't update regular topic progress
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
            
            if (is_string($answer)) {
                // SURGICAL OPTIMIZATION: Cache mb_strlen to avoid expensive UTF-8 calculations
                static $stringLengthCache = [];
                if (!isset($stringLengthCache[$answer])) {
                    $stringLengthCache[$answer] = mb_strlen($answer);
                }
                $answerLength = $stringLengthCache[$answer];
                
                if ($answerLength > self::MAX_ANSWER_LENGTH) {
                    // INSTANT OPTIMIZATION: Pre-build error message to avoid concatenation
                    static $errorMessageTemplate = null;
                    if ($errorMessageTemplate === null) {
                        $errorMessageTemplate = "Answer for question %s exceeds maximum allowed length of " . self::MAX_ANSWER_LENGTH . " characters.";
                    }
                    throw new \InvalidArgumentException(sprintf($errorMessageTemplate, $qid));
                }
                
                $totalLength += $answerLength;
            }
        }

        if ($totalLength > self::MAX_TOTAL_LENGTH) {
            // INSTANT OPTIMIZATION: Pre-build error message to avoid concatenation
            static $totalLengthError = null;
            if ($totalLengthError === null) {
                $totalLengthError = "Total answer payload exceeds maximum allowed length of " . self::MAX_TOTAL_LENGTH . " characters.";
            }
            throw new \InvalidArgumentException($totalLengthError);
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
                'id', 'question', 'question_type', 'correct_answer', 'explanation',
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
    private function processAnswers(User $user, $questions, array $answers, Topic $topic, string $mode): array
    {
        $score = 0;
        $totalQuestions = $questions->count();
        $sourceData = [];
        $mistakes = [];
        $existingMistakeQuestionIds = Mistake::query()
            ->where('user_id', $user->id)
            ->whereIn('question_id', $questions->pluck('id')->all())
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $requiredStreak = $this->resolveRequiredStreakForSession($user, $mode);

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
                

                    
                \App\Models\Mistake::recordFailure($user->id, $question, $actualAnswer);
                
                $mistakes[] = $question->id;
            }

            $this->userMistakeMasteryService->recordAttempt(
                $user,
                $question,
                $isCorrect,
                $requiredStreak,
                (int) $topic->course_id
            );

            $sourceData[$source]['question_data'][] = [
                'question_id' => $question->id,
                'user_answer' => $submittedAnswer,
                'is_correct' => $isCorrect
            ];
        }


        $percentageGrade = $totalQuestions > 0 ? min(100, ($score / $totalQuestions) * 100) : 0;
        $totalCorrect = $score;

        // 🔥 INSTANT CACHE CLEAR - Clear cache if any mistakes were recorded
        if (!empty($mistakes) || !empty($existingMistakeQuestionIds)) {
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
    private function processTutorAnswers(User $user, $questions, array $answers, Topic $topic, string $mode): array
    {
        // 🚀 ULTRA EFFICIENT: Single-pass evaluation with immediate processing
        $score = 0;
        $totalQuestions = $questions->count();
        $sourceData = [];
        $mistakes = [];
        $evaluations = [];
        $existingMistakeQuestionIds = Mistake::query()
            ->where('user_id', $user->id)
            ->whereIn('question_id', $questions->pluck('id')->all())
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $requiredStreak = $this->resolveRequiredStreakForSession($user, $mode);

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
                    \App\Models\Mistake::recordFailure($user->id, $question, $actualAnswer);
                    
                    $mistakes[] = $question->id;
                }

                $this->userMistakeMasteryService->recordAttempt(
                    $user,
                    $question,
                    $isCorrect,
                    $requiredStreak,
                    (int) $topic->course_id
                );

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

        // 🔥 INSTANT CACHE CLEAR - Clear cache if any mistakes were recorded
        if (!empty($mistakes) || !empty($existingMistakeQuestionIds)) {
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
     * @return array
     */
    private function evaluateWrittenQuestion(Question $question, string $submittedAnswer): array
    {
        try {
            return $this->writtenAnswerEvaluationService->evaluateQuestionAnswer($question, $submittedAnswer);
        } catch (\Exception $e) {
            Log::error('Written answer evaluation failed', [
                'question_id' => $question->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'isCorrect' => $question->matchesWrittenAnswerFallback($submittedAnswer),
                'feedback' => 'We could not fully analyze the wording, so a conservative fallback check was used.',
            ];
        }
    }

    private function evaluateWrittenAnswer(Question $question, string $submittedAnswer): bool
    {
        $evaluation = $this->evaluateWrittenQuestion($question, $submittedAnswer);

        return (bool) ($evaluation['isCorrect'] ?? false);
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
        
        $assignment = \App\Models\Assignment::with(['attempts' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }])
            ->where('id', $assignmentId)
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
        // OPTIMIZATION: Removed debug logging for production performance
        if (empty($sourceData)) {
            Log::error('No source data provided to createQuizAttempts');
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
        // PERFORMANCE: Defer per-attempt invalidation to avoid duplicate cache fan-out for multi-source quizzes.
        $attemptsCreated = 0;
        \App\Models\QuizAttempt::setProgressInvalidationDeferred(true);
        try {
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
                    'session_type' => null,
                    'source' => $source,
                    'score' => $score,
                    'total_questions' => $totalQuestions,
                    'percentage_grade' => $percentage,
                    'duration_seconds' => $durationPerSource,
                    'question_data' => $data['question_data'],
                    'correct_answers' => $score,
                    'attempt_id' => $attemptId // 🚀 CRITICAL: Same ID for all sources in this quiz
                ];

                \App\Models\QuizAttempt::createWithProgress($attemptData, false);
                $attemptsCreated++;
            }
        } finally {
            \App\Models\QuizAttempt::setProgressInvalidationDeferred(false);
        }

        // OPTIMIZATION: Reduced logging frequency for better performance
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

        // Consolidate all question data from all sources - OPTIMIZED for performance
        $allQuestionData = [];
        foreach ($gradingResult['sourceData'] as $sourceData) {
            // Use array spread operator instead of array_merge for better performance
            $allQuestionData = [...$allQuestionData, ...$sourceData['question_data']];
        }

        // Use createWithProgress to ensure attempt_id is set properly
        $attemptData = [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'assignment_id' => $assignmentId,
            'quiz_type' => $quizType,
            'study_mode' => $mode,
            'session_type' => 'random_quiz',
            'source' => null, // Random quizzes have no specific source
            'score' => $gradingResult['score'],
            'total_questions' => $gradingResult['totalQuestions'],
            'percentage_grade' => $gradingResult['percentageGrade'],
            'duration_seconds' => $duration,
            'question_data' => $allQuestionData,
            'correct_answers' => $gradingResult['score'],
            'attempt_id' => $attemptId // 🚀 CRITICAL: Set attempt_id for chart grouping
        ];

        \App\Models\QuizAttempt::createWithProgress($attemptData, false);

    }

    private function resolveRequiredStreakForSession(User $user, string $mode): int
    {
        // Streak always from user's repair difficulty, not session mode.
        // $mode is accepted for future differentiation if needed.
        return $this->userMistakeMasteryService->requiredStreakForUser($user);
    }



    /**
     * Finalize level-up workflow after XP has already been atomically written.
     *
     * @param User $user
     * @param int $earnedXP
     * @return array
     */
    private function finalizeXpAndLevel(User $user, int $earnedXP): array
    {
        // Compare previous vs current level using in-memory XP (already updated atomically in UserStatsService).
        $previousXp = max(0, (int) $user->xp - $earnedXP);
        $previousLevel = (int) floor($previousXp / self::XP_PER_LEVEL) + 1;

        // Check for level up
        $levelUpResult = $this->checkForLevelUp($user, $previousLevel);

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
    private function checkForLevelUp(User $user, int $previousLevel): array
    {
        $newLevel = $user->getCurrentLevel();

        if ($newLevel > $previousLevel) {
            // Create level up notification
            $this->notificationService->createLevelUpNotification($user, $newLevel, $user->getXPForNextLevel());


            return [
                'levelUp' => true,
                'newLevel' => $newLevel
            ];
        }

        return [
            'levelUp' => false,
            'newLevel' => $newLevel
        ];
    }

    /**
     * Update topic progress for user
     *
     * @param User $user
     * @param Topic $topic
     * @param int $score
     * @param int $totalQuestions
     * @param array|null $overallTopicProgress - OPTIMIZATION: Use passed progress instead of re-querying
     */
    private function updateTopicProgress(User $user, Topic $topic, int $score, int $totalQuestions, ?array $overallTopicProgress = null): void
    {
        // Keep fast mode-scoped cache clear for immediate UI freshness.
        // NOTE: broad user_progress invalidation is executed once at gradeQuiz() end.
        $this->progressCacheInvalidator->invalidateUserTopicProgress($user->id, $topic->id, 'quiz', false);
        
        // OPTIMIZATION: Use passed progress data instead of re-querying (major performance gain)
        if ($overallTopicProgress !== null) {
            $progress = $overallTopicProgress;
        } else {
            // Fallback: Only query if progress not provided
            $progress = \App\Models\QuizAttempt::getTopicProgress($user->id, $topic->id, 'quiz');
        }
        
        $overallPercentage = $progress['overall']['percentage'] ?? 0;

        \App\Models\UserTopicProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'topic_id' => $topic->id
            ],
            [
                // NOTE: topics page reads percentage_grade from user_topic_progress
                // Keep this mapped to QUIZ-MODE-only overall percentage.
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

        $sourceCounts = [];
        $firstQuestion = $questions->first();

        // OPTIMIZATION: Get all source question counts in a single query to avoid N+1 problem
        if ($firstQuestion) {
            $sourceCounts = Question::where(function ($query) use ($firstQuestion) {
                    $query->where('topic_id', $firstQuestion->topic_id);

                    if (DB::getDriverName() === 'sqlite') {
                        $query->orWhereRaw(
                            "(',' || REPLACE(COALESCE(extra_topic, ''), ' ', '') || ',') LIKE ?",
                            ['%,' . $firstQuestion->topic_id . ',%']
                        );
                    } else {
                        $query->orWhereRaw('FIND_IN_SET(?, REPLACE(extra_topic, " ", "")) > 0', [$firstQuestion->topic_id]);
                    }
                })
                ->selectRaw('source, COUNT(*) as total_questions')
                ->groupBy('source')
                ->pluck('total_questions', 'source')
                ->toArray();
        }

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

        // Build incorrect_answers map for review mode - OPTIMIZED: Use existing results
        $incorrectAnswers = [];
        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            
            // OPTIMIZATION: Check if answer was marked as incorrect in grading results
            // This avoids re-evaluating the answer (significant performance gain)
            $isCorrect = $this->isAnswerCorrectInGradingResult($question->id, $gradingResult);
            
            if (!$isCorrect) {
                $incorrectAnswers[$question->id] = [
                    'submitted_answer' => $this->sanitizeAnswerForResponse($submittedAnswer),
                    'correct_answer' => $question->correct_answer,
                    'explanation' => $question->explanation,
                ];
            }
        }

        return [
            'success' => true,
            'score' => $gradingResult['score'],
            'total' => $gradingResult['totalQuestions'],
            'totalQuestions' => $gradingResult['totalQuestions'],
            'percentage' => $gradingResult['percentageGrade'],
            'percentageGrade' => $gradingResult['percentageGrade'],
            'encouragement' => $this->getEncouragementMessage($gradingResult['percentageGrade']),
            'source_progress' => $sourceProgress, // Current quiz attempt progress
            'overall_topic_progress' => $overallTopicProgress, // Overall progress including all attempts
            'overall_source_progress' => $overallSourceProgress, // Overall progress by source
            'correct_answers' => $questions->mapWithKeys(function ($question) {
                return [$question->id => $question->correct_answer];
            })->toArray(),
            'question_explanations' => $questions->mapWithKeys(function ($question) {
                return [$question->id => $question->explanation];
            })->toArray(),
            'incorrect_answers' => $incorrectAnswers, // For review mode - shows which answers were wrong
            'xp_earned' => $xpResult['earnedXP'],
            'level_up' => $xpResult['levelUp'],
            'new_level' => $xpResult['newLevel'],
            'mistakes' => $gradingResult['mistakes']
        ];
    }

    private function sanitizeAnswerForResponse(mixed $answer): string
    {
        $normalized = $answer ?? 'unanswered';

        if (! is_scalar($normalized)) {
            $normalized = 'unanswered';
        }

        return htmlspecialchars((string) $normalized, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
            $writtenEvaluation = null;

            if ($question->question_type === Question::TYPE_WRITTEN) {
                $writtenEvaluation = $this->evaluateWrittenQuestion($question, $answer);
                $isCorrect = (bool) ($writtenEvaluation['isCorrect'] ?? false);
            } else {
                $isCorrect = $this->evaluateAnswer($question, $answer);
            }

            $hadExistingMistake = Mistake::query()
                ->where('user_id', $user->id)
                ->where('question_id', $question->id)
                ->exists();
            if (!$isCorrect) {

                \App\Models\Mistake::recordFailure($user->id, $question, $answer);
                
                // 🔥 INSTANT CACHE CLEAR - Mistakes show up immediately!
            }
            $this->userMistakeMasteryService->recordAttempt(
                $user,
                $question,
                $isCorrect,
                $this->resolveRequiredStreakForSession($user, 'tutor'),
                (int) ($question->topic?->course_id ?? $question->topic()->value('course_id'))
            );
            if (!$isCorrect || $hadExistingMistake) {
                \App\Http\Controllers\Quiz\QuizMistakeController::clearUserMistakeCaches($user->id);
            }
            $topic = Topic::find((int) session('current_topic_id')) ?: $question->topic;
            if (!$topic) {
                throw new \Exception('Question has no associated topic');
            }
            $isNewAttempt = $this->updateTutorProgress($user, $question, $topic, $isCorrect);
            $this->tutorSourceProgressService->recordAnsweredQuestion($user->id, $topic, $question, $isCorrect);
            
            if ($isNewAttempt) {
                // Award XP for correct tutor answers (conservative: 5 XP per correct)
                $tutorXp = $isCorrect ? self::XP_PER_CORRECT_TUTOR : 0;
                // Update aggregate stats immediately for accurate pie charts in profile
                app(\App\Services\UserStatsService::class)->updateAfterQuizAttempt($user, 1, $isCorrect ? 1 : 0, 0, $tutorXp);

                // Check for level-up when XP is awarded
                if ($tutorXp > 0) {
                    $this->finalizeXpAndLevel($user, $tutorXp);
                }
            }
            
            $this->checkAndCreateTutorAttempt($user, $question, $topic);
            $message = $writtenEvaluation['feedback']
                ?? ($isCorrect ? 'Correct!' : 'Incorrect. Try again!');
            $explanation = $question->explanation;

            if ($question->question_type === Question::TYPE_WRITTEN && !$isCorrect && !empty($writtenEvaluation['feedback'])) {
                $explanation = trim($writtenEvaluation['feedback'] . "\n\n" . (string) $question->explanation);
            }

            return [
                'success' => true,
                'is_correct' => $isCorrect,
                'message' => $message,
                'correct_answer' => $question->correct_answer,
                'explanation' => $explanation,
                'source_progress' => $this->getTutorSourceProgress($user, $topic, $question->source),
                'xp_earned' => ($isNewAttempt && $isCorrect) ? self::XP_PER_CORRECT_TUTOR : 0,
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
     * @return bool True if this is a newly attempted question matching current session
     */
    private function updateTutorProgress(User $user, Question $question, Topic $topic, bool $isCorrect): bool
    {
        $now = time();
        $tutorProgress = session()->get('tutor_progress', ['sources' => []]);
        $source = $question->source ?: 'General';

        if (!isset($tutorProgress['sources'][$source])) {
            $tutorProgress['sources'][$source] = [
                'correct' => 0,
                'total' => 0,
                'completed_questions' => [], // Keep as array for storage
                'completed_questions_set' => [], // hash set for O(1) lookups
                'correct_questions_set' => [], // Track actual correctness per question
                'start_time' => $now // Use single time source
            ];
        }

        $isNewAttempt = false;

        // Use hash set for O(1) lookup instead of O(n) in_array
        if (!isset($tutorProgress['sources'][$source]['completed_questions_set'][$question->id])) {
            $isNewAttempt = true;
            $tutorProgress['sources'][$source]['total']++;
            if ($isCorrect) {
                $tutorProgress['sources'][$source]['correct']++;
                $tutorProgress['sources'][$source]['correct_questions_set'][$question->id] = true;
            }
            $tutorProgress['sources'][$source]['completed_questions'][] = $question->id;
            $tutorProgress['sources'][$source]['completed_questions_set'][$question->id] = true; // O(1) lookup
        }

        session(['tutor_progress' => $tutorProgress]);
        
        return $isNewAttempt;
    }

    /**
     * Check if tutor attempt should be created for this source
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
        
        $sessionSourceQuestions = collect(session("tutor_session_questions.sources.{$source}", []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (empty($sessionSourceQuestions)) {
            return;
        }

        $remainingQuestions = count(array_diff(
            $sessionSourceQuestions,
            array_map('intval', $tutorProgress['sources'][$source]['completed_questions'] ?? [])
        ));
            
        // Fallback: if this is the last question for the source, always create the attempt
        if ($remainingQuestions === 0) {
            $this->createTutorAttempt($user, $topic, $source, $tutorProgress['sources'][$source]);
        }
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

        // SURGICAL OPTIMIZATION: Use single time source to eliminate system call
        static $requestStartTime = null;
        if ($requestStartTime === null) {
            $requestStartTime = time();
        }
        
        $sourceDuration = $requestStartTime - $sourceProgress['start_time'];
        
        // SURGICAL OPTIMIZATION: Replace expensive max() with constant where total > 0 is guaranteed
        $totalQuestions = $sourceProgress['total'];
        if ($totalQuestions > 0) {
            $percentageGrade = ($sourceProgress['correct'] / $totalQuestions) * 100;
        } else {
            $percentageGrade = 0; // Edge case: no questions
        }

        // INSTANT OPTIMIZATION: Cache rounded calculations to avoid redundant floating-point ops
        static $calculationCache = [];
        $cacheKey = "{$sourceProgress['correct']}_{$sourceProgress['total']}_{$sourceDuration}";
        
        if (!isset($calculationCache[$cacheKey])) {
            // SURGICAL FIX: Use already calculated percentageGrade to avoid redundant calculation
            $calculationCache[$cacheKey] = [
                'percentage_grade' => round($percentageGrade, 2),
                'rounded_percentage' => round($percentageGrade, 2)
            ];
        }
        
        $cachedValues = $calculationCache[$cacheKey];

        // Generate attempt_id for tutor attempt (single source = 1 chart point)
        $attemptId = \App\Models\QuizAttempt::generateAttemptId($user->id, $topic->id);

        // OPTIMIZED: Build question data more efficiently - avoid array_unique() overhead
        $completedQuestions = $sourceProgress['completed_questions'] ?? [];
        $correctQuestionsSet = $sourceProgress['correct_questions_set'] ?? [];
        $seenQuestions = []; // Track seen questions to avoid duplicates
        $questionData = [];
        
        foreach ($completedQuestions as $questionId) {
            if (!isset($seenQuestions[$questionId])) {
                $questionData[] = [
                    'question_id' => $questionId,
                    'user_answer' => null,
                    'is_correct' => isset($correctQuestionsSet[$questionId])
                ];
                $seenQuestions[$questionId] = true;
            }
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
            'percentage_grade' => $cachedValues['percentage_grade'],
            'duration_seconds' => $sourceDuration,
            'question_data' => $questionData,
            'correct_answers' => $sourceProgress['correct'],
            'attempt_id' => $attemptId
        ];

        \App\Models\QuizAttempt::createWithProgress($attemptData, false);


    } catch (\Exception $e) {
        Log::error('Failed to create tutor attempt', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'source' => $source,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Get tutor source progress efficiently with optimized queries
 *
 * @param User $user
 * @param Topic $topic
 * @param string $source
 * @return array
 */
private function getTutorSourceProgress(User $user, Topic $topic, string $source): array
{
    $progress = $this->tutorSourceProgressService->getSourceProgress(
        $user->id,
        $topic,
        $source ?: 'General'
    );

    return [
        'completed' => (int) ($progress['completed'] ?? 0),
        'attempted' => (int) ($progress['attempted'] ?? $progress['completed'] ?? 0),
        'correct' => (int) ($progress['correct'] ?? 0),
        'total' => (int) ($progress['total'] ?? 0),
        'remaining_count' => (int) ($progress['remaining_count'] ?? 0),
        'percentage' => (float) ($progress['percentage'] ?? 0),
        'accuracy_percentage' => (float) ($progress['accuracy_percentage'] ?? 0),
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

        } catch (\Exception $e) {
            Log::error('Failed to update study streak', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
