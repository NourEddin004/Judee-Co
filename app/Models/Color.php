<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use App\Support\StoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** A named color ("Olive Noir") in one of the shop filter's color families (C.2 Color). */
class Color extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity;

    protected $fillable = ['shade_id', 'slug', 'name_en', 'name_ar', 'swatch'];

    public static function storeCacheKey(): string
    {
        return 'catalog';
    }

    protected static function booted(): void
    {
        // Its name in addresses, /product/wrap-set?color=navy, from the English name (REQ-DMO-98).
        static::saving(function (Color $color) {
            if (blank($color->slug)) {
                $color->slug = static::freeSlug((string) $color->name_en, $color->getKey());
            }
        });
        // Rack garments link to /product/…?color={slug}.
        static::saved(fn () => app(StoreCache::class)->forget('rack'));
    }

    public static function freeSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($name) ?: 'color', 56, '');
        $slug = $base;

        for ($n = 2; static::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists(); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }

    public function shade(): BelongsTo
    {
        return $this->belongsTo(Shade::class);
    }

    public function productColors(): HasMany
    {
        return $this->hasMany(ProductColor::class);
    }

    /** The swatch customers see: the owner's own color, else the color family's (REQ-CMS-46). */
    public function swatchHex(): string
    {
        return $this->swatch ?: (string) $this->shade?->swatch;
    }
}
