<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignUserRoleRequest;
use App\Http\Requests\Admin\BulkUserActionRequest;
use App\Http\Requests\Admin\RemoveUserRoleRequest;
use App\Http\Requests\Admin\StoreInfluencerRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateInfluencerRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Admin\RoleAssignmentService;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserService $userService,
        private readonly RoleAssignmentService $roleAssignment,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => $this->users->search(
                array_filter([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'role' => $request->input('role'),
                    'status' => $request->input('status'),
                    'staff_only' => ! $request->filled('role'),
                    'sort' => $request->input('sort'),
                    'direction' => $request->input('direction'),
                ], fn ($value) => $value !== null && $value !== ''),
            ),
            'roles' => $this->roleAssignment->assignableRoleNames($request->user()),
        ]);
    }

    public function influencers(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.influencers.index', [
            'users' => $this->users->search([
                'role' => 'influencer',
                'sort' => 'created_at',
                'direction' => 'desc',
            ]),
        ]);
    }

    public function createInfluencer(): View
    {
        $this->authorize('create', User::class);

        return view('admin.influencers.form', [
            'influencer' => new User(['status' => UserStatus::Active]),
        ]);
    }

    public function storeInfluencer(StoreInfluencerRequest $request): RedirectResponse
    {
        $this->userService->createInfluencer(
            $request->validated(),
            $request->file('profile_photo'),
        );

        return redirect()
            ->route('admin.influencers.index')
            ->with('success', 'Influencer created successfully.');
    }

    public function editInfluencer(User $influencer): View
    {
        $this->assertInfluencer($influencer);

        $actor = request()->user();

        if ($actor === null || ! app(UserPolicy::class)->update($actor, $influencer)) {
            abort(403);
        }

        return view('admin.influencers.form', [
            'influencer' => $influencer,
        ]);
    }

    public function updateInfluencer(UpdateInfluencerRequest $request, User $influencer): RedirectResponse
    {
        $this->assertInfluencer($influencer);

        $this->userService->updateInfluencer(
            $influencer,
            $request->validated(),
            $request->file('profile_photo'),
        );

        return redirect()
            ->route('admin.influencers.index')
            ->with('success', 'Influencer updated successfully.');
    }

    public function activateInfluencer(User $influencer): RedirectResponse
    {
        $this->assertInfluencer($influencer);

        $actor = request()->user();

        if ($actor === null || ! app(UserPolicy::class)->update($actor, $influencer)) {
            abort(403);
        }

        $this->userService->setStatus($influencer, UserStatus::Active, $actor);

        return redirect()
            ->route('admin.influencers.index')
            ->with('success', 'Influencer activated.');
    }

    public function deactivateInfluencer(User $influencer): RedirectResponse
    {
        $this->assertInfluencer($influencer);

        $actor = request()->user();

        if ($actor === null || ! app(UserPolicy::class)->update($actor, $influencer)) {
            abort(403);
        }

        $this->userService->setStatus($influencer, UserStatus::Inactive, $actor);

        return redirect()
            ->route('admin.influencers.index')
            ->with('success', 'Influencer deactivated.');
    }

    public function destroyInfluencer(User $influencer): RedirectResponse
    {
        $this->assertInfluencer($influencer);

        $actor = request()->user();

        if ($actor === null || ! app(UserPolicy::class)->delete($actor, $influencer)) {
            abort(403);
        }

        $this->userService->delete($influencer, $actor);

        return redirect()
            ->route('admin.influencers.index')
            ->with('success', 'Influencer deleted successfully.');
    }

    private function assertInfluencer(User $user): void
    {
        abort_unless($user->hasRole('influencer'), 404);
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load('roles');

        $activityLogs = ActivityLog::query()
            ->forSubject($user)
            ->with('user')
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('admin.users.show', [
            'user' => $user,
            'activityLogs' => $activityLogs,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', [
            'user' => new User(['status' => UserStatus::Active]),
            'roles' => $this->roleAssignment->assignableRoles($request->user()),
            'rolePermissions' => collect(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create(
            $request->validated(),
            $request->file('profile_photo'),
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $actor = request()->user();

        if ($actor === null || ! app(UserPolicy::class)->update($actor, $user)) {
            abort(403);
        }

        $user->load(['roles.permissions']);

        return view('admin.users.form', [
            'user' => $user,
            'roles' => $this->roleAssignment->assignableRoles(request()->user()),
            'rolePermissions' => $user->roles->first()?->permissions->pluck('name')->sort()->values() ?? collect(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update(
            $user,
            $request->validated(),
            $request->file('profile_photo'),
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function updateRole(AssignUserRoleRequest $request, User $user): RedirectResponse
    {
        $this->roleAssignment->changeRole(
            $user,
            $request->string('role')->toString(),
            $request->user(),
        );

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'User role updated successfully.');
    }

    public function bulkAction(BulkUserActionRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $userIds = $request->input('user_ids');

        $result = match ($request->string('action')->toString()) {
            'activate' => $this->userService->bulkActivate($userIds, $actor),
            'deactivate' => $this->userService->bulkDeactivate($userIds, $actor),
            'delete' => $this->userService->bulkDelete($userIds, $actor),
        };

        $message = match ($request->input('action')) {
            'activate' => "{$result['processed']} user(s) activated.",
            'deactivate' => "{$result['processed']} user(s) deactivated.",
            'delete' => "{$result['processed']} user(s) deleted.",
        };

        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} user(s) skipped due to permissions or restrictions.";
        }

        if ($result['processed'] === 0 && $result['skipped'] === 0) {
            $message = 'No changes were made to the selected users.';
        }

        return redirect()
            ->route('admin.users.index', $request->only(['name', 'email', 'role', 'status', 'sort', 'direction']))
            ->with('success', $message);
    }

    public function destroy(User $user): RedirectResponse
    {
        $actor = request()->user();

        if ($actor === null || ! app(UserPolicy::class)->delete($actor, $user)) {
            abort(403);
        }

        $this->userService->delete($user, $actor);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function destroyRole(RemoveUserRoleRequest $request, User $user): RedirectResponse
    {
        $this->roleAssignment->removeRole($user, $request->user());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Staff role removed from user.');
    }
}
