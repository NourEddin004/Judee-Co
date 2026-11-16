<?php

namespace Database\Seeders;

use App\Domain\Orders\OrderNumber;
use App\Domain\Orders\OrderStatus;
use App\Models\DeliveryCity;
use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Fills a throwaway copy of the store up to 1,000 products and 10,000 orders, the sizes the speed
 * requirements name (REQ-PRF-03, 04, REQ-DB-24). Run it after the normal seed:
 * `php artisan db:seed --class=SpeedTestSeeder`. The extra products share the demo's photo files,
 * so deleting one of them deletes a demo photo too: use a copy of the database and the photos.
 */
class SpeedTestSeeder extends Seeder
{
    public const PRODUCTS = 1000;

    public const ORDERS = 10000;

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The speed test data is for a throwaway copy, never the live store.');
        }

        $this->products();
        $this->orders();
    }

    private function products(): void
    {
        $demo = Product::withoutGlobalScopes()->with(['colors.photos', 'collections', 'sizes'])->orderBy('id')->get();
        $now = now();

        for ($n = $demo->count() + 1; $n <= self::PRODUCTS; $n++) {
            $source = $demo[$n % $demo->count()];
            $id = DB::table('products')->insertGetId([
                ...Arr::except($source->getAttributes(), ['id', 'created_at', 'updated_at']),
                'slug' => "{$source->slug}-{$n}",
                'name_en' => "{$source->name_en} {$n}",
                'name_ar' => $source->name_ar ? "{$source->name_ar} {$n}" : null,
                'is_featured' => false,
                'display_order' => $n,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($source->colors as $color) {
                $colorId = DB::table('product_colors')->insertGetId([
                    'product_id' => $id, 'color_id' => $color->color_id, 'in_stock' => $color->in_stock,
                    'position' => $color->position, 'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('product_photos')->insert($color->photos->map(fn ($photo) => [
                    ...Arr::except($photo->getAttributes(), ['id', 'product_color_id', 'created_at', 'updated_at']),
                    'product_color_id' => $colorId, 'created_at' => $now, 'updated_at' => $now,
                ])->all());
            }
            DB::table('collection_product')->insert($source->collections->map(fn ($c) => ['collection_id' => $c->id, 'product_id' => $id])->all());
            DB::table('product_size')->insert($source->sizes->map(fn ($s) => ['product_id' => $id, 'size_id' => $s->id])->all());
        }
    }

    private function orders(): void
    {
        $cities = DeliveryCity::query()->get();
        $colors = ProductColor::query()->with(['product' => fn ($query) => $query->withoutGlobalScopes()->withCount('colors'), 'color'])->inRandomOrder()->limit(200)->get();
        // Most orders end up delivered; the newest are still on their way.
        $statuses = [...array_fill(0, 14, OrderStatus::Delivered), OrderStatus::Cancelled, OrderStatus::Confirmed, OrderStatus::OutForDelivery, OrderStatus::New];
        $numbers = [];

        foreach (array_chunk(range(1, self::ORDERS), 500) as $chunk) {
            $orders = [];
            $items = [];

            foreach ($chunk as $n) {
                do {
                    $number = OrderNumber::generate();
                } while (isset($numbers[$number]));
                $numbers[$number] = true;

                $placed = now()->subMinutes((self::ORDERS - $n) * 100 + random_int(0, 90));
                $city = $cities->random();
                $lines = $colors->random(random_int(1, 3));
                $subtotal = $lines->sum(fn (ProductColor $color) => $color->product->price);
                $status = $placed->gt(now()->subDays(3)) ? Arr::random([OrderStatus::New, OrderStatus::Confirmed, OrderStatus::OutForDelivery]) : Arr::random($statuses);

                $orders[] = [
                    'number' => $number,
                    'link_token' => OrderNumber::token(),
                    'customer_name' => fake()->firstName().' '.fake()->lastName(),
                    'phone' => '+962 7'.Arr::random(['7', '8', '9']).fake()->numerify('#######'),
                    'email' => $n % 3 ? null : fake()->safeEmail(),
                    'delivery_city_id' => $city->id,
                    'city_name' => $city->name_en,
                    'area' => fake()->city(),
                    'address' => fake()->streetAddress(),
                    'note' => $n % 7 ? null : 'Please call before delivery.',
                    'status' => $status->value,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $city->fee,
                    'total' => $subtotal + $city->fee,
                    'payment_method' => 'cash_on_delivery',
                    'language' => $n % 4 ? 'en' : 'ar',
                    'placed_at' => $placed,
                    'created_at' => $placed,
                    'updated_at' => $placed,
                ];
                $items[$number] = $lines->map(fn (ProductColor $color) => [
                    'product_id' => $color->product_id,
                    'product_color_id' => $color->id,
                    'product_name' => $color->name('en'),
                    'color_name' => $color->color->name_en,
                    'size' => null,
                    'unit_price' => $color->product->price,
                    'quantity' => 1,
                    'created_at' => $placed,
                    'updated_at' => $placed,
                ])->all();
            }

            DB::table('orders')->insert($orders);
            $ids = DB::table('orders')->whereIn('number', array_keys($items))->pluck('id', 'number');
            DB::table('order_items')->insert(collect($items)->flatMap(
                fn ($lines, $number) => array_map(fn ($line) => ['order_id' => $ids[$number], ...$line], $lines)
            )->all());
        }
    }
}
