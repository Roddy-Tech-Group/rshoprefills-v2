<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\PaymentSession;
use App\Models\WalletFunding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearPendingTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear-pending {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all pending, failed, or cancelled transactions (Orders and Wallet Fundings)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('This will delete all pending, failed, cancelled, and expired orders and wallet fundings from the database.');

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to proceed?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $orderStatuses = ['pending', 'failed', 'cancelled', 'requires_attention'];
        $fundingStatuses = ['pending', 'failed', 'cancelled'];

        DB::transaction(function () use ($orderStatuses, $fundingStatuses) {
            $ordersCount = Order::whereIn('status', $orderStatuses)->count();
            $fundingsCount = WalletFunding::whereIn('status', $fundingStatuses)->count();

            // Fetch the models so we can trigger Eloquent events if necessary,
            // or just use DB::table for raw performance if cascading is set up.
            // Since we know orders cascade to payment_attempts, and attempts cascade to sessions,
            // we can just delete the orders. But WalletFunding does not cascade to payment_attempts.
            // We must delete the associated payment_attempts manually first.

            $fundings = WalletFunding::whereIn('status', $fundingStatuses)->get();
            $attemptsDeleted = 0;
            $sessionsDeleted = 0;

            foreach ($fundings as $funding) {
                $attempts = PaymentAttempt::where('payable_type', WalletFunding::class)
                    ->where('payable_id', $funding->id)
                    ->get();
                
                foreach ($attempts as $attempt) {
                    $sessionsDeleted += PaymentSession::where('payment_attempt_id', $attempt->id)->delete();
                    $attempt->delete();
                    $attemptsDeleted++;
                }
                
                $funding->delete();
            }

            // For orders, cascading deletes payment_attempts (which then deletes sessions).
            // However, it's safer to manually trigger deletes for polymorphic or deep relations if foreign keys are tricky.
            $orders = Order::whereIn('status', $orderStatuses)->get();
            foreach ($orders as $order) {
                $attempts = PaymentAttempt::where('order_id', $order->id)->get();
                foreach ($attempts as $attempt) {
                    $sessionsDeleted += PaymentSession::where('payment_attempt_id', $attempt->id)->delete();
                    $attempt->delete();
                    $attemptsDeleted++;
                }
                $order->delete();
            }

            // Also clean up any completely orphaned attempts/sessions just in case
            $orphanSessions = PaymentSession::whereNotIn('payment_attempt_id', PaymentAttempt::select('id'))->delete();
            $orphanAttempts = PaymentAttempt::whereNotIn('order_id', Order::select('id'))
                ->where('payable_type', Order::class)
                ->delete();

            $this->info("Successfully deleted {$ordersCount} orders and {$fundingsCount} wallet fundings.");
            $this->info("Cleaned up {$attemptsDeleted} payment attempts and {$sessionsDeleted} payment sessions.");
            if ($orphanSessions > 0 || $orphanAttempts > 0) {
                $this->info("Cleaned up an additional {$orphanSessions} orphaned sessions and {$orphanAttempts} orphaned attempts.");
            }
        });

        $this->info('Cleanup complete.');
    }
}
