<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="adminShell('{{ config('admin.sidebar.storage_key') }}')"
    :class="shellClasses()"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <script>
        (function () {
            var key = @json(config('admin.sidebar.storage_key'));
            if (localStorage.getItem(key) === 'true' && window.matchMedia('(min-width: 1024px)').matches) {
                document.documentElement.classList.add('admin-sidebar-collapsed');
            }
        })();
    </script>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    <style>
        :root {
            --admin-sidebar-width: {{ config('admin.sidebar.width') }};
            --admin-sidebar-transition: {{ config('admin.sidebar.transition_ms') }}ms ease;
        }
    </style>
    @stack('head')
</head>
<body class="font-sans antialiased bg-slate-100 text-gray-900">
    {{-- Mobile overlay --}}
    <div
        x-show="mobileOpen && !isDesktop"
        x-transition.opacity.duration.250ms
        class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden"
        @click="closeMobileSidebar()"
    ></div>

    {{-- Sidebar (fixed; off-screen when collapsed — no layout space reserved) --}}
    <aside
        class="admin-sidebar fixed inset-y-0 left-0 z-50 flex flex-col bg-slate-900 text-white"
        @keydown.escape.window="closeMobileSidebar()"
        :aria-hidden="(isDesktop && sidebarCollapsed) || (!isDesktop && !mobileOpen) ? 'true' : 'false'"
    >
        <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-4">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold">SS</div>
            <div class="min-w-0">
                <a href="{{ route('admin.dashboard') }}" class="block truncate text-sm font-semibold tracking-tight">{{ config('app.name') }}</a>
                <p class="truncate text-xs text-slate-400">ERP Admin</p>
            </div>
        </div>

        @include('admin.partials.sidebar')

        <div class="border-t border-slate-800 p-3 text-xs text-slate-500">
            <p class="truncate">{{ auth()->user()->name }}</p>
            <p class="truncate text-slate-400">
                {{ \App\Enums\StaffRole::tryFromName(auth()->user()->getRoleNames()->first())?->label() ?? 'No role' }}
            </p>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="admin-main-shell flex min-h-screen flex-col">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur">
            <div class="flex items-center justify-between gap-3 px-4 py-3 lg:px-5">
                <div class="flex min-w-0 items-center gap-2">
                    <button
                        type="button"
                        class="rounded-md p-2 text-gray-600 hover:bg-gray-100"
                        @click="toggleSidebar()"
                        :aria-expanded="isDesktop ? !sidebarCollapsed : mobileOpen"
                        aria-label="Toggle navigation menu"
                    >
                        <svg x-show="!(isDesktop && sidebarCollapsed)" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="isDesktop && sidebarCollapsed" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"/>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        @hasSection('breadcrumb')
                            @yield('breadcrumb')
                        @else
                            <p class="text-xs text-gray-500">ERP</p>
                        @endif
                        <h1 class="truncate text-base font-semibold text-gray-900 lg:text-lg">@yield('title', 'Dashboard')</h1>
                    </div>
                </div>

                <div class="relative" @click.outside="profileOpen = false">
                    <button type="button" class="flex items-center gap-2 rounded-lg border border-gray-200 px-2.5 py-1.5 text-sm hover:bg-gray-50" @click="profileOpen = !profileOpen">
                        <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">{{ auth()->user()->initials }}</span>
                    </button>
                    <div x-show="profileOpen" x-transition class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-5">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>

    <style>[x-cloak]{display:none!important}</style>
    @stack('scripts')
</body>
</html>
