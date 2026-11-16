<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SizeGuide extends Model
{
    use FlushesStoreCache, LogsActivity;

    protected $fillable = ['category_id', 'content_en', 'content_ar'];

    protected $casts = ['content_en' => 'array', 'content_ar' => 'array'];

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
}
