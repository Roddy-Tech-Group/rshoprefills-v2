<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Generic site-wide key/value setting. Use the `get()` / `put()` statics so
 * the cache stays consistent — direct Eloquent writes skip cache invalidation.
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property string $group
 * @property string|null $description
 */
class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'description'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    /**
     * Read a setting with a default fallback. Cached for an hour so hot keys
     * (homepage hero stats, review aggregate) don't hit the DB on every page
     * render. Call `put()` to update — it busts the cache key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cached = Cache::remember(
            self::cacheKey($key),
            now()->addHour(),
            fn () => self::query()->where('key', $key)->value('value'),
        );

        return $cached !== null ? $cached : $default;
    }

    public static function put(string $key, mixed $value, string $group = 'general', ?string $description = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'description' => $description],
        );

        Cache::forget(self::cacheKey($key));

        return $setting;
    }

    private static function cacheKey(string $key): string
    {
        return 'site_setting:'.$key;
    }
}
