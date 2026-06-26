<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="ds-root font-sans">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-surface-subtle">
            <div>
                <a href="{{ route('shop.home') }}" class="ds-heading-3 text-ink hover:text-ink-700 transition-colors">
                    {{ config('app.name', 'Sanitary Store') }}
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 ds-card-elevated overflow-hidden sm:rounded-ds-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
