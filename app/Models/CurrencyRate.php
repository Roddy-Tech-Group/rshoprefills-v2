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
     * Resolve the active rate row for a currency code. Falls back to the USD row,
     * then to a synthetic 1:1 USD rate, so callers always get a usable object even
     * if the table is empty or the requested currency is missing/inactive.
     */
    public static function resolve(?string $code): self
    {
        $code = strtoupper(trim((string) $code)) ?: 'USD';

        $rate = static::query()->where('is_active', true)->where('code', $code)->first()
            ?? static::query()->where('is_active', true)->where('code', 'USD')->first();

        return $rate ?? new self([
            'code' => 'USD',
            'name' => 'United States Dollar',
            'type' => 'fiat',
            'rate_per_usd' => 1.0,
        ]);
    }

    /**
     * Convert a USD amount into this currency using rate_per_usd.
     */
    public function convert(float $usd): float
    {
        return $usd * (float) $this->rate_per_usd;
    }

    /**
     * Full URL to the currency icon when set. Falls back to null so the view can
     * render a coloured initial badge instead.
     */
    public function iconUrl(): ?string
    {
        return $this->icon_path ? asset('assets/'.$this->icon_path) : null;
    }
}
