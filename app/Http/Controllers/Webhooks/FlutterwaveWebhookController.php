<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Payment\Exceptions\InvalidWebhookException;
use App\Domain\Payment\Services\FlutterwaveService;
use App\Domain\Wallet\Jobs\ProcessFundingWebhookJob;
use App\Http\Controllers\Controller;
use App\Models\PaymentWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FlutterwaveWebhookController extends Controller
{
    public function __construct(
        private readonly FlutterwaveService $flutterwaveService
    ) {}

    /**
     * Hardened signature-verified asynchronous webhook entry point.
     */
    public function handle(Request $request)
    {
        $signature = $request->header('verif-hash');
        $payload = $request->all();
        $headers = $request->headers->all();

        $eventType = $payload['event'] ?? ($payload['event-type'] ?? 'unknown');
        $data = $payload['data'] ?? [];
        $txRef = $data['tx_ref'] ?? ($payload['txRef'] ?? null);

        // 1. Persist the raw webhook immediately for absolute auditability
        $webhook = PaymentWebhook::create([
            'gateway' => 'flutterwave',
            'event_type' => $eventType,
            'reference' => $txRef,
            'payload' => $payload,
            'headers' => $headers,
            'signature' => $signature,
            'processed' => false,
            'processing_status' => 'pending',
            'processing_attempts' => 0,
        ]);

        try {
            // 2. Perform signature checking
            $this->flutterwaveService->verifyWebhookSignature($signature);

        } catch (InvalidWebhookException $e) {
            Log::warning('Flutterwave webhook signature check failed.', [
                'webhook_id' => $webhook->id,
                'signature' => $signature
            ]);

            $webhook->update([
                'processing_status' => 'failed',
                'exception_traces' => 'Signature verification failed: ' . $e->getMessage(),
            ]);

            return response()->json(['message' => 'Unauthorized signature'], 401);
        }

        // 3. Dispatch the processing job asynchronously
        ProcessFundingWebhookJob::dispatch($webhook->id);

        Log::info('Flutterwave webhook queued successfully.', [
            'webhook_id' => $webhook->id,
            'reference' => $txRef,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook queued for processing',
            'webhook_id' => $webhook->id
        ], 200);
    }
}
