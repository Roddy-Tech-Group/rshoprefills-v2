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
        $signature = (string) $request->header('verif-hash');
        $expectedSignature = (string) config('services.flutterwave.webhook_hash');

        // Fail closed: a webhook secret must be configured and must match the
        // signature header exactly. There is no mock/bypass fallback, so an
        // unsigned or mismatched request is always rejected. hash_equals guards
        // against timing attacks; reading via config() survives config:cache.
        if ($expectedSignature === '' || ! hash_equals($expectedSignature, $signature)) {
            Log::warning('Flutterwave webhook rejected: invalid or missing signature');

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $txRef = $payload['txRef'] ?? ($payload['data']['tx_ref'] ?? null);
        $status = $payload['status'] ?? ($payload['data']['status'] ?? null);
        $id = $payload['id'] ?? ($payload['data']['id'] ?? null);

        // Redacted log: never persist the full provider payload (card data,
        // customer PII, auth metadata). Only the reference + status are recorded.
        Log::info('Flutterwave webhook received', ['tx_ref' => $txRef, 'status' => $status]);

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
