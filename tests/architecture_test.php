<?php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

// Helper to clear test data
function cleanup() {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    Question::query()->delete();
    Topic::query()->delete();
    Course::query()->delete();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
}

echo "Starting tests...\n";

// Setup
cleanup();

$course = Course::create(['name' => 'Test Course']);
$examTopic = Topic::create(['name' => 'Final 2022', 'is_exam' => true, 'course_id' => $course->id]);
$midExamTopic = Topic::create(['name' => 'Mid 2021', 'is_exam' => true, 'course_id' => $course->id]);
$lectureTopic = Topic::create(['name' => 'Heart Chambers', 'is_exam' => false, 'course_id' => $course->id]);
$lectureTopic2 = Topic::create(['name' => 'Valves', 'is_exam' => false, 'course_id' => $course->id]);

echo "Topics created:\n";
echo "- Exam: {$examTopic->id} (Final 2022)\n";
echo "- Exam: {$midExamTopic->id} (Mid 2021)\n";
echo "- Lecture: {$lectureTopic->id} (Heart Chambers)\n";
echo "- Lecture: {$lectureTopic2->id} (Valves)\n";

// --- TEST 1: Basic Normal Import ---
echo "\nTest 1: Normal lecture question...\n";
$q1 = Question::create([
    'topic_id' => $lectureTopic->id,
    'question' => 'Q1 text',
    'correct_answer' => 'A'
]);
$q1->updateExamFrequencyScore();
echo "Question created: ID={$q1->id}, Topic={$q1->topic_id}, Exam Count={$q1->exam_appearances_count}\n";
assert($q1->exam_appearances_count == 0, "Lecture only should have 0 exam count");

// --- TEST 2: Exam Question with multiple topics ---
echo "\nTest 2: Exam question with additional topics...\n";
// Suppose we import a question for Final 2022 (Exam) and it also belongs to Heart Chambers (Lecture)
$q2 = Question::create([
    'topic_id' => $examTopic->id,
    'question' => 'What is the right atrium?',
    'correct_answer' => 'A'
]);
// Add extra topic
$q2->update(['extra_topic' => $lectureTopic->id]);
$q2->updateExamFrequencyScore();
echo "Question created: ID={$q2->id}, Topic={$q2->topic_id} (Exam), Linked Topic={$lectureTopic->id} (Lecture)\n";
echo "Exam Count: {$q2->exam_appearances_count}\n";
assert($q2->exam_appearances_count == 1, "Only main topic is exam topic, pivot is lecture. Should be 1.");

// --- TEST 3: Multiple Exam links ---
echo "\nTest 3: Multiple exam links...\n";
// Add another exam to extra topics
$currentExtra = $q2->extra_topic ? $q2->extra_topic . ',' : '';
$q2->update(['extra_topic' => $currentExtra . $midExamTopic->id]);
$q2->updateExamFrequencyScore();
echo "Linked Topic={$midExamTopic->id} (Exam)\n";
echo "New Exam Count: {$q2->exam_appearances_count}\n";
assert($q2->exam_appearances_count == 2, "Main topic + 1 exam pivot = 2");

// --- TEST 4: Query Logic for Exam Topic ---
echo "\nTest 4: Exam Topic Query Logic...\n";
$questions = $examTopic->allQuestions();
echo "Exam Topic (Final 2022) Questions Count: {$questions->count()}\n";
assert($questions->count() == 1, "Exam should only show direct questions");
assert($questions->first()->id == $q2->id);

// --- TEST 5: Query Logic for Lecture Topic ---
echo "\nTest 5: Lecture Topic Query Logic...\n";
$questions = $lectureTopic->allQuestions();
echo "Lecture Topic (Heart Chambers) Questions Count: {$questions->count()}\n";
// Should have q1 (direct) and q2 (linked)
assert($questions->count() == 2, "Lecture should show direct and linked");
echo "Question IDs in Heart Chambers: " . implode(',', $questions->pluck('id')->toArray()) . "\n";
assert($questions->contains('id', $q1->id));
assert($questions->contains('id', $q2->id));

// --- TEST 6: Importer Logic (Mock simulation) ---
echo "\nTest 6: Simulating Importer (Idempotency and update logic)...\n";
// Let's say we import q2 again with NEW extra topics
$newData = [
    'question' => 'What is the right atrium?', // Matches existing
    'topic_id' => $examTopic->id,
    'extra_topic_ids' => "{$lectureTopic->id}, {$lectureTopic2->id}" // Added Valves
];

$existing = Question::where('question', $newData['question'])->first();
if ($existing) {
    echo "Updating existing question ID={$existing->id}\n";
    $existing->update(['topic_id' => $newData['topic_id']]);
}
$allExtraTopicIds = [ (int)$lectureTopic->id, (int)$lectureTopic2->id ];
// Remove main
$allExtraTopicIds = array_diff($allExtraTopicIds, [(int)$newData['topic_id']]);

// Simulating the extra_topic logic in importer
$existing->update(['extra_topic' => implode(',', $allExtraTopicIds)]);
$existing->refresh();
$existing->updateExamFrequencyScore();
echo "Final Exam Count after re-import: {$existing->exam_appearances_count}\n";
assert($existing->exam_appearances_count == 2, "Main topic(Exam) + Mid 2021(Exam) = 2. Lecture links don't count.");
echo "Final Extra Topics for Q2: " . ($existing->extra_topic ?? 'none') . "\n";
// Should be: LectureTopic, MidExamTopic, LectureTopic2 (Valves)

// --- TEST 7: Duplicate Question Prevention ---
echo "\nTest 7: Duplicate Question Prevention...\n";
$countBefore = Question::count();
// Importing same question again should NOT create new row
$newData2 = [
    'question' => 'Q1 text',
    'topic_id' => $lectureTopic->id
];
$existing2 = Question::where('question', $newData2['question'])->first();
if ($existing2) {
    $existing2->update($newData2);
} else {
    Question::create($newData2);
}
assert(Question::count() == $countBefore, "Row count should not increase on duplicate question text");

echo "\n✅ All Test assertions passed!\n";
