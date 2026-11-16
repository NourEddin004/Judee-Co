<?php

namespace Database\Seeders;

use App\Domain\Media\ImageStore;
use App\Models\ProductColor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/** Reads the demo catalog export and copies demo images onto the public disk. */
final class SeedData
{
    public static function catalog(): array
    {
        return json_decode(File::get(database_path('seeders/data/demo-catalog.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    /** The product colour the demo data names, {"product": "wrap-set", "color": "beige"}; no colour means the first one. */
    public static function productColorId(string $product, ?string $color): ?int
    {
        return ProductColor::query()
            ->whereHas('product', fn ($query) => $query->withoutGlobalScopes()->where('slug', $product))
            ->when($color, fn ($query) => $query->whereHas('color', fn ($query) => $query->where('slug', $color)))
            ->orderBy('position')
            ->value('id');
    }

    /** Copies reference/demo/brand-assets/{from} to the public disk at {to}; returns the disk path. */
    public static function image(string $from, string $to): string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($to)) {
            $disk->put($to, File::get(base_path("reference/demo/brand-assets/{$from}")));
        }

        app(ImageStore::class)->addCopies($to);

        return $to;
    }
}
