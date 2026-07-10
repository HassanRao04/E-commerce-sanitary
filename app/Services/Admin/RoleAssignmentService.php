<?php

namespace App\Services\Admin;

use App\Enums\StaffRole;
use App\Models\User;
use App\Services\UserActivityLogService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleAssignmentService
{
    public function __construct(private readonly UserActivityLogService $activityLog) {}

    /**
     * @return Collection<int, Role>
     */
    public function assignableRoles(User $actor): Collection
    {
        $query = Role::query()
            ->whereIn('name', StaffRole::values())
            ->orderBy('name');

        if (! $actor->hasRole(StaffRole::SuperAdmin->value)) {
            $query->where('name', '!=', StaffRole::SuperAdmin->value);
        }

        return $query->get();
    }

    /**
     * @return list<string>
     */
    public function assignableRoleNames(User $actor): array
    {
        return $this->assignableRoles($actor)->pluck('name')->all();
    }

    public function roleLabel(string $roleName): string
    {
        return StaffRole::tryFromName($roleName)?->label() ?? str_replace('-', ' ', ucfirst($roleName));
    }

    public function isStaffRole(string $roleName): bool
    {
        return StaffRole::tryFromName($roleName) !== null;
    }

    /**
     * @throws ValidationException
     */
    public function validateAssignment(User $actor, string $roleName, ?User $target = null): void
    {
        if (! $this->isStaffRole($roleName)) {
            throw ValidationException::withMessages([
                'role' => 'The selected role is invalid.',
            ]);
        }

        if (! in_array($roleName, $this->assignableRoleNames($actor), true)) {
            throw ValidationException::withMessages([
                'role' => 'You do not have permission to assign this role.',
            ]);
        }

        if ($roleName === StaffRole::SuperAdmin->value && ! $actor->hasRole(StaffRole::SuperAdmin->value)) {
            throw ValidationException::withMessages([
                'role' => 'Only a super admin can assign the super admin role.',
            ]);
        }

        if ($target !== null
            && $target->hasRole(StaffRole::SuperAdmin->value)
            && $roleName !== StaffRole::SuperAdmin->value
            && ! $actor->hasRole(StaffRole::SuperAdmin->value)) {
            throw ValidationException::withMessages([
                'role' => 'Only a super admin can change the role of a super admin.',
            ]);
        }
    }

    public function assignRole(User $user, string $roleName, User $actor): User
    {
        $this->validateAssignment($actor, $roleName, $user);

        return DB::transaction(function () use ($user, $roleName, $actor): User {
            $previousRole = $user->roles->pluck('name')->first();

            $user->syncRoles([$roleName]);

            $this->activityLog->logRoleAssigned($user, $actor, $roleName, $previousRole);

            return $user->fresh(['roles']);
        });
    }

    public function changeRole(User $user, string $roleName, User $actor): User
    {
        $user->load('roles');
        $currentRole = $user->roles->pluck('name')->first();

        if ($currentRole === null) {
            return $this->assignRole($user, $roleName, $actor);
        }

        if ($currentRole === $roleName) {
            return $user;
        }

        if ($currentRole === StaffRole::SuperAdmin->value
            && $roleName !== StaffRole::SuperAdmin->value
            && $this->isLastSuperAdmin($user)) {
            throw ValidationException::withMessages([
                'role' => 'Cannot remove the last super admin role from the system.',
            ]);
        }

        $this->validateAssignment($actor, $roleName, $user);

        return DB::transaction(function () use ($user, $roleName, $currentRole, $actor): User {
            $user->syncRoles([$roleName]);

            $this->activityLog->logRoleChanged($user, $actor, (string) $currentRole, $roleName);

            return $user->fresh(['roles']);
        });
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function removeRole(User $user, User $actor): User
    {
        $user->load('roles');
        $currentRole = $user->roles->pluck('name')->first();

        if ($currentRole === null) {
            throw ValidationException::withMessages([
                'role' => 'This user does not have a staff role assigned.',
            ]);
        }

        if ($actor->is($user)) {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own staff role.',
            ]);
        }

        if ($user->hasRole(StaffRole::SuperAdmin->value) && ! $actor->hasRole(StaffRole::SuperAdmin->value)) {
            throw new AuthorizationException('Only a super admin can remove the super admin role.');
        }

        if ($user->hasRole(StaffRole::SuperAdmin->value) && $this->isLastSuperAdmin($user)) {
            throw ValidationException::withMessages([
                'role' => 'Cannot remove the last super admin role from the system.',
            ]);
        }

        return DB::transaction(function () use ($user, $currentRole, $actor): User {
            $user->syncRoles([]);

            $this->activityLog->logRoleRemoved($user, $actor, (string) $currentRole);

            return $user->fresh(['roles']);
        });
    }

    public function isLastSuperAdmin(User $user): bool
    {
        if (! $user->hasRole(StaffRole::SuperAdmin->value)) {
            return false;
        }

        return User::role(StaffRole::SuperAdmin->value)->count() <= 1;
    }
}
