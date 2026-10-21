<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminQuestionPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_load_topics_sources_and_export_question_pdf_when_subject_column_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create(['name' => 'Pathology']);
        $firstTopic = Topic::factory()->forCourse($course)->create([
            'name' => 'Skin Pathology',
            'subject' => 'Dermatology',
            'display_order' => 2,
        ]);
        $secondTopic = Topic::factory()->forCourse($course)->create([
            'name' => 'Blood Pathology',
            'subject' => 'Hematology',
            'display_order' => 1,
        ]);

        Question::factory()->multipleChoice()->forTopic($firstTopic)->create([
            'source' => 'Lecture 32',
            'question' => 'Which process describes intercellular epidermal edema?',
            'correct_answer' => 'B',
            'option_e' => '-',
        ]);
        Question::factory()->multipleChoice()->forTopic($secondTopic)->create([
            'source' => 'Lecture 32',
            'question' => 'Which anemia is microcytic?',
            'correct_answer' => 'A',
        ]);
        Question::factory()->essay()->forTopic($secondTopic)->create([
            'source' => 'Lecture 32',
            'question' => 'Explain the pathogenesis of osteomyelitis.',
            'correct_answer' => 'Written answer rubric',
        ]);
        Question::factory()->multipleChoice()->forTopic($firstTopic)->create([
            'source' => 'Other File',
            'question' => 'This source is not selected.',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.question-pdf.topics', $course))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('topics.0.name', 'Blood Pathology')
            ->assertJsonPath('topics.0.subject', 'Hematology')
            ->assertJsonPath('topics.1.name', 'Skin Pathology');

        $this->actingAs($admin)
            ->getJson(route('admin.question-pdf.sources', [
                'course_id' => $course->id,
                'topic_ids' => [$firstTopic->id, $secondTopic->id],
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'Lecture 32', 'questions_count' => 3]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.question-pdf.export'), [
                '_token' => 'test-token',
                'course_id' => $course->id,
                'topic_ids' => [$firstTopic->id, $secondTopic->id],
                'sources' => ['Lecture 32'],
                'include_answer_key' => '1',
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_load_topics_sources_and_export_question_pdf_when_subject_column_is_missing(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->premium()->create(['name' => 'Pathology']);
        $firstTopic = Topic::factory()->forCourse($course)->create([
            'name' => 'Skin Pathology',
            'display_order' => 2,
        ]);
        $secondTopic = Topic::factory()->forCourse($course)->create([
            'name' => 'Blood Pathology',
            'display_order' => 1,
        ]);

        Question::factory()->multipleChoice()->forTopic($firstTopic)->create([
            'source' => 'Lecture 32',
            'question' => 'Which process describes intercellular epidermal edema?',
            'correct_answer' => 'B',
        ]);
        Question::factory()->multipleChoice()->forTopic($secondTopic)->create([
            'source' => 'Lecture 32',
            'question' => 'Which anemia is microcytic?',
            'correct_answer' => 'A',
        ]);

        foreach (Schema::getIndexes('topics') as $index) {
            if (in_array('subject', $index['columns'], true)) {
                Schema::table('topics', fn ($table) => $table->dropIndex($index['name']));
            }
        }

        Schema::table('topics', function ($table) {
            $table->dropColumn('subject');
        });

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->actingAs($admin)
            ->getJson(route('admin.question-pdf.topics', $course))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('topics.0.name', 'Blood Pathology')
            ->assertJsonPath('topics.0.subject', '')
            ->assertJsonPath('topics.1.name', 'Skin Pathology');

        $this->actingAs($admin)
            ->getJson(route('admin.question-pdf.sources', [
                'course_id' => $course->id,
                'topic_ids' => [$firstTopic->id, $secondTopic->id],
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'Lecture 32', 'questions_count' => 2]);

        $this->actingAs($admin)
            ->withSession(['_token' => 'test-token'])
            ->post(route('admin.question-pdf.export'), [
                '_token' => 'test-token',
                'course_id' => $course->id,
                'topic_ids' => [$firstTopic->id, $secondTopic->id],
                'sources' => ['Lecture 32'],
                'include_answer_key' => '1',
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $unsafeTopicSubjectQueries = collect($queries)->filter(function (string $sql): bool {
            $normalized = strtolower($sql);

            return (str_contains($normalized, 'from "topics"') || str_contains($normalized, 'from `topics`'))
                && preg_match('/\\bsubject\\b/', $normalized);
        });

        $this->assertCount(0, $unsafeTopicSubjectQueries, $unsafeTopicSubjectQueries->implode("\n"));
    }

    public function test_explanations_are_optional_numbered_and_separate_from_questions(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create();
        $first = Topic::factory()->forCourse($course)->create(['display_order' => 1]);
        $second = Topic::factory()->forCourse($course)->create(['display_order' => 2]);
        Question::factory()->multipleChoice()->forTopic($first)->create(['source' => 'Review', 'explanation' => "Clinical note <script>unsafe</script>\nSecond paragraph."]);
        Question::factory()->multipleChoice()->forTopic($first)->create(['source' => 'Review', 'explanation' => "   \n"]);
        Question::factory()->essay()->forTopic($second)->create(['source' => 'Review', 'explanation' => 'Written explanation.']);
        $data = [];
        \Illuminate\Support\Facades\View::composer('admin.question-pdf.pdf', function ($view) use (&$data) {
            $data = $view->getData();
        });
        $payload = ['course_id' => $course->id, 'topic_ids' => [$first->id, $second->id], 'sources' => ['Review']];
        $this->actingAs($admin)->get(route('admin.question-pdf.index'))->assertOk()->assertSee('Include explanations');
        foreach ([null, false, true] as $enabled) {
            $request = $payload;
            if ($enabled !== null) $request['include_explanations'] = $enabled;
            $this->post(route('admin.question-pdf.export'), $request)->assertOk()->assertHeader('content-type', 'application/pdf');
            $html = view('admin.question-pdf.pdf', $data)->render();
            $this->assertSame($enabled === true, $data['includeExplanations']);
            if ($enabled === true) {
                $this->assertSame(2, substr_count($html, '<div class="content explanations">'));
                $this->assertStringContainsString('Clinical note &lt;script&gt;unsafe&lt;/script&gt;', $html);
                $this->assertStringContainsString('<h3 class="explanation-heading">Q3', $html);
                $this->assertStringNotContainsString('<h3 class="explanation-heading">Q2', $html);
                $this->assertStringNotContainsString('<div class="answer-key">', $html);
            } else {
                $this->assertStringNotContainsString('<div class="content explanations">', $html);
                $this->assertStringNotContainsString('Clinical note', $html);
            }
        }
        $this->post(route('admin.question-pdf.export'), $payload + ['include_explanations' => true, 'include_answer_key' => true])->assertOk();
        $html = view('admin.question-pdf.pdf', $data)->render();
        $this->assertStringContainsString('MCQ Answer Key', $html);
        $this->assertStringContainsString('Written Answer Key', $html);
        $this->assertStringContainsString('<div class="content explanations">', $html);
        $this->postJson(route('admin.question-pdf.export'), $payload + ['include_explanations' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('include_explanations');
    }

}
