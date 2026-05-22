<?php

namespace App\Domain\Reconciliation\Console;

use App\Domain\Reconciliation\Models\ReconciliationReport;
use App\Models\PaymentSession;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ReconcileOrphanedSessionsCommand extends Command
{
    protected $signature = 'reconcile:orphaned-sessions';

    protected $description = 'Detect and fail abandoned payment sessions';

    public function handle()
    {
        $this->info('Starting orphaned sessions reconciliation...');

        $report = ReconciliationReport::create([
            'type' => 'orphaned_sessions',
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // Find sessions pending or awaiting action for more than 2 hours
        $threshold = Carbon::now()->subHours(2);

        $orphanedSessions = PaymentSession::whereIn('status', ['pending', 'awaiting_customer_action'])
            ->where('created_at', '<', $threshold)
            ->get();

        $anomalies = [];

        foreach ($orphanedSessions as $session) {
            $anomalies[] = [
                'session_id' => $session->id,
                'order_id' => $session->order_id,
                'status' => $session->status,
                'created_at' => $session->created_at->toDateTimeString(),
            ];

            // Auto-fail the abandoned session to unlock wallet funds if necessary
            $session->status = 'failed';
            $session->metadata = array_merge($session->metadata ?? [], ['failed_reason' => 'orphaned_timeout']);
            $session->save();

            // Here we could also trigger an event to unlock the Wallet locked_balance
        }

        $report->update([
            'status' => count($anomalies) > 0 ? 'anomalies_found' : 'clean',
            'anomalies_found' => count($anomalies) > 0 ? $anomalies : null,
            'completed_at' => now(),
        ]);

        $this->info('Reconciliation finished. '.count($anomalies).' orphaned sessions auto-failed.');
    }
}
