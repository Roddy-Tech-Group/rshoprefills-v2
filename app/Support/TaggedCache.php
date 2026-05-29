<?php

namespace App\Support;

use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Tag-aware cache accessor.
 *
 * Cache tagging is only supported by a subset of stores (redis, memcached,
 * array). On non-taggable stores (file, database) calling Cache::tags() throws
 * BadMethodCallException. This helper applies tags when the active store
 * supports them and otherwise falls back to the default store, so tag-based
 * grouping works in production (Redis) without breaking local/database caching.
 */
class TaggedCache
{
    /**
     * @param  array<int, string>  $tags
     */
    public static function for(array $tags): Repository
    {
        return Cache::getStore() instanceof TaggableStore
            ? Cache::tags($tags)
            : Cache::store();
    }
}
