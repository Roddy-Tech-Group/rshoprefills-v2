<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Payment\Exceptions\InvalidWebhookException;
use App\Domain\Payment\Services\FlutterwaveService;
use App\Domain\Wallet\Services\WalletFundingService;
use App\Http\Controllers\Controller;
use App\Models\PaymentWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FlutterwaveWebhookController extends Controller
{
    public function __construct(
        private readonly FlutterwaveService $flutterwaveService,
        private readonly WalletFundingService $fundingService,
    ) {}

    public function handle(Request $request): Response
    {
        $signature = $request->header('verif-hash');
        $payload = $request->all();

        try {
            // Verify signature
            $this->flutterwaveService->verifyWebhookSignature($signature);

            // Extract core fields
            $eventType = $payload['event'] ?? 'unknown';
            $data = $payload['data'] ?? [];
            $txRef = $data['tx_ref'] ?? null;
            $transactionId = $data['id'] ?? null;
            $status = $data['status'] ?? 'unknown';

            // Store raw webhook for audit
            $webhook = PaymentWebhook::create([
                'gateway' => 'flutterwave',
                'event_type' => $eventType,
                'reference' => $txRef,
                'payload' => $payload,
                'signature' => $signature,
                'processed' => false,
            ]);

            // Only process successful payments
            if ($eventType === 'charge.completed' && $status === 'successful') {
                if (! $txRef || ! $transactionId) {
                    throw InvalidWebhookException::missingReference();
                }

                // Process the funding via our service.
                // It internally verifies the transaction via API and idempotently credits the wallet.
                $this->fundingService->processSuccessfulFunding($txRef, (string) $transactionId, $payload);

                $webhook->update(['processed' => true, 'processed_at' => now()]);
            }

            return response()->noContent();

        } catch (InvalidWebhookException $e) {
            Log::warning('Webhook processing aborted: '.$e->getMessage());

            return response()->noContent(401); // 401 Unauthorized for bad signatures
        } catch (\Exception $e) {
            Log::error('Webhook processing failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Return 200 so Flutterwave doesn't endlessly retry if it's our internal error (like already processed)
            // unless it's a transient failure. For safety against double-credits on our end, we catch and log.
            return response()->noContent(200);
        }
    }
}
