<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Validation\Validator;

/**
 * Custom exception for input validation errors
 */
class ValidationException extends Exception
{
    protected array $errors = [];
    protected array $validatedData = [];
    protected Validator $validator;

    public function __construct(
        string $message = "",
        array $errors = [],
        array $validatedData = [],
        ?Validator $validator = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->errors = $errors;
        $this->validatedData = $validatedData;
        $this->validator = $validator ?? app(Validator::class);

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    /**
     * Get the validator instance
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }

    /**
     * Create exception for required field missing
     */
    public static function required(string $field, array $context = []): self
    {
        return new self(
            "Required field '{$field}' is missing",
            [$field => ["The {$field} field is required."]],
            [],
            null,
            422
        );
    }

    /**
     * Create exception for invalid email format
     */
    public static function invalidEmail(string $email, array $context = []): self
    {
        return new self(
            "Invalid email format: {$email}",
            ['email' => ['The email format is invalid.']],
            ['email' => $email],
            null,
            422
        );
    }

    /**
     * Create exception for invalid numeric value
     */
    public static function invalidNumber(string $field, $value, array $context = []): self
    {
        return new self(
            "Invalid numeric value for field '{$field}': {$value}",
            [$field => ["The {$field} must be a valid number."]],
            [$field => $value],
            null,
            422
        );
    }

    /**
     * Create exception for value out of range
     */
    public static function outOfRange(string $field, $value, $min, $max, array $context = []): self
    {
        return new self(
            "Value for field '{$field}' is out of range: {$value} (must be between {$min} and {$max})",
            [$field => ["The {$field} must be between {$min} and {$max}."]],
            [$field => $value],
            null,
            422
        );
    }

    /**
     * Create exception for invalid date format
     */
    public static function invalidDate(string $field, string $value, ?string $format = null, array $context = []): self
    {
        $message = $format
            ? "Invalid date format for field '{$field}': {$value} (expected format: {$format})"
            : "Invalid date format for field '{$field}': {$value}";

        return new self(
            $message,
            [$field => ["The {$field} must be a valid date."]],
            [$field => $value],
            null,
            422
        );
    }

    /**
     * Create exception for string too long
     */
    public static function stringTooLong(string $field, string $value, int $maxLength, array $context = []): self
    {
        return new self(
            "String too long for field '{$field}': {$maxLength} characters maximum",
            [$field => ["The {$field} may not be greater than {$maxLength} characters."]],
            [$field => $value],
            null,
            422
        );
    }

    /**
     * Create exception for invalid choice/option
     */
    public static function invalidChoice(string $field, $value, array $validChoices, array $context = []): self
    {
        return new self(
            "Invalid choice for field '{$field}': {$value}",
            [$field => ["The selected {$field} is invalid. Valid options: " . implode(', ', $validChoices)]],
            [$field => $value],
            null,
            422
        );
    }

    /**
     * Create exception for unique constraint violation
     */
    public static function notUnique(string $field, $value, array $context = []): self
    {
        return new self(
            "Value for field '{$field}' must be unique: {$value}",
            [$field => ["The {$field} has already been taken."]],
            [$field => $value],
            null,
            422
        );
    }

    /**
     * Create exception for foreign key constraint violation
     */
    public static function foreignKeyViolation(string $field, $value, string $relatedTable, array $context = []): self
    {
        return new self(
            "Invalid reference for field '{$field}': {$value} does not exist in {$relatedTable}",
            [$field => ["The selected {$field} is invalid."]],
            [$field => $value],
            null,
            422
        );
    }

    /**
     * Create exception for file upload error
     */
    public static function fileUploadError(string $field, string $error, array $context = []): self
    {
        return new self(
            "File upload error for field '{$field}': {$error}",
            [$field => ["File upload failed: {$error}"]],
            [],
            null,
            422
        );
    }

    /**
     * Create exception for invalid file type
     */
    public static function invalidFileType(string $field, string $filename, array $allowedTypes, array $context = []): self
    {
        return new self(
            "Invalid file type for field '{$field}': {$filename}",
            [$field => ["The {$field} must be a file of type: " . implode(', ', $allowedTypes)]],
            [$field => $filename],
            null,
            422
        );
    }

    /**
     * Create exception for file too large
     */
    public static function fileTooLarge(string $field, string $filename, int $maxSizeKB, array $context = []): self
    {
        return new self(
            "File too large for field '{$field}': {$filename} (max {$maxSizeKB}KB)",
            [$field => ["The {$field} may not be greater than {$maxSizeKB} kilobytes."]],
            [$field => $filename],
            null,
            422
        );
    }

    /**
     * Convert to array for JSON response
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'validated_data' => $this->validatedData,
            'code' => $this->getCode()
        ];
    }
}
