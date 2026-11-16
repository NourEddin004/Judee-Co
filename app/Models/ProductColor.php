<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\StoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** One color of a product, with its own stock state and photos (REQ-DB-37, REQ-CMS-08, REQ-CMS-10). */
class ProductColor extends Model
{
    use LogsActivity;

    protected $fillable = ['product_id', 'color_id', 'in_stock', 'position', 'legacy_slug'];

    protected $casts = ['in_stock' => 'boolean', 'position' => 'integer'];

    protected static function booted(): void
    {
        // Photo rows are deleted one by one so their files go too; rack garments can open a color.
        static::deleting(fn (ProductColor $color) => $color->photos()->get()->each->delete());
        static::saved(fn (ProductColor $color) => $color->changedProduct());
        static::deleted(fn (ProductColor $color) => $color->changedProduct());
    }

    /** A change to a color is a change to its product page, so the sitemap date moves too. */
    private function changedProduct(): void
    {
        Product::withoutGlobalScopes()->whereKey($this->product_id)->update(['updated_at' => $this->freshTimestamp()]);
        app(StoreCache::class)->forget('rack');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProductPhoto::class)->orderBy('position');
    }

    public function mainPhoto(): HasOne
    {
        return $this->hasOne(ProductPhoto::class)->where('is_main', true);
    }

    /** Card photo: the main photo, else the first one (photos must be loaded). */
    public function cardPhoto(): ?ProductPhoto
    {
        return $this->photos->firstWhere('is_main', true) ?? $this->photos->first();
    }

    /** Photo shown when the card is hovered, if the owner chose one (REQ-CMS-12). */
    public function cardHoverPhoto(): ?ProductPhoto
    {
        return $this->photos->firstWhere('is_hover', true);
    }

    /** Only colors in stock can go in the bag (REQ-CHK-16). */
    public function isPurchasable(): bool
    {
        return $this->in_stock;
    }

    /**
     * "Navy Wrap Set", or "طقم بربطة جانبية كحلي" in Arabic; a product with one color keeps its own
     * name (D.8.1). Needs the product, with its colors or their count, and the color.
     */
    public function name(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $product = (string) $this->product->t('name', $locale);

        if (! $this->product->hasSeveralColors()) {
            return $product;
        }

        $color = (string) $this->color->t('name', $locale);

        return $locale === 'ar' ? "{$product} {$color}" : "{$color} {$product}";
    }

    /** This color's product page; the first color needs no ?color= (REQ-DMO-98). Needs the product's colors. */
    public function url(?string $locale = null): string
    {
        $first = $this->product->colors->first();

        return product_url($this->product->slug, $first !== null && $first->is($this) ? null : $this->color->slug, $locale);
    }
}
