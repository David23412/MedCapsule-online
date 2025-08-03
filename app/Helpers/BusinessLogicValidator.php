<?php

namespace App\Helpers;

use InvalidArgumentException;

class BusinessLogicValidator
{
    public static function validateScore($score, $maxScore = 100)
    {
        if (!$score || $score < 0 || $score > $maxScore) {
            throw new InvalidArgumentException('Invalid score value');
        }
        return true;
    }

    public static function validateGradeCalculation($numerator, $denominator)
    {
        if ($denominator === 0) {
            throw new InvalidArgumentException('Division by zero in grade calculation');
        }
        return true;
    }

    public static function validatePaymentAmount($amount)
    {
        if (!$amount || $amount <= 0 || !is_numeric($amount)) {
            throw new InvalidArgumentException('Invalid payment amount');
        }
        return true;
    }

    public static function validateUserData(array $userData)
    {
        $required = ['name', 'email'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }
        return true;
    }
}