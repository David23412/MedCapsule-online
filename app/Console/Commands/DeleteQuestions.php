<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Topic;
use App\Models\Course;
use Illuminate\Console\Command;

class DeleteQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:delete 
                          {--all : Delete all questions}
                          {--topic= : Delete questions from a specific topic}
                          {--course= : Delete questions from a specific course}
                          {--select : List and select specific questions to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete questions from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('select')) {
            $this->deleteSpecificQuestions();
            return;
        }

        if (!$this->confirm('Are you sure you want to delete questions? This action cannot be undone.')) {
            $this->info('Operation cancelled.');
            return;
        }

        if ($this->option('all')) {
            $count = Question::count();
            Question::truncate();
            $this->info("Deleted all {$count} questions.");
            return;
        }

        if ($topicName = $this->option('topic')) {
            $topic = Topic::where('name', 'like', "%{$topicName}%")->first();
            if (!$topic) {
                $this->error("Topic not found: {$topicName}");
                return;
            }
            $count = Question::where('topic_id', $topic->id)->count();
            Question::where('topic_id', $topic->id)->delete();
            $this->info("Deleted {$count} questions from topic: {$topicName}");
            return;
        }

        if ($courseName = $this->option('course')) {
            $course = Course::where('name', 'like', "%{$courseName}%")->first();
            if (!$course) {
                $this->error("Course not found: {$courseName}");
                return;
            }
            $count = Question::whereIn('topic_id', $course->topics->pluck('id'))->count();
            Question::whereIn('topic_id', $course->topics->pluck('id'))->delete();
            $this->info("Deleted {$count} questions from course: {$courseName}");
            return;
        }

        // If no option is provided, show usage
        $this->info('Please specify one of the following options:');
        $this->line('--all         : Delete all questions');
        $this->line('--topic=NAME  : Delete questions from a specific topic');
        $this->line('--course=NAME : Delete questions from a specific course');
        $this->line('--select      : List and select specific questions to delete');
    }

    protected function deleteSpecificQuestions()
    {
        // First, let user select a topic
        $topics = Topic::orderBy('name')->get();
        if ($topics->isEmpty()) {
            $this->error('No topics found.');
            return;
        }

        $topicChoices = $topics->pluck('name')->toArray();
        $selectedTopicName = $this->choice(
            'Select a topic:',
            $topicChoices
        );

        $selectedTopic = Topic::where('name', $selectedTopicName)->first();
        if (!$selectedTopic) {
            $this->error('Topic not found.');
            return;
        }

        // Debug information
        $this->info("Selected Topic ID: " . $selectedTopic->id);
        $this->info("Selected Topic Name: " . $selectedTopic->name);
        
        // Get questions for the selected topic
        $questions = Question::where('topic_id', $selectedTopic->id)->get();
        
        // Debug information
        $this->info("SQL Query: " . Question::where('topic_id', $selectedTopic->id)->toSql());
        $this->info("Total questions found: " . $questions->count());

        if ($questions->isEmpty()) {
            $this->error('No questions found in this topic.');
            return;
        }

        // Display questions with numbers
        $this->info("\nQuestions in selected topic:");
        $questionChoices = [];
        foreach ($questions as $index => $question) {
            $number = $index + 1;
            $this->line("{$number}. {$question->question}");
            $questionChoices[$question->id] = $number;
        }

        // Let user select multiple questions
        $selectedNumbers = $this->ask('Enter question numbers to delete (comma-separated, e.g., 1,3,5)');
        $selectedNumbers = array_map('trim', explode(',', $selectedNumbers));

        // Validate and get question IDs
        $questionIds = [];
        foreach ($selectedNumbers as $number) {
            if (!is_numeric($number) || $number < 1 || $number > count($questions)) {
                $this->error("Invalid question number: {$number}");
                continue;
            }
            $questionIds[] = $questions[$number - 1]->id;
        }

        if (empty($questionIds)) {
            $this->info('No valid questions selected.');
            return;
        }

        // Confirm deletion
        if (!$this->confirm('Are you sure you want to delete the selected questions?')) {
            $this->info('Operation cancelled.');
            return;
        }

        // Delete selected questions
        $count = Question::whereIn('id', $questionIds)->delete();
        $this->info("Successfully deleted {$count} questions.");
    }
}
