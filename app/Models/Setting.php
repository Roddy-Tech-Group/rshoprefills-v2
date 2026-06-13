<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'description'];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get a setting value by key, with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return cache()->rememberForever("settings.{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Rcoin-to-USD conversion rate, the single source of truth for valuing
     * Rcoin in dollars. Previously every caller hardcoded its own fallback
     * (0.0001 / 0.005 / 0.01 were all in use), so the same balance could be
     * valued 100x differently page to page when the DB setting was unset.
     * One default lives here now: 0.005 (1 Rcoin = half a US cent).
     */
    public static function rcoinUsdRate(): float
    {
        return (float) static::get('rcoin_usd_rate', 0.005);
    }

    /**
     * Set a setting value and clear its cache.
     */
    public static function set(string $key, mixed $value, string $type = 'string', ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'description' => $description]
        );

        cache()->forget("settings.{$key}");
    }
}
