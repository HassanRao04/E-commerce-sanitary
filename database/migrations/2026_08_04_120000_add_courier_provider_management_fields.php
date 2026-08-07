<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_providers', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('slug');
            $table->string('api_base_url')->nullable()->after('tracking_url_template');
            $table->string('account_number')->nullable()->after('api_base_url');
            $table->text('pickup_address')->nullable()->after('account_number');
            $table->string('pickup_city')->nullable()->after('pickup_address');
            $table->decimal('default_package_weight', 8, 2)->nullable()->after('pickup_city');
        });
    }

    public function down(): void
    {
        Schema::table('courier_providers', function (Blueprint $table) {
            $table->dropColumn([
                'logo',
                'api_base_url',
                'account_number',
                'pickup_address',
                'pickup_city',
                'default_package_weight',
            ]);
        });
    }
};
