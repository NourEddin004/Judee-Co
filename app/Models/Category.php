<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Category extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity;

    protected $fillable = [
        'slug', 'name_en', 'name_ar', 'page_title_en', 'page_title_ar',
        'empty_title_en', 'empty_title_ar', 'empty_body_en', 'empty_body_ar', 'position',
    ];

    public static function storeCacheKey(): string
    {
        return 'catalog';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function sizeGuide(): HasOne
    {
        return $this->hasOne(SizeGuide::class);
    }
}
