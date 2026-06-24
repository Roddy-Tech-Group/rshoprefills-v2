<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCardRate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(GiftCardBrand::class, 'brand_id');
    }

    public function trades(): HasMany
    {
        return $this->hasMany(GiftCardTrade::class, 'rate_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }
}
