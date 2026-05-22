<?php

namespace App\Domain\Reconciliation\Console;

use App\Domain\Reconciliation\Models\ReconciliationReport;
use App\Domain\Shared\Enums\WalletTransactionType;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Notifications\CriticalSystemAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class ReconcileWalletBalancesCommand extends Command
{
    protected $signature = 'reconcile:wallet-balances';

    protected $description = 'Reconcile wallet balances against transaction history';

    public function handle()
    {
        $this->info('Starting wallet balance reconciliation...');

        $report = ReconciliationReport::create([
            'type' => 'wallet_balance',
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $anomalies = [];
        $wallets = Wallet::all();

        foreach ($wallets as $wallet) {
            $totalCredits = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::Credit)
                ->sum('amount');

            $totalDebits = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('type', WalletTransactionType::Debit)
                ->sum('amount');

            $expectedBalance = $totalCredits - $totalDebits;

            if (round($expectedBalance, 4) !== round($wallet->balance, 4)) {
                $anomalies[] = [
                    'wallet_id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'actual_balance' => $wallet->balance,
                    'expected_balance' => $expectedBalance,
                    'difference' => $wallet->balance - $expectedBalance,
                ];
            }
        }

        $report->update([
            'status' => count($anomalies) > 0 ? 'anomalies_found' : 'clean',
            'anomalies_found' => count($anomalies) > 0 ? $anomalies : null,
            'completed_at' => now(),
        ]);

        if (count($anomalies) > 0) {
            $this->error('Reconciliation finished with '.count($anomalies).' anomalies.');

            $adminEmail = env('ADMIN_ALERT_EMAIL', 'admin@roddytechgroup.com');
            Notification::route('mail', $adminEmail)
                ->notify(new CriticalSystemAlert(
                    title: 'Financial Ledger Anomaly Detected',
                    description: 'The daily wallet reconciliation report found '.count($anomalies).' discrepancies between Wallet balances and WalletTransaction sums. Immediate investigation is required.',
                    context: [
                        'Report ID' => $report->id,
                        'Anomalies' => $anomalies,
                    ]
                ));
        } else {
            $this->info('Reconciliation finished cleanly.');
        }
    }
}
