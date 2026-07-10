<?php

namespace App\Services\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\UserActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(
        private readonly UserActivityLogService $activityLog,
        private readonly RoleAssignmentService $roles,
    ) {}

    public function assignableRoles(User $actor)
    {
        return $this->roles->assignableRoles($actor);
    }

    public function create(array $data, ?UploadedFile $profilePhoto = null): User
    {
        return DB::transaction(function () use ($data, $profilePhoto) {
            $role = $data['role'];
            unset($data['role']);

            $status = $data['status'] instanceof UserStatus
                ? $data['status']
                : UserStatus::from($data['status']);

            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $status,
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);

            if ($profilePhoto !== null) {
                $user->update([
                    'profile_photo' => $this->storeProfilePhoto($user, $profilePhoto),
                ]);
            }

            $this->roles->assignRole($user, $role, auth()->user());

            $this->activityLog->logCreated(
                $user->fresh(['roles']),
                auth()->user(),
                [
                    'email' => $user->email,
                    'role' => $role,
                    'status' => $status->value,
                ],
            );

            return $user->fresh(['roles']);
        });
    }

    public function update(User $user, array $data, ?UploadedFile $profilePhoto = null): User
    {
        return DB::transaction(function () use ($user, $data, $profilePhoto) {
            $user->load('roles');
            $actor = auth()->user();

            $oldSnapshot = $this->snapshot($user);
            $newRole = $data['role'];
            unset($data['role']);

            $status = $data['status'] instanceof UserStatus
                ? $data['status']
                : UserStatus::from($data['status']);

            $attributes = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $status,
            ];

            if (filled($data['password'] ?? null)) {
                $attributes['password'] = $data['password'];
            }

            unset($data['password'], $data['password_confirmation'], $data['status']);

            $user->update($attributes);

            if ($profilePhoto !== null) {
                $user->update([
                    'profile_photo' => $this->storeProfilePhoto($user, $profilePhoto),
                ]);
            }

            $this->roles->changeRole($user, $newRole, $actor);

            $user = $user->fresh(['roles']);
            $newSnapshot = $this->snapshot($user);

            if (array_key_exists('password', $attributes)) {
                $this->activityLog->logPasswordReset($user, $actor, 'admin');
            }

            $profileChanges = $this->diffSnapshot($oldSnapshot, $newSnapshot, ['role']);

            if ($profileChanges !== []) {
                $this->activityLog->logUpdated(
                    $user,
                    $actor,
                    $profileChanges['old'],
                    $profileChanges['new'],
                );
            }

            return $user;
        });
    }

    public function delete(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor): void {
            $user->load('roles');
            $snapshot = $this->snapshot($user);

            $this->activityLog->logDeleted($user, $actor, $snapshot);

            $user->syncRoles([]);

            if (filled($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->delete();
        });
    }

    /**
     * @param  list<int>  $userIds
     * @return array{processed: int, skipped: int}
     */
    public function bulkActivate(array $userIds, User $actor): array
    {
        return $this->bulkSetStatus($userIds, UserStatus::Active, $actor);
    }

    /**
     * @param  list<int>  $userIds
     * @return array{processed: int, skipped: int}
     */
    public function bulkDeactivate(array $userIds, User $actor): array
    {
        return $this->bulkSetStatus($userIds, UserStatus::Inactive, $actor);
    }

    /**
     * @param  list<int>  $userIds
     * @return array{processed: int, skipped: int}
     */
    public function bulkDelete(array $userIds, User $actor): array
    {
        $policy = app(UserPolicy::class);
        $processed = 0;
        $skipped = 0;

        foreach (User::query()->whereIn('id', $userIds)->get() as $user) {
            if (! $policy->delete($actor, $user)) {
                $skipped++;

                continue;
            }

            $this->delete($user, $actor);
            $processed++;
        }

        return compact('processed', 'skipped');
    }

    /**
     * @param  list<int>  $userIds
     * @return array{processed: int, skipped: int}
     */
    private function bulkSetStatus(array $userIds, UserStatus $status, User $actor): array
    {
        $policy = app(UserPolicy::class);
        $processed = 0;
        $skipped = 0;

        foreach (User::query()->whereIn('id', $userIds)->get() as $user) {
            if (! $policy->update($actor, $user)) {
                $skipped++;

                continue;
            }

            if ($user->status === $status) {
                continue;
            }

            $oldStatus = $user->status?->value;

            $user->update(['status' => $status]);

            $this->activityLog->logUpdated(
                $user->fresh(['roles']),
                $actor,
                ['status' => $oldStatus],
                ['status' => $status->value],
            );

            $processed++;
        }

        return compact('processed', 'skipped');
    }

    private function storeProfilePhoto(User $user, UploadedFile $file): string
    {
        if (filled($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        return $file->store("users/{$user->id}", 'public');
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(User $user): array
    {
        return [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status?->value,
            'role' => $user->roles->pluck('name')->first(),
            'profile_photo' => $user->profile_photo,
        ];
    }

    /**
     * @param  array<int, string>  $ignoreKeys
     * @return array{old: array<string, mixed>, new: array<string, mixed>}|array{}
     */
    private function diffSnapshot(array $old, array $new, array $ignoreKeys = []): array
    {
        $changedOld = [];
        $changedNew = [];

        foreach ($old as $key => $value) {
            if (in_array($key, $ignoreKeys, true)) {
                continue;
            }

            if (($new[$key] ?? null) != $value) {
                $changedOld[$key] = $value;
                $changedNew[$key] = $new[$key] ?? null;
            }
        }

        if ($changedOld === []) {
            return [];
        }

        return [
            'old' => $changedOld,
            'new' => $changedNew,
        ];
    }
}
