<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('browser')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->softDeletes();

                $table->index('user_id', 'activity_logs_user_id_index');
                $table->index('action', 'activity_logs_action_index');
                $table->index('created_at', 'activity_logs_created_at_index');
            });

            return;
        }

        Schema::table('activity_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('activity_logs', 'description')) {
                $table->text('description')->nullable()->after('action');
            }

            if (! Schema::hasColumn('activity_logs', 'browser')) {
                $table->string('browser')->nullable()->after('ip_address');
            }

            if (! Schema::hasColumn('activity_logs', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $this->backfillBrowserFromUserAgent();

        Schema::table('activity_logs', function (Blueprint $table): void {
            if (! $this->indexExists('activity_logs', 'activity_logs_user_id_index')) {
                $table->index('user_id', 'activity_logs_user_id_index');
            }

            if (! $this->indexExists('activity_logs', 'activity_logs_action_index')) {
                $table->index('action', 'activity_logs_action_index');
            }

            if (! $this->indexExists('activity_logs', 'activity_logs_created_at_index')) {
                $table->index('created_at', 'activity_logs_created_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::table('activity_logs', function (Blueprint $table): void {
            if ($this->indexExists('activity_logs', 'activity_logs_created_at_index')) {
                $table->dropIndex('activity_logs_created_at_index');
            }

            if ($this->indexExists('activity_logs', 'activity_logs_action_index')) {
                $table->dropIndex('activity_logs_action_index');
            }

            if ($this->indexExists('activity_logs', 'activity_logs_user_id_index')) {
                $table->dropIndex('activity_logs_user_id_index');
            }
        });

        Schema::table('activity_logs', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('activity_logs', 'description') ? 'description' : null,
                Schema::hasColumn('activity_logs', 'browser') ? 'browser' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }

            if (Schema::hasColumn('activity_logs', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }

    private function backfillBrowserFromUserAgent(): void
    {
        if (! Schema::hasColumn('activity_logs', 'browser') || ! Schema::hasColumn('activity_logs', 'user_agent')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::table('activity_logs')
                ->whereNull('browser')
                ->whereNotNull('user_agent')
                ->update([
                    'browser' => DB::raw("substr(user_agent, 1, 255)"),
                ]);

            return;
        }

        DB::table('activity_logs')
            ->whereNull('browser')
            ->whereNotNull('user_agent')
            ->update([
                'browser' => DB::raw('LEFT(user_agent, 255)'),
            ]);
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $index]
            );

            return $indexes !== [];
        }

        if ($driver === 'mysql') {
            $database = $connection->getDatabaseName();
            $indexes = $connection->select(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
                [$database, $table, $index]
            );

            return $indexes !== [];
        }

        return false;
    }
};
