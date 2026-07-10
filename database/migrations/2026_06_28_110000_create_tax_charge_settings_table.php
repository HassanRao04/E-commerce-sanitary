<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_charge_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('vat_enabled')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->boolean('gst_enabled')->default(true);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->boolean('sales_tax_enabled')->default(false);
            $table->decimal('sales_tax_rate', 5, 2)->default(0);
            $table->string('default_tax_type', 20)->default('gst');
            $table->boolean('service_charge_enabled')->default(false);
            $table->string('service_charge_type', 20)->default('percent');
            $table->decimal('service_charge_value', 12, 2)->default(0);
            $table->boolean('handling_charge_enabled')->default(false);
            $table->string('handling_charge_type', 20)->default('fixed');
            $table->decimal('handling_charge_value', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_charge_settings');
    }
};
