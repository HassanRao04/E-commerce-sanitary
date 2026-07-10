<?php

namespace App\Support;

use App\Models\SiteSetting;

class HomepageSections
{
    public const FEATURED = 'featured';

    public const BEST_SELLERS = 'best_sellers';

    public const NEW_ARRIVALS = 'new_arrivals';

    public const TRENDING = 'trending';

    public const FLASH_SALE = 'flash_sale';

    public const CATEGORIES = 'categories';

    public const BRANDS = 'brands';

    public const TESTIMONIALS = 'testimonials';

    public const TRUST = 'trust';

    public const NEWSLETTER = 'newsletter';

    public const CTA = 'cta';

    /** @return list<string> */
    public static function orderedKeys(): array
    {
        return [
            self::FEATURED,
            self::BEST_SELLERS,
            self::NEW_ARRIVALS,
            self::TRENDING,
            self::FLASH_SALE,
            self::CATEGORIES,
            self::BRANDS,
            self::TESTIMONIALS,
            self::TRUST,
            self::NEWSLETTER,
            self::CTA,
        ];
    }

    /** @return list<string> */
    public static function carouselKeys(): array
    {
        return [
            self::FEATURED,
            self::BEST_SELLERS,
            self::NEW_ARRIVALS,
            self::TRENDING,
            self::FLASH_SALE,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            self::FEATURED => [
                'type' => 'carousel',
                'enabled' => true,
                'title' => 'Featured products',
                'subtitle' => 'Hand-picked premium fixtures for modern bathrooms and kitchens.',
                'badge' => "Editor's choice",
                'badge_class' => 'ds-badge-accent',
                'theme' => 'default',
                'view_all_label' => 'View all',
                'collection' => 'featured',
                'mode' => 'auto',
                'product_ids' => [],
                'limit' => 12,
            ],
            self::BEST_SELLERS => [
                'type' => 'carousel',
                'enabled' => true,
                'title' => 'Best selling products',
                'subtitle' => 'Top-rated picks loved by customers across Pakistan.',
                'badge' => 'Best sellers',
                'badge_class' => 'ds-badge-neutral',
                'theme' => 'muted',
                'view_all_label' => 'View all',
                'collection' => 'best-sellers',
                'mode' => 'auto',
                'product_ids' => [],
                'limit' => 12,
            ],
            self::NEW_ARRIVALS => [
                'type' => 'carousel',
                'enabled' => true,
                'title' => 'New arrivals',
                'subtitle' => 'Fresh styles and latest releases just landed.',
                'badge' => 'New in',
                'badge_class' => 'ds-badge-new',
                'theme' => 'default',
                'view_all_label' => 'View all',
                'collection' => 'new',
                'mode' => 'auto',
                'product_ids' => [],
                'limit' => 12,
            ],
            self::TRENDING => [
                'type' => 'carousel',
                'enabled' => true,
                'title' => 'Trending products',
                'subtitle' => "What's popular right now — based on recent sales and demand.",
                'badge' => 'Trending',
                'badge_class' => 'ds-badge-neutral !bg-ink !text-white border-0',
                'theme' => 'muted',
                'view_all_label' => 'View all',
                'collection' => 'trending',
                'mode' => 'auto',
                'product_ids' => [],
                'limit' => 12,
            ],
            self::FLASH_SALE => [
                'type' => 'carousel',
                'enabled' => true,
                'title' => 'Flash sale',
                'subtitle' => "Limited-time deals on selected sanitary ware — grab them before they're gone.",
                'badge' => 'Hot deals',
                'badge_class' => 'ds-badge-sale',
                'theme' => 'sale',
                'view_all_label' => 'Shop all deals',
                'collection' => 'sale',
                'mode' => 'auto',
                'product_ids' => [],
                'limit' => 12,
            ],
            self::CATEGORIES => [
                'type' => 'categories',
                'enabled' => true,
                'title' => 'Shop by category',
                'eyebrow' => 'Browse',
                'mode' => 'auto',
                'category_ids' => [],
                'limit' => 6,
            ],
            self::BRANDS => [
                'type' => 'brands',
                'enabled' => true,
                'title' => 'Trusted brands',
                'eyebrow' => 'Partners',
                'limit' => 8,
            ],
            self::TESTIMONIALS => [
                'type' => 'testimonials',
                'enabled' => true,
                'badge' => 'Testimonials',
                'title' => 'Loved by thousands of customers',
                'subtitle' => 'Real stories from homeowners, contractors, and designers who trust us for premium sanitary ware.',
                'limit' => 6,
            ],
            self::TRUST => [
                'type' => 'trust',
                'enabled' => true,
                'why_choose' => [
                    'badge' => 'Why choose us',
                    'title' => 'Built for quality you can trust',
                    'subtitle' => 'Everything we do is designed to make buying premium sanitary ware simple, safe, and satisfying.',
                    'items' => [
                        ['icon' => 'sparkles', 'title' => 'Premium quality', 'text' => 'Curated sanitary ware from trusted international and local brands.'],
                        ['icon' => 'headset', 'title' => 'Expert support', 'text' => 'Our specialists help you choose the right fixtures for every project.'],
                        ['icon' => 'badge-check', 'title' => 'Authorized dealer', 'text' => 'Genuine products with manufacturer-backed warranties.'],
                        ['icon' => 'shield-check', 'title' => 'Hassle-free returns', 'text' => 'Simple return process if something is not right with your order.'],
                    ],
                ],
                'shipping' => [
                    'badge' => 'Shipping',
                    'title' => 'Delivery benefits that matter',
                    'subtitle' => 'From free shipping thresholds to careful packaging — your order is in good hands.',
                    'items' => [
                        ['icon' => 'truck', 'title' => 'Free shipping', 'text' => 'Affordable delivery rates on every order nationwide.'],
                        ['icon' => 'clock', 'title' => 'Fast delivery', 'text' => 'Dispatch within 24–48 hours on in-stock items across major cities.'],
                        ['icon' => 'box', 'title' => 'Secure packaging', 'text' => 'Every item is carefully packed to arrive in perfect condition.'],
                        ['icon' => 'map-pin', 'title' => 'Live tracking', 'text' => 'Track your order from warehouse to doorstep with SMS updates.'],
                    ],
                ],
                'security' => [
                    'badge' => 'Security',
                    'title' => 'Shop with confidence',
                    'subtitle' => 'Your payment details and personal data are protected at every step.',
                    'items' => [
                        ['icon' => 'lock-closed', 'title' => 'SSL encrypted', 'text' => '256-bit encryption protects your data at checkout.'],
                        ['icon' => 'shield-check', 'title' => 'Secure checkout', 'text' => 'PCI-compliant payment processing you can trust.'],
                        ['icon' => 'user-group', 'title' => 'Privacy first', 'text' => 'We never sell your personal information to third parties.'],
                    ],
                ],
                'payments' => [
                    'badge' => 'Payments',
                    'title' => 'Flexible payment options',
                    'subtitle' => 'Pay the way that suits you — online, mobile wallet, bank transfer, or cash on delivery.',
                    'methods' => [
                        ['label' => 'Cash on Delivery', 'short' => 'COD', 'color' => 'trust-pay--cod'],
                        ['label' => 'JazzCash', 'short' => 'JC', 'color' => 'trust-pay--jazzcash'],
                        ['label' => 'Easypaisa', 'short' => 'EP', 'color' => 'trust-pay--easypaisa'],
                        ['label' => 'Bank Transfer', 'short' => 'BT', 'color' => 'trust-pay--bank'],
                        ['label' => 'Card / Stripe', 'short' => 'Card', 'color' => 'trust-pay--card'],
                    ],
                ],
            ],
            self::NEWSLETTER => [
                'type' => 'newsletter',
                'enabled' => true,
                'title' => 'Unlock exclusive offers',
                'subtitle' => 'Join our newsletter for early access to sales, new arrivals, and expert bathroom inspiration.',
                'offer' => '10% off your first order',
                'offer_code' => 'WELCOME10',
                'theme' => 'dark',
            ],
            self::CTA => [
                'type' => 'cta',
                'enabled' => true,
                'title' => 'Need help choosing?',
                'subtitle' => 'Our team can recommend the right products for your project.',
                'button_text' => 'Contact us',
                'button_url' => null,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function resolved(?SiteSetting $settings = null): array
    {
        $settings ??= SiteSetting::current();
        $stored = is_array($settings->homepage_sections) ? $settings->homepage_sections : [];
        $resolved = [];

        foreach (self::defaults() as $key => $defaults) {
            $resolved[$key] = array_replace_recursive($defaults, $stored[$key] ?? []);
            $resolved[$key]['product_ids'] = self::normalizeProductIds($resolved[$key]['product_ids'] ?? []);
            $resolved[$key]['category_ids'] = self::normalizeProductIds($resolved[$key]['category_ids'] ?? []);
            $resolved[$key]['limit'] = max(1, min(24, (int) ($resolved[$key]['limit'] ?? 12)));
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<string, mixed>>
     */
    public static function sanitize(array $input): array
    {
        $defaults = self::defaults();
        $sanitized = [];

        foreach (self::orderedKeys() as $key) {
            $sectionInput = is_array($input[$key] ?? null) ? $input[$key] : [];
            $section = array_replace_recursive($defaults[$key], $sectionInput);
            $section['enabled'] = filter_var($section['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $section['type'] = $defaults[$key]['type'];

            if ($section['type'] === 'carousel') {
                $section['title'] = trim((string) ($section['title'] ?? ''));
                $section['subtitle'] = trim((string) ($section['subtitle'] ?? ''));
                $section['badge'] = trim((string) ($section['badge'] ?? ''));
                $section['badge_class'] = trim((string) ($section['badge_class'] ?? 'ds-badge-accent'));
                $section['theme'] = in_array($section['theme'] ?? 'default', ['default', 'muted', 'sale'], true)
                    ? $section['theme']
                    : 'default';
                $section['view_all_label'] = trim((string) ($section['view_all_label'] ?? 'View all'));
                $section['collection'] = trim((string) ($section['collection'] ?? $key));
                $section['mode'] = ($section['mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';
                $section['product_ids'] = self::normalizeProductIds($section['product_ids'] ?? []);
                $section['limit'] = max(1, min(24, (int) ($section['limit'] ?? 12)));
            }

            if ($section['type'] === 'categories' || $section['type'] === 'brands') {
                $section['title'] = trim((string) ($section['title'] ?? ''));
                $section['eyebrow'] = trim((string) ($section['eyebrow'] ?? ''));
                $section['limit'] = max(1, min(24, (int) ($section['limit'] ?? 6)));

                if ($section['type'] === 'categories') {
                    $section['mode'] = ($section['mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';
                    $section['category_ids'] = self::normalizeProductIds($section['category_ids'] ?? []);
                }
            }

            if ($section['type'] === 'testimonials') {
                $section['badge'] = trim((string) ($section['badge'] ?? ''));
                $section['title'] = trim((string) ($section['title'] ?? ''));
                $section['subtitle'] = trim((string) ($section['subtitle'] ?? ''));
                $section['limit'] = max(1, min(12, (int) ($section['limit'] ?? 6)));
            }

            if ($section['type'] === 'newsletter') {
                $section['title'] = trim((string) ($section['title'] ?? ''));
                $section['subtitle'] = trim((string) ($section['subtitle'] ?? ''));
                $section['offer'] = trim((string) ($section['offer'] ?? ''));
                $section['offer_code'] = trim((string) ($section['offer_code'] ?? ''));
                $section['theme'] = ($section['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
            }

            if ($section['type'] === 'cta') {
                $section['title'] = trim((string) ($section['title'] ?? ''));
                $section['subtitle'] = trim((string) ($section['subtitle'] ?? ''));
                $section['button_text'] = trim((string) ($section['button_text'] ?? 'Contact us'));
                $section['button_url'] = filled($section['button_url'] ?? null)
                    ? trim((string) $section['button_url'])
                    : null;
            }

            if ($section['type'] === 'trust') {
                foreach (['why_choose', 'shipping', 'security', 'payments'] as $blockKey) {
                    if (! isset($section[$blockKey]) || ! is_array($section[$blockKey])) {
                        $section[$blockKey] = $defaults[$key][$blockKey] ?? [];

                        continue;
                    }

                    $section[$blockKey]['badge'] = trim((string) ($section[$blockKey]['badge'] ?? ''));
                    $section[$blockKey]['title'] = trim((string) ($section[$blockKey]['title'] ?? ''));
                    $section[$blockKey]['subtitle'] = trim((string) ($section[$blockKey]['subtitle'] ?? ''));

                    if ($blockKey === 'payments') {
                        $methods = self::sanitizePaymentMethods($section[$blockKey]['methods'] ?? []);
                        $section[$blockKey]['methods'] = $methods !== []
                            ? $methods
                            : ($defaults[$key][$blockKey]['methods'] ?? []);
                    } else {
                        $items = self::sanitizeTrustItems($section[$blockKey]['items'] ?? []);
                        $section[$blockKey]['items'] = $items !== []
                            ? $items
                            : ($defaults[$key][$blockKey]['items'] ?? []);
                    }
                }
            }

            $sanitized[$key] = $section;
        }

        return $sanitized;
    }

    /** @param  mixed  $items
     * @return list<array{icon: string, title: string, text: string}>
     */
    public static function sanitizeTrustItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $sanitized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $sanitized[] = [
                'icon' => trim((string) ($item['icon'] ?? 'sparkles')),
                'title' => $title,
                'text' => trim((string) ($item['text'] ?? '')),
            ];
        }

        return $sanitized;
    }

    /** @param  mixed  $methods
     * @return list<array{label: string, short: string, color: string}>
     */
    public static function sanitizePaymentMethods(mixed $methods): array
    {
        if (! is_array($methods)) {
            return [];
        }

        $sanitized = [];

        foreach ($methods as $method) {
            if (! is_array($method)) {
                continue;
            }

            $label = trim((string) ($method['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $sanitized[] = [
                'label' => $label,
                'short' => trim((string) ($method['short'] ?? strtoupper(substr($label, 0, 2)))),
                'color' => trim((string) ($method['color'] ?? 'trust-pay--cod')),
            ];
        }

        return $sanitized;
    }

    /**
     * @param  mixed  $value
     * @return list<int>
     */
    public static function normalizeCategoryIds(mixed $value): array
    {
        return self::normalizeProductIds($value);
    }

    /**
     * @param  mixed  $value
     * @return list<int>
     */
    public static function normalizeProductIds(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public static function label(string $key): string
    {
        return match ($key) {
            self::FEATURED => 'Featured products',
            self::BEST_SELLERS => 'Best sellers',
            self::NEW_ARRIVALS => 'New arrivals',
            self::TRENDING => 'Trending products',
            self::FLASH_SALE => 'Flash sale',
            self::CATEGORIES => 'Shop by category',
            self::BRANDS => 'Trusted brands',
            self::TESTIMONIALS => 'Testimonials',
            self::TRUST => 'Trust & security blocks',
            self::NEWSLETTER => 'Newsletter',
            self::CTA => 'Help CTA banner',
            default => str($key)->headline()->toString(),
        };
    }
}
