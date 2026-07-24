<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            if (! Schema::hasColumn('inquiries', 'source')) {
                $table->string('source')->default('contact_form')->after('type');
            }

            if (! Schema::hasColumn('inquiries', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('source');
            }
        });

        $statusMap = [
            'read' => 'pending',
            'in_progress' => 'pending',
            'resolved' => 'replied',
            'archived' => 'closed',
        ];

        foreach ($statusMap as $from => $to) {
            DB::table('inquiries')->where('status', $from)->update(['status' => $to]);
        }
    }

    public function down(): void
    {
        $statusMap = [
            'pending' => 'read',
            'replied' => 'resolved',
            'spam' => 'closed',
        ];

        foreach ($statusMap as $from => $to) {
            DB::table('inquiries')->where('status', $from)->update(['status' => $to]);
        }

        Schema::table('inquiries', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('inquiries', 'source')) {
                $columns[] = 'source';
            }

            if (Schema::hasColumn('inquiries', 'ip_address')) {
                $columns[] = 'ip_address';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
