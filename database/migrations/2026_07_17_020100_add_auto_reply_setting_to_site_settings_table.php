<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_settings', 'auto_reply_enabled')) {
                $table->boolean('auto_reply_enabled')->default(false)->after('whatsapp_notifications_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('site_settings', 'auto_reply_enabled')) {
                $table->dropColumn('auto_reply_enabled');
            }
        });
    }
};
