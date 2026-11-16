<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RackItem;
use Illuminate\Database\Seeder;

class RackSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SeedData::catalog()['rack'] as $position => $row) {
            RackItem::updateOrCreate(['name_en' => $row['name_en']], [
                'product_id' => Product::withoutGlobalScopes()->where('slug', $row['product'])->value('id'),
                'product_color_id' => SeedData::productColorId($row['product'], $row['color'] ?? null),
                'name_ar' => $row['name_ar'],
                'alt_en' => $row['alt_en'],
                'alt_ar' => $row['alt_ar'],
                'image_path' => SeedData::image("hero/{$row['image']}", "rack/{$row['image']}"),
                'position' => $position,
                'is_visible' => true,
            ]);
        }
    }
}
