<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('influencer_commission_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('influencer_commission_transactions', 'reference_order_id')) {
                $table->foreignId('reference_order_id')
                    ->nullable()
                    ->after('order_id')
                    ->constrained('orders')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('influencer_commission_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('influencer_commission_transactions', 'reference_order_id')) {
                $table->dropConstrainedForeignId('reference_order_id');
            }
        });
    }
};
