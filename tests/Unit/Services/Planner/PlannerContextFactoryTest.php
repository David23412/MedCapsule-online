<?php

namespace Tests\Unit\Services\Planner;

use App\Models\Course;
use App\Models\Mistake;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\StudyPlan;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopicProgress;
use App\Services\Planner\PlannerContextFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlannerContextFactoryTest extends TestCase
{
    use RefreshDatabase;

    private PlannerContextFactory $factory;
    private User $user;
    private Course $course;
    private Topic $weakTopic;
    private Topic $secureTopic;
    private StudyPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = app(PlannerContextFactory::class);
        $this->user = User::factory()->student()->create([
            'daily_study_minutes' => 120,
            'planner_focus' => 'balanced',
            'exam_date' => now()->addDays(30)->toDateString(),
            'repair_mode' => 'hard',
            'classic_repair_difficulty' => 'hard',
            'has_sr_access' => true,
        ]);
        $this->course = Course::create([
            'name' => 'Physiology',
            'description' => 'Planner context course',
            'type' => 'medical',
            'academic_year' => '2026-1',
            'price' => 0,
        ]);
        DB::table('course_user')->insert([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'is_enrolled' => true,
            'access_level' => User::COURSE_ACCESS_FULL,
            'planner_exam_date' => now()->addDays(5)->toDateString(),
            'planner_daily_study_minutes' => 45,
            'planner_focus' => 'revision',
            'planner_preferred_source' => 'Cairo',
            'planner_repair_mode' => 'spaced',
            'planner_classic_repair_difficulty' => 'easy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->weakTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Hemodynamics',
            'case_type' => 'quiz',
            'display_order' => 1,
            'is_locked' => false,
        ]);
        $this->secureTopic = Topic::create([
            'course_id' => $this->course->id,
            'name' => 'Cardiac output',
            'case_type' => 'quiz',
            'display_order' => 2,
            'is_locked' => false,
        ]);

        $this->plan = StudyPlan::create([
            'user_id' => $this->user->id,
            'plan_date' => now()->toDateString(),
            'daily_goal' => 4,
            'questions_completed' => 0,
            'tasks' => [],
            'topics' => [],
            'is_committed' => true,
        ]);
    }

    public function test_it_prefers_course_overrides_and_uses_rolling_source_fallback_when_samples_are_small(): void
    {
        $weakQuestion = $this->createQuestion($this->weakTopic, 'Cairo', 'Cardiac cycle');
        $this->createQuestion($this->weakTopic, 'Assuit', 'Cardiac valves');

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'percentage_grade' => 58,
            'last_attempt_date' => now()->subDay(),
        ]);

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 6,
            'correct_answers' => 6,
            'total_questions' => 10,
            'percentage_grade' => 60,
            'duration_seconds' => 600,
            'question_data' => [],
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        Mistake::create([
            'user_id' => $this->user->id,
            'question_id' => $weakQuestion->id,
            'submitted_answer' => 'B',
            'question_text' => $weakQuestion->question,
            'correct_answer' => $weakQuestion->correct_answer,
            'last_attempt_date' => now()->subDay(),
            'last_reviewed_at' => now()->subDay(),
            'next_review_at' => now()->subHours(2),
            'difficulty' => 0.52,
            'difficulty_baseline' => 0.52,
            'stability' => 1.0,
            'retrievability' => 0.62,
            'review_count' => 1,
            'lapse_count' => 1,
            'mastered' => false,
            'skipped' => false,
        ]);

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->secureTopic->id,
            'percentage_grade' => 88,
            'last_attempt_date' => now()->subHours(12),
        ]);

        $context = $this->factory->buildForCourse(
            $this->user,
            $this->course,
            collect([$this->weakTopic->fresh()->load('userProgress'), $this->secureTopic->fresh()->load('userProgress')]),
            $this->plan
        );

        $this->assertSame(45, $context->dailyStudyMinutes);
        $this->assertSame('revision', $context->plannerFocus);
        $this->assertSame('Cairo', $context->preferredSource);
        $this->assertSame('spaced', $context->repairMode);
        $this->assertSame('easy', $context->classicRepairDifficulty);
        $this->assertSame(now()->addDays(5)->format('Y-m-d'), $context->examDate?->format('Y-m-d'));

        $this->assertSame(1, data_get($context->topicSignals, 'source_samples.' . $this->weakTopic->id . '.Cairo'));
        $this->assertSame(
            60.0,
            (float) data_get($context->topicSignals, 'source_percentages.' . $this->weakTopic->id . '.Cairo')
        );
        $this->assertGreaterThan(
            (float) data_get($context->topicSignals, 'profiles.' . $this->secureTopic->id . '.priority_score', 0),
            (float) data_get($context->topicSignals, 'profiles.' . $this->weakTopic->id . '.priority_score', 0)
        );
    }

    public function test_it_infers_spaced_repair_mode_from_recent_course_activity_when_no_override_exists(): void
    {
        DB::table('course_user')
            ->where('user_id', $this->user->id)
            ->where('course_id', $this->course->id)
            ->update([
                'planner_repair_mode' => null,
                'planner_classic_repair_difficulty' => null,
            ]);

        $this->user->forceFill([
            'repair_mode' => null,
            'classic_repair_difficulty' => null,
        ])->save();

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'quiz_type' => 'mistake_review',
            'study_mode' => 'mistake_review',
            'source' => 'Cairo',
            'score' => 4,
            'correct_answers' => 4,
            'total_questions' => 5,
            'percentage_grade' => 80,
            'duration_seconds' => 420,
            'question_data' => [],
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $context = $this->factory->buildForCourse(
            $this->user->fresh(),
            $this->course,
            collect([$this->weakTopic->fresh()->load('userProgress')]),
            $this->plan
        );

        $this->assertSame('spaced', $context->repairMode);
        $this->assertSame('easy', $context->classicRepairDifficulty);
    }

    public function test_it_downweights_random_quiz_attempts_in_rolling_source_accuracy(): void
    {
        $question = $this->createQuestion($this->weakTopic, 'Cairo', 'Frank-Starling');

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'percentage_grade' => 0,
            'last_attempt_date' => now()->subDay(),
        ]);

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'session_type' => null,
            'source' => 'Cairo',
            'score' => 10,
            'correct_answers' => 10,
            'total_questions' => 10,
            'percentage_grade' => 100,
            'duration_seconds' => 600,
            'question_data' => [['question_id' => $question->id, 'is_correct' => true]],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'quiz_type' => 'random',
            'study_mode' => 'random',
            'session_type' => 'random_quiz',
            'source' => 'Cairo',
            'score' => 0,
            'correct_answers' => 0,
            'total_questions' => 10,
            'percentage_grade' => 0,
            'duration_seconds' => 600,
            'question_data' => [['question_id' => $question->id, 'is_correct' => false]],
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $context = $this->factory->buildForCourse(
            $this->user,
            $this->course,
            collect([$this->weakTopic->fresh()->load('userProgress')]),
            $this->plan
        );

        $this->assertEqualsWithDelta(
            57.14,
            (float) data_get($context->topicSignals, 'source_percentages.' . $this->weakTopic->id . '.Cairo'),
            0.01
        );
    }

    public function test_stage_two_pass_uses_preferred_source_questions_and_only_falls_back_when_none_exist(): void
    {
        $this->createQuestion($this->weakTopic, 'Cairo', 'Preferred concept');
        $this->createQuestion($this->weakTopic, 'Assuit', 'Other concept');
        $this->createQuestion($this->secureTopic, 'Assuit', 'Fallback concept');

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'percentage_grade' => 90,
            'last_attempt_date' => now()->subDay(),
        ]);
        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->secureTopic->id,
            'percentage_grade' => 80,
            'last_attempt_date' => now()->subDay(),
        ]);

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 1,
            'correct_answers' => 1,
            'total_questions' => 1,
            'percentage_grade' => 100,
            'duration_seconds' => 120,
            'question_data' => [],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
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
        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->secureTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Assuit',
            'score' => 8,
            'correct_answers' => 8,
            'total_questions' => 10,
            'percentage_grade' => 80,
            'duration_seconds' => 600,
            'question_data' => [],
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $context = $this->factory->buildForCourse(
            $this->user,
            $this->course,
            collect([
                $this->weakTopic->fresh()->load('userProgress'),
                $this->secureTopic->fresh()->load('userProgress'),
            ]),
            $this->plan
        );

        $this->assertFalse((bool) data_get($context->topicSignals, 'profiles.' . $this->weakTopic->id . '.stage_two_pass_ready'));
        $this->assertSame(1, (int) data_get($context->topicSignals, 'profiles.' . $this->weakTopic->id . '.stage_two_question_count'));
        $this->assertFalse((bool) data_get($context->topicSignals, 'profiles.' . $this->weakTopic->id . '.stage_two_fallback_used'));

        $this->assertTrue((bool) data_get($context->topicSignals, 'profiles.' . $this->secureTopic->id . '.stage_two_pass_ready'));
        $this->assertTrue((bool) data_get($context->topicSignals, 'profiles.' . $this->secureTopic->id . '.stage_two_fallback_used'));
        $this->assertSame(10, (int) data_get($context->topicSignals, 'profiles.' . $this->secureTopic->id . '.stage_two_question_count'));
    }

    public function test_frontier_topic_can_preclear_tutor_when_existing_accuracy_is_already_strong(): void
    {
        $this->createQuestion($this->weakTopic, 'Cairo', 'Preclear concept');

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'percentage_grade' => 82,
            'last_attempt_date' => now()->subDay(),
        ]);

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 8,
            'correct_answers' => 8,
            'total_questions' => 10,
            'percentage_grade' => 80,
            'duration_seconds' => 600,
            'question_data' => [],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $context = $this->factory->buildForCourse(
            $this->user,
            $this->course,
            collect([$this->weakTopic->fresh()->load('userProgress')]),
            $this->plan
        );

        $this->assertSame('cleared', data_get($context->topicSignals, 'profiles.' . $this->weakTopic->id . '.progression_stage'));
        $this->assertFalse((bool) data_get($context->topicSignals, 'profiles.' . $this->weakTopic->id . '.is_untouched'));
        $this->assertNotNull(data_get($context->topicSignals, 'profiles.' . $this->weakTopic->id . '.resolved_tutor_cleared_at'));
    }

    public function test_it_handles_older_quiz_attempt_schemas_without_session_type(): void
    {
        if (Schema::hasColumn('quiz_attempts', 'session_type')) {
            Schema::table('quiz_attempts', function ($table) {
                $table->dropColumn('session_type');
            });
        }

        UserTopicProgress::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'percentage_grade' => 0,
            'last_attempt_date' => now()->subDay(),
        ]);

        QuizAttempt::create([
            'user_id' => $this->user->id,
            'topic_id' => $this->weakTopic->id,
            'quiz_type' => 'normal',
            'study_mode' => 'quiz',
            'source' => 'Cairo',
            'score' => 8,
            'correct_answers' => 8,
            'total_questions' => 10,
            'percentage_grade' => 80,
            'duration_seconds' => 600,
            'question_data' => [],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $context = $this->factory->buildForCourse(
            $this->user,
            $this->course,
            collect([$this->weakTopic->fresh()->load('userProgress')]),
            $this->plan
        );

        $this->assertSame(
            80.0,
            (float) data_get($context->topicSignals, 'topic_rolling_accuracy.' . $this->weakTopic->id)
        );
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
}
