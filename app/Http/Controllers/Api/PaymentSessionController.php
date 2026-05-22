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
                if (!in_array($session->status, ['pending', 'awaiting_method', 'awaiting_payment', 'awaiting_transfer', 'awaiting_confirmation', 'awaiting_customer_action'])) {
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

    /**
     * Pay/authorize the specified active payment session.
     */
    public function pay(string $id, Request $request)
    {
        $session = PaymentSession::findOrFail($id);

        if (in_array($session->status, ['confirmed', 'failed', 'expired', 'cancelled'])) {
            return response()->json([
                'message' => 'Payment session is in a terminal state: ' . $session->status,
                'status' => $session->status
            ], 400);
        }

        $attempt = $session->paymentAttempt;
        if (!$attempt) {
            return response()->json(['message' => 'Associated payment attempt not found.'], 404);
        }

        $method = $request->input('method');
        $details = $request->input('details', []);

        // Validate that the payment method is supported in the session's currency
        $currency = strtoupper($attempt->currency);
        $supported = [
            'USD' => ['card', 'apple_pay', 'crypto', 'wallet'],
            'EUR' => ['card', 'apple_pay', 'crypto', 'wallet'],
            'GBP' => ['card', 'apple_pay', 'crypto', 'wallet'],
            'NGN' => ['card', 'apple_pay', 'bank_transfer', 'crypto', 'wallet'],
            'GHS' => ['card', 'apple_pay', 'mobile_money', 'crypto', 'wallet'],
            'XAF' => ['card', 'apple_pay', 'mobile_money', 'crypto', 'wallet'],
            'XOF' => ['card', 'apple_pay', 'mobile_money', 'crypto', 'wallet'],
        ];

        if ($session->session_type === 'wallet') {
            $allowedMethods = ['wallet'];
        } elseif ($session->session_type === 'crypto') {
            $allowedMethods = ['crypto'];
        } else {
            $allowedMethods = $supported[$currency] ?? ['card', 'apple_pay'];
        }

        if (!in_array($method, $allowedMethods)) {
            return response()->json([
                'message' => "The payment method {$method} is not available for currency {$currency}."
            ], 422);
        }
        
        $gatewayFactory = app(\App\Domain\Payment\Services\PaymentGatewayFactory::class);

        try {
            $result = null;

            if ($method === 'card') {
                $flwProvider = $gatewayFactory->getProvider('flutterwave');
                
                if ($otp = $request->input('otp')) {
                    $flwRef = $request->input('flw_ref') ?: ($session->payment_payload['flw_ref'] ?? '');
                    $result = $flwProvider->validateOTP($attempt, $otp, $flwRef);
                } 
                elseif ($pin = $request->input('pin')) {
                    $result = $flwProvider->chargeCard($attempt, $details, ['pin' => $pin]);
                }
                else {
                    $request->validate([
                        'details.card_number' => 'required|string',
                        'details.cvv' => 'required|string',
                        'details.expiry_month' => 'required|string',
                        'details.expiry_year' => 'required|string',
                    ]);
                    $result = $flwProvider->chargeCard($attempt, $details);
                }
            } 
            elseif ($method === 'bank_transfer') {
                $flwProvider = $gatewayFactory->getProvider('flutterwave');
                $result = $flwProvider->chargeBankTransfer($attempt);
            } 
            elseif ($method === 'apple_pay') {
                $flwProvider = $gatewayFactory->getProvider('flutterwave');
                $result = $flwProvider->chargeApplePay($attempt, $details);
            }
            elseif ($method === 'mobile_money') {
                $request->validate([
                    'details.phone_number' => 'required|string',
                    'details.network' => 'required|string',
                ]);
                $flwProvider = $gatewayFactory->getProvider('flutterwave');
                $result = $flwProvider->chargeMobileMoney($attempt, $details['phone_number'], $details['network']);
            } 
            elseif ($method === 'crypto') {
                $request->validate([
                    'details.pay_currency' => 'required|string',
                ]);
                $npProvider = $gatewayFactory->getProvider('nowpayments');
                $result = $npProvider->chargeCrypto($attempt, $details['pay_currency']);
            } 
            elseif ($method === 'wallet') {
                $request->validate([
                    'details.auth_token' => 'required|string',
                ]);
                
                $walletProvider = $gatewayFactory->getProvider('wallet');
                
                try {
                    $walletProvider->authorizeTransaction($attempt, $details['auth_token']);
                    $result = [
                        'status' => 'confirmed',
                        'transaction_id' => $attempt->gateway_reference,
                    ];
                } catch (\Exception $e) {
                    $result = [
                        'status' => 'failed',
                        'message' => $e->getMessage(),
                    ];
                }
            }
            else {
                return response()->json(['message' => 'Unsupported payment method.'], 400);
            }

            if (!$result || !isset($result['status'])) {
                throw new \RuntimeException('Gateway returned empty response.');
            }

            if ($result['status'] === 'confirmed') {
                $sessionService = app(\App\Domain\Payment\Services\PaymentSessionService::class);
                $sessionService->confirmSession($session, [
                    'transaction_id' => $result['transaction_id'] ?? $attempt->gateway_reference,
                    'payload' => $result,
                ]);

                if ($attempt->order) {
                    $orderService = app(\App\Domain\Order\Services\OrderService::class);
                    $orderService->transitionPaymentStatus($attempt->order, \App\Domain\Payment\Enums\PaymentStatus::Paid, $result);
                    foreach ($attempt->order->items as $item) {
                        \App\Jobs\FulfillOrderItemJob::dispatch($item);
                    }
                } 
                elseif ($attempt->payable_type === \App\Models\WalletFunding::class) {
                    $funding = $attempt->payable;
                    if ($funding && $funding->status !== \App\Domain\Shared\Enums\FundingStatus::Completed) {
                        $fundingService = app(\App\Domain\Wallet\Services\WalletFundingService::class);
                        $fundingService->processSuccessfulFunding(
                            $funding->reference,
                            $result['transaction_id'] ?? 'DIRECT-' . uniqid(),
                            $result
                        );
                    }
                }
            } 
            elseif ($result['status'] === 'awaiting_customer_action') {
                $session->transitionTo('awaiting_customer_action');
                $session->payment_payload = array_merge($session->payment_payload ?? [], $result);
                $session->save();
            } 
            elseif ($result['status'] === 'awaiting_transfer') {
                $session->transitionTo('awaiting_transfer');
                $session->payment_payload = array_merge($session->payment_payload ?? [], $result);
                $session->save();
            } 
            elseif ($result['status'] === 'awaiting_confirmation') {
                $session->transitionTo('awaiting_confirmation');
                $session->payment_payload = array_merge($session->payment_payload ?? [], $result);
                $session->save();
            } 
            elseif ($result['status'] === 'failed') {
                $sessionService = app(\App\Domain\Payment\Services\PaymentSessionService::class);
                $sessionService->failSession($session, $result['message'] ?? 'Payment failed', $result);
                
                if ($attempt->order) {
                    $orderService = app(\App\Domain\Order\Services\OrderService::class);
                    $orderService->transitionPaymentStatus($attempt->order, \App\Domain\Payment\Enums\PaymentStatus::Failed, $result);
                } 
                elseif ($attempt->payable_type === \App\Models\WalletFunding::class) {
                    $funding = $attempt->payable;
                    if ($funding) {
                        $funding->update(['status' => \App\Domain\Shared\Enums\FundingStatus::Failed]);
                    }
                }
            }

            return new PaymentSessionResource($session->fresh());

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Payment session pay failed: " . $e->getMessage());
            return response()->json(['message' => 'Payment processing failed: ' . $e->getMessage()], 400);
        }
    }
}
