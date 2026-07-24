<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('option_title')->nullable()->after('pipe_length_enabled');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('option_title')->nullable()->after('selected_offer');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('option_title')->nullable()->after('selected_offer');
        });

        // Preserve existing enabled products with a configured title in data (not frontend code).
        DB::table('products')
            ->where('pipe_length_enabled', true)
            ->where(function ($query): void {
                $query->whereNull('option_title')->orWhere('option_title', '');
            })
            ->update(['option_title' => 'Options']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('option_title');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('option_title');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('option_title');
        });
    }
};
