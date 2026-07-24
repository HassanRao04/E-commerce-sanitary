<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('support_email')->nullable()->after('email');
            $table->boolean('contact_form_enabled')->default(true)->after('whatsapp');
            $table->boolean('email_notifications_enabled')->default(true)->after('contact_form_enabled');
            $table->boolean('whatsapp_notifications_enabled')->default(true)->after('email_notifications_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'support_email',
                'contact_form_enabled',
                'email_notifications_enabled',
                'whatsapp_notifications_enabled',
            ]);
        });
    }
};
