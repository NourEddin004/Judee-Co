<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryArea extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity;

    protected $fillable = ['delivery_city_id', 'name_en', 'name_ar', 'position'];

    public static function storeCacheKey(): string
    {
        return 'delivery';
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(DeliveryCity::class, 'delivery_city_id');
    }
}
