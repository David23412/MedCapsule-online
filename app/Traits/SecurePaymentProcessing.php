<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Exception;

trait SecurePaymentProcessing
{
    protected function processPaymentSecurely(callable $paymentLogic)
    {
        DB::beginTransaction();
        try {
            $result = $paymentLogic();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            \Log::error('Payment processing failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function validatePaymentData(array $data)
    {
        $required = ['amount', 'currency', 'payment_method'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Missing payment field: {$field}");
            }
        }

        if ($data['amount'] <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive');
        }

        return true;
    }
}