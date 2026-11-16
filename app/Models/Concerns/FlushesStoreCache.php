<?php

namespace App\Models\Concerns;

use App\Support\StoreCache;

/** Clears the cached copy as soon as the owner changes the record (REQ-DB-26). */
trait FlushesStoreCache
{
    public static function bootFlushesStoreCache(): void
    {
        $flush = fn () => app(StoreCache::class)->forget(static::storeCacheKey());

        static::saved($flush);
        static::deleted($flush);
    }

    abstract public static function storeCacheKey(): string;
}
