<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('service_charge_total', 12, 2)->default(0)->after('shipping_total');
            $table->decimal('handling_charge_total', 12, 2)->default(0)->after('service_charge_total');
            $table->string('tax_type', 20)->nullable()->after('tax_total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_charge_total', 'handling_charge_total', 'tax_type']);
        });
    }
};
