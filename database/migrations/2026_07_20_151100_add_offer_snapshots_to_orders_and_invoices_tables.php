<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('offer_discount_total', 12, 2)->default(0)->after('discount_total');
            $table->decimal('shipping_discount_total', 12, 2)->default(0)->after('shipping_total');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('selected_offer')->nullable()->after('variant_options');
            $table->decimal('original_unit_price', 12, 2)->nullable()->after('unit_price');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('original_unit_price');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_percent');
            $table->string('pipe_length')->nullable()->after('discount_amount');
            $table->decimal('pipe_extra_cost', 12, 2)->default(0)->after('pipe_length');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('offer_discount_total', 12, 2)->default(0)->after('discount_total');
            $table->decimal('shipping_discount_total', 12, 2)->default(0)->after('shipping_total');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('selected_offer')->nullable()->after('variant_name');
            $table->decimal('original_unit_price', 12, 2)->nullable()->after('unit_price');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('original_unit_price');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_percent');
            $table->string('pipe_length')->nullable()->after('discount_amount');
            $table->decimal('pipe_extra_cost', 12, 2)->default(0)->after('pipe_length');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['offer_discount_total', 'shipping_discount_total']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'selected_offer',
                'original_unit_price',
                'discount_percent',
                'discount_amount',
                'pipe_length',
                'pipe_extra_cost',
            ]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['offer_discount_total', 'shipping_discount_total']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'selected_offer',
                'original_unit_price',
                'discount_percent',
                'discount_amount',
                'pipe_length',
                'pipe_extra_cost',
            ]);
        });
    }
};
