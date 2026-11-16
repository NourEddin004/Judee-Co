<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\OwnsImageFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RackItem extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity, OwnsImageFile;

    /** Set after an upload: whether the image had a see-through background (REQ-RCK-12). */
    public ?bool $hadTransparentBackground = null;

    protected $fillable = [
        'product_id', 'product_color_id', 'name_en', 'name_ar', 'alt_en', 'alt_ar', 'image_path', 'position', 'is_visible', 'size_adjust',
    ];

    protected $casts = ['is_visible' => 'boolean', 'size_adjust' => 'decimal:2'];

    public static function storeCacheKey(): string
    {
        return 'rack';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** The color the garment opens on; none opens the product's first color (3.7.1). */
    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    /**
     * The garment's product page, on its colour when one is chosen; the shop when the product is
     * not on the site. Needs the product with its colours and their colour.
     */
    public function url(): string
    {
        if ($this->product === null) {
            return store_route('shop');
        }

        $color = $this->product->colors->firstWhere('id', $this->product_color_id);

        return $color === null ? product_url($this->product->slug) : $color->setRelation('product', $this->product)->url();
    }

    protected function imageColumn(): string
    {
        return 'image_path';
    }
}
