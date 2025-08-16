<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentProcessingService
{
    /**
     * Process manual payment for a course
     */
    public function processManualPayment(User $user, Course $course, array $paymentData): Payment
    {
        DB::beginTransaction();
        
        try {
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $paymentData['amount'],
                'payment_method' => $paymentData['payment_method'],
                'reference_number' => $paymentData['reference_number'],
                'status' => 'pending',
                'phone_number' => $paymentData['phone_number'] ?? null,
            ]);

            DB::commit();
            
            return $payment;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Submit payment proof for a course
     */
    public function submitProofForCourse(User $user, int $courseId, array $proofData): Payment
    {
        $course = Course::findOrFail($courseId);
        
        DB::beginTransaction();
        
        try {
            // Check for existing payment
            $existingPayment = Payment::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->whereIn('status', ['pending', 'under_review'])
                ->first();
            
            if ($existingPayment) {
                $existingPayment->update([
                    'payment_method' => $proofData['payment_method'],
                    'reference_number' => $proofData['transaction_id'],
                    'status' => 'under_review',
                    'paid_at' => now()
                ]);
                
                $payment = $existingPayment;
            } else {
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'course_id' => $courseId,
                    'amount' => $course->price,
                    'payment_method' => $proofData['payment_method'],
                    'reference_number' => $proofData['transaction_id'],
                    'status' => 'under_review',
                    'paid_at' => now()
                ]);
            }

            DB::commit();
            
            return $payment;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Submit payment proof with image
     */
    public function submitProofWithImage(Payment $payment, string $proofPath, ?string $notes = null): Payment
    {
        DB::beginTransaction();
        
        try {
            $payment->update([
                'proof_image' => $proofPath,
                'notes' => $notes,
                'status' => 'under_review'
            ]);

            DB::commit();
            
            return $payment;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}