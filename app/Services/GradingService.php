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
    public function gradeQuiz(User $user, array $answers, int $duration, string $mode, Topic $topic, Course $course): array
    {
        DB::beginTransaction();
        try {
            // Validate inputs
            $this->validateQuizInputs($answers, $duration, $mode);

            // Get questions and validate they belong to the topic
            $questions = $this->getAndValidateQuestions($answers, $topic);

            // Process answers and calculate scores
            $gradingResult = $this->processAnswers($user, $questions, $answers, $topic);

            // Create quiz attempts for each source
            $this->createQuizAttempts($user, $gradingResult['sourceData'], $duration, $mode, $topic);

            // Update user statistics
            $this->userStatsService->updateAfterQuizAttempt(
                $user, 
                $gradingResult['totalQuestions'], 
                $gradingResult['totalCorrect'], 
                $duration
            );

            // Calculate and award XP
            $xpResult = $this->calculateAndAwardXP($user, $gradingResult['score'], $duration, $gradingResult['percentageGrade']);

            // Update topic progress
            $this->updateTopicProgress($user, $topic, $gradingResult['score'], $gradingResult['totalQuestions']);

            // Clear relevant caches
            $this->cacheService->clearUserTopicProgressCache($user->id, $topic->id);
            $this->cacheService->clearUserMistakesCache($user->id, $topic->id);

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
        // Validate answer payload size
        $maxAnswerLength = 2000;
        $maxTotalLength = 20000;
        $totalLength = 0;

        foreach ($answers as $qid => $answer) {
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

        // Validate other inputs
        if (empty($answers)) {
            throw new \InvalidArgumentException('No answers provided');
        }

        if (!is_numeric($duration) || $duration < 0) {
            throw new \InvalidArgumentException('Invalid duration');
        }

        // Default to 'quiz' mode if not valid
        if (!in_array($mode, ['quiz', 'tutor'])) {
            Log::warning('Invalid mode received, defaulting to quiz mode', [
                'received_mode' => $mode
            ]);
            $mode = 'quiz';
        }
    }

    /**
     * Get and validate questions
     *
     * @param array $answers
     * @param Topic $topic
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws \InvalidArgumentException
     */
    private function getAndValidateQuestions(array $answers, Topic $topic): \Illuminate\Database\Eloquent\Collection
    {
        $questions = Question::whereIn('id', array_keys($answers))->get();

        // Verify all questions belong to this topic
        if ($questions->where('topic_id', '!=', $topic->id)->count() > 0) {
            throw new \InvalidArgumentException('Invalid questions for this topic');
        }

        if ($questions->count() === 0) {
            throw new \InvalidArgumentException('No valid questions found');
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

        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            if ($submittedAnswer === null) {
                throw new \InvalidArgumentException("Missing answer for question {$question->id}");
            }

            $isCorrect = $this->evaluateAnswer($question, $submittedAnswer);
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

            $confidence = $evaluation['confidence'] ?? 0;
            $threshold = config('quiz.written_answer_threshold', 0.7);

            return $confidence >= $threshold;
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
     * Create quiz attempts for each source
     *
     * @param User $user
     * @param array $sourceData
     * @param int $duration
     * @param string $mode
     * @param Topic $topic
     */
    private function createQuizAttempts(User $user, array $sourceData, int $duration, string $mode, Topic $topic): void
    {
        $totalQuestionsAcrossSources = array_sum(array_column($sourceData, 'total'));

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
                'duration_seconds' => $sourceDuration,
                'question_data' => $data['question_data']
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
        // Get total questions in topic for overall progress
        $totalTopicQuestions = Question::where('topic_id', $topic->id)->count();
        
        // Calculate overall progress based on total questions in topic
        $overallProgress = $totalTopicQuestions > 0 ? min(100, ($score / $totalTopicQuestions) * 100) : 0;

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
            'total' => $gradingResult['totalQuestions'],
            'percentage' => $gradingResult['percentageGrade'],
            'encouragement' => $this->getEncouragementMessage($gradingResult['percentageGrade']),
            'source_progress' => $sourceProgress,
            'correct_answers' => $questions->mapWithKeys(function ($question) use ($answers) {
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
            $isCorrect = $this->evaluateAnswer($question, $answer);

            // Get topic for progress tracking
            $topic = $question->topic;
            if (!$topic) {
                throw new \Exception('Question has no associated topic');
            }

            // Update tutor progress in session
            $this->updateTutorProgress($user, $question, $topic, $isCorrect);

            // Check if source is complete and create attempt
            $this->checkAndCreateTutorAttempt($user, $question, $topic);

            return [
                'success' => true,
                'is_correct' => $isCorrect,
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

        if ($remainingQuestions === 0) {
            $this->createTutorAttempt($user, $topic, $source, $tutorProgress['sources'][$source]);
            
            // Remove this source's progress from session
            unset($tutorProgress['sources'][$source]);
            if (empty($tutorProgress['sources'])) {
                session()->forget('tutor_progress');
            } else {
                session(['tutor_progress' => $tutorProgress]);
            }
        }
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
                'score' => $sourceProgress['correct'],
                'total_questions' => $sourceProgress['total'],
                'percentage_grade' => ($sourceProgress['correct'] / $sourceProgress['total']) * 100,
                'duration_seconds' => $sourceDuration,
                'quiz_type' => 'tutor',
                'study_mode' => null,
                'source' => null,
                'question_data' => $questionData
            ]);

            // Update user stats
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