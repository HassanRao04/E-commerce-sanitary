<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cart_items', 'product_offer_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('product_offer_id')
                    ->nullable()
                    ->after('product_variant_id')
                    ->constrained('product_offers')
                    ->nullOnDelete();
            });
        } elseif (Schema::hasTable('product_offers') && ! $this->foreignKeyExists('cart_items', 'cart_items_product_offer_id_foreign')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreign('product_offer_id')
                    ->references('id')
                    ->on('product_offers')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('cart_items', 'pipe_length_option_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('pipe_length_option_id')
                    ->nullable()
                    ->after('product_offer_id')
                    ->constrained('product_pipe_length_options')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cart_items', 'pipe_length_option_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('pipe_length_option_id');
            });
        }

        if (Schema::hasColumn('cart_items', 'product_offer_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                if ($this->foreignKeyExists('cart_items', 'cart_items_product_offer_id_foreign')) {
                    $table->dropForeign(['product_offer_id']);
                }
                $table->dropColumn('product_offer_id');
            });
        }
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, $table, $foreignKey, 'FOREIGN KEY']
        );

        return $result !== [];
    }
};
