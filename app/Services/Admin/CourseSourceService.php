<?php

namespace App\Services\Admin;

use App\Models\{Course, StudyPlan, Topic};
use App\Services\Core\UltraConsolidatedCacheService;
use App\Services\UltraFastQuizService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\{Cache, DB};
use Illuminate\Validation\ValidationException;

class CourseSourceService
{
    public function sources(Course $course): array
    {
        $sourceExpression = DB::getDriverName() === 'mysql' ? 'BINARY questions.source' : 'questions.source';
        return DB::table('questions')->whereIn('topic_id', $course->topics()->select('id'))
            ->whereNotNull('source')->where('source', '!=', '')
            ->selectRaw($sourceExpression.' as name, COUNT(*) as question_count')
            ->groupByRaw($sourceExpression)->orderBy('name')->get()
            ->map(fn ($row) => ['name' => $row->name, 'question_count' => (int) $row->question_count])->all();
    }

    public function rename(Course $course, string $old, string $new): int
    {
        $topicIds = $course->topics()->pluck('id')->all();
        $count = DB::transaction(function () use ($course, $topicIds, $old, $new) {
            Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $questions = $this->exact(DB::table('questions')->whereIn('topic_id', $topicIds), 'source', $old);
            $rows = (clone $questions)->lockForUpdate()->get(['id', 'extra_topic']);
            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['source' => 'This source is no longer available in this course. Refresh the list.']);
            }

            // Shared questions have one source field. Do not silently rename another course's content.
            $linkedIds = $rows->flatMap(fn ($row) => explode(',', (string) $row->extra_topic))
                ->map(fn ($id) => (int) trim($id))->filter()->unique()->all();
            if (Topic::whereIn('id', $linkedIds)->where('course_id', '!=', $course->id)->exists()) {
                throw ValidationException::withMessages(['source' => 'These questions are shared with another course. A course-only rename cannot change their shared source.']);
            }

            $references = [
                [DB::table('questions')->whereIn('topic_id', $topicIds), 'source'],
                [DB::table('assignments')->where('course_id', $course->id), 'assigned_source'],
                [DB::table('quiz_attempts')->whereIn('topic_id', $topicIds), 'source'],
                [DB::table('user_topic_source_progress')->whereIn('topic_id', $topicIds), 'source'],
                [DB::table('course_user')->where('course_id', $course->id), 'planner_preferred_source'],
                [DB::table('planner_task_outcomes')->where('course_id', $course->id), 'source'],
            ];
            foreach ($references as [$query, $column]) {
                $destination = (clone $query)->whereRaw('LOWER('.$column.') = LOWER(?)', [$new]);
                $destination = DB::getDriverName() === 'mysql'
                    ? $destination->whereRaw('BINARY '.$column.' != ?', [$old])
                    : $destination->where($column, '!=', $old);
                if ($destination->exists()) {
                    throw ValidationException::withMessages(['new_name' => 'That source name is already used in this course or its saved progress. Choose a different name.']);
                }
            }
            foreach ($references as [$query, $column]) {
                $this->exact($query, $column, $old)->update([$column => $new]);
            }

            StudyPlan::whereNotNull('tasks->courses->'.$course->id)->lockForUpdate()->chunkById(100, function ($plans) use ($course, $old, $new) {
                foreach ($plans as $plan) {
                    $tasks = $plan->tasks;
                    $tasks['courses'][$course->id] = $this->renamePayload($tasks['courses'][$course->id], $old, $new);
                    if (isset($tasks['planner_meta'][$course->id])) {
                        $tasks['planner_meta'][$course->id] = $this->renamePayload($tasks['planner_meta'][$course->id], $old, $new);
                    }
                    $plan->update(['tasks' => $tasks]);
                }
            });
            DB::table('planner_task_outcomes')->where('course_id', $course->id)->whereNotNull('task_payload')
                ->lockForUpdate()->chunkById(100, function ($outcomes) use ($old, $new) {
                    foreach ($outcomes as $outcome) {
                        $payload = json_decode($outcome->task_payload, true, 512, JSON_THROW_ON_ERROR);
                        $renamed = $this->renamePayload($payload, $old, $new);
                        if ($renamed !== $payload) {
                            DB::table('planner_task_outcomes')->where('id', $outcome->id)->update(['task_payload' => json_encode($renamed, JSON_THROW_ON_ERROR)]);
                        }
                    }
                });
            return $rows->count();
        }, 3);

        $cache = app(UltraConsolidatedCacheService::class);
        $fastQuiz = app(UltraFastQuizService::class);
        foreach (Topic::whereIn('id', $topicIds)->get(['id', 'course_id']) as $topic) {
            foreach (["topic:{$topic->id}:all_questions", "topic:{$topic->id}:all_sources", "api:topic:{$topic->id}:question_counts", "api:topic:{$topic->id}:sources"] as $key) {
                Cache::forget($key);
            }
            $cache->clearTopicCaches($topic->id);
            $cache->clearQuizCaches($topic->id);
            $fastQuiz->clearQuizCache($topic);
        }
        $progressPairs = DB::table('quiz_attempts')->whereIn('topic_id', $topicIds)->where('source', $new)
            ->select('user_id', 'topic_id')->distinct()->get()
            ->concat(DB::table('user_topic_source_progress')->whereIn('topic_id', $topicIds)->where('source', $new)->get(['user_id', 'topic_id']))
            ->unique(fn ($row) => $row->user_id.':'.$row->topic_id);
        foreach ($progressPairs as $pair) {
            foreach (['quiz', 'tutor', 'quick'] as $mode) {
                Cache::forget("user_{$pair->user_id}_topic_{$pair->topic_id}_progress_{$mode}");
                Cache::forget("topic_progress_{$pair->user_id}_{$pair->topic_id}_{$mode}");
            }
        }
        Cache::forget("course_{$course->id}_questions_search_payload_v10");
        Cache::forget("course_{$course->id}_questions_search_payload_v11");
        return $count;
    }

    private function exact(Builder $query, string $column, string $value): Builder
    {
        return DB::getDriverName() === 'mysql'
            ? $query->whereRaw('BINARY '.$column.' = ?', [$value])
            : $query->where($column, $value);
    }

    private function renamePayload(array $payload, string $old, string $new): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $key === 'sources'
                    ? array_map(fn ($source) => $source === $old ? $new : $source, $value)
                    : $this->renamePayload($value, $old, $new);
            } elseif (in_array($key, ['source', 'resolved_source', 'preferred_source', 'planner_preferred_source', 'assigned_source'], true) && $value === $old) {
                $payload[$key] = $new;
            } elseif ($key === 'meta' && is_string($value)) {
                $payload[$key] = implode(' · ', array_map(fn ($part) => $part === $old ? $new : $part, explode(' · ', $value)));
            }
        }
        return $payload;
    }
}
