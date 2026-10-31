<?php

namespace Tests\Feature;

use App\Models\{Assignment, Course, Question, QuizAttempt, StudyPlan, Topic, User, UserTopicSourceProgress};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, DB};
use Tests\TestCase;

class CourseSourceRenameTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $course = Course::factory()->premium()->create();
        $topics = Topic::factory()->count(2)->forCourse($course)->create();
        foreach ($topics as $topic) Question::factory()->create(['topic_id' => $topic->id, 'source' => 'Downloaded CNS']);
        $other = Course::factory()->premium()->create();
        $otherTopic = Topic::factory()->forCourse($other)->create();
        Question::factory()->create(['topic_id' => $otherTopic->id, 'source' => 'Downloaded CNS']);
        $this->actingAs(User::factory()->admin()->create());
        return [$course, $topics[0], $other, $otherTopic];
    }

    public function test_rename_updates_all_course_topics_and_preserves_related_progress_and_assignments(): void
    {
        [$course, $topic, $other] = $this->fixture();
        $student = User::factory()->student()->create();
        $question = $topic->questions()->first();
        $student->courses()->attach($course, ['is_enrolled' => true, 'planner_preferred_source' => 'Downloaded CNS']);
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'topic_id' => $topic->id, 'assigned_source' => 'Downloaded CNS']);
        $attempt = QuizAttempt::factory()->create(['user_id' => $student->id, 'topic_id' => $topic->id, 'source' => 'Downloaded CNS']);
        $progress = UserTopicSourceProgress::create([
            'user_id' => $student->id, 'topic_id' => $topic->id, 'source' => 'Downloaded CNS',
            'completed_question_ids' => [$question->id], 'correct_question_ids' => [$question->id],
            'completed_count' => 1, 'correct_count' => 1, 'total_questions' => 1,
        ]);
        $task = ['id' => 'keep-id', 'source' => 'Downloaded CNS', 'resolved_source' => 'Downloaded CNS', 'completed' => true, 'meta' => 'Tutor · Downloaded CNS · 1 question'];
        $plan = StudyPlan::create(['user_id' => $student->id, 'plan_date' => today(), 'tasks' => ['courses' => [
            $course->id => [$task], $other->id => [$task],
        ]]]);
        DB::table('planner_task_outcomes')->insert([
            'user_id' => $student->id, 'course_id' => $course->id, 'topic_id' => $topic->id, 'plan_date' => today(),
            'task_id' => 'keep-id', 'task_type' => 'study', 'source' => 'Downloaded CNS', 'task_payload' => json_encode($task),
        ]);
        $key = "user_{$student->id}_topic_{$topic->id}_progress_quiz";
        Cache::put($key, ['sources' => ['Downloaded CNS' => []]], 300);
        $quizCache = app(\App\Services\Core\UltraConsolidatedCacheService::class);
        $this->assertSame('Downloaded CNS', $quizCache->getOptimizedQuiz($student->id, $topic->id, ['all'])[0]['source']);
        $this->postJson(route('admin.course-sources.rename', $course), ['source' => 'Downloaded CNS', 'new_name' => 'Revision'])
            ->assertOk()->assertJsonPath('question_count', 2);
        $this->assertSame(2, Question::where('source', 'Revision')->count());
        $this->assertSame(1, Question::where('source', 'Downloaded CNS')->count());
        $this->assertSame('Revision', $assignment->fresh()->assigned_source);
        $this->assertSame('Revision', $attempt->fresh()->source);
        $this->assertSame($attempt->score, $attempt->fresh()->score);
        $this->assertEquals($attempt->created_at, $attempt->fresh()->created_at);
        $this->assertSame('Revision', $progress->fresh()->source);
        $this->assertSame([$question->id], $progress->fresh()->completed_question_ids);
        $this->assertSame(1, $progress->fresh()->completed_count);
        $this->assertDatabaseHas('course_user', ['user_id' => $student->id, 'course_id' => $course->id, 'planner_preferred_source' => 'Revision']);
        $tasks = $plan->fresh()->tasks['courses'];
        $this->assertSame('Revision', $tasks[$course->id][0]['source']);
        $this->assertTrue($tasks[$course->id][0]['completed']);
        $this->assertSame('keep-id', $tasks[$course->id][0]['id']);
        $this->assertSame('Downloaded CNS', $tasks[$other->id][0]['source']);
        $outcome = DB::table('planner_task_outcomes')->first();
        $this->assertSame('Revision', $outcome->source);
        $this->assertSame('Revision', json_decode($outcome->task_payload, true)['source']);
        $this->assertFalse(Cache::has($key));
        $this->assertSame('Revision', $quizCache->getOptimizedQuiz($student->id, $topic->id, ['all'])[0]['source']);
        $this->getJson(route('admin.course-sources.index', $course))->assertOk()
            ->assertExactJson(['sources' => [['name' => 'Revision', 'question_count' => 2]]]);
    }

    public function test_existing_source_names_are_combined_using_existing_spelling(): void
    {
        [$course, $topic] = $this->fixture();
        Question::factory()->create(['topic_id' => $topic->id, 'source' => 'MedCapsule']);
        $this->postJson(route('admin.course-sources.rename', $course), ['source' => 'Downloaded CNS', 'new_name' => 'MEDCAPSULE'])
            ->assertOk()->assertJsonPath('question_count', 2);
        $this->assertSame(3, Question::where('source', 'MedCapsule')->count());
        $this->assertSame(1, Question::where('source', 'Downloaded CNS')->count());
        $this->getJson(route('admin.course-sources.index', $course))->assertExactJson([
            'sources' => [['name' => 'MedCapsule', 'question_count' => 3]],
        ]);
        $this->postJson(route('admin.course-sources.rename', $course), ['source' => 'Missing source', 'new_name' => 'New name'])
            ->assertUnprocessable();
        $this->assertSame(0, Question::where('source', 'New name')->count());
    }

    public function test_combining_sources_merges_progress_without_double_counting_and_preserves_history(): void
    {
        [$course, $topic, $other] = $this->fixture();
        $student = User::factory()->student()->create();
        $oldQuestion = $topic->questions()->first();
        $newQuestion = Question::factory()->create(['topic_id' => $topic->id, 'source' => 'MedCapsule']);
        $older = now()->subDay()->startOfSecond();
        $newer = now()->startOfSecond();
        foreach (['Downloaded CNS' => [$oldQuestion->id], 'MedCapsule' => [$oldQuestion->id, $newQuestion->id]] as $source => $ids) {
            UserTopicSourceProgress::create([
                'user_id' => $student->id, 'topic_id' => $topic->id, 'source' => $source,
                'completed_question_ids' => $ids, 'correct_question_ids' => $ids,
                'completed_count' => count($ids), 'correct_count' => count($ids), 'total_questions' => count($ids),
                'last_attempt_date' => $source === 'MedCapsule' ? $older : $newer,
                'last_activity_at' => $source === 'MedCapsule' ? $newer : $older,
            ]);
        }
        $assignment = Assignment::factory()->create(['course_id' => $course->id, 'topic_id' => $topic->id, 'assigned_source' => 'Downloaded CNS']);
        $attempt = QuizAttempt::factory()->create(['user_id' => $student->id, 'topic_id' => $topic->id, 'source' => 'Downloaded CNS']);
        $task = ['sources' => ['Downloaded CNS', 'MedCapsule'], 'source' => 'Downloaded CNS', 'completed' => true];
        $plan = StudyPlan::create(['user_id' => $student->id, 'plan_date' => today(), 'tasks' => ['courses' => [$course->id => [$task], $other->id => [$task]]]]);
        $this->postJson(route('admin.course-sources.rename', $course), ['source' => 'Downloaded CNS', 'new_name' => 'MedCapsule'])->assertOk();
        $records = UserTopicSourceProgress::where('user_id', $student->id)->where('topic_id', $topic->id)->get();
        $this->assertCount(1, $records);
        $progress = $records->first();
        $this->assertSame('MedCapsule', $progress->source);
        $this->assertSame([$oldQuestion->id, $newQuestion->id], $progress->completed_question_ids);
        $this->assertSame(2, $progress->completed_count);
        $this->assertSame(2, $progress->correct_count);
        $this->assertSame(2, $progress->total_questions);
        $this->assertEquals($newer, $progress->last_attempt_date);
        $this->assertEquals($newer, $progress->last_activity_at);
        $this->assertSame('MedCapsule', $assignment->fresh()->assigned_source);
        $this->assertSame('MedCapsule', $attempt->fresh()->source);
        $this->assertSame($attempt->score, $attempt->fresh()->score);
        $this->assertSame(['MedCapsule'], $plan->fresh()->tasks['courses'][$course->id][0]['sources']);
        $this->assertSame($task, $plan->fresh()->tasks['courses'][$other->id][0]);
    }

    public function test_existing_progress_only_destination_is_supported(): void
    {
        [$course, $topic] = $this->fixture();
        $student = User::factory()->student()->create();
        UserTopicSourceProgress::create(['user_id' => $student->id, 'topic_id' => $topic->id, 'source' => 'Revision']);
        UserTopicSourceProgress::create(['user_id' => $student->id, 'topic_id' => $topic->id, 'source' => 'Downloaded CNS']);
        $this->postJson(route('admin.course-sources.rename', $course), ['source' => 'Downloaded CNS', 'new_name' => 'Revision'])->assertOk();
        $this->assertSame(1, UserTopicSourceProgress::where('user_id', $student->id)->count());
        $this->assertSame(2, Question::where('source', 'Revision')->count());
    }

    public function test_names_are_exact_and_allow_punctuation_unicode_and_case_changes(): void
    {
        [$course, $topic] = $this->fixture();
        Question::factory()->create(['topic_id' => $topic->id, 'source' => 'Downloaded CNS extra']);
        $name = 'Revision & Notes "أعصاب"';
        $this->postJson(route('admin.course-sources.rename', $course), ['source' => 'Downloaded CNS', 'new_name' => $name])->assertOk();
        $this->assertSame(2, Question::where('source', $name)->count());
        $this->assertSame(1, Question::where('source', 'Downloaded CNS extra')->count());
        $this->postJson(route('admin.course-sources.rename', $course), ['source' => $name, 'new_name' => mb_strtoupper($name)])->assertOk();
    }

    public function test_admin_only_validation_and_manage_tab(): void
    {
        [$course] = $this->fixture();
        foreach (['', 'all', 'ALL', 'Downloaded CNS', str_repeat('x', 256), "bad\nname"] as $name) {
            $this->postJson(route('admin.course-sources.rename', $course), ['source' => 'Downloaded CNS', 'new_name' => $name])->assertUnprocessable();
        }
        $this->get(route('admin.user-course', ['tab' => 'sources']))->assertOk()->assertSee('Rename Course Source')
            ->assertSee('show active" id="sources"', false);
        $this->actingAs(User::factory()->student()->create());
        $this->getJson(route('admin.course-sources.index', $course))->assertForbidden();
        $this->postJson(route('admin.course-sources.rename', $course), ['source' => 'Downloaded CNS', 'new_name' => 'Revision'])->assertForbidden();
    }

    public function test_shared_questions_cannot_be_renamed_across_course_boundaries(): void
    {
        [$course, $topic, , $otherTopic] = $this->fixture();
        $topic->questions()->first()->update(['extra_topic' => (string) $otherTopic->id]);
        $this->postJson(route('admin.course-sources.rename', $course), ['source' => 'Downloaded CNS', 'new_name' => 'Revision'])->assertUnprocessable();
        $this->assertSame(0, Question::where('source', 'Revision')->count());
    }
}
