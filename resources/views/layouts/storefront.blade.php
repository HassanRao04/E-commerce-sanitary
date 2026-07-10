<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Shop premium sanitary ware, basins, mixers, toilets and bathroom accessories at '.config('app.name').'.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', 'Shop premium sanitary ware at '.config('app.name').'.')">
    <meta property="og:url" content="{{ url()->current() }}">
    @stack('meta')
    <meta name="theme-color" content="#0B0B0F">
    @php($siteSettings = \App\Models\SiteSetting::current())
    @if ($siteSettings->favicon_url)
        <link rel="icon" href="{{ $siteSettings->favicon_url }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ds-root font-sans flex flex-col min-h-screen overflow-x-clip">
    <x-storefront.header
        :cart-item-count="$cartItemCount"
        :wishlist-item-count="$wishlistItemCount"
        :header-cart-preview="$headerCartPreview"
        :header-nav-categories="$headerNavCategories"
        :header-nav-brands="$headerNavBrands"
    />

    @if (session('success'))
        <div class="storefront-flash storefront-flash--success" role="status">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="storefront-flash storefront-flash--error" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <main class="flex-1 min-w-0">
        @yield('content')
    </main>

    @include('storefront.partials.footer')

    <x-storefront.quick-view-modal />
</body>
</html>
