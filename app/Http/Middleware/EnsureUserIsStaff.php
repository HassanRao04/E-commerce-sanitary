<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Services\Auth\UserAuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStaff
{
    public function __construct(private readonly UserAuthenticationService $authService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStaff()) {
            abort(403, 'Unauthorized access to admin area.');
        }

        if ($user->status === UserStatus::Suspended) {
            $this->authService->forceLogoutSuspendedUser();

            return redirect()
                ->route('login')
                ->with('error', $this->authService->loginDeniedMessage(UserStatus::Suspended));
        }

        if (! $user->canAccessAdmin()) {
            abort(403, 'Your account is inactive and cannot access the admin area.');
        }

        return $next($request);
    }
}
