<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SRS 1.4 (5 Nov 2026): delivery countries, 3 JD to every Jordan city, the 6 Gulf countries at
 * 25 JD per order (REQ-CHK-26 to 30, REQ-CMS-48 to 50), and the 4 new categories (REQ-STR-29, REQ-CMS-47).
 * Rows are only added when missing, so running it on a store that already has them changes nothing.
 */
return new class extends Migration
{
    /** Delivery rows for the Gulf: one per country, no areas; the customer types the city and area. */
    public const GULF = [
        ['key' => 'saudi-arabia', 'country' => 'SA', 'name_en' => 'Saudi Arabia', 'name_ar' => 'السعودية'],
        ['key' => 'uae', 'country' => 'AE', 'name_en' => 'United Arab Emirates', 'name_ar' => 'الإمارات'],
        ['key' => 'kuwait', 'country' => 'KW', 'name_en' => 'Kuwait', 'name_ar' => 'الكويت'],
        ['key' => 'qatar', 'country' => 'QA', 'name_en' => 'Qatar', 'name_ar' => 'قطر'],
        ['key' => 'bahrain', 'country' => 'BH', 'name_en' => 'Bahrain', 'name_ar' => 'البحرين'],
        ['key' => 'oman', 'country' => 'OM', 'name_en' => 'Oman', 'name_ar' => 'عُمان'],
    ];

    /** Arabic names are drafts until the owner approves them (REQ-DMO-87). */
    public const CATEGORIES = [
        ['slug' => 'jackets', 'name_en' => 'Jackets', 'name_ar' => 'جاكيتات', 'page_title_en' => 'Jackets', 'page_title_ar' => 'جاكيتات', 'position' => 4],
        ['slug' => 'shirts', 'name_en' => 'Shirts', 'name_ar' => 'قمصان', 'page_title_en' => 'Shirts', 'page_title_ar' => 'قمصان', 'position' => 5],
        ['slug' => 'hijab-basics', 'name_en' => 'Hijab Basics', 'name_ar' => 'أساسيات الحجاب', 'page_title_en' => 'Hijab Basics', 'page_title_ar' => 'أساسيات الحجاب', 'position' => 6],
        ['slug' => 'dresses', 'name_en' => 'Dresses', 'name_ar' => 'فساتين', 'page_title_en' => 'Dresses', 'page_title_ar' => 'فساتين', 'position' => 7],
    ];

    public function up(): void
    {
        Schema::table('delivery_cities', function (Blueprint $table) {
            $table->char('country', 2)->default('JO')->after('key');
            $table->index(['country', 'is_active', 'position']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->char('country', 2)->default('JO')->after('delivery_city_id');
            $table->index('country');
        });

        $now = now();

        DB::table('delivery_cities')->where('country', 'JO')->update(['fee' => 3, 'updated_at' => $now]);

        $position = (int) DB::table('delivery_cities')->max('position');
        foreach (self::GULF as $row) {
            if (! DB::table('delivery_cities')->where('key', $row['key'])->exists()) {
                DB::table('delivery_cities')->insert($row + [
                    'fee' => 25, 'is_active' => true, 'position' => ++$position, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        foreach (self::CATEGORIES as $row) {
            if (! DB::table('categories')->where('slug', $row['slug'])->exists()) {
                DB::table('categories')->insert($row + ['created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        // Only rows nothing points at are removed, so no order or product loses its link.
        foreach (self::CATEGORIES as $row) {
            $id = DB::table('categories')->where('slug', $row['slug'])->value('id');
            if ($id && ! DB::table('products')->where('category_id', $id)->exists()) {
                DB::table('categories')->where('id', $id)->delete();
            }
        }

        foreach (self::GULF as $row) {
            $id = DB::table('delivery_cities')->where('key', $row['key'])->value('id');
            if ($id && ! DB::table('orders')->where('delivery_city_id', $id)->exists()) {
                DB::table('delivery_cities')->where('id', $id)->delete();
            }
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['country']);
            $table->dropColumn('country');
        });

        Schema::table('delivery_cities', function (Blueprint $table) {
            $table->dropIndex(['country', 'is_active', 'position']);
            $table->dropColumn('country');
        });
    }
};
