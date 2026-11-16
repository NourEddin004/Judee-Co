<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\OwnsImageFile;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity, OwnsImageFile;

    protected $fillable = ['author', 'country_code', 'image_path', 'body_en', 'body_ar', 'position', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public static function storeCacheKey(): string
    {
        return 'content';
    }

    protected function imageColumn(): string
    {
        return 'image_path';
    }
}
