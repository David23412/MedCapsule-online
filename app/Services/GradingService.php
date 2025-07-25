<?php

namespace App\Services;

use App\Models\User;
use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\Mistake;
use App\Models\UserTopicProgress;
use App\Services\CacheService;
use App\Services\UserStatsService;
use App\Services\NotificationService;
use App\Services\WrittenAnswerEvaluationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GradingService
{
    protected $cacheService;
    protected $userStatsService;
    protected $notificationService;
    protected $writtenAnswerEvaluationService;

    public function __construct(
        CacheService $cacheService,
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

            // Process answers and calculate scores
            $gradingResult = $this->processAnswers($user, $questions, $answers, $topic);

            // Check if this is an assignment quiz
            $isAssignment = $this->isAssignmentQuiz($user, $topic, $course, $assignmentId);

            // Create quiz attempts for each source
            $this->createQuizAttempts($user, $gradingResult['sourceData'], $duration, $mode, $topic, $isAssignment);

            // Update user statistics
            $this->userStatsService->updateAfterQuizAttempt(
                $user, 
                $gradingResult['totalQuestions'], 
                $gradingResult['totalCorrect'], 
                $duration
            );

            // Calculate and award XP
            $xpResult = $this->calculateAndAwardXP($user, $gradingResult['score'], $duration, $gradingResult['percentageGrade']);

            // Update topic progress ONLY for regular quizzes, NOT assignments
            if (!$assignmentId) {
                $this->updateTopicProgress($user, $topic, $gradingResult['score'], $gradingResult['totalQuestions']);
            } else {
                // Assignment completion - don't update regular topic progress
                Log::info('Assignment quiz completed - skipping regular topic progress update', [
                    'assignment_id' => $assignmentId,
                    'user_id' => $user->id,
                    'topic_id' => $topic->id
                ]);
            }

            // Clear relevant caches
            $this->cacheService->clearTopicProgressCaches($topic->id, $user->id, 'quiz');
            $this->cacheService->invalidateUserCaches($user->id, $topic->id);

            DB::commit();

            // Prepare response
            return $this->prepareGradingResponse($gradingResult, $questions, $answers, $xpResult);

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
        $maxAnswerLength = 2000;
        $maxTotalLength = 20000;
        $totalLength = 0;

        foreach ($answers as $qid => $answer) {
            // Skip null/empty answers (they're allowed)
            if ($answer === null || $answer === '') {
                continue;
            }
            
            if (is_string($answer) && mb_strlen($answer) > $maxAnswerLength) {
                throw new \InvalidArgumentException("Answer for question $qid exceeds maximum allowed length of $maxAnswerLength characters.");
            }
            if (is_string($answer)) {
                $totalLength += mb_strlen($answer);
            }
        }

        if ($totalLength > $maxTotalLength) {
            throw new \InvalidArgumentException("Total answer payload exceeds maximum allowed length of $maxTotalLength characters.");
        }

        // Allow empty answers array (user might not answer anything)
        if (!is_array($answers)) {
            throw new \InvalidArgumentException('Answers must be an array');
        }

        if (!is_numeric($duration) || $duration < 0) {
            throw new \InvalidArgumentException('Invalid duration');
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
            throw new \InvalidArgumentException("Questions not found: " . implode(', ', $missingIds));
        }
        
        // CACHE: Store questions for potential reuse
        $cacheKey = 'questions_' . md5(implode(',', $questionIds));
        Cache::put($cacheKey, $questions, 300); // Cache for 5 minutes
        
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

        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            
            // Handle unanswered questions (set as wrong but don't throw exception)
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
                // Record mistake
                $this->recordMistake($user, $question, $submittedAnswer);
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
        return $assignmentId !== null && \App\Models\Assignment::where('id', $assignmentId)
            ->where('student_id', $user->id)
            ->where('topic_id', $topic->id)
            ->where('course_id', $course->id)
            ->whereIn('status', [\App\Models\Assignment::STATUS_ACTIVE, \App\Models\Assignment::STATUS_OVERDUE])
            ->exists();
    }

    /**
     * Record a mistake for incorrect answers
     *
     * @param User $user
     * @param Question $question
     * @param string $submittedAnswer
     */
    private function recordMistake(User $user, Question $question, string $submittedAnswer): void
    {
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

    /**
     * Create a single quiz attempt for the entire quiz session
     *
     * @param User $user
     * @param array $sourceData
     * @param int $duration
     * @param string $mode
     * @param Topic $topic
     */
    private function createQuizAttempts(User $user, array $sourceData, int $duration, string $mode, Topic $topic, bool $isAssignment = false): void
    {
        $numSources = count($sourceData);
        $durationPerSource = $numSources > 0 ? (int) round($duration / $numSources) : $duration;
        
        // Determine quiz type based on assignment status
        $quizType = $isAssignment ? 'assignment' : 'normal';
        
        foreach ($sourceData as $source => $data) {
            $score = $data['score'];
            $totalQuestions = $data['total'];
            $percentage = $totalQuestions > 0 ? min(100, ($score / $totalQuestions) * 100) : 0;
            QuizAttempt::create([
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'quiz_type' => $quizType,
                'study_mode' => $mode,
                'source' => $source,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'percentage_grade' => $percentage,
                'duration_seconds' => $durationPerSource,
                'question_data' => $data['question_data'],
                'correct_answers' => $score
            ]);
        }
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
        $baseXP = $score * 10; // 10 XP per correct answer
        $timeBonus = max(0, 50 - floor($duration / 60)); // Up to 50 bonus XP for fast completion
        $accuracyBonus = floor($percentageGrade / 10) * 5; // Up to 50 bonus XP for high accuracy
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
        $newLevel = floor($user->xp / 1000) + 1;

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
     * @return array
     */
    private function prepareGradingResponse(array $gradingResult, $questions, array $answers, array $xpResult): array
    {
        // Get updated progress for all sources
        $sourceProgress = [];
        foreach ($gradingResult['sourceData'] as $source => $data) {
            $totalSourceQuestions = Question::where('topic_id', $questions->first()->topic_id)
                ->where('source', $source)
                ->count();
            
            $sourceProgress[$source] = [
                'score' => $data['score'],
                'total' => $data['total'],
                'total_questions_available' => $totalSourceQuestions,
                'percentage' => $data['total'] > 0 ? min(100, ($data['score'] / $data['total']) * 100) : 0
            ];
        }

        return [
            'success' => true,
            'score' => $gradingResult['score'],
            'totalQuestions' => $gradingResult['totalQuestions'],
            'total_questions' => $gradingResult['totalQuestions'],
            'percentageGrade' => $gradingResult['percentageGrade'],
            'percentage_grade' => $gradingResult['percentageGrade'],
            'encouragement' => $this->getEncouragementMessage($gradingResult['percentageGrade']),
            'source_progress' => $sourceProgress,
            'correct_answers' => $questions->mapWithKeys(function ($question) use ($answers) {
                return [$question->id => $question->correct_answer];
            }),
            'correctAnswers' => $questions->mapWithKeys(function ($question) use ($answers) {
                return [$question->id => $question->correct_answer];
            }),
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
                $this->recordMistake($user, $question, $answer);
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
        $allQuestions = Question::where('topic_id', $topic->id)->where('source', $source)->pluck('id')->toArray();
        return count($allQuestions) > 0 && !array_diff($allQuestions, [ $question->id ]);
    }

    /**
     * Create tutor attempt record
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
            $questionData = [];
            foreach (array_unique($sourceProgress['completed_questions']) as $questionId) {
                $questionData[] = [
                    'question_id' => $questionId,
                    'user_answer' => null,
                    'is_correct' => true
                ];
            }
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'quiz_type' => 'normal',
                'study_mode' => 'tutor',
                'source' => $source,
                'score' => $sourceProgress['correct'],
                'total_questions' => $sourceProgress['total'],
                'percentage_grade' => ($sourceProgress['correct'] / max(1, $sourceProgress['total'])) * 100,
                'duration_seconds' => $sourceDuration,
                'question_data' => $questionData,
                'correct_answers' => $sourceProgress['correct']
            ]);
            $this->userStatsService->updateAfterQuizAttempt(
                $user,
                $sourceProgress['total'],
                $sourceProgress['correct'],
                $sourceDuration
            );
            Log::info('Tutor quiz attempt created successfully', [
                'attempt_id' => $attempt->id,
                'user_id' => $user->id,
                'topic_id' => $topic->id,
                'source' => $source
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
        $tutorProgress = session()->get('tutor_progress', ['sources' => []]);
        $source = $source ?: 'General';

        if (!isset($tutorProgress['sources'][$source])) {
            return ['completed' => 0, 'total' => 0, 'percentage' => 0];
        }

        $totalQuestionsInSource = Question::where('topic_id', $topic->id)
            ->where('source', $source)
            ->count();

        $completedQuestionsInSource = count(array_unique($tutorProgress['sources'][$source]['completed_questions']));
        $progressPercentage = $totalQuestionsInSource > 0 ? ($completedQuestionsInSource / $totalQuestionsInSource) * 100 : 0;

        return [
            'completed' => $completedQuestionsInSource,
            'total' => $totalQuestionsInSource,
            'percentage' => round($progressPercentage, 2)
        ];
    }
} 