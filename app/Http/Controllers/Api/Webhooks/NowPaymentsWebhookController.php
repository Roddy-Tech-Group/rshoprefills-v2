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

        $signingData = json_encode($request->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $calculatedSig = hash_hmac('sha512', $signingData, $ipnSecret);

        if ($signature === '' || ! hash_equals($calculatedSig, $signature)) {
            Log::warning('NowPayments webhook rejected: invalid or missing signature');

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $invoiceId = $payload['invoice_id'] ?? null;
        $paymentStatus = $payload['payment_status'] ?? null;

        // Redacted log: only the invoice + status, never the full payload.
        Log::info('NowPayments webhook received', ['invoice_id' => $invoiceId, 'payment_status' => $paymentStatus]);

        if (! $invoiceId) {
            return response()->json(['message' => 'Missing invoice ID'], 400);
        }

        // Locate attempt
        $attempt = PaymentAttempt::where('gateway_reference', $invoiceId)->first();

        if (! $attempt) {
            Log::warning("NowPayments webhook: payment attempt not found for invoice {$invoiceId}");

            return response()->json(['message' => 'Payment attempt not found'], 404);
        }

        $attempt->webhook_payload = $payload;
        $attempt->save();

        if (in_array(strtolower($paymentStatus), ['confirmed', 'sending', 'finished'])) {
            VerifyPaymentJob::dispatch($attempt);
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }
}
