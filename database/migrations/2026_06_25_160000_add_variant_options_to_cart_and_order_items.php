<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->json('variant_options')->nullable()->after('unit_price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('variant_options')->nullable()->after('variant_name');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('variant_options');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('variant_options');
        });
    }
};
