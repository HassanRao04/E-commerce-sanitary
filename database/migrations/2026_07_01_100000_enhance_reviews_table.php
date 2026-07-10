<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->foreignId('order_item_id')
                ->nullable()
                ->after('order_id')
                ->constrained('order_items')
                ->nullOnDelete();
            $table->boolean('is_featured')->default(false)->after('status');
        });

        Schema::create('review_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('review_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('reviews_enabled')->default(true);
            $table->boolean('auto_approve')->default(false);
            $table->boolean('show_on_homepage')->default(true);
            $table->unsignedTinyInteger('max_featured')->default(6);
            $table->string('homepage_mode')->default('featured');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_settings');
        Schema::dropIfExists('review_images');

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_item_id');
            $table->dropColumn('is_featured');
        });
    }
};
