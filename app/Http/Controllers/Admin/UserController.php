<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' =>             $this->users->search(
                $request->input('q'),
                array_filter([
                    'role' => $request->input('role'),
                    'staff_only' => ! $request->filled('role'),
                ]),
            ),
            'roles' => Role::query()->where('name', '!=', 'customer')->orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', [
            'user' => new User(['status' => 'active']),
            'roles' => Role::query()->where('name', '!=', 'customer')->orderBy('name')->get(),
        ]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return view('admin.users.form', [
            'user' => $user,
            'roles' => Role::query()->where('name', '!=', 'customer')->orderBy('name')->get(),
        ]);
    }
}
