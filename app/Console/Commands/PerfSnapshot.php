<?php

namespace App\Console\Commands;

use App\Http\Controllers\TopicController;
use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PerfSnapshot extends Command
{
    protected $signature = 'perf:snapshot {--endpoint=quiz-grade} {--iterations=200}';
    protected $description = 'Collect lightweight latency/query metrics for hot endpoints.';

    public function handle(): int
    {
        $endpoint = (string) $this->option('endpoint');
        $iterations = max(1, (int) $this->option('iterations'));

        $user = User::query()->first();
        $course = Course::query()->first();
        $topic = Topic::where('course_id', $course?->id)->first();
        $questions = Question::where('topic_id', $topic?->id)->take(2)->get();

        if (!$user || !$course || !$topic || $questions->isEmpty()) {
            $this->error('Missing seed data (user/course/topic/questions)');
            return self::FAILURE;
        }

        $latencies = [];
        $queryCounts = [];
        $queryTimes = [];

        $topicController = app(TopicController::class);
        $gradingService = app(GradingService::class);
        $progressInvalidator = app(\App\Services\ProgressCacheInvalidator::class);

        request()->setUserResolver(static fn () => $user);
        Auth::setUser($user);

        $redisBefore = $this->getRedisCommandstats();

        $slowQueries = [];
        $invalidationCalls = [];
        $broadInvalidationCalls = [];

        for ($i = 0; $i < $iterations; $i++) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $progressInvalidator->resetPerfCounters();

            $start = microtime(true);

            switch ($endpoint) {
                case 'quiz-grade':
                    $answers = [];
                    foreach ($questions as $q) {
                        $answers[$q->id] = $q->correct_answer;
                    }
                    $gradingService->gradeQuiz($user, $answers, 45, 'quiz', $topic, $course, null);
                    break;

                case 'topic-page':
                    $topicController->forCourse($course->id);
                    break;

                case 'source-progress':
                    request()->merge(['mode' => 'quiz']);
                    $topicController->getSourceProgress($topic);
                    break;

                default:
                    $this->error('Unknown endpoint. Use quiz-grade|topic-page|source-progress');
                    return self::FAILURE;
            }

            $latencies[] = (microtime(true) - $start) * 1000;
            $log = DB::getQueryLog();
            $queryCounts[] = count($log);
            $queryTimes[] = array_sum(array_column($log, 'time'));

            foreach ($log as $entry) {
                $slowQueries[] = [
                    'time' => (float) ($entry['time'] ?? 0),
                    'query' => preg_replace('/\s+/', ' ', trim((string) ($entry['query'] ?? ''))),
                    'bindings_count' => is_array($entry['bindings'] ?? null) ? count($entry['bindings']) : 0,
                ];
            }

            $counters = $progressInvalidator->getPerfCounters();
            $invalidationCalls[] = (int) ($counters['invalidation_calls'] ?? 0);
            $broadInvalidationCalls[] = (int) ($counters['broad_invalidation_calls'] ?? 0);
        }

        $redisAfter = $this->getRedisCommandstats();
        $redisDelta = $this->redisDelta($redisBefore, $redisAfter, ['keys', 'scan', 'del', 'mget', 'get', 'set']);

        usort($slowQueries, static fn (array $a, array $b) => $b['time'] <=> $a['time']);
        $topSlow = array_slice($slowQueries, 0, 10);

        $this->line('endpoint=' . $endpoint);
        $this->line('iterations=' . $iterations);
        $this->line('scan_invalidation_enabled=' . (config('features.use_scan_invalidation') ? '1' : '0'));
        $this->line('p50_ms=' . round($this->pctl($latencies, 50), 2));
        $this->line('p95_ms=' . round($this->pctl($latencies, 95), 2));
        $this->line('p99_ms=' . round($this->pctl($latencies, 99), 2));
        $this->line('avg_query_count=' . round(array_sum($queryCounts) / count($queryCounts), 2));
        $this->line('avg_db_time_ms=' . round(array_sum($queryTimes) / count($queryTimes), 2));
        $this->line('avg_invalidation_calls=' . round(array_sum($invalidationCalls) / max(1, count($invalidationCalls)), 2));
        $this->line('avg_broad_invalidation_calls=' . round(array_sum($broadInvalidationCalls) / max(1, count($broadInvalidationCalls)), 2));
        $this->line('redis_keys_delta=' . ($redisDelta['keys'] ?? 0));
        $this->line('redis_scan_delta=' . ($redisDelta['scan'] ?? 0));
        $this->line('redis_del_delta=' . ($redisDelta['del'] ?? 0));
        $this->line('redis_mget_delta=' . ($redisDelta['mget'] ?? 0));
        $this->line('redis_get_delta=' . ($redisDelta['get'] ?? 0));
        $this->line('redis_set_delta=' . ($redisDelta['set'] ?? 0));

        foreach ($topSlow as $idx => $query) {
            $this->line(sprintf(
                'slow_query_%d_ms=%s sql="%s" bindings_count=%d',
                $idx + 1,
                round((float) ($query['time'] ?? 0), 2),
                (string) ($query['query'] ?? ''),
                (int) ($query['bindings_count'] ?? 0)
            ));
        }

        // quick cache-hit inference for progress key
        if ($endpoint === 'source-progress' || $endpoint === 'quiz-grade') {
            $progressKey = "topic_progress_{$user->id}_{$topic->id}_quiz";
            Cache::forget($progressKey);
            DB::flushQueryLog();
            DB::enableQueryLog();
            \App\Models\QuizAttempt::getTopicProgress($user->id, $topic->id, 'quiz');
            $miss = count(DB::getQueryLog());
            DB::flushQueryLog();
            DB::enableQueryLog();
            \App\Models\QuizAttempt::getTopicProgress($user->id, $topic->id, 'quiz');
            $hit = count(DB::getQueryLog());
            $this->line('progress_cache_miss_queries=' . $miss);
            $this->line('progress_cache_hit_queries=' . $hit);
        }

        return self::SUCCESS;
    }

    private function pctl(array $values, int $p): float
    {
        sort($values);
        $n = count($values);
        if ($n === 0) {
            return 0;
        }
        $idx = (int) max(0, min($n - 1, ceil(($p / 100) * $n) - 1));
        return (float) $values[$idx];
    }

    private function getRedisCommandstats(): array
    {
        $raw = Redis::command('INFO', ['commandstats']);
        if (!is_string($raw)) {
            return [];
        }

        $stats = [];
        foreach (explode("\n", $raw) as $line) {
            if (!str_starts_with($line, 'cmdstat_')) {
                continue;
            }

            if (!preg_match('/^cmdstat_(\w+):calls=(\d+)/', trim($line), $m)) {
                continue;
            }

            $stats[strtolower($m[1])] = (int) $m[2];
        }

        return $stats;
    }

    private function redisDelta(array $before, array $after, array $commands): array
    {
        $delta = [];
        foreach ($commands as $cmd) {
            $delta[$cmd] = (int) ($after[$cmd] ?? 0) - (int) ($before[$cmd] ?? 0);
        }

        return $delta;
    }
}
