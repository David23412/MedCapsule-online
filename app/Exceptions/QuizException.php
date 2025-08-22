<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for quiz-related errors
 */
class QuizException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = "",
        array $context = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get additional context for the exception
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set additional context
     */
    public function setContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    /**
     * Create exception for quiz not found
     */
    public static function quizNotFound(int $quizId, array $context = []): self
    {
        return new self(
            "Quiz with ID {$quizId} not found",
            array_merge($context, ['quiz_id' => $quizId]),
            404
        );
    }

    /**
     * Create exception for quiz access denied
     */
    public static function accessDenied(int $quizId, int $userId, array $context = []): self
    {
        return new self(
            "User {$userId} does not have access to quiz {$quizId}",
            array_merge($context, ['quiz_id' => $quizId, 'user_id' => $userId]),
            403
        );
    }

    /**
     * Create exception for quiz already completed
     */
    public static function alreadyCompleted(int $quizId, int $userId, array $context = []): self
    {
        return new self(
            "Quiz {$quizId} has already been completed by user {$userId}",
            array_merge($context, ['quiz_id' => $quizId, 'user_id' => $userId]),
            409
        );
    }

    /**
     * Create exception for invalid quiz configuration
     */
    public static function invalidConfiguration(string $reason, array $context = []): self
    {
        return new self(
            "Invalid quiz configuration: {$reason}",
            $context,
            422
        );
    }

    /**
     * Create exception for quiz timeout
     */
    public static function timeout(int $quizId, int $duration, array $context = []): self
    {
        return new self(
            "Quiz {$quizId} timed out after {$duration} seconds",
            array_merge($context, ['quiz_id' => $quizId, 'duration' => $duration]),
            408
        );
    }
}
