<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\VerifyPaymentJob;
use App\Models\PaymentAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NowPaymentsWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = (string) $request->header('x-nowpayments-sig');
        $ipnSecret = (string) config('services.nowpayments.ipn_secret');

        // Fail closed: the IPN secret must be configured. There is no mock/bypass
        // fallback, so a webhook is only trusted when its HMAC-SHA512 signature
        // matches. hash_equals guards against timing attacks.
        if ($ipnSecret === '') {
            Log::warning('NowPayments webhook rejected: IPN secret not configured');

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // NOWPayments signs the JSON body with its keys sorted alphabetically
        // (recursively) using HMAC-SHA512. We must sort identically before hashing
        // - otherwise the signature never matches and every real payment
        // notification is rejected, so orders never auto-confirm.
        $signingData = json_encode($this->sortRecursive($request->all()), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $calculatedSig = hash_hmac('sha512', $signingData, $ipnSecret);

        if ($signature === '' || ! hash_equals($calculatedSig, $signature)) {
            Log::warning('NowPayments webhook rejected: invalid or missing signature');

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        // The /payment flow (what this integration uses) keys every notification
        // by payment_id - which is exactly what the provider stores as
        // gateway_reference. invoice_id only exists for the separate /invoice flow
        // and is absent here, so the old lookup-by-invoice_id never matched and
        // confirmed payments were silently dropped.
        $paymentId = $payload['payment_id'] ?? null;
        $paymentStatus = $payload['payment_status'] ?? null;

        // Redacted log: only the payment id + status, never the full payload.
        Log::info('NowPayments webhook received', ['payment_id' => $paymentId, 'payment_status' => $paymentStatus]);

        if (! $paymentId) {
            return response()->json(['message' => 'Missing payment ID'], 400);
        }

        // Locate attempt by the stored gateway_reference (= NOWPayments payment_id).
        $attempt = PaymentAttempt::where('gateway_reference', (string) $paymentId)->first();

        if (! $attempt) {
            Log::warning("NowPayments webhook: payment attempt not found for payment {$paymentId}");

            return response()->json(['message' => 'Payment attempt not found'], 404);
        }

        $attempt->webhook_payload = $payload;
        $attempt->save();

        if (in_array(strtolower((string) $paymentStatus), ['confirmed', 'sending', 'finished'])) {
            VerifyPaymentJob::dispatch($attempt);
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    /**
     * Recursively sort an array by its keys so the JSON we sign byte-matches the
     * JSON NOWPayments signed - its IPN signature is computed over key-sorted JSON.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sortRecursive(array $data): array
    {
        ksort($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sortRecursive($value);
            }
        }

        return $data;
    }
}
