<?php

namespace App\Services;

use App\Models\Mistake;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class QuizMistakeService
{
    /**
     * Get mistakes with smart caching
     * FIXED: Extracted from fat controller for better separation of concerns
     */
    public function getMistakesWithCache(int $userId, ?string $courseFilter): Collection
    {
        $cacheKey = "mistakes_smart_{$userId}_{$courseFilter}_v3";
        
        return Cache::remember($cacheKey, 300, function() use ($userId, $courseFilter) {
            return $this->getMistakesFromDatabase($userId, $courseFilter);
        });
    }

    /**
     * FIXED: Extract database query logic to eliminate duplication
     */
    private function getMistakesFromDatabase(int $userId, ?string $courseFilter): Collection
    {
        $query = DB::table('mistakes')
            ->join('questions', 'mistakes.question_id', '=', 'questions.id')
            ->join('topics', 'questions.topic_id', '=', 'topics.id')
            ->join('courses', 'topics.course_id', '=', 'courses.id')
            ->select([
                'mistakes.id',
                'mistakes.question_id',
                'mistakes.submitted_answer',
                'mistakes.last_attempt_date',
                'mistakes.correct_streak',
                'mistakes.skipped',
                'questions.question',
                'questions.correct_answer',
                'questions.image_url',
                'questions.explanation',
                'questions.question_type',
                'questions.option_a',
                'questions.option_b',
                'questions.option_c',
                'questions.option_d',
                'topics.id as topic_id',
                'topics.name as topic_name',
                'courses.id as course_id',
                'courses.name as course_name'
            ])
            ->where('mistakes.user_id', $userId);

        $this->applyCourseFilter($query, $courseFilter);

        $rawData = $query->orderByDesc('mistakes.last_attempt_date')->get();

        return $this->transformMistakesData($rawData);
    }

    /**
     * FIXED: Extract course filter logic to eliminate duplication
     */
    private function applyCourseFilter($query, ?string $courseFilter): void
    {
        if ($courseFilter === 'skipped') {
            $query->where(function($q) {
                $q->where('mistakes.skipped', true)
                  ->orWhere('mistakes.submitted_answer', 'unanswered')
                  ->orWhere('mistakes.submitted_answer', '')
                  ->orWhereNull('mistakes.submitted_answer');
            });
        } elseif ($courseFilter) {
            $query->where('courses.id', $courseFilter);
        }
    }

    /**
     * FIXED: Extract data transformation logic to eliminate duplication
     */
    private function transformMistakesData(Collection $rawData): Collection
    {
        return $rawData->map(function($mistake) {
            $submittedAnswer = $this->normalizeSubmittedAnswer($mistake->submitted_answer, $mistake->skipped);
            
            return (object) [
                'id' => $mistake->id,
                'question_id' => $mistake->question_id,
                'submitted_answer' => $submittedAnswer,
                'last_attempt_date' => Carbon::parse($mistake->last_attempt_date),
                'correct_streak' => $mistake->correct_streak,
                'skipped' => (bool) $mistake->skipped,
                'question' => (object) [
                    'id' => $mistake->question_id,
                    'question' => $mistake->question,
                    'correct_answer' => $mistake->correct_answer,
                    'image_url' => $mistake->image_url,
                    'explanation' => $mistake->explanation,
                    'question_type' => $mistake->question_type,
                    'option_a' => $mistake->option_a,
                    'option_b' => $mistake->option_b,
                    'option_c' => $mistake->option_c,
                    'option_d' => $mistake->option_d,
                    'topic' => (object) [
                        'id' => $mistake->topic_id,
                        'name' => $mistake->topic_name,
                        'course' => (object) [
                            'id' => $mistake->course_id,
                            'name' => $mistake->course_name
                        ]
                    ]
                ]
            ];
        });
    }

    /**
     * FIXED: Extract answer normalization logic to eliminate duplication
     */
    private function normalizeSubmittedAnswer($submittedAnswer, $isSkipped): string
    {
        if ($isSkipped || 
            $submittedAnswer === 'unanswered' || 
            $submittedAnswer === '' || 
            $submittedAnswer === null) {
            return 'unanswered';
        }

        return $submittedAnswer;
    }
}
