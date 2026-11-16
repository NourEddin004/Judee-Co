<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Orders module (SRS 3.3, 3.4, 3.7). Order items copy product data (REQ-DB-07, REQ-CMS-42). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->char('number', 6)->unique();          // REQ-DB-10, REQ-DMO-73
            $table->char('link_token', 64)->unique();     // REQ-DB-32, REQ-SEC-41
            $table->string('customer_name', 120);
            $table->string('phone', 24);                  // "+962 791234567"
            $table->string('email', 191)->nullable();
            $table->foreignId('delivery_city_id')->constrained()->restrictOnDelete();
            $table->string('city_name', 64);
            $table->string('area', 120);
            $table->string('address', 255);
            $table->text('note')->nullable();
            $table->enum('status', ['new', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled'])->default('new');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 8, 2);
            $table->decimal('total', 10, 2);
            $table->char('language', 2)->default('en');
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index('delivery_city_id');
            $table->index('phone');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 128);
            $table->string('color_name', 64);
            $table->string('size', 16)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedTinyInteger('quantity');
            $table->timestamps();

            $table->index('order_id');
        });

        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notes');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
