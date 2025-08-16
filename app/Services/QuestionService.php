<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class QuestionService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createQuestion(array $data): Question
    {
        try {
            $question = Question::create($data);
            $this->logger->info('Question created successfully', ['question_id' => $question->id]);
            return $question;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create question', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getQuestionById(int $id): Question
    {
        try {
            $question = Question::findOrFail($id);
            return $question;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get question by ID', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateQuestion(int $id, array $data): Question
    {
        try {
            $question = Question::findOrFail($id);
            $question->update($data);
            $this->logger->info('Question updated successfully', ['question_id' => $id]);
            return $question;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update question', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteQuestion(int $id): bool
    {
        try {
            $question = Question::findOrFail($id);
            $question->delete();
            $this->logger->info('Question deleted successfully', ['question_id' => $id]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete question', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
