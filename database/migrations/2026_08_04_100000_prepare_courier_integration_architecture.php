<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 50)->unique();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_sandbox')->default(false);
            $table->string('tracking_url_template')->nullable();
            $table->json('config')->nullable();
            $table->text('credentials')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('courier_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_slug', 50);
            $table->string('event_type')->nullable();
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->timestamps();

            $table->index(['provider_slug', 'processed']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('courier_provider_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->string('external_shipment_id')->nullable()->after('courier_name');
            $table->string('awb_number')->nullable()->after('tracking_number');
            $table->string('label_path')->nullable()->after('awb_number');
            $table->string('booking_status', 20)->default('manual')->after('status');
            $table->timestamp('booked_at')->nullable()->after('delivered_at');
            $table->json('booking_meta')->nullable()->after('booked_at');

            $table->index('external_shipment_id');
            $table->index('awb_number');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['courier_provider_id']);
            $table->dropIndex(['external_shipment_id']);
            $table->dropIndex(['awb_number']);
            $table->dropColumn([
                'courier_provider_id',
                'external_shipment_id',
                'awb_number',
                'label_path',
                'booking_status',
                'booked_at',
                'booking_meta',
            ]);
        });

        Schema::dropIfExists('courier_webhook_logs');
        Schema::dropIfExists('courier_providers');
    }
};
