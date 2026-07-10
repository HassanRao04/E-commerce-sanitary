<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'out_for_delivery')
            ->update(['status' => 'shipped']);

        DB::table('orders')
            ->where('status', 'refunded')
            ->update(['status' => 'returned']);

        DB::table('order_status_histories')
            ->where('status', 'out_for_delivery')
            ->update(['status' => 'shipped']);

        DB::table('order_status_histories')
            ->where('status', 'refunded')
            ->update(['status' => 'returned']);
    }

    public function down(): void
    {
        // Legacy slugs are not restored automatically.
    }
};
