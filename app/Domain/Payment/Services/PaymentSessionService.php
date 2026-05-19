<?php

namespace App\Domain\Payment\Services;

use App\Models\PaymentSession;
use App\Models\PaymentAttempt;
use App\Models\Order;
use App\Models\WalletFunding;
use App\Domain\Payment\Events\PaymentSessionCreated;
use App\Domain\Payment\Events\PaymentSessionConfirmed;
use App\Domain\Payment\Events\PaymentSessionFailed;
use App\Domain\Payment\Events\PaymentSessionExpired;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentSessionService
{
    /**
     * Create a payment session for an Order checkout attempt.
     */
    public function createForOrder(Order $order, PaymentAttempt $attempt, array $providerInitData): PaymentSession
    {
        return DB::transaction(function () use ($order, $attempt, $providerInitData) {
            $expiresAt = $attempt->expires_at ?? now()->addMinutes(15);

            $session = PaymentSession::create([
                'payment_attempt_id' => $attempt->id,
                'provider' => $attempt->gateway,
                'session_type' => $this->resolveSessionType($attempt->gateway),
                'status' => 'pending',
                'client_reference' => 'SESS_' . Str::random(40),
                'provider_reference' => $providerInitData['gateway_reference'] ?? $attempt->gateway_reference,
                'provider_transaction_id' => null,
                'amount' => $attempt->amount,
                'currency' => $attempt->currency,
                'display_currency' => $order->display_currency,
                'exchange_rate_snapshot' => $attempt->exchange_rate_snapshot ?? 1.0000,
                'payment_payload' => $providerInitData,
                'checkout_context' => [
                    'order_number' => $order->order_number,
                    'items_count' => $order->items()->count(),
                ],
                'customer_email' => $order->user->email,
                'customer_ip' => request()->ip(),
                'device_metadata' => [
                    'user_agent' => request()->userAgent(),
                ],
                'expires_at' => $expiresAt,
            ]);

            $initialStatus = match ($session->session_type) {
                'wallet' => 'awaiting_payment',
                'crypto' => 'awaiting_transfer',
                default => 'awaiting_method',
            };
            $session->transitionTo($initialStatus);

            event(new PaymentSessionCreated($session));

            return $session;
        });
    }

    /**
     * Create a payment session for a Wallet Deposit funding attempt.
     */
    public function createForWalletFunding(WalletFunding $funding, PaymentAttempt $attempt, array $providerInitData): PaymentSession
    {
        return DB::transaction(function () use ($funding, $attempt, $providerInitData) {
            $expiresAt = $attempt->expires_at ?? now()->addMinutes(15);

            $session = PaymentSession::create([
                'payment_attempt_id' => $attempt->id,
                'provider' => $attempt->gateway,
                'session_type' => $this->resolveSessionType($attempt->gateway),
                'status' => 'pending',
                'client_reference' => 'SESS_' . Str::random(40),
                'provider_reference' => $providerInitData['gateway_reference'] ?? $attempt->gateway_reference,
                'provider_transaction_id' => null,
                'amount' => $attempt->amount,
                'currency' => $attempt->currency,
                'display_currency' => $funding->currency->value,
                'exchange_rate_snapshot' => $attempt->exchange_rate_snapshot ?? 1.0000,
                'payment_payload' => $providerInitData,
                'checkout_context' => [
                    'wallet_funding_reference' => $funding->reference,
                ],
                'customer_email' => $funding->user->email,
                'customer_ip' => request()->ip(),
                'device_metadata' => [
                    'user_agent' => request()->userAgent(),
                ],
                'expires_at' => $expiresAt,
            ]);

            $initialStatus = match ($session->session_type) {
                'wallet' => 'awaiting_payment',
                'crypto' => 'awaiting_transfer',
                default => 'awaiting_method',
            };
            $session->transitionTo($initialStatus);

            event(new PaymentSessionCreated($session));

            return $session;
        });
    }

    /**
     * Transition payment session to confirmed.
     */
    public function confirmSession(PaymentSession $session, ?array $payload = null): void
    {
        DB::transaction(function () use ($session, $payload) {
            $session = PaymentSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if ($session->status === 'confirmed') {
                return;
            }

            if (in_array($session->status, ['awaiting_payment', 'awaiting_method', 'awaiting_transfer', 'awaiting_confirmation', 'awaiting_customer_action'])) {
                $session->transitionTo('processing');
            }

            $session->transitionTo('confirmed');

            if ($payload) {
                $session->metadata = array_merge($session->metadata ?? [], ['confirmation_payload' => $payload]);
                if (isset($payload['transaction_id'])) {
                    $session->provider_transaction_id = $payload['transaction_id'];
                }
            }

            $session->save();

            event(new PaymentSessionConfirmed($session));
        });
    }

    /**
     * Transition payment session to failed.
     */
    public function failSession(PaymentSession $session, string $reason, ?array $payload = null): void
    {
        DB::transaction(function () use ($session, $reason, $payload) {
            $session = PaymentSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if (in_array($session->status, ['confirmed', 'failed', 'expired', 'cancelled'])) {
                return;
            }

            if (in_array($session->status, ['awaiting_payment', 'awaiting_method', 'awaiting_transfer', 'awaiting_confirmation', 'awaiting_customer_action'])) {
                $session->transitionTo('processing');
            }

            $session->transitionTo('failed');

            $session->metadata = array_merge($session->metadata ?? [], [
                'failure_reason' => $reason,
                'failure_payload' => $payload,
            ]);
            $session->save();

            event(new PaymentSessionFailed($session, $reason));
        });
    }

    /**
     * Transition payment session to expired.
     */
    public function expireSession(PaymentSession $session): void
    {
        DB::transaction(function () use ($session) {
            $session = PaymentSession::where('id', $session->id)->lockForUpdate()->firstOrFail();

            if (!in_array($session->status, ['awaiting_payment', 'awaiting_method', 'awaiting_transfer', 'awaiting_confirmation', 'awaiting_customer_action'])) {
                return;
            }

            $session->transitionTo('expired');
            event(new PaymentSessionExpired($session));
        });
    }

    /**
     * Resolve type of session from gateway name.
     */
    private function resolveSessionType(string $gateway): string
    {
        return match ($gateway) {
            'wallet' => 'wallet',
            'nowpayments', 'crypto' => 'crypto',
            'flutterwave' => 'card',
            default => 'card',
        };
    }
}
