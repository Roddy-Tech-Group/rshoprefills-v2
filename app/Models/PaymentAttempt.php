<?php

namespace App\Models;

use App\Domain\Payment\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $order_id
 * @property int $user_id
 * @property string $gateway
 * @property string|null $gateway_reference
 * @property string $idempotency_key
 * @property string $currency
 * @property float $amount
 * @property float $exchange_rate_snapshot
 * @property PaymentStatus $payment_status
 * @property string|null $payment_url
 * @property array|null $verification_payload
 * @property array|null $webhook_payload
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $expires_at
 * @property array|null $metadata
 */
class PaymentAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id',
        'user_id',
        'payable_type',
        'payable_id',
        'gateway',
        'gateway_reference',
        'idempotency_key',
        'currency',
        'amount',
        'exchange_rate_snapshot',
        'payment_status',
        'payment_url',
        'verification_payload',
        'webhook_payload',
        'confirmed_at',
        'failed_at',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'amount' => 'decimal:4',
            'exchange_rate_snapshot' => 'decimal:4',
            'verification_payload' => 'array',
            'webhook_payload' => 'array',
            'confirmed_at' => 'datetime',
            'failed_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }
}
