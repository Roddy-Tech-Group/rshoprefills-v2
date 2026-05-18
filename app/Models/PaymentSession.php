<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Represents a payment session container for inline and embedded checkouts/deposits.
 *
 * @property string $id
 * @property string $payment_attempt_id
 * @property string $provider
 * @property string $session_type
 * @property string $status
 * @property string $client_reference
 * @property string|null $provider_reference
 * @property string|null $provider_transaction_id
 * @property float $amount
 * @property string $currency
 * @property string $display_currency
 * @property float $exchange_rate_snapshot
 * @property array|null $payment_payload
 * @property array|null $checkout_context
 * @property string $customer_email
 * @property string|null $customer_ip
 * @property array|null $device_metadata
 * @property Carbon|null $expires_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $failed_at
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PaymentSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'payment_attempt_id',
        'provider',
        'session_type',
        'status',
        'client_reference',
        'provider_reference',
        'provider_transaction_id',
        'amount',
        'currency',
        'display_currency',
        'exchange_rate_snapshot',
        'payment_payload',
        'checkout_context',
        'customer_email',
        'customer_ip',
        'device_metadata',
        'expires_at',
        'confirmed_at',
        'failed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'exchange_rate_snapshot' => 'decimal:4',
            'payment_payload' => 'array',
            'checkout_context' => 'array',
            'device_metadata' => 'array',
            'metadata' => 'array',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * Get the associated PaymentAttempt.
     */
    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    /**
     * Get the Order associated with this session.
     */
    public function order()
    {
        return $this->hasOneThrough(
            Order::class,
            PaymentAttempt::class,
            'id', // Foreign key on payment_attempts table
            'id', // Foreign key on orders table
            'payment_attempt_id', // Local key on payment_sessions table
            'order_id' // Local key on payment_attempts table
        );
    }

    /**
     * Get the WalletFunding associated with this session.
     */
    public function walletFunding()
    {
        return $this->hasOneThrough(
            WalletFunding::class,
            PaymentAttempt::class,
            'id', // Foreign key on payment_attempts table
            'id', // Foreign key on wallet_fundings table
            'payment_attempt_id', // Local key on payment_sessions table
            'payable_id' // Local key on payment_attempts table
        )->where('payable_type', WalletFunding::class);
    }

    /**
     * Advance the session status strictly via the allowed transition matrix.
     *
     * Allowed transitions:
     * pending -> awaiting_payment
     * awaiting_payment -> processing
     * processing -> confirmed
     * processing -> failed
     * awaiting_payment -> expired
     * awaiting_payment -> cancelled
     */
    public function transitionTo(string $newStatus): void
    {
        $allowedTransitions = [
            'pending' => ['awaiting_payment'],
            'awaiting_payment' => ['processing', 'expired', 'cancelled', 'confirmed', 'failed'],
            'processing' => ['confirmed', 'failed'],
            'confirmed' => [],
            'failed' => [],
            'expired' => [],
            'cancelled' => [],
        ];

        $currentStatus = $this->status;

        // Idempotent transition
        if ($currentStatus === $newStatus) {
            return;
        }

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            throw new \DomainException("Invalid payment session status transition from {$currentStatus} to {$newStatus}.");
        }

        $this->status = $newStatus;

        if ($newStatus === 'confirmed') {
            $this->confirmed_at = now();
        } elseif ($newStatus === 'failed') {
            $this->failed_at = now();
        }

        $this->save();
    }
}
