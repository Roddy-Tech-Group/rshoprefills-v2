<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'display_currency',
        'display_amount',
        'provider_cost_usd',
        'exchange_rate_snapshot',
        'markup_amount',
        'unit_price_snapshot',
        'subtotal_snapshot',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'display_amount' => 'decimal:4',
            'provider_cost_usd' => 'decimal:4',
            'exchange_rate_snapshot' => 'decimal:4',
            'markup_amount' => 'decimal:4',
            'unit_price_snapshot' => 'decimal:4',
            'subtotal_snapshot' => 'decimal:4',
            'metadata_snapshot' => 'array',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
