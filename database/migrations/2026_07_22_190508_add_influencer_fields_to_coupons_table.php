<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('influencer_id')
                ->nullable()
                ->after('is_active')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('commission_type')->nullable()->after('influencer_id');
            $table->decimal('commission_value', 12, 2)->nullable()->after('commission_type');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('influencer_id');
            $table->dropColumn(['commission_type', 'commission_value']);
        });
    }
};
