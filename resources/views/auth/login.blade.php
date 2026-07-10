<x-guest-layout>
    @php
        $returnUrl = request('redirect') ?? session('url.intended');
        $registerParams = filled($returnUrl) ? ['redirect' => $returnUrl] : [];
        $fromCheckout = filled($returnUrl) && str_contains((string) $returnUrl, 'checkout');
    @endphp

    @if ($fromCheckout)
        <div class="mb-5 rounded-ds border border-accent/20 bg-accent/5 px-4 py-3">
            <p class="text-sm font-semibold text-ink">Sign in to complete checkout</p>
            <p class="text-sm text-ink-600 mt-1">Your cart is saved. Sign in or create an account to continue.</p>
        </div>
    @endif

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        @if (filled($returnUrl))
            <input type="hidden" name="redirect" value="{{ $returnUrl }}">
        @endif

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="ds-checkbox" name="remember">
                <span class="ms-2 ds-body-sm">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="ds-link ds-body-sm" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="mt-6 pt-6 border-t border-ink-100 text-center">
            <p class="ds-body-sm text-ink-600">Don't have an account?</p>
            <a href="{{ route('register', $registerParams) }}" class="ds-btn-secondary w-full mt-3 inline-flex justify-center">
                Create account
            </a>
        </div>
    @endif
</x-guest-layout>
