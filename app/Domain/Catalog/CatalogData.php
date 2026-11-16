<?php

namespace App\Domain\Catalog;

use App\Models\Category;
use App\Models\Color;
use App\Models\Shade;
use App\Models\Size;
use App\Support\StoreCache;
use Illuminate\Support\Collection;

/** Categories, shades, colours and sizes: small lists kept in the memory cache (REQ-DB-25). */
class CatalogData
{
    private ?array $data = null;

    public function __construct(private readonly StoreCache $cache) {}

    private function data(): array
    {
        return $this->data ??= $this->cache->remember('catalog', fn () => [
            'categories' => Category::query()->with('sizeGuide')->orderBy('position')->get(),
            'shades' => Shade::query()->orderBy('position')->get(),
            'colors' => Color::query()->get(['id', 'shade_id', 'slug', 'name_en', 'name_ar', 'swatch'])->keyBy('id'),
            'sizes' => Size::query()->orderBy('position')->get(['id', 'label', 'position']),
        ]);
    }

    public function categories(): Collection
    {
        return $this->data()['categories'];
    }

    public function category(string $slug): ?Category
    {
        return $this->categories()->firstWhere('slug', $slug);
    }

    public function categoryById(int $id): ?Category
    {
        return $this->categories()->firstWhere('id', $id);
    }

    public function shades(): Collection
    {
        return $this->data()['shades'];
    }

    public function shadeById(int $id): ?Shade
    {
        return $this->shades()->firstWhere('id', $id);
    }

    public function colors(): Collection
    {
        return $this->data()['colors'];
    }

    public function sizes(): Collection
    {
        return $this->data()['sizes'];
    }

    public function shadeOfColor(int $colorId): ?Shade
    {
        $color = $this->colors()->get($colorId);

        return $color ? $this->shadeById($color->shade_id) : null;
    }

    /** The swatch customers see: the colour's own, else its family's (REQ-CMS-46). */
    public function swatchOf(int $colorId): string
    {
        $color = $this->colors()->get($colorId);

        return $color?->swatch ?: (string) ($color ? $this->shadeById($color->shade_id)?->swatch : null);
    }

    /** Colour ids belonging to the given shade keys. */
    public function colorIdsForShades(array $shadeKeys): array
    {
        $shadeIds = $this->shades()->whereIn('key', $shadeKeys)->pluck('id')->all();

        return $this->colors()->whereIn('shade_id', $shadeIds)->keys()->all();
    }
}
