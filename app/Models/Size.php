<?php

namespace App\Models;

use App\Models\Concerns\FlushesStoreCache;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Size extends Model
{
    use FlushesStoreCache, LogsActivity;

    protected $fillable = ['label', 'position'];

    /** The shop's size filter lists sizes from the cached catalog lists. */
    public static function storeCacheKey(): string
    {
        return 'catalog';
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withoutGlobalScopes();
    }
}
