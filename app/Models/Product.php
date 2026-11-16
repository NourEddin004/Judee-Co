<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use App\Models\Scopes\PublishedScope;
use App\Support\StoreCache;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One piece in one style, sold in one or more colors (REQ-DB-37). */
#[ScopedBy(PublishedScope::class)]
class Product extends Model
{
    use HasTranslations, LogsActivity;

    protected $fillable = [
        'category_id', 'slug', 'name_en', 'name_ar', 'description_en', 'description_ar',
        'note_en', 'note_ar', 'badge_en', 'badge_ar', 'price', 'is_published', 'is_featured', 'display_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Colors are deleted one by one so their photo files go too; the rack shows product links.
        static::deleting(fn (Product $product) => $product->colors()->get()->each->delete());
        static::saved(fn () => app(StoreCache::class)->forget('rack'));
        static::deleted(fn () => app(StoreCache::class)->forget('rack'));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** In the owner's order: the first color is the one the site shows first (REQ-CMS-45). */
    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('position')->orderBy('id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class)->orderBy('position');
    }

    /** Items of a product with 2 or more colors are named with their color (D.8.1). */
    public function hasSeveralColors(): bool
    {
        $count = match (true) {
            array_key_exists('colors_count', $this->attributes) => (int) $this->attributes['colors_count'],
            $this->relationLoaded('colors') => $this->colors->count(),
            default => $this->colors()->count(),
        };

        return $count > 1;
    }
}
