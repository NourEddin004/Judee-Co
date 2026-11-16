<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog module: products and the lists used to organise them (SRS 3.7, Appendix C.2).
 * Prices are DECIMAL (REQ-DB-08); every customer-facing text has _en and _ar (REQ-DB-09).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Colour families used by the shop's colour filter (Appendix D.2, 10 swatches).
        Schema::create('shades', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('name_en', 64);
            $table->string('name_ar', 64)->nullable();
            $table->char('swatch', 7);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // A product's named colour ("Olive Noir"), grouped into a shade family (C.2 Color).
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shade_id')->constrained()->restrictOnDelete();
            $table->string('name_en', 64);
            $table->string('name_ar', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name_en', 64);
            $table->string('name_ar', 64)->nullable();
            $table->string('page_title_en', 96);
            $table->string('page_title_ar', 96)->nullable();
            $table->string('empty_title_en', 128)->nullable();
            $table->string('empty_title_ar', 128)->nullable();
            $table->string('empty_body_en', 255)->nullable();
            $table->string('empty_body_ar', 255)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name_en', 96);
            $table->string('name_ar', 96)->nullable();
            $table->timestamps();
        });

        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->string('label', 16)->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // One size table per category; content holds {"columns": [...], "rows": [[...], ...]}.
        Schema::create('size_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('content_en');
            $table->json('content_ar')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('color_id')->constrained()->restrictOnDelete();
            $table->string('slug', 128)->unique();
            $table->string('name_en', 128);
            $table->string('name_ar', 128)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('note_en', 255)->nullable();
            $table->string('note_ar', 255)->nullable();
            $table->string('badge_en', 32)->nullable();
            $table->string('badge_ar', 32)->nullable();
            // Products sharing a style are shown as "Available colors" of each other (D.5.8).
            $table->string('style', 64)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('in_stock')->default(true);
            $table->boolean('is_published')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_newly_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Indexes from SRS 3.7.4 (REQ-DB-21, REQ-DB-22).
            $table->index(['is_published', 'display_order']);
            $table->index(['category_id', 'is_published', 'display_order']);
            $table->index(['category_id', 'is_published', 'is_newly_featured', 'display_order'], 'products_cat_pub_new_order_index');
            $table->index(['category_id', 'is_published', 'name_en']);
            $table->index(['category_id', 'is_published', 'name_ar']);
            $table->index('style');
            $table->fullText(['name_en', 'name_ar', 'description_en', 'description_ar'], 'products_search_fulltext');
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['collection_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::create('product_size', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('size_id')->constrained()->restrictOnDelete();
            $table->primary(['product_id', 'size_id']);
            $table->index('size_id');
        });

        Schema::create('product_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_main')->default(false);
            $table->boolean('is_hover')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_main', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_photos');
        Schema::dropIfExists('product_size');
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('size_guides');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('shades');
    }
};
