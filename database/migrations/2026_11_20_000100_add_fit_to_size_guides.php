<?php

use App\Domain\Catalog\SizeFit;
use App\Support\StoreCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Size help on the product page (2026-11-20): each size guide gets a height and weight range per
 * size. Existing guides start with the standard starting values for the sizes they list; the owner
 * replaces them with JUDE's own chart under Size guides.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('size_guides', function (Blueprint $table) {
            $table->json('fit')->nullable()->after('content_ar');
        });

        foreach (DB::table('size_guides')->get() as $guide) {
            $table = json_decode((string) $guide->content_en, true) ?: [];
            $sizes = array_map(fn ($row) => (string) ($row[0] ?? ''), $table['rows'] ?? []);
            $fit = SizeFit::defaultsFor($sizes);

            if ($fit !== []) {
                DB::table('size_guides')->where('id', $guide->id)->update(['fit' => json_encode($fit)]);
            }
        }

        // The storefront caches categories with their size guides; drop that copy so the new column shows.
        app(StoreCache::class)->flush();
    }

    public function down(): void
    {
        Schema::table('size_guides', function (Blueprint $table) {
            $table->dropColumn('fit');
        });
    }
};
