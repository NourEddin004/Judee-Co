<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Delivery module: cities, their areas and fees (SRS 3.5, Appendix D.8.2). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_cities', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('name_en', 64);
            $table->string('name_ar', 64)->nullable();
            $table->decimal('fee', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_city_id')->constrained()->cascadeOnDelete();
            $table->string('name_en', 96);
            $table->string('name_ar', 96)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('delivery_city_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_areas');
        Schema::dropIfExists('delivery_cities');
    }
};
