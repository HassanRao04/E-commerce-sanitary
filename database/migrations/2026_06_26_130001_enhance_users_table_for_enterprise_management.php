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
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }

            if (! Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $this->backfillNameParts();

        Schema::table('users', function (Blueprint $table): void {
            if (! $this->indexExists('users', 'users_status_index')) {
                $table->index('status', 'users_status_index');
            }

            if (! $this->indexExists('users', 'users_last_login_at_index')) {
                $table->index('last_login_at', 'users_last_login_at_index');
            }

            if (! $this->indexExists('users', 'users_suspended_at_index')) {
                $table->index('suspended_at', 'users_suspended_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if ($this->indexExists('users', 'users_suspended_at_index')) {
                $table->dropIndex('users_suspended_at_index');
            }

            if ($this->indexExists('users', 'users_last_login_at_index')) {
                $table->dropIndex('users_last_login_at_index');
            }

            if ($this->indexExists('users', 'users_status_index')) {
                $table->dropIndex('users_status_index');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('users', 'first_name') ? 'first_name' : null,
                Schema::hasColumn('users', 'last_name') ? 'last_name' : null,
                Schema::hasColumn('users', 'profile_photo') ? 'profile_photo' : null,
                Schema::hasColumn('users', 'last_login_ip') ? 'last_login_ip' : null,
                Schema::hasColumn('users', 'suspended_at') ? 'suspended_at' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }

            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }

    private function backfillNameParts(): void
    {
        if (! Schema::hasColumn('users', 'first_name')) {
            return;
        }

        DB::table('users')
            ->whereNull('first_name')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $name = trim((string) $user->name);
                    $parts = preg_split('/\s+/', $name, 2) ?: [];

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'first_name' => $parts[0] ?? 'User',
                            'last_name' => $parts[1] ?? null,
                        ]);
                }
            });
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
