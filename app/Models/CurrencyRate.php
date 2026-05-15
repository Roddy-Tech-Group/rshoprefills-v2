<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'rate_per_usd',
        'icon_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_usd' => 'decimal:8',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFiat($query)
    {
        return $query->where('type', 'fiat');
    }

    public function scopeCrypto($query)
    {
        return $query->where('type', 'crypto');
    }

    /**
     * Full URL to the currency icon when set. Falls back to null so the view can
     * render a coloured initial badge instead.
     */
    public function iconUrl(): ?string
    {
        return $this->icon_path ? asset('assets/' . $this->icon_path) : null;
    }
}
