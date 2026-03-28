<?php

namespace App\Services;

use App\Models\Course;
use App\Models\SurveyDraft;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FreeCourseSurveyService
{
    public function surveyKey(): string
    {
        return (string) config('free_course_survey.survey_key', 'free_course_unlock_v1');
    }

    public function definition(): array
    {
        return config('free_course_survey');
    }

    public function questions(): array
    {
        return $this->definition()['questions'] ?? [];
    }

    public function questionMap(): array
    {
        return collect($this->questions())->keyBy('id')->all();
    }

    public function freeCourses(): Collection
    {
        return Course::query()
            ->select('id', 'name', 'access_type', 'price')
            ->surveyUnlockable()
            ->orderBy('name')
            ->get();
    }

    public function draftFor(User $user): ?SurveyDraft
    {
        return $user->surveyDrafts()
            ->where('survey_key', $this->surveyKey())
            ->latest('updated_at')
            ->first();
    }

    public function responseFor(User $user): ?SurveyResponse
    {
        return $user->surveyResponses()
            ->where('survey_key', $this->surveyKey())
            ->latest('submitted_at')
            ->first();
    }

    public function saveDraft(User $user, array $payload): SurveyDraft
    {
        return SurveyDraft::updateOrCreate(
            [
                'user_id' => $user->id,
                'survey_key' => $this->surveyKey(),
            ],
            [
                'answers' => Arr::only($payload['answers'] ?? [], array_keys($this->questionMap())),
                'current_step' => max(1, (int) ($payload['current_step'] ?? 1)),
                'redirect_to' => $this->sanitizeRedirect($payload['redirect_to'] ?? null),
                'last_saved_at' => now(),
            ]
        );
    }

    public function complete(User $user, array $answers, ?string $redirectTo = null): array
    {
        return DB::transaction(function () use ($user, $answers, $redirectTo) {
            $existingResponse = $this->responseFor($user);
            $wasUnlocked = $user->hasUnlockedFreeCourses();
            $freeCourses = $this->freeCourses()
                ->map(fn (Course $course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                ])
                ->values();
            $safeRedirect = $this->sanitizeRedirect($redirectTo)
                ?? $this->draftFor($user)?->redirect_to
                ?? route('courses.index');

            if (!$existingResponse) {
                SurveyResponse::create([
                    'user_id' => $user->id,
                    'survey_key' => $this->surveyKey(),
                    'answers' => $this->formatAnswersForStorage($answers),
                    'redirect_to' => $safeRedirect,
                    'submitted_at' => now(),
                ]);
            }

            $user->forceFill([
                'survey_completed' => true,
                'free_courses_unlocked' => true,
                'survey_completed_at' => $user->survey_completed_at ?? now(),
                'free_courses_unlocked_at' => $user->free_courses_unlocked_at ?? now(),
            ])->save();

            $user->enrollInAllFreeCourses();

            if (!$wasUnlocked) {
                $this->createUnlockNotification($user, $freeCourses, $safeRedirect);
            }

            $user->surveyDrafts()
                ->where('survey_key', $this->surveyKey())
                ->delete();

            Cache::forget("user_course_optimization_data_{$user->id}");

            session()->flash('free_course_unlock_success', config('free_course_survey.success_message'));

            return [
                'message' => (string) config('free_course_survey.success_message'),
                'redirect_to' => $safeRedirect,
            ];
        });
    }

    public function sanitizeRedirect(?string $redirectTo): ?string
    {
        if (!$redirectTo) {
            return null;
        }

        $redirectTo = trim($redirectTo);

        if ($redirectTo === '') {
            return null;
        }

        if (str_starts_with($redirectTo, 'http://') || str_starts_with($redirectTo, 'https://') || str_starts_with($redirectTo, '//')) {
            return null;
        }

        if (!str_starts_with($redirectTo, '/')) {
            return null;
        }

        return $redirectTo;
    }

    public function formatAnswersForStorage(array $answers): array
    {
        $questionMap = $this->questionMap();

        return collect($questionMap)
            ->map(function (array $question, string $questionId) use ($answers) {
                $value = $answers[$questionId] ?? null;
                $label = $question['type'] === 'single_choice'
                    ? ($question['options'][$value] ?? null)
                    : $value;

                return [
                    'id' => $questionId,
                    'prompt' => $question['prompt'],
                    'type' => $question['type'],
                    'value' => $value,
                    'label' => $label,
                    'required' => (bool) ($question['required'] ?? false),
                ];
            })
            ->values()
            ->all();
    }

    public function adminInsights(int $recentLimit = 6, int $feedbackLimit = 5): array
    {
        $surveyKey = $this->surveyKey();
        $questions = collect($this->questions())->values();
        $questionMap = $questions->keyBy('id');

        $totalResponses = SurveyResponse::query()
            ->where('survey_key', $surveyKey)
            ->count();

        $studentCount = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->count();

        $completedStudents = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where('survey_completed', true)
            ->where('free_courses_unlocked', true)
            ->count();

        $draftCount = SurveyDraft::query()
            ->where('survey_key', $surveyKey)
            ->count();

        $lastSubmittedAt = SurveyResponse::query()
            ->where('survey_key', $surveyKey)
            ->max('submitted_at');

        $aggregates = $questions->mapWithKeys(function (array $question) {
            return [
                $question['id'] => [
                    'answered_count' => 0,
                    'choices' => collect($question['options'] ?? [])->mapWithKeys(fn ($label, $value) => [
                        $value => 0,
                    ])->all(),
                ],
            ];
        })->all();

        SurveyResponse::query()
            ->where('survey_key', $surveyKey)
            ->select('id', 'answers')
            ->orderBy('id')
            ->chunkById(200, function (Collection $responses) use (&$aggregates, $questionMap) {
                foreach ($responses as $response) {
                    $indexedAnswers = $this->answersByQuestionId($response->answers ?? []);

                    foreach ($questionMap as $questionId => $question) {
                        $answer = $indexedAnswers[$questionId] ?? null;
                        $normalizedValue = $this->normalizedAnswerValue($answer);

                        if ($normalizedValue === null) {
                            continue;
                        }

                        $aggregates[$questionId]['answered_count']++;

                        if (($question['type'] ?? null) !== 'single_choice') {
                            continue;
                        }

                        if (array_key_exists($answer['value'] ?? '', $aggregates[$questionId]['choices'])) {
                            $aggregates[$questionId]['choices'][$answer['value']]++;
                        }
                    }
                }
            });

        $questionInsights = $questions->map(function (array $question) use ($aggregates, $totalResponses) {
            $questionAggregate = $aggregates[$question['id']];
            $answeredCount = $questionAggregate['answered_count'];
            $responseRate = $totalResponses > 0
                ? round(($answeredCount / $totalResponses) * 100, 1)
                : 0;

            if (($question['type'] ?? null) !== 'single_choice') {
                return [
                    'id' => $question['id'],
                    'prompt' => $question['prompt'],
                    'type' => $question['type'],
                    'answered_count' => $answeredCount,
                    'response_rate' => $responseRate,
                ];
            }

            $topCount = empty($questionAggregate['choices']) ? 0 : max($questionAggregate['choices']);

            return [
                'id' => $question['id'],
                'prompt' => $question['prompt'],
                'type' => $question['type'],
                'answered_count' => $answeredCount,
                'response_rate' => $responseRate,
                'choices' => collect($question['options'] ?? [])->map(function ($label, $value) use ($questionAggregate, $answeredCount, $topCount) {
                    $count = $questionAggregate['choices'][$value] ?? 0;

                    return [
                        'value' => $value,
                        'label' => $label,
                        'count' => $count,
                        'percentage' => $answeredCount > 0 ? round(($count / $answeredCount) * 100, 1) : 0,
                        'is_top' => $count > 0 && $count === $topCount,
                    ];
                })->values()->all(),
            ];
        })->all();

        $recentResponses = SurveyResponse::query()
            ->with('user:id,name,email')
            ->where('survey_key', $surveyKey)
            ->select('id', 'user_id', 'answers', 'submitted_at')
            ->latest('submitted_at')
            ->limit($recentLimit)
            ->get()
            ->map(function (SurveyResponse $response) {
                $indexedAnswers = $this->answersByQuestionId($response->answers ?? []);

                return [
                    'user_name' => $response->user?->name ?? 'Deleted user',
                    'user_email' => $response->user?->email,
                    'submitted_at' => $response->submitted_at,
                    'highlights' => array_filter([
                        'Year' => $indexedAnswers['q10']['label'] ?? null,
                        'Device' => $indexedAnswers['q6']['label'] ?? null,
                        'Time saved' => $indexedAnswers['q5']['label'] ?? null,
                    ]),
                ];
            })
            ->values()
            ->all();

        $feedbackSamples = SurveyResponse::query()
            ->with('user:id,name')
            ->where('survey_key', $surveyKey)
            ->select('id', 'user_id', 'answers', 'submitted_at')
            ->latest('submitted_at')
            ->limit(max($feedbackLimit * 4, 12))
            ->get()
            ->map(function (SurveyResponse $response) {
                $indexedAnswers = $this->answersByQuestionId($response->answers ?? []);
                $feedback = $this->normalizedAnswerValue($indexedAnswers['q11'] ?? null);

                if ($feedback === null) {
                    return null;
                }

                return [
                    'user_name' => $response->user?->name ?? 'Deleted user',
                    'submitted_at' => $response->submitted_at,
                    'text' => $feedback,
                ];
            })
            ->filter()
            ->take($feedbackLimit)
            ->values()
            ->all();

        return [
            'summary' => [
                'eligible_students' => $studentCount,
                'completed_students' => $completedStudents,
                'students_pending' => max($studentCount - $completedStudents, 0),
                'total_responses' => $totalResponses,
                'drafts_in_progress' => $draftCount,
                'completion_rate' => $studentCount > 0
                    ? round(($completedStudents / $studentCount) * 100, 1)
                    : 0,
                'last_submitted_at' => $lastSubmittedAt,
            ],
            'questions' => $questionInsights,
            'recent_responses' => $recentResponses,
            'feedback_samples' => $feedbackSamples,
        ];
    }

    private function answersByQuestionId(array $answers): array
    {
        return collect($answers)
            ->filter(fn ($answer) => is_array($answer) && isset($answer['id']))
            ->keyBy('id')
            ->all();
    }

    private function normalizedAnswerValue(?array $answer): ?string
    {
        if (!$answer) {
            return null;
        }

        $value = $answer['label'] ?? $answer['value'] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function createUnlockNotification(User $user, Collection $freeCourses, string $redirectTo): void
    {
        $courseNames = $freeCourses
            ->pluck('name')
            ->filter()
            ->values();

        if ($courseNames->isEmpty()) {
            return;
        }

        $courseList = $courseNames->join('، ');

        UniversalNotificationService::create(
            $user,
            'course_access_granted',
            'Free courses unlocked',
            "You now have access to the free courses: {$courseList}",
            [
                'source' => 'free_course_survey_unlock',
                'redirect_to' => $redirectTo,
                'courses' => $freeCourses->all(),
            ],
            UniversalNotificationService::PRIORITY_HIGH
        );
    }
}
