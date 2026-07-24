<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('offers_enabled')->default(false)->after('is_project_suitable');
        });

        Schema::create('product_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('buy_quantity');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->boolean('free_shipping')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'buy_quantity']);
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_offers');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('offers_enabled');
        });
    }
};
