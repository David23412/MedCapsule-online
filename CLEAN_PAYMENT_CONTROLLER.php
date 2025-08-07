<?php

/**
 * CLEAN PAYMENT CONTROLLER GENERATOR
 * Creates a clean, working PaymentProcessingController
 */

echo "🔥 CLEAN PAYMENT CONTROLLER GENERATOR - ENGAGED\n";
echo "🎯 TARGET: Create clean PaymentProcessingController.php\n\n";

$cleanController = '<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentProcessingController extends Controller
{
    /**
     * Process manual payment for a single course
     */
    public function processManualPayment(Request $request, Course $course)
    {
        $user = Auth::user();
        
        // Validate payment data
        $validated = $request->validate([
            \'payment_method\' => \'required|string|in:vodafone_cash,fawry,instapay\',
            \'phone_number\' => \'required_if:payment_method,vodafone_cash|string|max:15\',
            \'reference_number\' => \'required|string|max:50\',
            \'amount\' => \'required|numeric|min:0.01\'
        ]);

        try {
            DB::beginTransaction();
            
            // Create payment record
            $payment = Payment::create([
                \'user_id\' => $user->id,
                \'course_id\' => $course->id,
                \'amount\' => $validated[\'amount\'],
                \'payment_method\' => $validated[\'payment_method\'],
                \'reference_number\' => $validated[\'reference_number\'],
                \'status\' => \'pending\',
                \'phone_number\' => $validated[\'phone_number\'] ?? null,
            ]);

            DB::commit();
            
            return response()->json([
                \'success\' => true,
                \'message\' => \'Payment initiated successfully. Please submit proof of payment.\',
                \'payment_id\' => $payment->id
            ]);
            
        } catch (Exception $e) {
            DB::rollback();
            
            return response()->json([
                \'success\' => false,
                \'message\' => \'Payment processing failed: \' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit payment proof
     */
    public function submitProof(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            \'proof_image\' => \'required|image|max:2048\',
            \'notes\' => \'nullable|string|max:500\'
        ]);

        try {
            DB::beginTransaction();
            
            // Store proof image
            $proofPath = $request->file(\'proof_image\')->store(\'payment_proofs\', \'public\');
            
            $payment->update([
                \'proof_image\' => $proofPath,
                \'notes\' => $validated[\'notes\'] ?? null,
                \'status\' => \'under_review\'
            ]);

            DB::commit();
            
            return response()->json([
                \'success\' => true,
                \'message\' => \'Payment proof submitted successfully\'
            ]);
            
        } catch (Exception $e) {
            DB::rollback();
            
            return response()->json([
                \'success\' => false,
                \'message\' => \'Failed to submit proof: \' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment (admin only)
     */
    public function verifyPayment(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            \'status\' => \'required|in:approved,rejected\',
            \'admin_notes\' => \'nullable|string|max:500\'
        ]);

        try {
            DB::beginTransaction();
            
            $payment->update([
                \'status\' => $validated[\'status\'],
                \'admin_notes\' => $validated[\'admin_notes\'] ?? null,
                \'verified_at\' => now(),
                \'verified_by\' => Auth::id()
            ]);

            // If approved, grant course access
            if ($validated[\'status\'] === \'approved\') {
                $payment->user->courses()->attach($payment->course_id, [
                    \'enrolled_at\' => now()
                ]);
            }

            DB::commit();
            
            return response()->json([
                \'success\' => true,
                \'message\' => \'Payment \' . $validated[\'status\'] . \' successfully\'
            ]);
            
        } catch (Exception $e) {
            DB::rollback();
            
            return response()->json([
                \'success\' => false,
                \'message\' => \'Verification failed: \' . $e->getMessage()
            ], 500);
        }
    }
}
';

file_put_contents('app/Http/Controllers/Payment/PaymentProcessingController.php', $cleanController);

echo "✅ Created clean PaymentProcessingController.php\n";
echo "🔧 Added proper error handling\n";
echo "🛡️ Added input validation\n";
echo "💾 Added database transactions\n";

echo "\n🎉 CLEAN PAYMENT CONTROLLER GENERATED!\n";

?>