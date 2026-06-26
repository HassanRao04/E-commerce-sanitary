<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class VerifyPhase1Command extends Command
{
    protected $signature = 'erp:verify-phase1';

    protected $description = 'Verify Phase 1 ERP foundation is correctly installed';

    public function handle(): int
    {
        $checks = [
            'Roles table' => Role::query()->exists(),
            'Permissions table' => Permission::query()->exists(),
            'Super admin user' => User::role('super-admin')->exists(),
            'Site settings' => SiteSetting::query()->exists(),
            'Shop config' => config('shop.currency') !== null,
        ];

        $failed = false;

        foreach ($checks as $label => $passed) {
            if ($passed) {
                $this->info("✓ {$label}");
            } else {
                $this->error("✗ {$label}");
                $failed = true;
            }
        }

        if ($failed) {
            $this->newLine();
            $this->warn('Run: php artisan migrate --seed');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Phase 1 verification passed.');

        return self::SUCCESS;
    }
}
