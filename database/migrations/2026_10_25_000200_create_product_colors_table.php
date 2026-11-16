<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Products hold their colors (SRS 1.2, REQ-DB-37): one products row per style, one product_colors
 * row per color, and each color has its own stock state and photos (REQ-CMS-08, REQ-CMS-10).
 *
 * Existing rows are moved, so a store with data keeps it: products of one category that share a
 * style group become the colors of one product, named without the color ("Navy Wrap Set" becomes
 * "Wrap Set"). Each moved product's address stays on its color and redirects there (REQ-DMO-103).
 * Orders, rack garments, collections and sizes follow. The demo's sample products are then tidied
 * to match the seed data, but only where their texts are still the demo's own.
 */
return new class extends Migration
{
    /** The demo's plain hijabs had no style group; the seed data now sells them as one product. */
    private const DEMO_PLAIN_HIJABS = [
        'petal-pink-hijab', 'black-hijab', 'olive-green-hijab', 'bright-white-hijab', 'cool-gray-hijab', 'hazelnut-hijab',
        'merlot-hijab', 'brown-hijab', 'dark-denim-hijab', 'navy-blue-hijab', 'violet-ice-hijab',
    ];

    /** Demo texts that named one color, keyed by the moved product's new address. */
    private const DEMO_TEXTS = [
        'hijab' => [
            'from' => 'Plain hijab in petal pink.',
            'slug' => 'plain-hijab', 'name_en' => 'Plain Hijab', 'name_ar' => 'حجاب سادة',
            'description_en' => 'A plain hijab in classic colors.', 'description_ar' => 'حجاب سادة بألوان كلاسيكية.',
        ],
        'wrap-set' => [
            'from' => 'Two-piece set in black: a high-neck top with a side wrap tie and wide-leg pants.',
            'description_en' => 'Two-piece set: a high-neck top with a side wrap tie and wide-leg pants.',
            'description_ar' => 'طقم من قطعتين: بلوزة بياقة عالية وربطة جانبية وبنطال واسع.',
        ],
        'belted-set' => [
            'from' => 'Two-piece set in black: a stand-collar belted jacket and wide-leg pants.',
            'description_en' => 'Two-piece set: a stand-collar belted jacket and wide-leg pants.',
            'description_ar' => 'طقم من قطعتين: جاكيت بياقة واقفة وحزام وبنطال واسع.',
        ],
        'hooded-set' => [
            'from' => 'Two-piece set in black: a hooded button-up jacket with pockets and wide-leg pants.',
            'description_en' => 'Two-piece set: a hooded button-up jacket with pockets and wide-leg pants.',
            'description_ar' => 'طقم من قطعتين: جاكيت بقبعة وأزرار وجيوب وبنطال واسع.',
        ],
    ];

    private const LOOKS = 'home.about.looks';

    public function up(): void
    {
        // A color's address name (?color=navy) and the swatch customers see (REQ-CMS-46).
        Schema::table('colors', function (Blueprint $table) {
            $table->string('slug', 64)->nullable()->after('shade_id');
            $table->char('swatch', 7)->nullable()->after('name_ar');
        });
        $this->fillColorSlugs();
        Schema::table('colors', function (Blueprint $table) {
            $table->string('slug', 64)->nullable(false)->change();
            $table->unique('slug');
        });

        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->constrained()->restrictOnDelete();
            $table->boolean('in_stock')->default(true);
            // The first color is the one the site shows first (REQ-CMS-45).
            $table->unsignedSmallInteger('position')->default(0);
            // The address this color had as a product of its own, which now redirects (REQ-DMO-103).
            $table->string('legacy_slug', 128)->nullable()->unique();
            $table->timestamps();

            // A product cannot have the same color twice (REQ-DB-37).
            $table->unique(['product_id', 'color_id']);
            $table->index('color_id');
        });

        Schema::table('product_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('product_color_id')->nullable()->after('id');
            $table->index(['product_color_id', 'is_main', 'position']);
            $table->foreign('product_color_id')->references('id')->on('product_colors')->cascadeOnDelete();
        });

        // Order items keep the color they were bought in; their names now include the color (D.8.1).
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_color_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('product_name', 200)->change();
        });

        // A garment can open its product on one color (3.7.1).
        Schema::table('rack_items', function (Blueprint $table) {
            $table->foreignId('product_color_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });

        DB::transaction(function () {
            DB::table('products')->whereNull('style')->whereIn('slug', self::DEMO_PLAIN_HIJABS)->update(['style' => 'plain-hijab']);
            $this->moveProducts();
            $this->tidyDemoTexts();
            $this->moveLooks();
        });

        Schema::table('product_photos', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id', 'is_main', 'position']);
            $table->dropColumn('product_id');
        });
        Schema::table('product_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('product_color_id')->nullable(false)->change();
        });

        // Stock is kept per color now, and there is no sort menu left for "Recently featured" (SRS 1.2).
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['color_id']);
            $table->dropIndex('products_cat_pub_new_order_index');
            $table->dropIndex(['style']);
            $table->dropColumn(['color_id', 'style', 'in_stock', 'is_newly_featured']);
        });

        $this->forgetCachedLists();
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('color_id')->nullable()->after('category_id');
            $table->string('style', 64)->nullable()->after('badge_ar');
            $table->boolean('in_stock')->default(true)->after('price');
            $table->boolean('is_newly_featured')->default(false)->after('is_featured');
            $table->index(['category_id', 'is_published', 'is_newly_featured', 'display_order'], 'products_cat_pub_new_order_index');
            $table->index('style');
        });

        Schema::table('product_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('id');
            $table->index(['product_id', 'is_main', 'position']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        DB::transaction(fn () => $this->splitColors());

        Schema::table('product_photos', function (Blueprint $table) {
            $table->dropForeign(['product_color_id']);
            $table->dropIndex(['product_color_id', 'is_main', 'position']);
            $table->dropColumn('product_color_id');
        });
        Schema::table('product_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
        // MySQL cannot make a column NOT NULL while a foreign key uses it, so the key comes after.
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('color_id')->nullable(false)->change();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('color_id')->references('id')->on('colors')->restrictOnDelete();
        });

        Schema::table('rack_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_color_id');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_color_id');
        });

        Schema::dropIfExists('product_colors');

        Schema::table('colors', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'swatch']);
        });

        $this->forgetCachedLists();
    }

    private function fillColorSlugs(): void
    {
        $taken = [];

        foreach (DB::table('colors')->orderBy('id')->get(['id', 'name_en']) as $color) {
            $base = Str::limit(Str::slug($color->name_en) ?: 'color', 56, '');
            $slug = $base;

            for ($n = 2; isset($taken[$slug]); $n++) {
                $slug = "{$base}-{$n}";
            }

            $taken[$slug] = true;
            DB::table('colors')->where('id', $color->id)->update(['slug' => $slug]);
        }
    }

    /** Products of one category sharing a style group become the colors of the first one. */
    private function moveProducts(): void
    {
        $colors = DB::table('colors')->get(['id', 'name_en', 'name_ar'])->keyBy('id');
        $groups = DB::table('products')->orderBy('display_order')->orderBy('id')->get()
            ->groupBy(fn (object $product) => $product->style === null ? "product:{$product->id}" : "style:{$product->category_id}:{$product->style}");

        foreach ($groups as $members) {
            // A second product of the group in a color the group already has stays a product of its own.
            [$merged, $alone] = $members->partition(fn (object $product, int $index) => $members->search(fn (object $other) => $other->color_id === $product->color_id) === $index);

            $this->mergeInto($merged->values(), $colors);
            $alone->each(fn (object $product) => $this->mergeInto(collect([$product]), $colors));
        }
    }

    /** @param  Collection<int, object>  $members  ordered; the first one is kept */
    private function mergeInto(Collection $members, Collection $colors): void
    {
        $base = $members->first();
        $slug = $base->slug;

        if ($members->count() > 1) {
            $color = $colors->get($base->color_id);
            $nameEn = $this->withoutColor($base->name_en, $color?->name_en) ?: $base->name_en;
            $nameAr = $base->name_ar === null ? null : ($this->withoutColor($base->name_ar, $color?->name_ar) ?: $base->name_ar);
            $candidate = Str::limit(Str::slug($nameEn), 128, '');
            $taken = DB::table('products')->where('slug', $candidate)->whereNotIn('id', $members->pluck('id'))->exists();
            $slug = $candidate !== '' && ! $taken ? $candidate : $base->slug;

            DB::table('products')->where('id', $base->id)->update(['name_en' => $nameEn, 'name_ar' => $nameAr, 'slug' => $slug]);
        }

        foreach ($members->values() as $position => $member) {
            $colorId = DB::table('product_colors')->insertGetId([
                'product_id' => $base->id,
                'color_id' => $member->color_id,
                'in_stock' => $member->in_stock,
                'position' => $position,
                'legacy_slug' => $member->slug !== $slug ? $member->slug : null,
                'created_at' => $member->created_at,
                'updated_at' => now(),
            ]);

            $moved = ['product_id' => $base->id, 'product_color_id' => $colorId];
            DB::table('product_photos')->where('product_id', $member->id)->update($moved);
            DB::table('order_items')->where('product_id', $member->id)->update($moved);
            DB::table('rack_items')->where('product_id', $member->id)->update($moved);

            if ($member->id !== $base->id) {
                foreach (['collection_product' => 'collection_id', 'product_size' => 'size_id'] as $table => $column) {
                    DB::table($table)->insertOrIgnore(DB::table($table)->where('product_id', $member->id)->pluck($column)
                        ->map(fn (int $id) => [$column => $id, 'product_id' => $base->id])->all());
                }
                DB::table('products')->where('id', $member->id)->delete();
            }
        }
    }

    /** "Navy Wrap Set" without "Navy" is "Wrap Set"; "طقم بربطة جانبية كحلي" without "كحلي" is "طقم بربطة جانبية". */
    private function withoutColor(string $name, ?string $color): string
    {
        if ($color === null || trim($color) === '') {
            return $name;
        }

        $pattern = '/(?<![\p{L}\p{N}])'.preg_quote(trim($color), '/').'(?![\p{L}\p{N}])/iu';

        return trim((string) preg_replace('/\s{2,}/u', ' ', (string) preg_replace($pattern, '', $name, 1)));
    }

    private function tidyDemoTexts(): void
    {
        foreach (self::DEMO_TEXTS as $slug => $texts) {
            $product = DB::table('products')->where('slug', $slug)->first();

            if ($product === null || $product->description_en !== $texts['from']) {
                continue;
            }

            $taken = isset($texts['slug']) && DB::table('products')->where('slug', $texts['slug'])->exists();
            DB::table('products')->where('id', $product->id)->update(array_diff_key(
                $taken ? array_diff_key($texts, ['slug' => 1]) : $texts,
                ['from' => 1],
            ));
        }
    }

    /** The home page looks pointed at product addresses; they now point at product colors. */
    private function moveLooks(): void
    {
        $block = DB::table('content_blocks')->where('key', self::LOOKS)->first();

        if ($block === null) {
            return;
        }

        $ids = collect(explode(',', (string) $block->value_en))->map(fn (string $slug) => trim($slug))->filter()
            ->map(fn (string $slug) => DB::table('product_colors')->where('legacy_slug', $slug)->value('id')
                ?? DB::table('product_colors')->join('products', 'products.id', '=', 'product_colors.product_id')
                    ->where('products.slug', $slug)->orderBy('product_colors.position')->value('product_colors.id'))
            ->filter()->unique()->values();

        DB::table('content_blocks')->where('id', $block->id)->update(['value_en' => $ids->implode(',')]);
    }

    /** Back to one product per color: the first color stays on the product, the others become products again. */
    private function splitColors(): void
    {
        $colors = DB::table('colors')->get(['id', 'name_en', 'name_ar', 'slug'])->keyBy('id');
        $productOf = [];

        foreach (DB::table('products')->orderBy('id')->get() as $product) {
            $productColors = DB::table('product_colors')->where('product_id', $product->id)->orderBy('position')->get();
            $style = $productColors->count() > 1 ? Str::limit($product->slug, 64, '') : null;

            foreach ($productColors->values() as $index => $productColor) {
                $color = $colors->get($productColor->color_id);
                $id = $product->id;

                if ($index > 0) {
                    $copy = (array) $product;
                    unset($copy['id']);
                    $id = DB::table('products')->insertGetId(array_merge($copy, [
                        'slug' => $productColor->legacy_slug ?? Str::limit($product->slug.'-'.$color->slug, 128, ''),
                        'name_en' => Str::limit(trim($color->name_en.' '.$product->name_en), 128, ''),
                        'name_ar' => $product->name_ar === null ? null : Str::limit(trim($product->name_ar.' '.$color->name_ar), 128, ''),
                        'display_order' => $product->display_order + $index,
                    ]));

                    foreach (['collection_product' => 'collection_id', 'product_size' => 'size_id'] as $table => $column) {
                        DB::table($table)->insert(DB::table($table)->where('product_id', $product->id)->pluck($column)
                            ->map(fn (int $value) => [$column => $value, 'product_id' => $id])->all());
                    }
                }

                $productOf[$productColor->id] = $id;
                DB::table('products')->where('id', $id)->update(['color_id' => $productColor->color_id, 'in_stock' => $productColor->in_stock, 'style' => $style]);
                DB::table('product_photos')->where('product_color_id', $productColor->id)->update(['product_id' => $id]);
                DB::table('order_items')->where('product_color_id', $productColor->id)->update(['product_id' => $id]);
                DB::table('rack_items')->where('product_color_id', $productColor->id)->update(['product_id' => $id]);
            }
        }

        $slugs = collect(explode(',', (string) DB::table('content_blocks')->where('key', self::LOOKS)->value('value_en')))
            ->map(fn (string $id) => $productOf[(int) $id] ?? null)->filter()
            ->map(fn (int $id) => DB::table('products')->where('id', $id)->value('slug'));
        DB::table('content_blocks')->where('key', self::LOOKS)->update(['value_en' => $slugs->implode(',')]);
    }

    /** Lists cached by the storefront were read before the move (REQ-DB-26). */
    private function forgetCachedLists(): void
    {
        foreach (['catalog', 'rack', 'content'] as $key) {
            Cache::forget("store.{$key}");
        }
    }
};
