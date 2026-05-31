<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\VerifyPaymentJob;
use App\Models\PaymentAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlutterwaveWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Flutterwave Webhook received', $request->all());

        $signature = $request->header('verif-hash');
        $expectedSignature = env('FLUTTERWAVE_SECRET_HASH', 'FLW_SECRET_HASH_MOCK');

        // Verify webhook signature (allow mock signature in development)
        if ($expectedSignature !== 'FLW_SECRET_HASH_MOCK' && $signature !== $expectedSignature) {
            Log::warning('Flutterwave webhook signature mismatch', [
                'received' => $signature,
                'expected' => $expectedSignature,
            ]);

            return response()->json(['message' => 'Unauthorized signature'], 401);
        }

        $payload = $request->all();
        $txRef = $payload['txRef'] ?? ($payload['data']['tx_ref'] ?? null);
        $status = $payload['status'] ?? ($payload['data']['status'] ?? null);
        $id = $payload['id'] ?? ($payload['data']['id'] ?? null);

        if (! $txRef) {
            return response()->json(['message' => 'Missing transaction reference'], 400);
        }

        // Locate correct PaymentAttempt
        $attempt = PaymentAttempt::where('idempotency_key', $txRef)->first();

        if (! $attempt) {
            Log::warning("Flutterwave webhook: payment attempt not found for reference {$txRef}");

            return response()->json(['message' => 'Payment attempt not found'], 404);
        }

        // Save raw response in webhook payload for history/audit
        $attempt->webhook_payload = $payload;
        $attempt->gateway_reference = $id ?? $attempt->gateway_reference;
        $attempt->save();

        if (strtolower($status) === 'successful') {
            // Dispatch async status checking job
            VerifyPaymentJob::dispatch($attempt);
        }

        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }
}
