<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Route;

class StorefrontFooter
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'tagline' => 'Premium sanitary ware for homes, offices, and commercial projects across Pakistan.',
            'copyright_name' => null,
            'bottom_meta' => 'Secure checkout · Cash on delivery · Fast nationwide shipping',
            'newsletter' => [
                'title' => 'Newsletter',
                'copy' => 'Get 10% off your first order. Exclusive deals, no spam.',
            ],
            'columns' => [
                'company' => [
                    'heading' => 'Company',
                    'links' => [
                        ['label' => 'About us', 'route' => 'shop.about'],
                        ['label' => 'Contact', 'route' => 'shop.contact'],
                        ['label' => 'Storefront', 'route' => 'shop.home'],
                    ],
                ],
                'shop' => [
                    'heading' => 'Shop',
                    'links' => [
                        ['label' => 'All products', 'route' => 'shop.products.index'],
                        ['label' => 'New arrivals', 'route' => 'shop.products.index', 'params' => ['collection' => 'new']],
                        ['label' => 'Best sellers', 'route' => 'shop.products.index', 'params' => ['collection' => 'best-sellers']],
                        ['label' => 'Flash sale', 'route' => 'shop.products.index', 'params' => ['collection' => 'sale']],
                        ['label' => 'Wishlist', 'route' => 'shop.wishlist.index'],
                        ['label' => 'Shopping cart', 'route' => 'shop.cart.index'],
                    ],
                ],
                'support' => [
                    'heading' => 'Support',
                    'links' => [
                        ['label' => 'Contact us', 'route' => 'shop.contact'],
                        ['label' => 'Track order', 'route' => 'shop.orders.track'],
                        ['label' => 'My account', 'route' => 'shop.account.dashboard'],
                        ['label' => 'About us', 'route' => 'shop.about'],
                        ['label' => 'Checkout', 'route' => 'shop.checkout.index'],
                    ],
                ],
                'policies' => [
                    'heading' => 'Policies',
                    'links' => [
                        ['label' => 'Shipping policy', 'route' => 'shop.contact'],
                        ['label' => 'Returns & refunds', 'route' => 'shop.contact'],
                        ['label' => 'Privacy policy', 'route' => 'shop.contact'],
                        ['label' => 'Terms of service', 'route' => 'shop.contact'],
                    ],
                ],
            ],
            'categories' => [
                'mode' => 'auto',
                'category_ids' => [],
                'limit' => 6,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function resolved(?SiteSetting $settings = null): array
    {
        $settings ??= SiteSetting::current();
        $stored = is_array($settings->storefront_footer) ? $settings->storefront_footer : [];

        return self::sanitize(array_replace_recursive(self::defaults(), $stored), $settings);
    }

    /** @param  array<string, mixed>  $input */
    public static function sanitize(array $input, ?SiteSetting $settings = null): array
    {
        $defaults = self::defaults();
        $footer = array_replace_recursive($defaults, $input);

        $footer['tagline'] = trim((string) ($footer['tagline'] ?? ''));
        $footer['copyright_name'] = filled($footer['copyright_name'] ?? null)
            ? trim((string) $footer['copyright_name'])
            : null;
        $footer['bottom_meta'] = trim((string) ($footer['bottom_meta'] ?? ''));
        $footer['newsletter']['title'] = trim((string) ($footer['newsletter']['title'] ?? 'Newsletter'));
        $footer['newsletter']['copy'] = trim((string) ($footer['newsletter']['copy'] ?? ''));

        foreach (array_keys($defaults['columns']) as $columnKey) {
            $footer['columns'][$columnKey]['heading'] = trim((string) ($footer['columns'][$columnKey]['heading'] ?? ''));
            $footer['columns'][$columnKey]['links'] = self::sanitizeLinks($footer['columns'][$columnKey]['links'] ?? []);
        }

        $footer['categories']['mode'] = ($footer['categories']['mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';
        $footer['categories']['category_ids'] = HomepageSections::normalizeProductIds($footer['categories']['category_ids'] ?? []);
        $footer['categories']['limit'] = max(1, min(12, (int) ($footer['categories']['limit'] ?? 6)));

        if ($settings && blank($footer['copyright_name'])) {
            $footer['copyright_name'] = $settings->displayName();
        }

        return $footer;
    }

    /**
     * @param  mixed  $links
     * @return list<array<string, mixed>>
     */
    private static function sanitizeLinks(mixed $links): array
    {
        if (! is_array($links)) {
            return [];
        }

        $sanitized = [];

        foreach ($links as $link) {
            if (! is_array($link)) {
                continue;
            }

            $label = trim((string) ($link['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $entry = ['label' => $label];

            if (filled($link['route'] ?? null)) {
                $entry['route'] = trim((string) $link['route']);
            }

            if (filled($link['url'] ?? null)) {
                $entry['url'] = trim((string) $link['url']);
            }

            if (isset($link['params']) && is_array($link['params'])) {
                $entry['params'] = $link['params'];
            }

            $sanitized[] = $entry;
        }

        return $sanitized;
    }

    /** @param  array<string, mixed>  $link */
    public static function linkUrl(array $link): string
    {
        if (filled($link['url'] ?? null)) {
            return (string) $link['url'];
        }

        if (filled($link['route'] ?? null) && Route::has($link['route'])) {
            return route($link['route'], $link['params'] ?? []);
        }

        return '#';
    }
}
