<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('badge_color', 32)->default('gray');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_cancellation')->default(false);
            $table->boolean('is_return')->default(false);
            $table->boolean('is_delivered')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('show_in_progress')->default(false);
            $table->string('customer_group', 32)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
