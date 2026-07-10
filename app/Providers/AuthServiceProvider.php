<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerUserGates();
    }

    private function registerUserGates(): void
    {
        Gate::define('users.view', fn (User $user): bool => $user->hasPermissionTo('users.view'));

        Gate::define('users.create', fn (User $user): bool => $user->hasPermissionTo('users.create'));

        Gate::define('users.update', fn (User $user): bool => $user->hasPermissionTo('users.update'));

        Gate::define('users.delete', fn (User $user): bool => $user->hasPermissionTo('users.delete'));
    }
}
