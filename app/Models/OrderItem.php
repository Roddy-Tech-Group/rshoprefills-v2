<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Represents a single product line within an order.
 *
 * product_type + product_id form a pseudo-polymorphic reference to the
 * catalog. product_name is snapshotted at purchase time so the record
 * remains accurate even if the catalog product is renamed or removed.
 *
 * Each item tracks its own fulfillment_status independently — one item
 * can be delivered while another retries. fulfillment_data stores the
 * Zendit API response (codes, PINs, voucher numbers).
 *
 * @property int $id
 * @property int $order_id
 * @property string $product_type
 * @property string|null $product_id
 * @property string $product_name
 * @property int $quantity
 * @property string $unit_price
 * @property string $total_price
 * @property string $currency
 * @property string $fulfillment_status
 * @property array|null $fulfillment_data
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_type',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
        'currency',
        'fulfillment_status',
        'fulfillment_data',
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
            'quantity' => 'integer',
            'unit_price' => 'decimal:4',
            'total_price' => 'decimal:4',
            'fulfillment_data' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the order this item belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
