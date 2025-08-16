<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentVerificationService
{
    /**
     * Verify payment (admin only)
     */
    public function verifyPayment(Payment $payment, string $status, int $verifiedBy, ?string $adminNotes = null): Payment
    {
        DB::beginTransaction();
        
        try {
            $payment->update([
                'status' => $status,
                'admin_notes' => $adminNotes,
                'verified_at' => now(),
                'verified_by' => $verifiedBy
            ]);

            // If approved, grant course access
            if ($status === 'approved') {
                $this->grantCourseAccess($payment);
            }

            DB::commit();
            
            return $payment;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Grant course access to user
     */
    private function grantCourseAccess(Payment $payment): void
    {
        $payment->user->courses()->attach($payment->course_id, [
            'enrolled_at' => now()
        ]);
    }
}