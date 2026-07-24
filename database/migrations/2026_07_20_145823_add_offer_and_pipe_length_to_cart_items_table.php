<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_offer_id')
                ->nullable()
                ->after('product_variant_id')
                ->constrained('product_offers')
                ->nullOnDelete();

            $table->foreignId('pipe_length_option_id')
                ->nullable()
                ->after('product_offer_id')
                ->constrained('product_pipe_length_options')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_offer_id');
            $table->dropConstrainedForeignId('pipe_length_option_id');
        });
    }
};
