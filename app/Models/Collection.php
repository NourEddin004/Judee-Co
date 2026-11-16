<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    use HasTranslations, LogsActivity;

    protected $fillable = ['slug', 'name_en', 'name_ar'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
