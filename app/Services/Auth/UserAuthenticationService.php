<?php

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserAuthenticationService
{
    public function loginDeniedMessage(UserStatus $status): string
    {
        return match ($status) {
            UserStatus::Suspended => 'Your account has been suspended. Please contact an administrator.',
            default => 'You cannot sign in with this account.',
        };
    }

    /**
     * @throws ValidationException
     */
    public function ensureMayLogin(User $user): void
    {
        if ($user->status?->isLoginAllowed() ?? false) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => $this->loginDeniedMessage($user->status ?? UserStatus::Inactive),
        ]);
    }

    public function recordSuccessfulLogin(User $user): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    public function forceLogoutSuspendedUser(): void
    {
        $user = Auth::user();

        if (! $user instanceof User || $user->status !== UserStatus::Suspended) {
            return;
        }

        Auth::guard('web')->logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }
}
