<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentLog;
use App\Services\PaymentLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Services\QuizPerformanceService;
use Illuminate\Support\Facades\Cache;
use App\Services\QuizCacheService;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class PaymentLogController extends Controller
{
    /**
     * @var PaymentLogService
     */
    protected $logService;

    /**
     * Create a new controller instance.
     *
     * @param PaymentLogService $logService
     * @return void
     */
    public function __construct(PaymentLogService $logService)
    {
        $this->middleware(['auth']);
        $this->logService = $logService;
    }

    /**
     * Display the payment logs page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showPaymentLogs(Request $request)
    {
        try {
            // LETHAL CACHING: Ultra-aggressive caching for payment logs
            $filters = $request->only(['payment_id', 'action', 'date_from', 'date_to']);
            $cacheKey = QuizCacheService::generateCacheKey('payment_logs', [
                'filters' => $filters,
                'user_id' => auth()->id(),
                'timestamp' => now()->format('Y-m-d-H')
            ]);

            $data = Cache::remember($cacheKey, 1800, function () use ($request, $filters) {
                $query = PaymentLog::query()
                    ->with(['payment:id,user_id,amount,status', 'user:id,name,email', 'admin:id,name,email'])
                    ->select('id', 'payment_id', 'user_id', 'admin_id', 'action', 'details', 'created_at')
                    ->orderBy('created_at', 'desc');
                
                // Apply filters
                if (!empty($filters['payment_id'])) {
                    $query->where('payment_id', $filters['payment_id']);
                }
                
                if (!empty($filters['action'])) {
                    $query->where('action', $filters['action']);
                }
                
                if (!empty($filters['date_from'])) {
                    $query->whereDate('created_at', '>=', $filters['date_from']);
                }
                
                if (!empty($filters['date_to'])) {
                    $query->whereDate('created_at', '<=', $filters['date_to']);
                }

                return [
                    'logs' => $query->paginate(20),
                    'stats' => $this->getPaymentLogStats()
                ];
            });

            return view('payment-logs', $data);
            
        } catch (\Exception $e) {
            Log::error('Payment logs failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            return view('payment-logs', [
                'logs' => collect([]),
                'stats' => [],
                'error' => 'Failed to load payment logs'
            ]);
        }
    }

    /**
     * LETHAL OPTIMIZATION: Get payment log statistics
     */
    private function getPaymentLogStats(): array
    {
        return [
            'total_logs' => PaymentLog::count(),
            'today_logs' => PaymentLog::whereDate('created_at', today())->count(),
            'actions' => PaymentLog::select('action')->distinct()->pluck('action')
        ];
    }
        
        // Get available actions for filter dropdown
        $actions = PaymentLog::select('action')
            ->distinct()
            ->pluck('action')
            ->toArray();
        
        // Paginate results
        $logs = $query->paginate(20);
        
        return view('admin.payment_logs.index', [
            'logs' => $logs,
            'actions' => $actions,
            'filters' => $request->all()
        ]);
    }

    /**
     * Display logs for a specific payment.
     *
     * @param Payment $payment
     * @return \Illuminate\View\View
     */
    public function showPaymentHistory(Payment $payment)
    {
        try {

        $logs = $this->logService->getPaymentHistory($payment);
        
        return view('admin.payment_logs.payment_history', [
            'payment' => $payment,
            'logs' => $logs
        ]);
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}

    /**
     * Export payment logs to CSV.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPaymentLogs(Request $request)
    {
        try {

        // Validate request
        // TODO: Create FormRequest class for validation
        
        $paymentIds = $request->payment_ids ?? [];
        $actions = $request->actions ?? [];
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        
        // Export to CSV
        $exportResult = $this->logService->exportToCsv(
            $paymentIds,
            $actions,
            $dateFrom,
            $dateTo
        );
        
        // Return file download
        return Response::download(
            $exportResult['path'],
            $exportResult['filename'],
            ['Content-Type' => 'text/csv']
        );
    
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
}

    /**
     * Run log summarization manually.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function summarizePaymentLogs(Request $request)
    {
        try {

        // Validate request
        // TODO: Create FormRequest class for validation
        
        // Run summarization
        $result = $this->logService->summarizeOldLogs(
            $request->days,
            $request->batch_size
        );
        
        return redirect()
            ->route('payment-logs')
            ->with('success', "Summarized logs for {$result['processed_payments']
        } catch (\Exception $e) {
            \Log::error('Method execution failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Operation failed'], 500);
        }
} payments. Cutoff date: {$result['cutoff_date']}");
    }
} 