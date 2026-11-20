<?php

namespace App\Models;

use App\Domain\Catalog\SizeFit;
use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class SizeGuide extends Model
{
    use FlushesStoreCache, LogsActivity;

    protected $fillable = ['category_id', 'content_en', 'content_ar', 'fit'];

    protected $casts = ['content_en' => 'array', 'content_ar' => 'array', 'fit' => 'array'];

    public static function storeCacheKey(): string
    {
        return 'catalog';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** {"columns": [...], "rows": [[...]]} in the current locale, English as fallback. */
    public function table(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return ($locale === 'ar' ? $this->content_ar : null) ?: $this->content_en ?: ['columns' => [], 'rows' => []];
    }

    /** Height and weight per size for "Find my size"; empty when the owner has not filled it in. */
    public function fitRows(): array
    {
        // A guide cached before the fit column existed lacks the attribute: read it from the
        // database, or treat it as empty while the migration has not run yet.
        if (! array_key_exists('fit', $this->getAttributes())) {
            $this->setRawAttributes(['fit' => Schema::hasColumn($this->getTable(), 'fit')
                ? static::query()->whereKey($this->getKey())->toBase()->value('fit')
                : null] + $this->getAttributes(), true);
        }

        return SizeFit::clean($this->fit);
    }
}
