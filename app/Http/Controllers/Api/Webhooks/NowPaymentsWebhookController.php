<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use App\Jobs\VerifyPaymentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NowPaymentsWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('NowPayments Webhook received', $request->all());

        // Validate crypt signature if configured
        $signature = $request->header('x-nowpayments-sig');
        $ipnSecret = config('services.nowpayments.ipn_secret', 'NOWPAYMENTS_IPN_MOCK');

        if ($ipnSecret !== 'NOWPAYMENTS_IPN_MOCK' && empty($signature)) {
            Log::warning('NowPayments webhook: missing signature header');
            return response()->json(['message' => 'Missing signature'], 401);
        }

        // Verify crypt signature mathematically if possible or bypass in MOCK
        if ($ipnSecret !== 'NOWPAYMENTS_IPN_MOCK') {
            $signingData = json_encode($request->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $calculatedSig = hash_hmac('sha512', $signingData, $ipnSecret);

            if ($signature !== $calculatedSig) {
                Log::warning('NowPayments webhook signature mismatch', [
                    'received' => $signature,
                    'calculated' => $calculatedSig,
                ]);
                return response()->json(['message' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->all();
        $invoiceId = $payload['invoice_id'] ?? null;
        $paymentStatus = $payload['payment_status'] ?? null;

        if (!$invoiceId) {
            return response()->json(['message' => 'Missing invoice ID'], 400);
        }

        // Locate attempt
        $attempt = PaymentAttempt::where('gateway_reference', $invoiceId)->first();

        if (!$attempt) {
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
