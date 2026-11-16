<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity;

    protected $fillable = ['question_en', 'question_ar', 'answer_en', 'answer_ar', 'position', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public static function storeCacheKey(): string
    {
        return 'content';
    }
}
