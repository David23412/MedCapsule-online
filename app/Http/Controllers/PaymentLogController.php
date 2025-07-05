<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentLog;
use App\Services\PaymentLogService;
use Illuminate\Http\Request;
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
        $this->middleware('auth');
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
        $query = PaymentLog::query()
            ->with(['payment', 'user', 'admin'])
            ->orderBy('created_at', 'desc');
        
        // Apply filters
        if ($request->filled('payment_id')) {
            $query->where('payment_id', $request->payment_id);
        }
        
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
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
        $logs = $this->logService->getPaymentHistory($payment);
        
        return view('admin.payment_logs.payment_history', [
            'payment' => $payment,
            'logs' => $logs
        ]);
    }

    /**
     * Export payment logs to CSV.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPaymentLogs(Request $request)
    {
        // Validate request
        $request->validate([
            'payment_ids' => 'nullable|array',
            'actions' => 'nullable|array',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);
        
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
    }

    /**
     * Run log summarization manually.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function summarizePaymentLogs(Request $request)
    {
        // Validate request
        $request->validate([
            'days' => 'required|integer|min=30|max=365',
            'batch_size' => 'required|integer|min=50|max=500',
        ]);
        
        // Run summarization
        $result = $this->logService->summarizeOldLogs(
            $request->days,
            $request->batch_size
        );
        
        return redirect()
            ->route('payment-logs')
            ->with('success', "Summarized logs for {$result['processed_payments']} payments. Cutoff date: {$result['cutoff_date']}");
    }
} 