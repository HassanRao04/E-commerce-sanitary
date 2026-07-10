<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Services\ShippingSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class StorefrontHeader
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $currency = config('shop.currency_symbol', 'Rs.');
        $threshold = number_format(app(ShippingSettingsService::class)->freeShippingThreshold(), 0);

        return [
            'announcement' => [
                'enabled' => true,
                'text' => "Free shipping on orders over {$currency} {$threshold} · Expert support · Genuine brands",
                'link_url' => null,
                'link_label' => null,
            ],
            'social' => [
                'show_in_top_bar' => true,
                'show_in_footer' => true,
            ],
            'nav_items' => [
                [
                    'label' => 'Home',
                    'route' => 'shop.home',
                    'url' => null,
                    'active_patterns' => ['shop.home'],
                    'enabled' => true,
                    'sort_order' => 0,
                    'mega_menu' => false,
                    'open_in_new_tab' => false,
                ],
                [
                    'label' => 'Shop',
                    'route' => 'shop.products.index',
                    'url' => null,
                    'active_patterns' => ['shop.products.*', 'shop.categories.*'],
                    'enabled' => true,
                    'sort_order' => 10,
                    'mega_menu' => true,
                    'open_in_new_tab' => false,
                ],
                [
                    'label' => 'Wishlist',
                    'route' => 'shop.wishlist.index',
                    'url' => null,
                    'active_patterns' => ['shop.wishlist.*'],
                    'enabled' => true,
                    'sort_order' => 20,
                    'mega_menu' => false,
                    'open_in_new_tab' => false,
                ],
                [
                    'label' => 'Track Order',
                    'route' => 'shop.orders.track',
                    'url' => null,
                    'active_patterns' => ['shop.orders.track*'],
                    'enabled' => true,
                    'sort_order' => 30,
                    'mega_menu' => false,
                    'open_in_new_tab' => false,
                ],
                [
                    'label' => 'About',
                    'route' => 'shop.about',
                    'url' => null,
                    'active_patterns' => ['shop.about'],
                    'enabled' => true,
                    'sort_order' => 40,
                    'mega_menu' => false,
                    'open_in_new_tab' => false,
                ],
                [
                    'label' => 'Contact',
                    'route' => 'shop.contact',
                    'url' => null,
                    'active_patterns' => ['shop.contact*'],
                    'enabled' => true,
                    'sort_order' => 50,
                    'mega_menu' => false,
                    'open_in_new_tab' => false,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolved(?SiteSetting $settings = null): array
    {
        $settings ??= SiteSetting::current();
        $stored = is_array($settings->storefront_header) ? $settings->storefront_header : [];
        $defaults = self::defaults();

        $announcement = array_replace_recursive($defaults['announcement'], $stored['announcement'] ?? []);
        $social = array_replace_recursive($defaults['social'], $stored['social'] ?? []);
        $social['show_in_top_bar'] = filter_var($social['show_in_top_bar'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $social['show_in_footer'] = filter_var($social['show_in_footer'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $navItems = self::sanitizeNavItems($stored['nav_items'] ?? $defaults['nav_items'], $defaults['nav_items']);

        return [
            'announcement' => $announcement,
            'social' => $social,
            'nav_items' => $navItems,
        ];
    }

    public static function showSocialInTopBar(?SiteSetting $settings = null): bool
    {
        $settings ??= SiteSetting::current();
        $social = self::resolved($settings)['social'] ?? [];

        return ($social['show_in_top_bar'] ?? true) && SocialLinks::hasAny($settings);
    }

    public static function showSocialInFooter(?SiteSetting $settings = null): bool
    {
        $settings ??= SiteSetting::current();
        $social = self::resolved($settings)['social'] ?? [];

        return ($social['show_in_footer'] ?? true) && SocialLinks::hasAny($settings);
    }

    public static function showTopBar(?SiteSetting $settings = null): bool
    {
        $settings ??= SiteSetting::current();
        $header = self::resolved($settings);
        $announcement = $header['announcement'] ?? [];

        $announcementVisible = ($announcement['enabled'] ?? true) && filled($announcement['text'] ?? null);

        return $announcementVisible || self::showSocialInTopBar($settings);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function visibleNavItems(?SiteSetting $settings = null): array
    {
        return collect(self::resolved($settings)['nav_items'])
            ->filter(fn (array $item): bool => (bool) ($item['enabled'] ?? true))
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function sanitize(array $input): array
    {
        $defaults = self::defaults();

        $announcement = array_replace_recursive($defaults['announcement'], $input['announcement'] ?? []);
        $announcement['enabled'] = filter_var($announcement['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $announcement['text'] = trim((string) ($announcement['text'] ?? ''));
        $announcement['link_url'] = filled($announcement['link_url'] ?? null)
            ? trim((string) $announcement['link_url'])
            : null;
        $announcement['link_label'] = filled($announcement['link_label'] ?? null)
            ? trim((string) $announcement['link_label'])
            : null;

        $social = array_replace_recursive($defaults['social'], $input['social'] ?? []);
        $social['show_in_top_bar'] = filter_var($social['show_in_top_bar'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $social['show_in_footer'] = filter_var($social['show_in_footer'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'announcement' => $announcement,
            'social' => $social,
            'nav_items' => self::sanitizeNavItems($input['nav_items'] ?? [], $defaults['nav_items']),
        ];
    }

    /**
     * @param  mixed  $items
     * @param  list<array<string, mixed>>  $fallback
     * @return list<array<string, mixed>>
     */
    public static function sanitizeNavItems(mixed $items, array $fallback): array
    {
        if (! is_array($items) || $items === []) {
            return $fallback;
        }

        $sanitized = [];

        foreach (array_values($items) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $route = filled($item['route'] ?? null) ? trim((string) $item['route']) : null;
            $url = filled($item['url'] ?? null) ? trim((string) $item['url']) : null;

            if ($route !== null && ! Route::has($route)) {
                $route = null;
            }

            if ($route === null && $url === null) {
                continue;
            }

            $patterns = $item['active_patterns'] ?? [];

            if (is_string($patterns)) {
                $patterns = array_filter(array_map('trim', explode(',', $patterns)));
            }

            if (! is_array($patterns)) {
                $patterns = [];
            }

            $sanitized[] = [
                'label' => $label,
                'route' => $route,
                'url' => $url,
                'active_patterns' => array_values(array_filter(array_map('strval', $patterns))),
                'enabled' => filter_var($item['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'sort_order' => (int) ($item['sort_order'] ?? ($index * 10)),
                'mega_menu' => filter_var($item['mega_menu'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'open_in_new_tab' => filter_var($item['open_in_new_tab'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        if ($sanitized === []) {
            return $fallback;
        }

        return collect($sanitized)
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function itemUrl(array $item): string
    {
        $route = $item['route'] ?? null;

        if (filled($route) && Route::has($route)) {
            return route($route);
        }

        $url = trim((string) ($item['url'] ?? ''));

        if ($url === '') {
            return url('/');
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function itemIsActive(array $item, ?Request $request = null): bool
    {
        $request ??= request();

        foreach ($item['active_patterns'] ?? [] as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        $url = trim((string) ($item['url'] ?? ''));

        if ($url !== '' && ! str_starts_with($url, 'http')) {
            $path = ltrim($url, '/');

            if ($path !== '' && $request->is($path, $path.'/*')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function routeOptions(): array
    {
        return collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with($route->getName() ?? '', 'shop.'))
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
