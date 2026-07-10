<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_rules_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('minimum_order_enabled')->default(false);
            $table->decimal('minimum_order_amount', 12, 2)->default(0);
            $table->boolean('coupons_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_rules_settings');
    }
};
