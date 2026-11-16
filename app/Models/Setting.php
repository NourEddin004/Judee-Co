<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use FlushesStoreCache, LogsActivity;

    protected $fillable = ['key', 'value'];

    public static function storeCacheKey(): string
    {
        return 'settings';
    }
}
