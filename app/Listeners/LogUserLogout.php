<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\UserActivityLogService;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    public function __construct(private readonly UserActivityLogService $activityLog) {}

    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->activityLog->logLogout($event->user);
    }
}
