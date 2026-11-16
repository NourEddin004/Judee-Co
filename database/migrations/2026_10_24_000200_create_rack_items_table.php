<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Hero rack module: the garments hanging on the home page rail (SRS 3.2). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rack_items', function (Blueprint $table) {
            $table->id();
            // Deleting a product keeps the garment; it just stops linking anywhere.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_en', 96);
            $table->string('name_ar', 96)->nullable();
            $table->string('alt_en', 160)->nullable();
            $table->string('alt_ar', 160)->nullable();
            $table->string('image_path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->decimal('size_adjust', 4, 2)->default(1.00);
            $table->timestamps();

            $table->index(['is_visible', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_items');
    }
};
