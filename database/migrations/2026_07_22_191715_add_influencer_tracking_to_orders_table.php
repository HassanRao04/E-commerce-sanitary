<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('coupon_id')
                ->nullable()
                ->after('coupon_code')
                ->constrained('coupons')
                ->nullOnDelete();
            $table->foreignId('influencer_id')
                ->nullable()
                ->after('coupon_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->decimal('influencer_commission_amount', 12, 2)
                ->default(0)
                ->after('influencer_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropConstrainedForeignId('influencer_id');
            $table->dropColumn('influencer_commission_amount');
        });
    }
};
