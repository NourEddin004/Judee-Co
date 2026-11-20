<?php

namespace Database\Seeders;

use App\Domain\Catalog\SizeFit;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductPhoto;
use App\Models\Shade;
use App\Models\Size;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /** Swatch colours of the 10 shade families (Appendix D.2). */
    private const SWATCHES = [
        'black' => '#2b2427', 'white' => '#f7f4f2', 'gray' => '#8a8d93', 'beige' => '#cdb59a', 'brown' => '#6b4430',
        'green' => '#6f7454', 'blue' => '#2f3e5f', 'pink' => '#e3aeb8', 'red' => '#7d2335', 'purple' => '#8e7590',
    ];

    public function run(): void
    {
        $data = SeedData::catalog();

        $shades = [];
        foreach ($data['shades'] as $position => $shade) {
            $shades[$shade['key']] = Shade::updateOrCreate(['key' => $shade['key']], [
                'name_en' => $shade['name_en'], 'name_ar' => $shade['name_ar'],
                'swatch' => self::SWATCHES[$shade['key']], 'position' => $position,
            ]);
        }

        $sizes = [];
        $categories = [];
        foreach ($data['categories'] as $row) {
            $category = Category::updateOrCreate(['slug' => $row['slug']], [
                'name_en' => $row['name_en'], 'name_ar' => $row['name_ar'],
                'page_title_en' => $row['page_title_en'], 'page_title_ar' => $row['page_title_ar'],
                'position' => $row['position'],
            ]);
            $categories[$row['slug']] = [$category, $row['sizes']];

            foreach ($row['sizes'] as $position => $label) {
                $sizes[$label] ??= Size::updateOrCreate(['label' => $label], ['position' => $position]);
            }

            if ($row['size_guide']) {
                $category->sizeGuide()->updateOrCreate([], [
                    'content_en' => $row['size_guide'],
                    'content_ar' => [
                        'columns' => ['المقاس', 'الصدر (سم)', 'الخصر (سم)', 'الورك (سم)'],
                        'rows' => $row['size_guide']['rows'],
                    ],
                    // Starting height and weight per size for "Find my size", until JUDE's own chart is in.
                    'fit' => SizeFit::defaultsFor(array_column($row['size_guide']['rows'], 0)),
                ]);
            }
        }

        $collections = [];
        foreach ($data['collections'] as $row) {
            $collections[$row['slug']] = Collection::updateOrCreate(['slug' => $row['slug']], $row);
        }

        foreach ($data['products'] as $row) {
            [$category, $categorySizes] = $categories[$row['category']];

            $product = Product::withoutGlobalScopes()->updateOrCreate(['slug' => $row['slug']], [
                'category_id' => $category->id,
                'name_en' => $row['name_en'], 'name_ar' => $row['name_ar'],
                'description_en' => $row['description_en'], 'description_ar' => $row['description_ar'],
                'badge_en' => $row['badge_en'], 'badge_ar' => $row['badge_ar'],
                'price' => $row['price'],
                'is_published' => true,
                'is_featured' => $row['is_featured'],
                'display_order' => $row['display_order'],
            ]);

            $product->collections()->sync(array_map(fn ($slug) => $collections[$slug]->id, $row['collections']));
            $product->sizes()->sync(array_map(fn ($label) => $sizes[$label]->id, $categorySizes));

            // Each colour with its stock state and photos, in the demo's order (REQ-DB-37). A
            // demo product that is now one colour keeps its old address (REQ-DMO-103).
            $kept = [];
            foreach ($row['colors'] as $position => $entry) {
                $color = Color::firstOrCreate(
                    ['shade_id' => $shades[$entry['shade']]->id, 'name_en' => $entry['color_en']],
                    ['name_ar' => $entry['color_ar'], 'swatch' => $entry['swatch']],
                );
                $productColor = $product->colors()->updateOrCreate(['color_id' => $color->id], [
                    'in_stock' => $entry['in_stock'],
                    'position' => $position,
                    'legacy_slug' => $entry['legacy_slug'],
                ]);
                $kept[] = $productColor->id;

                // Rows only: the demo files stay and are copied again where missing.
                $productColor->photos()->delete();
                foreach ($entry['photos'] as $photoPosition => $file) {
                    $productColor->photos()->create([
                        'path' => SeedData::image("products/{$file}", "products/{$file}"),
                        'position' => $photoPosition,
                        'is_main' => $file === $entry['main'],
                        'is_hover' => $file === $entry['hover'],
                    ]);
                }
            }

            $gone = $product->colors()->whereKeyNot($kept)->pluck('id');
            ProductPhoto::query()->whereIn('product_color_id', $gone)->delete();
            ProductColor::query()->whereKey($gone)->delete();
        }
    }
}
