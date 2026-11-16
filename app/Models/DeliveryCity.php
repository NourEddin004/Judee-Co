<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryCity extends Model
{
    use FlushesStoreCache, HasTranslations, LogsActivity;

    protected $fillable = ['key', 'country', 'name_en', 'name_ar', 'fee', 'is_active', 'position'];

    protected $casts = ['fee' => 'decimal:2', 'is_active' => 'boolean'];

    public static function storeCacheKey(): string
    {
        return 'delivery';
    }

    /** Jordan cities have listed areas; a Gulf country is one row where the customer types city and area (REQ-CHK-27, 28). */
    public function isJordan(): bool
    {
        return $this->country === 'JO';
    }

    public function areas(): HasMany
    {
        return $this->hasMany(DeliveryArea::class)->orderBy('position');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
