<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Services\Auth\UserAuthenticationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotSuspended
{
    public function __construct(private readonly UserAuthenticationService $authService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status === UserStatus::Suspended) {
            $this->authService->forceLogoutSuspendedUser();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $this->authService->loginDeniedMessage(UserStatus::Suspended),
                ], 403);
            }

            return redirect()
                ->route('login')
                ->with('error', $this->authService->loginDeniedMessage(UserStatus::Suspended));
        }

        return $next($request);
    }
}
