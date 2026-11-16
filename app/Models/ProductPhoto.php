<?php

namespace App\Models;

use App\Domain\Media\ImageStore;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\OwnsImageFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** A photo of one product color (REQ-CMS-10). */
class ProductPhoto extends Model
{
    use LogsActivity, OwnsImageFile;

    protected $fillable = ['product_color_id', 'path', 'position', 'is_main', 'is_hover'];

    protected $casts = ['is_main' => 'boolean', 'is_hover' => 'boolean'];

    public function productColor(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /** The smallest copy at least 400 px wide: sharp in the bag's thumbnails on any phone. */
    public function smallUrl(): string
    {
        $sources = array_filter(app(ImageStore::class)->sources($this->path), fn (int $width) => $width >= 400, ARRAY_FILTER_USE_KEY);

        return Storage::disk('public')->url(reset($sources) ?: $this->path);
    }

    protected function imageColumn(): string
    {
        return 'path';
    }
}
