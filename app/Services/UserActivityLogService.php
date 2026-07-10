<?php

namespace App\Services;

use App\Enums\UserActivityAction;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserActivityLogService
{
    public function __construct(private readonly ActivityLogService $activityLog) {}

    public function logCreated(User $user, User $actor, array $context = []): ActivityLog
    {
        return $this->activityLog->log(
            UserActivityAction::Created->value,
            $user,
            null,
            $context,
            sprintf('Created staff user %s', $user->full_name),
            $actor->id,
        );
    }

    public function logUpdated(User $user, User $actor, array $old, array $new): ActivityLog
    {
        return $this->activityLog->log(
            UserActivityAction::Updated->value,
            $user,
            $old,
            $new,
            sprintf('Updated profile for %s', $user->full_name),
            $actor->id,
        );
    }

    public function logDeleted(User $user, User $actor, array $snapshot): ActivityLog
    {
        return $this->activityLog->log(
            UserActivityAction::Deleted->value,
            $user,
            $snapshot,
            null,
            sprintf('Deleted staff user %s', $user->full_name),
            $actor->id,
        );
    }

    public function logLogin(User $user): ActivityLog
    {
        return $this->activityLog->log(
            UserActivityAction::Login->value,
            $user,
            null,
            ['email' => $user->email],
            sprintf('%s signed in', $user->full_name),
            $user->id,
        );
    }

    public function logLogout(User $user): ActivityLog
    {
        return $this->activityLog->log(
            UserActivityAction::Logout->value,
            $user,
            null,
            null,
            sprintf('%s signed out', $user->full_name),
            $user->id,
        );
    }

    public function logPasswordReset(User $user, ?User $actor = null, string $context = 'self'): ActivityLog
    {
        $description = $context === 'admin'
            ? sprintf('Reset password for %s', $user->full_name)
            : sprintf('%s reset their password', $user->full_name);

        return $this->activityLog->log(
            UserActivityAction::PasswordReset->value,
            $user,
            null,
            ['email' => $user->email, 'context' => $context],
            $description,
            $actor?->id ?? $user->id,
        );
    }

    public function logRoleAssigned(User $user, User $actor, string $roleName, ?string $previousRole = null): ActivityLog
    {
        return $this->activityLog->log(
            UserActivityAction::RoleAssigned->value,
            $user,
            $previousRole ? ['role' => $previousRole] : null,
            ['role' => $roleName],
            sprintf('Assigned %s role to %s', $this->roleLabel($roleName), $user->full_name),
            $actor->id,
        );
    }

    public function logRoleChanged(User $user, User $actor, string $fromRole, string $toRole): ActivityLog
    {
        return $this->activityLog->log(
            UserActivityAction::RoleChanged->value,
            $user,
            ['role' => $fromRole],
            ['role' => $toRole],
            sprintf(
                'Changed role for %s from %s to %s',
                $user->full_name,
                $this->roleLabel($fromRole),
                $this->roleLabel($toRole),
            ),
            $actor->id,
        );
    }

    public function logRoleRemoved(User $user, User $actor, string $roleName): ActivityLog
    {
        return $this->activityLog->log(
            UserActivityAction::RoleRemoved->value,
            $user,
            ['role' => $roleName],
            null,
            sprintf('Removed %s role from %s', $this->roleLabel($roleName), $user->full_name),
            $actor->id,
        );
    }

    private function roleLabel(string $roleName): string
    {
        return \App\Enums\StaffRole::tryFromName($roleName)?->label()
            ?? str_replace('-', ' ', ucfirst($roleName));
    }
}
