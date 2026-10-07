<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class MagazineCacheService
{
    /**
     * Cache an item using tags if supported, or falling back to a tag registry.
     */
    public static function remember(string|array $tags, string $key, int $ttlSeconds, Closure $callback): mixed
    {
        $tags = (array) $tags;

        if (Cache::supportsTags()) {
            return Cache::tags($tags)->remember($key, $ttlSeconds, $callback);
        }

        self::registerTagKey($tags, $key);

        return Cache::remember($key, $ttlSeconds, $callback);
    }

    /**
     * Invalidate all cache items tagged with given tags.
     */
    public static function flush(string|array $tags): void
    {
        $tags = (array) $tags;

        if (Cache::supportsTags()) {
            Cache::tags($tags)->flush();

            return;
        }

        foreach ($tags as $tag) {
            $registryKey = "tag_registry:{$tag}";
            $keys = Cache::get($registryKey, []);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
            Cache::forget($registryKey);
        }
    }

    /**
     * Register a cache key under tags for stores without native tagging.
     */
    protected static function registerTagKey(array $tags, string $key): void
    {
        foreach ($tags as $tag) {
            $registryKey = "tag_registry:{$tag}";
            $keys = Cache::get($registryKey, []);
            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
                Cache::forever($registryKey, $keys);
            }
        }
    }
}
