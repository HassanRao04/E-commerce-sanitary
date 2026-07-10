<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\UserActivityLogService;
use Illuminate\Auth\Events\PasswordReset;

class LogUserPasswordReset
{
    public function __construct(private readonly UserActivityLogService $activityLog) {}

    public function handle(PasswordReset $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->activityLog->logPasswordReset($event->user);
    }
}
