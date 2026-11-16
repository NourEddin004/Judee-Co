<?php

namespace Tests\Feature;

use App\Domain\Content\SiteContent;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Support\StoreCache;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function indexes(string $table): array
    {
        return collect(Schema::getIndexes($table))->map(fn ($index) => implode(',', $index['columns']))->all();
    }

    #[Test]
    public function req_db_01_the_site_uses_one_mysql_database_on_the_current_lts(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertTrue(version_compare(DB::selectOne('select version() as v')->v, '9.7', '>='), 'MySQL 9.7 LTS or newer');
    }

    #[Test]
    public function req_db_05_links_between_records_are_enforced(): void
    {
        $this->assertNotEmpty(Schema::getForeignKeys('products'));
        $this->assertNotEmpty(Schema::getForeignKeys('order_items'));
        $this->assertNotEmpty(Schema::getForeignKeys('delivery_areas'));
    }

    #[Test]
    public function req_db_08_prices_are_exact_decimals(): void
    {
        foreach (['products' => 'price', 'orders' => 'total', 'order_items' => 'unit_price', 'delivery_cities' => 'fee'] as $table => $column) {
            $this->assertSame('decimal', Schema::getColumnType($table, $column), "{$table}.{$column}");
        }
    }

    #[Test]
    public function req_db_09_customer_facing_texts_have_english_and_arabic_columns(): void
    {
        foreach (['products' => ['name', 'description'], 'categories' => ['name'], 'faqs' => ['question', 'answer'], 'content_blocks' => ['value'], 'delivery_areas' => ['name']] as $table => $fields) {
            foreach ($fields as $field) {
                $this->assertTrue(Schema::hasColumns($table, ["{$field}_en", "{$field}_ar"]), "{$table}.{$field}");
            }
        }
    }

    #[Test]
    public function req_db_10_and_32_order_numbers_and_link_tokens_are_unique(): void
    {
        $indexes = collect(Schema::getIndexes('orders'))->where('unique', true)->map(fn ($index) => implode(',', $index['columns']))->all();

        $this->assertContains('number', $indexes);
        $this->assertContains('link_token', $indexes);
    }

    #[Test]
    public function req_db_11_dates_use_jordan_time(): void
    {
        $this->assertSame('Asia/Amman', config('app.timezone'));
    }

    #[Test]
    public function req_db_21_and_22_filter_sort_and_search_columns_are_indexed(): void
    {
        $products = $this->indexes('products');
        $this->assertContains('slug', $products);
        $this->assertContains('name_en,name_ar,description_en,description_ar', $products);
        $this->assertNotEmpty(array_filter($products, fn ($index) => str_starts_with($index, 'is_published')));
    }

    #[Test]
    public function req_db_20_lazy_loading_is_prevented_outside_production(): void
    {
        $this->expectException(LazyLoadingViolationException::class);

        Product::query()->limit(2)->get()->each(fn ($product) => $product->category);
    }

    #[Test]
    public function req_db_25_and_26_cached_content_is_cleared_when_it_changes(): void
    {
        $this->assertSame('Shop now', app(SiteContent::class)->text('home.hero.cta'));
        $this->assertTrue(cache()->has('store.content'));

        ContentBlock::query()->where('key', 'home.hero.cta')->firstOrFail()->update(['value_en' => 'Shop the edit']);

        $this->assertFalse(cache()->has('store.content'));
        $this->app->forgetScopedInstances();
        $this->assertSame('Shop the edit', app(SiteContent::class)->text('home.hero.cta'));
    }

    #[Test]
    public function req_db_26_editing_a_category_clears_the_catalog_cache(): void
    {
        app(StoreCache::class)->remember('catalog', fn () => 'cached');
        Category::query()->firstOrFail()->update(['name_en' => 'Renamed']);

        $this->assertFalse(cache()->has('store.catalog'));
    }

    #[Test]
    public function req_db_31_published_products_only_is_enforced_in_one_place(): void
    {
        $total = Product::withoutGlobalScopes()->count();
        Product::query()->limit(1)->update(['is_published' => false]);

        $this->assertSame($total - 1, Product::query()->count());
    }
}
