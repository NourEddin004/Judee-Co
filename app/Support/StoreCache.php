<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Data that rarely changes (categories, settings, delivery cities, the hero rack, site content)
 * is served from the server's memory cache (REQ-DB-25) and dropped when the owner edits it (REQ-DB-26).
 */
class StoreCache
{
    public const KEYS = ['catalog', 'settings', 'delivery', 'rack', 'content'];

    public function __construct(private readonly Repository $cache) {}

    public function remember(string $key, Closure $callback): mixed
    {
        return $this->cache->rememberForever("store.{$key}", $callback);
    }

    public function forget(string $key): void
    {
        $this->cache->forget("store.{$key}");
    }

    public function flush(): void
    {
        foreach (self::KEYS as $key) {
            $this->forget($key);
        }
    }
}
