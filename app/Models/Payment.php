<?php

namespace App\Models;

use App\Domain\Shared\Enums\PaymentGateway;
use App\Domain\Shared\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Records a payment attempt against an order.
 *
 * An order can have multiple payments — a failed Flutterwave attempt
 * followed by a successful wallet payment, for example. Each payment
 * tracks its own gateway, status, and response data independently.
 *
 * paid_at records the exact moment the gateway confirmed the payment,
 * which is distinct from updated_at (last record modification).
 *
 * gateway_response stores the full webhook/callback payload from the
 * payment provider for debugging and compliance purposes.
 *
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property PaymentGateway $gateway
 * @property string|null $gateway_transaction_id
 * @property PaymentStatus $status
 * @property string $amount
 * @property string $currency
 * @property array|null $gateway_response
 * @property Carbon|null $paid_at
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'gateway',
        'gateway_transaction_id',
        'status',
        'amount',
        'currency',
        'gateway_response',
        'paid_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway' => PaymentGateway::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:4',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the order this payment is for.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who made this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
