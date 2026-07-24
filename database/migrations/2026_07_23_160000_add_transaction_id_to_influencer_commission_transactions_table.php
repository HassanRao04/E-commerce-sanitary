<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('influencer_commission_transactions', function (Blueprint $table): void {
            $table->string('transaction_id')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('influencer_commission_transactions', function (Blueprint $table): void {
            $table->dropColumn('transaction_id');
        });
    }
};
