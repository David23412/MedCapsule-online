<?php

namespace App\Exceptions;

use Exception;

/**
 * Custom exception for payment processing errors
 */
class PaymentException extends Exception
{
    protected array $context = [];
    protected array $paymentData = [];

    public function __construct(
        string $message = "",
        array $context = [],
        array $paymentData = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->context = $context;
        $this->paymentData = $paymentData;
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
     * Get payment data (sanitized)
     */
    public function getPaymentData(): array
    {
        return $this->paymentData;
    }

    /**
     * Create exception for payment processing failure
     */
    public static function paymentFailed(string $reason, array $context = [], array $paymentData = []): self
    {
        return new self(
            "Payment processing failed: {$reason}",
            $context,
            $paymentData,
            402
        );
    }

    /**
     * Create exception for insufficient funds
     */
    public static function insufficientFunds(float $required, float $available, array $context = []): self
    {
        return new self(
            "Insufficient funds. Required: \${$required}, Available: \${$available}",
            array_merge($context, [
                'required_amount' => $required,
                'available_amount' => $available
            ]),
            [],
            402
        );
    }

    /**
     * Create exception for payment method declined
     */
    public static function paymentDeclined(string $reason, array $context = [], array $paymentData = []): self
    {
        return new self(
            "Payment method declined: {$reason}",
            $context,
            $paymentData,
            402
        );
    }

    /**
     * Create exception for payment timeout
     */
    public static function timeout(int $timeoutSeconds, array $context = []): self
    {
        return new self(
            "Payment processing timed out after {$timeoutSeconds} seconds",
            array_merge($context, ['timeout_seconds' => $timeoutSeconds]),
            [],
            408
        );
    }

    /**
     * Create exception for invalid payment configuration
     */
    public static function invalidConfiguration(string $issue, array $context = []): self
    {
        return new self(
            "Invalid payment configuration: {$issue}",
            $context,
            [],
            500
        );
    }

    /**
     * Create exception for payment already processed
     */
    public static function alreadyProcessed(string $paymentId, array $context = []): self
    {
        return new self(
            "Payment {$paymentId} has already been processed",
            array_merge($context, ['payment_id' => $paymentId]),
            [],
            409
        );
    }

    /**
     * Create exception for payment service unavailable
     */
    public static function serviceUnavailable(string $service, array $context = []): self
    {
        return new self(
            "Payment service '{$service}' is currently unavailable",
            array_merge($context, ['service' => $service]),
            [],
            503
        );
    }

    /**
     * Create exception for invalid payment amount
     */
    public static function invalidAmount(float $amount, string $currency, array $context = []): self
    {
        return new self(
            "Invalid payment amount: {$amount} {$currency}",
            array_merge($context, [
                'amount' => $amount,
                'currency' => $currency
            ]),
            [],
            422
        );
    }

    /**
     * Create exception for payment fraud detected
     */
    public static function fraudDetected(array $indicators, array $context = []): self
    {
        return new self(
            "Payment fraud detected",
            array_merge($context, ['fraud_indicators' => $indicators]),
            [],
            403
        );
    }

    /**
     * Create exception for payment refund failed
     */
    public static function refundFailed(string $paymentId, string $reason, array $context = []): self
    {
        return new self(
            "Payment refund failed for {$paymentId}: {$reason}",
            array_merge($context, [
                'payment_id' => $paymentId,
                'reason' => $reason
            ]),
            [],
            402
        );
    }
}
