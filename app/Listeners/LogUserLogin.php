<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\UserActivityLogService;
use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    public function __construct(private readonly UserActivityLogService $activityLog) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->activityLog->logLogin($event->user);
    }
}
