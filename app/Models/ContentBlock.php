<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity;

    protected $fillable = ['key', 'value_en', 'value_ar'];

    public static function storeCacheKey(): string
    {
        return 'content';
    }
}
