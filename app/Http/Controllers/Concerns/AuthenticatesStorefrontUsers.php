<?php

namespace App\Http\Controllers\Concerns;

use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait AuthenticatesStorefrontUsers
{
    protected function redirectAfterAuthentication(Request $request): RedirectResponse
    {
        $this->mergeGuestSessionData();

        if ($request->filled('redirect')) {
            $url = $request->input('redirect');

            if (is_string($url) && str_starts_with($url, url('/'))) {
                return redirect($url);
            }
        }

        $user = Auth::user();

        if ($user?->canAccessAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user?->isInfluencer()) {
            return redirect()->intended(route('influencer.dashboard'));
        }

        return redirect()->intended(route('shop.account.dashboard'));
    }

    protected function mergeGuestSessionData(): void
    {
        if (! Auth::check()) {
            return;
        }

        $userId = Auth::id();

        app(CartService::class)->mergeSessionCartIntoUser($userId);
        app(WishlistService::class)->mergeGuestWishlist($userId);
    }
}
