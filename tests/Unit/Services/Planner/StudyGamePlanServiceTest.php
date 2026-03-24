<?php

namespace Tests\Unit\Services\Planner;

use App\Models\Course;
use App\Models\Mistake;
use App\Models\PlannerTaskOutcome;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\StudyPlan;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserMistakeMastery;
use App\Models\UserTopicProgress;
use App\Services\StudyGamePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudyGamePlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudyGamePlanService $service;
    private User $user;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StudyGamePlanService::class);
        $this->user = User::factory()->student()->create([
            'daily_study_minutes' => 90,
            'target_score' => 88,
            'planner_focus' => 'balanced',
            'has_sr_access' => true,
        ]);
        $this->course = Course::create([
            'name' => 'Planner runtime',
            'description' => 'Planner runtime course',
            'type' => 'medical',
            'academic_year' => '2026-1',
            'price' => 0,
        ]);
        DB::table('course_user')->insert([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_reuses_the_same_plan_within_the_stability_window_and_exposes_task_metadata(): void
    {
        [$weakTopic, $secureTopic] = $this->createPlannerTopics();
        $topics = collect([$weakTopic->fresh()->load('userProgress'), $secureTopic->fresh()->load('userProgress')]);

        $first = $this->service->buildForCourse($this->user, $this->course, $topics);
        $second = $this->service->buildForCourse($this->user, $this->course, $topics);

        $this->assertSame(37, $first['version']);
        $this->assertSame($first['version'], $second['version']);
        $this->assertSame($first['generated_at'], $second['generated_at']);
        $this->assertSame('stable_reuse', $second['trigger_reason']);
        $this->assertArrayHasKey('planned_minutes', $second);
        $this->assertArrayHasKey('planned_questions', $second);
        $this->assertArrayHasKey('resolved_settings', $second['settings']);
        $this->assertNotEmpty($second['tasks']);

        $firstTask = $second['tasks'][0];
        $this->assertArrayHasKey('priority_score', $firstTask);
        $this->assertArrayHasKey('score_components', $firstTask);
        $this->assertArrayHasKey('resolved_focus', $firstTask);
        $this->assertArrayHasKey('trigger_reason', $firstTask);
        $this->assertArrayHasKey('sizing_basis', $firstTask);
        $this->assertArrayHasKey('xp_value', $firstTask);
        $this->assertArrayHasKey('repair_mode', $firstTask);
        $this->assertArrayHasKey('target_concepts', $firstTask);
        $this->assertArrayHasKey('completion_state', $firstTask);
        $this->assertArrayHasKey('mode_label', $firstTask);
        $this->assertArrayHasKey('slot_index', $firstTask);
        $this->assertArrayHasKey('backfill_level', $firstTask);
        $this->assertArrayHasKey('counts_toward_progression', $firstTask);
    }

    public function test_it_recomputes_when_due_spaced_load_changes_materially(): void
    {
        [$weakTopic, $secureTopic, $weakQuestion] = $this->createPlannerTopics(includeQuestion: true);
        $topics = collect([$weakTopic->fresh()->load('userProgress'), $secureTopic->fresh()->load('userProgress')]);

        $initial = $this->service->buildForCourse($this->user, $this->course, $topics);
        $initialVersion = $initial['version'];

        $secondQuestion = $this->createQuestion($weakTopic, 'Cairo', 'Afterload');
        $thirdQuestion = $this->createQuestion($weakTopic, 'Assuit', 'Stroke volume');

        $this->createDueMistake($weakQuestion);
        $this->createDueMistake($secondQuestion);
        $this->createDueMistake($thirdQuestion);

        $recomputed = $this->service->buildForCourse($this->user, $this->course, $topics);
        $taskTopicIds = collect($recomputed['tasks'])
            ->pluck('topic_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->assertSame($initialVersion, $recomputed['version']);
        $this->assertContains((int) $weakTopic->id, $taskTopicIds);
    }

    public function test_it_shrinks_new_topic_tasks_when_recent_behavior_shows_abandonment(): void
    {
        $this->user->forceFill(['planner_focus' => 'new_topics'])->save();
        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Untouched topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
        for ($i = 0; $i < 14; $i++) {
            $this->createQuestion($topic, 'Cairo', 'Concept ' . $i);
        }

        $historyTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'History topic',
            'case_type' => 'quiz',
            'display_order' => 2,
            'is_locked' => false,
        ]);
        $this->createQuestion($historyTopic, 'Cairo', 'History concept');

        for ($i = 1; $i <= 5; $i++) {
            PlannerTaskOutcome::create([
                'user_id' => $this->user->id,
                'course_id' => $this->course->id,
                'plan_date' => now()->subDays($i)->toDateString(),
                'task_id' => 'start_topic_' . $i,
                'task_type' => 'start_topic',
                'study_mode' => 'tutor',
                'question_target' => 12,
                'duration_minutes' => 20,
                'is_completed' => $i === 1,
                'first_presented_at' => now()->subDays($i)->subMinutes(40),
                'last_presented_at' => now()->subDays($i),
                'completed_at' => $i === 1 ? now()->subDays($i) : null,
                'completion_latency_minutes' => $i === 1 ? 40 : null,
            ]);
        }

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $historyTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'tutor',
            'source' => 'Cairo',
            'score' => 4,
            'correct_answers' => 4,
            'total_questions' => 4,
            'percentage_grade' => 100,
            'duration_seconds' => 600,
            'question_data' => [],
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        $startTask = collect($plan['tasks'])->firstWhere('type', 'start_topic');

        $this->assertNotNull($startTask);
        $this->assertLessThanOrEqual(8, (int) $startTask['question_target']);
        $this->assertSame('new_topics', $startTask['resolved_focus']);
        $this->assertArrayHasKey('large_task_abandonment', $startTask['sizing_basis']['adjustments']);
        $this->assertArrayHasKey('completion_rate', $startTask['sizing_basis']['adjustments']);
    }

    public function test_it_routes_weak_stale_topics_into_tutor_rebuild_tasks(): void
    {
        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Stale weak topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        for ($i = 0; $i < 8; $i++) {
            $this->createQuestion($topic, 'Cairo', 'Concept ' . $i);
        }

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'percentage_grade' => 44,
            'last_attempt_date' => now()->subDays(5),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        $studyTask = collect($plan['tasks'])->first(fn (array $task) => str_starts_with((string) ($task['id'] ?? ''), 'start_topic_'));

        $this->assertNotNull($studyTask);
        $this->assertSame('start_topic', $studyTask['type']);
        $this->assertSame('tutor', $studyTask['study_mode']);
        $this->assertSame('Tutor', $studyTask['mode_label']);
        $this->assertStringStartsWith('Study:', $studyTask['title']);
    }

    public function test_it_never_labels_untouched_topics_as_repair_tasks(): void
    {
        $this->user->forceFill(['planner_focus' => 'new_topics'])->save();
        $untouchedTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Fresh topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->createQuestion($untouchedTopic, 'Cairo', 'Fresh concept ' . $i);
        }

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([$untouchedTopic->fresh()->load('userProgress')])
        );

        $startTask = collect($plan['tasks'])->firstWhere('type', 'start_topic');
        $repairTask = collect($plan['tasks'])->first(fn (array $task) => ($task['type'] ?? null) === 'repair_topic'
            && (int) ($task['topic_id'] ?? 0) === (int) $untouchedTopic->id);

        $this->assertNotNull($startTask);
        $this->assertSame('Study: Fresh topic', $startTask['title']);
        $this->assertNull($repairTask);
    }

    public function test_it_prioritizes_untouched_topics_in_course_display_order(): void
    {
        $this->user->forceFill(['planner_focus' => 'new_topics'])->save();

        $lateTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Fifth topic',
            'case_type' => 'quiz',
            'display_order' => 5,
            'is_locked' => false,
        ]);

        $earlyTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'First topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->createQuestion($lateTopic, 'Cairo', 'Late concept ' . $i);
            $this->createQuestion($earlyTopic, 'Cairo', 'Early concept ' . $i);
        }

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([
                $lateTopic->fresh()->load('userProgress'),
                $earlyTopic->fresh()->load('userProgress'),
            ])
        );

        $startTasks = collect($plan['tasks'])
            ->where('type', 'start_topic')
            ->values();

        $this->assertNotEmpty($startTasks);
        $this->assertSame((int) $earlyTopic->id, (int) ($startTasks->first()['topic_id'] ?? 0));
        $this->assertSame('Study: First topic', $startTasks->first()['title']);
    }

    public function test_it_locks_tasks_to_the_first_unfinished_topic_until_it_is_clear(): void
    {
        $this->user->forceFill(['planner_focus' => 'balanced'])->save();

        $firstTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Cardiovascular System',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        $laterTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Fracture Diagnosis',
            'case_type' => 'quiz',
            'display_order' => 5,
            'is_locked' => false,
        ]);

        $firstQuestion = $this->createQuestion($firstTopic, 'Cairo', 'Cardio concept');

        for ($i = 0; $i < 6; $i++) {
            $this->createQuestion($laterTopic, 'Cairo', 'Later concept ' . $i);
        }

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $firstTopic->id,
            'percentage_grade' => 52,
            'last_attempt_date' => now()->subDay(),
        ]);

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $laterTopic->id,
            'percentage_grade' => 20,
            'last_attempt_date' => now()->subHours(3),
        ]);

        $this->createHighRiskMistake($firstQuestion, [
            'next_review_at' => now()->addHours(6),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([
                $firstTopic->fresh()->load('userProgress'),
                $laterTopic->fresh()->load('userProgress'),
            ])
        );

        $topicIdsInTasks = collect($plan['tasks'])
            ->pluck('topic_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->assertSame([$firstTopic->id], $topicIdsInTasks);
        $this->assertNotContains($laterTopic->id, $topicIdsInTasks);
    }

    public function test_it_keeps_classic_topic_tasks_on_the_preferred_college_source_when_available(): void
    {
        DB::table('course_user')
            ->where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->update([
                'planner_preferred_source' => 'Cairo',
            ]);

        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Source-priority topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        $cairoQuestion = $this->createQuestion($topic, 'Cairo', 'Preferred concept');
        $assuitQuestion = $this->createQuestion($topic, 'Assuit', 'Other concept');

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'percentage_grade' => 48,
            'last_attempt_date' => now()->subDay(),
        ]);

        $oldAttempt = QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 10,
            'correct_answers' => 10,
            'total_questions' => 10,
            'percentage_grade' => 100,
            'duration_seconds' => 600,
            'question_data' => [],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $preferredMistake = $this->createHighRiskMistake($cairoQuestion, [
            'next_review_at' => now()->addHours(5),
        ]);
        $otherMistake = $this->createHighRiskMistake($assuitQuestion, [
            'next_review_at' => now()->addHours(6),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user->fresh(),
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        $repairTask = collect($plan['tasks'])->firstWhere('type', 'repair_topic');

        $this->assertNotNull($repairTask);
        $this->assertSame('Cairo', $repairTask['resolved_source']);
        $this->assertSame([$preferredMistake->id], $repairTask['assigned_mistake_ids']);
        $this->assertNotContains($otherMistake->id, $repairTask['assigned_mistake_ids']);
    }

    public function test_preferred_source_progress_overrides_other_source_accuracy_for_topic_unlocks(): void
    {
        DB::table('course_user')
            ->where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->update([
                'planner_preferred_source' => 'Cairo',
            ]);

        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Cardiovascular System',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        $this->createQuestion($topic, 'Cairo', 'Preferred-source concept');
        $this->createQuestion($topic, 'Assuit', 'Other-source concept');

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Assuit',
            'score' => 10,
            'correct_answers' => 10,
            'total_questions' => 10,
            'percentage_grade' => 100,
            'duration_seconds' => 600,
            'question_data' => [],
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user->fresh(),
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        $firstTask = collect($plan['tasks'])->first();

        $this->assertNotNull($firstTask);
        $this->assertSame('start_topic', $firstTask['type']); // Fallback used because no preferred attempts, stays in tutor mode
        $this->assertSame('Cairo', $firstTask['resolved_source']);
        $this->assertStringStartsWith('Study: Cardiovascular System', $firstTask['title']);
    }

    public function test_it_does_not_split_frontier_repair_tasks_by_source_without_a_preferred_source(): void
    {
        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Cardiovascular System',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        $this->clearStages($topic, 'mistakes', 'Cairo University');
        $this->clearStages($topic, 'mistakes', 'USMLE Step 1');

        $cairoQuestion = $this->createQuestion($topic, 'Cairo University', 'Cardio concept A');
        $usmleQuestion = $this->createQuestion($topic, 'USMLE Step 1', 'Cardio concept B');

        $this->createHighRiskMistake($cairoQuestion, [
            'next_review_at' => now()->addHours(4),
        ]);
        $this->createHighRiskMistake($usmleQuestion, [
            'next_review_at' => now()->addHours(5),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        $repairTasks = collect($plan['tasks'])
            ->where('type', 'repair_topic')
            ->values();

        $this->assertCount(1, $repairTasks);
        $this->assertStringStartsWith('Revise: Cardiovascular System', $repairTasks->first()['title']);
        $this->assertNull($repairTasks->first()['resolved_source']);
    }

    public function test_it_adds_a_study_task_for_an_extremely_weak_frontier_topic(): void
    {
        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Cardiovascular System',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'percentage_grade' => 0,
            'last_attempt_date' => now()->subHour(),
        ]);

        $question = $this->createQuestion($topic, 'Cairo', 'Cardio concept');
        $this->createHighRiskMistake($question, [
            'next_review_at' => now()->addHours(5),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        $studyTask = collect($plan['tasks'])->firstWhere('type', 'start_topic');

        $this->assertNotNull($studyTask);
        $this->assertStringStartsWith('Study: Cardiovascular System', $studyTask['title']);
    }

    public function test_it_uses_rapid_overview_and_blocks_other_tutor_tasks_inside_the_final_exam_gate(): void
    {
        DB::table('course_user')
            ->where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->update([
                'planner_exam_date' => now()->addDays(2)->toDateString(),
            ]);

        $this->user->forceFill(['planner_focus' => 'balanced'])->save();

        $untouchedTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Untouched topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
        $weakTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Weak stale topic',
            'case_type' => 'quiz',
            'display_order' => 2,
            'is_locked' => false,
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->createQuestion($untouchedTopic, 'Cairo', 'Untouched concept ' . $i);
            $this->createQuestion($weakTopic, 'Cairo', 'Weak concept ' . $i);
        }

        $highRiskQuestion = $this->createQuestion($weakTopic, 'Cairo', 'Fragile concept');
        $this->createHighRiskMistake($highRiskQuestion, [
            'retrievability' => 0.16,
            'difficulty' => 0.82,
            'difficulty_baseline' => 0.82,
            'lapse_count' => 3,
            'review_count' => 3,
            'next_review_at' => now()->subHours(4),
        ]);

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $weakTopic->id,
            'percentage_grade' => 48,
            'last_attempt_date' => now()->subDays(6),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user->fresh(),
            $this->course,
            collect([
                $untouchedTopic->fresh()->load('userProgress'),
                $weakTopic->fresh()->load('userProgress'),
            ])
        );

        $rapidOverviewTask = collect($plan['tasks'])->firstWhere('type', 'rapid_overview');
        $nonRapidTutorTasks = collect($plan['tasks'])
            ->filter(fn (array $task) => ($task['study_mode'] ?? null) === 'tutor' && ($task['type'] ?? null) !== 'rapid_overview');

        $this->assertNotNull($rapidOverviewTask);
        $this->assertSame('tutor', $rapidOverviewTask['study_mode']);
        $this->assertLessThanOrEqual(10, (int) $rapidOverviewTask['duration_minutes']);
        $this->assertCount(0, $nonRapidTutorTasks);
    }

    public function test_exam_gate_keeps_tutor_available_at_exactly_three_days_remaining(): void
    {
        DB::table('course_user')
            ->where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->update([
                'planner_exam_date' => now()->addDays(3)->toDateString(),
            ]);

        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Three day boundary topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->createQuestion($topic, 'Cairo', 'Boundary concept ' . $i);
        }

        $plan = $this->service->buildForCourse(
            $this->user->fresh(),
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        $taskTypes = collect($plan['tasks'])->pluck('type')->all();
        $this->assertContains('start_topic', $taskTypes);
        $this->assertNotContains('rapid_overview', $taskTypes);
    }

    public function test_planner_never_emits_mixed_quiz_sprint_in_any_strategy_path(): void
    {
        DB::table('course_user')
            ->where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->update([
                'planner_exam_date' => now()->addDays(5)->toDateString(),
            ]);

        $topicA = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Topic A',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
        $topicB = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Topic B',
            'case_type' => 'quiz',
            'display_order' => 2,
            'is_locked' => false,
        ]);

        for ($i = 0; $i < 8; $i++) {
            $this->createQuestion($topicA, 'Cairo', 'A concept ' . $i);
            $this->createQuestion($topicB, 'Cairo', 'B concept ' . $i);
        }

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $topicA->id,
            'percentage_grade' => 86,
            'last_attempt_date' => now()->subDay(),
            'planner_tutor_cleared_at' => now()->subDays(3),
            'planner_quiz_cleared_at' => now()->subDays(3),
            'planner_cleared_at' => now()->subDays(3),
        ]);
        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $topicB->id,
            'percentage_grade' => 74,
            'last_attempt_date' => now()->subHours(6),
            'planner_tutor_cleared_at' => now()->subHours(8),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user->fresh(),
            $this->course,
            collect([
                $topicA->fresh()->load('userProgress'),
                $topicB->fresh()->load('userProgress'),
            ])
        );

        $this->assertNotContains('mixed_quiz_sprint', collect($plan['tasks'])->pluck('type')->all());
    }

    public function test_slot_one_is_the_frontier_and_slots_two_and_three_are_locked_progression_previews(): void
    {
        $frontierTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Frontier topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
        $nextTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Next topic',
            'case_type' => 'quiz',
            'display_order' => 2,
            'is_locked' => false,
        ]);
        $clearedTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Cleared topic',
            'case_type' => 'quiz',
            'display_order' => 3,
            'is_locked' => false,
        ]);

        $this->createQuestion($frontierTopic, 'Cairo', 'Frontier concept');
        $this->createQuestion($nextTopic, 'Cairo', 'Next concept');
        $this->createQuestion($clearedTopic, 'Cairo', 'Cleared concept');

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $frontierTopic->id,
            'percentage_grade' => 52,
            'last_attempt_date' => now()->subHours(4),
            'planner_tutor_cleared_at' => now()->subHours(5),
        ]);
        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $nextTopic->id,
            'percentage_grade' => 0,
            'last_attempt_date' => null,
        ]);
        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $clearedTopic->id,
            'percentage_grade' => 88,
            'last_attempt_date' => now()->subDay(),
            'planner_tutor_cleared_at' => now()->subDays(3),
            'planner_quiz_cleared_at' => now()->subDays(3),
            'planner_cleared_at' => now()->subDays(3),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([
                $frontierTopic->fresh()->load('userProgress'),
                $nextTopic->fresh()->load('userProgress'),
                $clearedTopic->fresh()->load('userProgress'),
            ])
        );

        $tasks = collect($plan['tasks'])->values();

        $this->assertCount(3, $tasks);
        $this->assertSame((int) $frontierTopic->id, (int) ($tasks[0]['topic_id'] ?? 0));
        $this->assertSame('frontier_topic_quiz', $tasks[0]['type']);
        $this->assertSame(1, (int) ($tasks[0]['slot_index'] ?? 0));
        $this->assertTrue((bool) ($tasks[0]['counts_toward_progression'] ?? false));
        $this->assertFalse((bool) ($tasks[0]['is_preview'] ?? false));

        $this->assertSame(2, (int) ($tasks[1]['slot_index'] ?? 0));
        $this->assertTrue((bool) ($tasks[1]['counts_toward_progression'] ?? false)); // Repair tasks count toward progression even as previews
        $this->assertTrue((bool) ($tasks[1]['is_preview'] ?? false));
        $this->assertSame((int) $frontierTopic->id, (int) ($tasks[1]['topic_id'] ?? 0));
        $this->assertSame('repair_topic', $tasks[1]['type']);
        $this->assertSame($tasks[0]['id'], $tasks[1]['unlocks_after_task_id']);

        $this->assertSame(3, (int) ($tasks[2]['slot_index'] ?? 0));
        $this->assertFalse((bool) ($tasks[2]['counts_toward_progression'] ?? true));
        $this->assertTrue((bool) ($tasks[2]['is_preview'] ?? false));
        $this->assertSame((int) $nextTopic->id, (int) ($tasks[2]['topic_id'] ?? 0));
        $this->assertSame('start_topic', $tasks[2]['type']);
        $this->assertSame($tasks[1]['id'], $tasks[2]['unlocks_after_task_id']);
        $this->assertNotContains('mixed_quiz_sprint', $tasks->pluck('type')->all());

        $storedPlan = StudyPlan::query()->where('user_id', $this->user->id)->firstOrFail();
        $storedTasks = collect(data_get($storedPlan->tasks, 'courses.' . $this->course->id, []));

        $this->assertCount(1, $storedTasks);
        $this->assertSame($tasks[0]['id'], $storedTasks->first()['id']);
    }

    public function test_maintenance_mode_uses_real_backfill_tasks_when_no_forward_progression_preview_exists(): void
    {
        $firstTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'First cleared topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
        $secondTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Second cleared topic',
            'case_type' => 'quiz',
            'display_order' => 2,
            'is_locked' => false,
        ]);

        $firstQuestion = $this->createQuestion($firstTopic, 'Cairo', 'First concept');
        $this->createQuestion($secondTopic, 'Cairo', 'Second concept');

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $firstTopic->id,
            'percentage_grade' => 90,
            'last_attempt_date' => now()->subDay(),
            'planner_tutor_cleared_at' => now()->subDays(2),
            'planner_quiz_cleared_at' => now()->subDays(2),
            'planner_cleared_at' => now()->subDays(2),
        ]);
        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $secondTopic->id,
            'percentage_grade' => 92,
            'last_attempt_date' => now()->subDay(),
            'planner_tutor_cleared_at' => now()->subDays(2),
            'planner_quiz_cleared_at' => now()->subDays(2),
            'planner_cleared_at' => now()->subDays(2),
        ]);

        $this->createHighRiskMistake($firstQuestion, [
            'next_review_at' => now()->subHour(),
        ]);

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([
                $firstTopic->fresh()->load('userProgress'),
                $secondTopic->fresh()->load('userProgress'),
            ])
        );

        $tasks = collect($plan['tasks'])->values();

        $this->assertNotEmpty($tasks);
        $this->assertFalse((bool) ($tasks->first()['is_preview'] ?? false));
        $this->assertSame(1, (int) ($tasks->first()['slot_index'] ?? 0));
        $this->assertFalse((bool) ($tasks->first()['counts_toward_progression'] ?? true));
    }

    public function test_it_spawns_a_child_concept_repair_task_immediately_after_parent_completion(): void
    {
        $weakTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Weak stale topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        $question = $this->createQuestion($weakTopic, 'Cairo', 'Valve lesions');

        $this->clearStages($weakTopic, 'mistakes', 'Cairo');

        $this->createHighRiskMistake($question, [
            'retrievability' => 0.18,
            'difficulty' => 0.86,
            'difficulty_baseline' => 0.86,
            'lapse_count' => 3,
            'review_count' => 3,
            'next_review_at' => now()->subHours(3),
        ]);

        $topics = collect([$weakTopic->fresh()->load('userProgress')]);

        $initialPlan = $this->service->buildForCourse($this->user, $this->course, $topics);
        $parentTask = collect($initialPlan['tasks'])->firstWhere('type', 'repair_topic');

        $this->assertNotNull($parentTask);

        Mistake::query()
            ->whereIn('id', $parentTask['assigned_mistake_ids'] ?? [])
            ->update([
                'correct_streak' => 2,
                'mastered' => false,
                'next_review_at' => now()->addDays(2),
            ]);

        foreach ($parentTask['assigned_mistake_ids'] ?? [] as $mistakeId) {
            $mistake = Mistake::query()->findOrFail($mistakeId);

            UserMistakeMastery::updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'question_id' => $mistake->question_id,
                ],
                [
                    'course_id' => $this->course->id,
                    'consecutive_correct' => 2,
                    'graduated_at' => now(),
                    'last_attempt_at' => now(),
                    'last_attempt_result' => 'correct',
                    'total_attempts' => 3,
                ]
            );
        }

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $weakTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 4,
            'correct_answers' => 4,
            'total_questions' => 5,
            'percentage_grade' => 80,
            'duration_seconds' => 540,
            'question_data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $updatedPlan = $this->service->buildForCourse($this->user->fresh(), $this->course, $topics);
        $childTask = collect($updatedPlan['tasks'])->first(fn (array $task) => str_starts_with(
            (string) ($task['id'] ?? ''),
            'concept_repair_child_' . $parentTask['id']
        ));

        $this->assertNotNull($childTask);
        $this->assertSame($parentTask['id'], $childTask['spawned_from_task_id']);
        $this->assertSame('child_spawn_after_completion', $childTask['trigger_reason']);
        $this->assertSame('pending', $childTask['completion_state']);
    }

    public function test_a_quiz_attempt_does_not_complete_a_legacy_tutor_task_without_study_mode(): void
    {
        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Legacy tutor topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->createQuestion($topic, 'Cairo', 'Concept ' . $i);
        }

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'percentage_grade' => 44,
            'last_attempt_date' => now()->subDays(5),
        ]);

        $topics = collect([$topic->fresh()->load('userProgress')]);
        $initialPlan = $this->service->buildForCourse($this->user, $this->course, $topics);
        $tutorTask = collect($initialPlan['tasks'])->firstWhere('type', 'start_topic');

        $this->assertNotNull($tutorTask);

        $plan = StudyPlan::query()
            ->where('user_id', $this->user->id)
            ->whereDate('plan_date', now()->toDateString())
            ->firstOrFail();

        $tasks = $plan->tasks;
        $courseKey = (string) $this->course->id;
        $tasks['courses'][$courseKey] = collect($tasks['courses'][$courseKey] ?? [])
            ->map(function (array $task) use ($tutorTask) {
                if (($task['id'] ?? null) !== ($tutorTask['id'] ?? null)) {
                    return $task;
                }

                unset($task['study_mode'], $task['mode_label']);
                $task['completed'] = false;
                $task['completion_state'] = 'pending';
                $task['completion_progress'] = 0.0;
                $task['completed_at'] = null;

                return $task;
            })
            ->all();
        $plan->tasks = $tasks;
        $plan->save();

        $oldAttempt = QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 4,
            'correct_answers' => 4,
            'total_questions' => 5,
            'percentage_grade' => 80,
            'duration_seconds' => 540,
            'question_data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $updatedPlan = $this->service->buildForCourse($this->user, $this->course, $topics);
        $updatedTutorTask = collect($updatedPlan['tasks'])->firstWhere('id', $tutorTask['id']);

        if ($updatedTutorTask !== null) {
            $this->assertFalse((bool) $updatedTutorTask['completed']);
            $this->assertSame('pending', $updatedTutorTask['completion_state']);
            $this->assertSame('Tutor', $updatedTutorTask['mode_label']);
        }

        $completedTutorTaskForTopic = collect($updatedPlan['tasks'])->first(fn (array $task) => (int) ($task['topic_id'] ?? 0) === (int) $topic->id
            && (($task['study_mode'] ?? null) === 'tutor' || ($task['type'] ?? null) === 'start_topic')
            && (bool) ($task['completed'] ?? false));

        $this->assertNull($completedTutorTaskForTopic);
    }

    public function test_a_random_quiz_attempt_does_not_complete_a_planner_quiz_task(): void
    {
        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Strict quiz mode topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->createQuestion($topic, 'Cairo', 'Concept ' . $i);
        }

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'percentage_grade' => 82,
            'last_attempt_date' => now()->subHours(10),
        ]);

        $topics = collect([$topic->fresh()->load('userProgress')]);
        $initialPlan = $this->service->buildForCourse($this->user, $this->course, $topics);
        $quizTask = collect($initialPlan['tasks'])->firstWhere('type', 'frontier_topic_quiz');

        $this->assertNotNull($quizTask);

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'random',
            'study_mode' => 'random',
            'session_type' => 'random_quiz',
            'source' => 'Cairo',
            'score' => 5,
            'correct_answers' => 5,
            'total_questions' => 5,
            'percentage_grade' => 100,
            'duration_seconds' => 540,
            'question_data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $updatedPlan = $this->service->buildForCourse($this->user, $this->course, $topics);
        $updatedQuizTask = collect($updatedPlan['tasks'])->firstWhere('id', $quizTask['id']);
        $this->service->syncAfterSession($this->user, $this->course);

        $this->assertNotNull($updatedQuizTask);
        $this->assertFalse((bool) $updatedQuizTask['completed']);
        $this->assertSame('preview', $updatedQuizTask['completion_state']);
        $this->assertSame('Quiz', $updatedQuizTask['mode_label']);
        $this->assertNull(UserTopicProgress::query()
            ->where('user_id', $this->user->id)
            ->where('topic_id', $topic->id)
            ->value('planner_quiz_cleared_at'));
    }

    public function test_it_excludes_graduated_mastery_rows_from_classic_repair_assignments(): void
    {
        $weakTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Classic repair topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $weakTopic->id,
            'percentage_grade' => 48,
            'last_attempt_date' => now()->subDay(),
        ]);

        $this->clearStages($weakTopic, 'mistakes', 'Cairo');

        $resolvedQuestion = $this->createQuestion($weakTopic, 'Cairo', 'Resolved concept');
        $openQuestion = $this->createQuestion($weakTopic, 'Cairo', 'Open concept');

        $resolvedMistake = $this->createHighRiskMistake($resolvedQuestion, [
            'next_review_at' => now()->addHours(5),
        ]);
        $openMistake = $this->createHighRiskMistake($openQuestion, [
            'next_review_at' => now()->addHours(6),
        ]);

        UserMistakeMastery::create([
            'user_id' => $this->user->id,
            'question_id' => $resolvedQuestion->id,
            'course_id' => $this->course->id,
            'consecutive_correct' => 2,
            'graduated_at' => now()->subHour(),
            'last_attempt_at' => now()->subHour(),
            'last_attempt_result' => 'correct',
            'total_attempts' => 3,
        ]);

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([$weakTopic->fresh()->load('userProgress')])
        );

        $repairTask = collect($plan['tasks'])->firstWhere('type', 'repair_topic');

        $this->assertNotNull($repairTask);
        $this->assertSame([$openMistake->id], $repairTask['assigned_mistake_ids']);
        $this->assertNotContains($resolvedMistake->id, $repairTask['assigned_mistake_ids']);
    }

    public function test_sync_after_session_writes_frontier_stage_markers_for_progression_tasks(): void
    {
        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Marker write topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        $this->createQuestion($topic, 'Cairo', 'Marker concept');

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'percentage_grade' => 0,
            'last_attempt_date' => null,
        ]);

        $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'tutor',
            'source' => 'Cairo',
            'score' => 1,
            'correct_answers' => 1,
            'total_questions' => 1,
            'percentage_grade' => 100,
            'duration_seconds' => 420,
            'question_data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->syncAfterSession($this->user, $this->course);

        $progress = UserTopicProgress::query()
            ->where('user_id', $this->user->id)
            ->where('topic_id', $topic->id)
            ->first();

        $this->assertNotNull($progress?->planner_tutor_cleared_at);
        $this->assertNull($progress?->planner_quiz_cleared_at);
        $this->assertNull($progress?->planner_cleared_at);
    }

    public function test_real_regression_clears_quiz_and_cleared_markers_but_preserves_tutor_clear(): void
    {
        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Regression topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        $this->createQuestion($topic, 'Cairo', 'Regression concept');

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'percentage_grade' => 92,
            'last_attempt_date' => now()->subDays(3),
            'planner_tutor_cleared_at' => now()->subDays(5),
            'planner_quiz_cleared_at' => now()->subDays(4),
            'planner_cleared_at' => now()->subDays(4),
        ]);

        $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        $oldAttempt = QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 3,
            'correct_answers' => 3,
            'total_questions' => 10,
            'percentage_grade' => 30,
            'duration_seconds' => 480,
            'question_data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->syncAfterSession($this->user, $this->course);

        $progress = UserTopicProgress::query()
            ->where('user_id', $this->user->id)
            ->where('topic_id', $topic->id)
            ->first();

        $this->assertNotNull($progress?->planner_tutor_cleared_at);
        $this->assertNull($progress?->planner_quiz_cleared_at);
        $this->assertNull($progress?->planner_cleared_at);
    }

    public function test_daily_progress_uses_todays_real_attempt_duration_for_course_topics(): void
    {
        $topic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Daily time topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->createQuestion($topic, 'Cairo', 'Concept ' . $i);
        }

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 4,
            'correct_answers' => 4,
            'total_questions' => 5,
            'percentage_grade' => 80,
            'duration_seconds' => 1500,
            'question_data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'tutor',
            'study_mode' => 'tutor',
            'source' => 'Cairo',
            'score' => 1,
            'correct_answers' => 1,
            'total_questions' => 1,
            'percentage_grade' => 100,
            'duration_seconds' => 900,
            'question_data' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldAttempt = QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $topic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 3,
            'correct_answers' => 3,
            'total_questions' => 4,
            'percentage_grade' => 75,
            'duration_seconds' => 1800,
            'question_data' => [],
        ]);
        $oldAttempt->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();

        $plan = $this->service->buildForCourse(
            $this->user,
            $this->course,
            collect([$topic->fresh()->load('userProgress')])
        );

        $this->assertSame(40, $plan['daily_progress']['tracked_minutes']);
        $this->assertSame(6, $plan['daily_progress']['tracked_questions']);
        $this->assertSame(44, $plan['daily_progress']['progress_percent']);
    }

    private function clearStages(Topic $topic, string $stage = 'mistakes', ?string $source = 'Cairo'): void
    {
        if (in_array($stage, ['quiz', 'mistakes'])) {
            QuizAttempt::create([
                'user_id' => $this->user->id,
                'topic_id' => $topic->id,
                'quiz_type' => 'normal',
                'study_mode' => 'tutor',
                'source' => $source,
                'score' => 1,
                'total_questions' => 1,
                'percentage_grade' => 100,
                'duration_seconds' => 300,
                'created_at' => now()->subHours(10),
            ]);
        }

        if ($stage === 'mistakes') {
            QuizAttempt::create([
                'user_id' => $this->user->id,
                'topic_id' => $topic->id,
                'quiz_type' => 'normal',
                'study_mode' => 'quiz',
                'source' => $source,
                'score' => 8,
                'total_questions' => 10,
                'percentage_grade' => 80,
                'duration_seconds' => 600,
                'created_at' => now()->subHours(5),
            ]);
        }
    }

    private function createPlannerTopics(bool $includeQuestion = false): array
    {
        $weakTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Weak topic',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
        $secureTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Secure topic',
            'case_type' => 'quiz',
            'display_order' => 2,
            'is_locked' => false,
        ]);

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $weakTopic->id,
            'percentage_grade' => 58,
            'last_attempt_date' => now()->subDay(),
        ]);
        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $secureTopic->id,
            'percentage_grade' => 86,
            'last_attempt_date' => now()->subHours(10),
        ]);

        $weakQuestion = $this->createQuestion($weakTopic, 'Cairo', 'Cardiac cycle');
        $this->createQuestion($secureTopic, 'Assuit', 'Stroke volume');
        if (!$includeQuestion) {
            $this->createHighRiskMistake($weakQuestion, [
                'next_review_at' => now()->addHours(6),
            ]);
        }

        $this->clearStages($weakTopic, 'mistakes', 'Cairo');
        $this->clearStages($secureTopic, 'mistakes', 'Assuit');

        if ($includeQuestion) {
            return [$weakTopic, $secureTopic, $weakQuestion];
        }

        return [$weakTopic, $secureTopic];
    }

    private function createQuestion(Topic $topic, string $source, ?string $concept): Question
    {
        return Question::create([
            'topic_id' => $topic->id,
            'question' => 'What is the correct answer?',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'concept' => $concept,
            'source' => $source,
        ]);
    }

    private function createDueMistake(Question $question): Mistake
    {
        return Mistake::create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'submitted_answer' => 'B',
            'question_text' => $question->question,
            'correct_answer' => $question->correct_answer,
            'last_attempt_date' => now()->subDay(),
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subHours(3),
            'difficulty' => 0.54,
            'difficulty_baseline' => 0.54,
            'stability' => 1.1,
            'retrievability' => 0.58,
            'review_count' => 1,
            'lapse_count' => 1,
            'mastered' => false,
            'skipped' => false,
        ]);
    }

    private function createHighRiskMistake(Question $question, array $overrides = []): Mistake
    {
        return Mistake::create(array_merge([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'submitted_answer' => 'B',
            'question_text' => $question->question,
            'correct_answer' => $question->correct_answer,
            'last_attempt_date' => now()->subDay(),
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subHours(2),
            'difficulty' => 0.8,
            'difficulty_baseline' => 0.8,
            'stability' => 1.0,
            'retrievability' => 0.2,
            'review_count' => 2,
            'lapse_count' => 2,
            'mastered' => false,
            'skipped' => false,
        ], $overrides));
    }
}
