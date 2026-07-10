<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('logo')->nullable()->after('site_name');
            $table->string('favicon')->nullable()->after('logo');
        });

        Schema::table('banners', function (Blueprint $table): void {
            $table->json('metadata')->nullable()->after('button_url');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['logo', 'favicon']);
        });

        Schema::table('banners', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};
