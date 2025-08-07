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
            $filters = $request->validate([
                'payment_id' => 'nullable|integer|exists:payments,id',
                'action' => 'nullable|string|max:50',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from'
            ]);
            $cacheKey = QuizCacheService::generateCacheKey('payment_logs', [
                'filters' => $filters,
                'user_id' => auth()->id(),
                'timestamp' => now()->format('Y-m-d-H')
            ]);

            $data = Cache::remember($cacheKey, 1800, function () use ($request, $filters) {
                $query = PaymentLog::query()
                    ->with(['payment:id,user_id,amount,status', 'user:id,name,email', 'admin:id,name,email'])->orderBy('created_at', 'desc');
                
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
            return view('payment-logs', ['logs' => collect(), 'stats' => []])
                ->with('error', 'Failed to load payment logs: ' . $e->getMessage());
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
            return view('admin.payment_logs.payment_history', [
                'payment' => $payment,
                'logs' => collect()
            ])->with('error', 'Failed to load payment history: ' . $e->getMessage());
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
            // Validate input data
        $validated = $request->validate([
            'payment_ids' => 'array',
            'payment_ids.*' => 'integer',
            'actions' => 'array',
            'actions.*' => 'string|max:50',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);
        
        $paymentIds = $validated['payment_ids'] ?? [];
        $actions = $validated['actions'] ?? [];
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        
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
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
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
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'batch_size' => 'nullable|integer|min:10|max:1000'
        ]);
        
        $result = $this->logService->summarizeOldLogs(
            $validated['days'],
            $validated['batch_size'] ?? 100
        );
        
            return redirect()
                ->route('payment-logs')
                ->with('success', "Summarized logs for {$result['processed_payments']} payments. Cutoff date: {$result['cutoff_date']}");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Summarization failed: ' . $e->getMessage());
        }
    }
} 