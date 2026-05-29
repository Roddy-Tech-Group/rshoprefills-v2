<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Ledger\Models\FinancialLedgerEvent;
use App\Domain\Reconciliation\Models\ReconciliationReport;
use App\Domain\Shared\Services\CircuitBreaker;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AdminSreController extends Controller
{
    /**
     * Get paginated audit logs.
     */
    public function auditLogs(Request $request)
    {
        $logs = AuditLog::with('actor')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 50));

        return response()->json($logs);
    }

    /**
     * Get paginated financial ledger events.
     */
    public function ledgerEvents(Request $request)
    {
        $events = FinancialLedgerEvent::orderByDesc('id')
            ->paginate($request->input('per_page', 50));

        return response()->json($events);
    }

    /**
     * Get paginated reconciliation reports.
     */
    public function reconciliationReports(Request $request)
    {
        $reports = ReconciliationReport::orderByDesc('id')
            ->paginate($request->input('per_page', 20));

        return response()->json($reports);
    }

    /**
     * Get current circuit breaker and fraud velocity metrics.
     */
    public function systemMetrics()
    {
        // Requires Redis driver. Use prefix to scan keys if possible,
        // or just return known status.
        $metrics = [
            'zendit_circuit_breaker_open' => app(CircuitBreaker::class, ['serviceName' => 'zendit_api'])->isOpen(),
        ];

        return response()->json($metrics);
    }
}
