<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentSession;
use App\Http\Resources\PaymentSessionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentSessionController extends Controller
{
    /**
     * Display the specified payment session details.
     */
    public function show(string $id)
    {
        $session = PaymentSession::findOrFail($id);

        return new PaymentSessionResource($session);
    }

    /**
     * Retrieve the current raw status of the payment session (optimized for fast polling).
     */
    public function status(string $id)
    {
        $session = PaymentSession::select('id', 'status', 'expires_at', 'confirmed_at', 'failed_at')->findOrFail($id);

        return response()->json([
            'id' => $session->id,
            'status' => $session->status,
            'is_expired' => $session->expires_at ? $session->expires_at->isPast() : false,
            'confirmed_at' => $session->confirmed_at?->toIso8601String(),
            'failed_at' => $session->failed_at?->toIso8601String(),
        ]);
    }

    /**
     * Cancel the specified active payment session.
     */
    public function cancel(string $id)
    {
        $session = PaymentSession::findOrFail($id);

        try {
            DB::transaction(function () use ($session) {
                // Check if session can be cancelled
                if (!in_array($session->status, ['pending', 'awaiting_payment'])) {
                    throw new \DomainException("Cannot cancel payment session that is already {$session->status}.");
                }

                $session->transitionTo('cancelled');

                // Cancel polymorphic payment attempt
                $attempt = $session->paymentAttempt;
                if ($attempt && $attempt->payment_status !== \App\Domain\Payment\Enums\PaymentStatus::Paid) {
                    $attempt->update([
                        'payment_status' => \App\Domain\Payment\Enums\PaymentStatus::Failed,
                        'failed_at' => now(),
                    ]);
                }
            });

            return response()->json([
                'message' => 'Payment session cancelled successfully.',
                'status' => 'cancelled',
                'is_expired' => $session->expires_at ? $session->expires_at->isPast() : false,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Trigger explicit verify/check of the payment session with the gateway.
     */
    public function verify(string $id, Request $request)
    {
        $session = PaymentSession::findOrFail($id);

        if ($session->status === 'confirmed') {
            return response()->json([
                'status' => 'confirmed',
                'message' => 'Payment session already confirmed.'
            ]);
        }

        $attempt = $session->paymentAttempt;
        if (!$attempt) {
            return response()->json(['message' => 'Associated payment attempt not found.'], 404);
        }

        // Retrieve transaction ID from request if provided (e.g. from Flutterwave inline callback response)
        if ($txId = $request->input('transaction_id')) {
            $attempt->gateway_reference = $txId;
            $attempt->save();
        }

        try {
            $gatewayFactory = app(\App\Domain\Payment\Services\PaymentGatewayFactory::class);
            $provider = $gatewayFactory->getProvider($attempt->gateway);
            
            // Call verifyPayment directly
            $isPaid = $provider->verifyPayment($attempt);

            if ($isPaid) {
                // Confirm the payment session
                $sessionService = app(\App\Domain\Payment\Services\PaymentSessionService::class);
                $sessionService->confirmSession($session, [
                    'transaction_id' => $attempt->gateway_reference,
                    'payload' => $attempt->verification_payload,
                ]);

                // If it is an order checkout payment, fulfill it
                if ($attempt->order) {
                    $orderService = app(\App\Domain\Order\Services\OrderService::class);
                    $orderService->transitionPaymentStatus($attempt->order, \App\Domain\Payment\Enums\PaymentStatus::Paid, $attempt->verification_payload);
                    
                    // Dispatch fulfillment jobs
                    foreach ($attempt->order->items as $item) {
                        \App\Jobs\FulfillOrderItemJob::dispatch($item);
                    }
                } 
                // If it is wallet funding
                elseif ($attempt->payable_type === \App\Models\WalletFunding::class) {
                    $funding = $attempt->payable;
                    if ($funding && $funding->status !== \App\Domain\Shared\Enums\FundingStatus::Completed) {
                        $fundingService = app(\App\Domain\Wallet\Services\WalletFundingService::class);
                        // Process funding safely
                        $fundingService->processSuccessfulFunding(
                            $funding->reference,
                            $attempt->gateway_reference ?: 'DIRECT-' . uniqid(),
                            $attempt->verification_payload ?: []
                        );
                    }
                }

                return response()->json([
                    'status' => 'confirmed',
                    'message' => 'Payment verified and session confirmed.'
                ]);
            }

            return response()->json([
                'status' => $session->fresh()->status,
                'message' => 'Payment could not be verified yet. Please try again.'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Explicit payment session verify failed: " . $e->getMessage());
            return response()->json(['message' => 'Verification failed: ' . $e->getMessage()], 500);
        }
    }
}
