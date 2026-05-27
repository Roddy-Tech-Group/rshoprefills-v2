<?php

namespace App\Domain\Payment\Console;

use App\Domain\Payment\Jobs\VerifyPaymentJob;
use App\Models\Order;
use Illuminate\Console\Command;

class ReplayWebhookCommand extends Command
{
    protected $signature = 'webhook:replay {order} {--provider=flutterwave}';

    protected $description = 'Manually replay a webhook fulfillment for a paid order';

    public function handle()
    {
        $orderNumber = $this->argument('order');
        $provider = $this->option('provider');

        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            $this->error("Order {$orderNumber} not found.");

            return;
        }

        $attempt = $order->paymentAttempts()->latest()->first();

        if (! $attempt) {
            $this->error("No payment attempt found for Order {$orderNumber}.");

            return;
        }

        $session = $attempt->paymentSession;

        if (! $session) {
            $this->error("No payment session found for Order {$orderNumber}.");

            return;
        }

        if ($session->status !== 'confirmed') {
            $this->warn("Payment session is not marked as confirmed. Status: {$session->status}");
            if (! $this->confirm('Do you want to proceed anyway?')) {
                return;
            }
        }

        $this->info("Replaying webhook for Order {$orderNumber} (Provider: {$provider})...");

        // The job simulates a successful verification, driving fulfillment
        VerifyPaymentJob::dispatch($attempt);

        $this->info('VerifyPaymentJob dispatched. Fulfillment should begin shortly.');
    }
}
