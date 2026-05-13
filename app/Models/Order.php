<?php

namespace App\Models;

use App\Domain\Shared\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Represents a customer order containing one or more digital products.
 *
 * Orders use soft deletes — we never permanently remove order records
 * for audit and compliance reasons. The order_number is a human-readable
 * reference generated in application code (e.g., RSR-20260513-A1B2).
 *
 * completed_at records when ALL items have been fulfilled, which is
 * distinct from updated_at (last record modification).
 *
 * @property int $id
 * @property int $user_id
 * @property string $order_number
 * @property OrderStatus $status
 * @property string $subtotal
 * @property string $tax
 * @property string $total
 * @property string $currency
 * @property string|null $notes
 * @property array|null $metadata
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'tax',
        'total',
        'currency',
        'notes',
        'metadata',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:4',
            'tax' => 'decimal:4',
            'total' => 'decimal:4',
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the user who placed this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the line items in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the payment attempts for this order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
