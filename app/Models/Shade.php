<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shade extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity;

    protected $fillable = ['key', 'name_en', 'name_ar', 'swatch', 'position'];

    public static function storeCacheKey(): string
    {
        return 'catalog';
    }

    public function colors(): HasMany
    {
        return $this->hasMany(Color::class);
    }
}
