<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PolicySection extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity;

    protected $fillable = ['page', 'title_en', 'title_ar', 'body_en', 'body_ar', 'position'];

    public static function storeCacheKey(): string
    {
        return 'content';
    }
}
