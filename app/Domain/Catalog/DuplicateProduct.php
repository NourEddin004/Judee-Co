<?php

namespace App\Domain\Catalog;

use App\Domain\Media\ImageStore;
use App\Models\Product;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copies a product with its sizes, collections, colors and their photos (REQ-CMS-05). The copy
 * starts hidden, so the owner can change its colors and photos before customers see it.
 */
class DuplicateProduct
{
    public function __invoke(Product $product): Product
    {
        $product->loadMissing(['sizes:id', 'collections:id', 'colors.photos']);
        $disk = Storage::disk('public');

        return DB::transaction(function () use ($product, $disk) {
            $copy = $product->replicate(['slug']);
            $copy->name_en = Str::limit($product->name_en, 120, '').' (copy)';
            $copy->name_ar = $product->name_ar ? Str::limit($product->name_ar, 120, '').' (نسخة)' : null;
            $copy->slug = $this->freeSlug($product->slug);
            $copy->is_published = false;
            $copy->save();

            $copy->sizes()->sync($product->sizes->modelKeys());
            $copy->collections()->sync($product->collections->modelKeys());

            // The old demo address stays with the original color (REQ-DMO-103).
            foreach ($product->colors as $color) {
                $colorCopy = $copy->colors()->create([
                    'color_id' => $color->color_id,
                    'in_stock' => $color->in_stock,
                    'position' => $color->position,
                ]);

                foreach ($color->photos as $photo) {
                    $colorCopy->photos()->create([
                        'path' => $this->copyFile($disk, $photo->path),
                        'position' => $photo->position,
                        'is_main' => $photo->is_main,
                        'is_hover' => $photo->is_hover,
                    ]);
                }
            }

            return $copy;
        });
    }

    private function freeSlug(string $slug): string
    {
        $base = Str::limit($slug, 110, '').'-copy';
        $candidate = $base;

        for ($n = 2; Product::withoutGlobalScopes()->where('slug', $candidate)->exists(); $n++) {
            $candidate = "{$base}-{$n}";
        }

        return $candidate;
    }

    /** Each copy owns its own files, so deleting one product never breaks the other. */
    private function copyFile(Filesystem $disk, string $path): string
    {
        $folder = Str::before($path, '/');
        $base = $folder.'/'.Str::random(40);
        $extension = Str::afterLast($path, '.');

        $disk->copy($path, "{$base}.{$extension}");

        foreach (config('store.images.variant_widths') as $width) {
            $variant = ImageStore::variantPath($path, $width);

            if ($disk->exists($variant)) {
                $disk->copy($variant, "{$base}-{$width}.webp");
            }
        }

        return "{$base}.{$extension}";
    }
}
