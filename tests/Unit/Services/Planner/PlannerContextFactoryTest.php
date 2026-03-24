
// course_user still has planner_repair_mode = 'spaced'

        $context = $this->factory->buildForCourse(
            $this->user->fresh(),
            $this->course,
            collect([$this->weakTopic->fresh()->load('userProgress')]),
            $this->plan
        );

        $this->assertSame('easy', $context->repairMode); // spaced blocked, falls to classic default
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
