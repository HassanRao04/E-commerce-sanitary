<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('flat_rate_enabled')->default(true);
            $table->decimal('flat_rate_amount', 12, 2)->default(0);
            $table->boolean('product_rate_enabled')->default(false);
            $table->boolean('category_rate_enabled')->default(false);
            $table->boolean('free_shipping_enabled')->default(true);
            $table->decimal('free_shipping_threshold', 12, 2)->default(0);
            $table->string('default_method', 20)->default('flat');
            $table->timestamps();
        });

        Schema::create('product_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('product_id');
        });

        Schema::create('category_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_shipping_rates');
        Schema::dropIfExists('product_shipping_rates');
        Schema::dropIfExists('shipping_settings');
    }
};
