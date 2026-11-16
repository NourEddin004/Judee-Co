<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Content module: editable site texts, FAQs, client reviews and policy pages (SRS 3.5). */
return new class extends Migration
{
    public function up(): void
    {
        // Single texts addressed by key, e.g. "home.hero.title" (REQ-CMS-19 to 21).
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key', 96)->unique();
            $table->text('value_en');
            $table->text('value_ar')->nullable();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->text('question_en');
            $table->text('question_ar')->nullable();
            $table->text('answer_en');
            $table->text('answer_ar')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'position']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('author', 96);
            $table->char('country_code', 2)->nullable();
            $table->string('image_path')->nullable();
            $table->text('body_en')->nullable();
            $table->text('body_ar')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'position']);
        });

        // Sections of the policy pages (Terms and Conditions, D.6.9).
        Schema::create('policy_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page', 32)->default('terms');
            $table->string('title_en', 128);
            $table->string('title_ar', 128)->nullable();
            $table->text('body_en');
            $table->text('body_ar')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['page', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_sections');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('content_blocks');
    }
};
