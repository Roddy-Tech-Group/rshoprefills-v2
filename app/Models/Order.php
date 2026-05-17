<?php

namespace App\Models;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Fulfillment\Enums\FulfillmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $user_id
 * @property string $order_number
 * @property string|null $cart_id
 * @property string $settlement_currency
 * @property string $display_currency
 * @property float $subtotal_amount
 * @property float $markup_amount
 * @property float $total_amount
 * @property string $payment_method
 * @property PaymentStatus $payment_status
 * @property FulfillmentStatus $fulfillment_status
 * @property OrderStatus $order_status
 * @property string|null $provider_status
 * @property string|null $provider_reference
 * @property Carbon|null $placed_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $failed_at
 * @property array|null $metadata
 */
class Order extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'order_number',
        'cart_id',
        'settlement_currency',
        'display_currency',
        'subtotal_amount',
        'markup_amount',
        'total_amount',
        'payment_method',
        'payment_status',
        'fulfillment_status',
        'order_status',
        'provider_status',
        'provider_reference',
        'placed_at',
        'completed_at',
        'failed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'order_status' => OrderStatus::class,
            'subtotal_amount' => 'decimal:4',
            'markup_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'placed_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }
}
