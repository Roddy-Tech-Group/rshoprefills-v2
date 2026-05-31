<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $base_currency
 * @property string $target_currency
 * @property float $rate
 * @property string $provider
 * @property string $source
 * @property bool $is_active
 * @property Carbon $fetched_at
 * @property Carbon|null $expires_at
 */
class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'provider',
        'source',
        'is_active',
        'fetched_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'is_active' => 'boolean',
            'fetched_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
