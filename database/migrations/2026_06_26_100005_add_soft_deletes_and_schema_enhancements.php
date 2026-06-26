<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->softDeletes();

            $table->foreign('billing_address_id')
                ->references('id')
                ->on('addresses')
                ->nullOnDelete();

            $table->foreign('shipping_address_id')
                ->references('id')
                ->on('addresses')
                ->nullOnDelete();
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->unique(['attribute_id', 'slug']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::table('frequently_bought_together', function (Blueprint $table) {
            $table->unique(['product_id', 'related_product_id']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['model_type', 'model_id']);
        });

        Schema::table('login_attempts', function (Blueprint $table) {
            $table->index(['email', 'attempted_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('transaction_id');
            $table->index('status');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('login_attempts', function (Blueprint $table) {
            $table->dropIndex(['email', 'attempted_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['model_type', 'model_id']);
        });

        Schema::table('frequently_bought_together', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'related_product_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_variant_id']);
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropUnique(['attribute_id', 'slug']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['billing_address_id']);
            $table->dropForeign(['shipping_address_id']);
            $table->dropSoftDeletes();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
