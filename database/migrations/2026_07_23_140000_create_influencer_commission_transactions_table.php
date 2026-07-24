<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('influencer_commission_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('influencer_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 16);
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->decimal('amount', 12, 2)->nullable()->comment('Debit amount only; credits read from orders');
            $table->text('admin_notes')->nullable();
            $table->string('status', 24)->default('completed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('order_id');
            $table->index(['influencer_id', 'created_at'], 'ict_influencer_created_idx');
            $table->index(['influencer_id', 'type'], 'ict_influencer_type_idx');
        });

        // Backfill credit pointers for existing attributed orders (no amount copied).
        if (Schema::hasColumn('orders', 'influencer_id')) {
            $now = now();
            DB::table('orders')
                ->whereNotNull('influencer_id')
                ->orderBy('id')
                ->select(['id', 'influencer_id', 'created_at'])
                ->chunkById(200, function ($orders) use ($now): void {
                    $rows = [];
                    foreach ($orders as $order) {
                        $rows[] = [
                            'influencer_id' => $order->influencer_id,
                            'type' => 'credit',
                            'order_id' => $order->id,
                            'amount' => null,
                            'admin_notes' => null,
                            'status' => 'pending',
                            'created_by' => null,
                            'created_at' => $order->created_at ?? $now,
                            'updated_at' => $now,
                        ];
                    }
                    if ($rows !== []) {
                        DB::table('influencer_commission_transactions')->insertOrIgnore($rows);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('influencer_commission_transactions');
    }
};
