<?php

namespace App\Support;

use App\Models\Banner;

class HomepageHeroSlides
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function forStorefront(): array
    {
        $slides = self::defaults();
        $banners = Banner::query()
            ->homeHero()
            ->active()
            ->ordered()
            ->get();

        foreach ($banners as $banner) {
            $index = max(0, (int) $banner->sort_order);

            if ($index >= count($slides)) {
                $slides[] = $banner->heroSlideData();

                continue;
            }

            $slides[$index] = self::applyBannerToSlide($slides[$index], $banner);
        }

        return array_values($slides);
    }

    /**
     * Overlay uploaded hero content onto a default slide without removing carousel copy.
     *
     * @param  array<string, mixed>  $slide
     * @return array<string, mixed>
     */
    public static function applyBannerToSlide(array $slide, Banner $banner): array
    {
        $meta = $banner->metadata ?? [];

        if (filled($banner->image)) {
            $slide['image_url'] = $banner->image_url;
        }

        if (filled($banner->title)) {
            $slide['title'] = $banner->title;
        }

        if (filled($banner->subtitle)) {
            $slide['subtitle'] = $banner->subtitle;
        }

        foreach (['eyebrow', 'badge', 'badge_class', 'promo', 'promo_detail'] as $field) {
            if (filled($meta[$field] ?? null)) {
                $slide[$field] = $meta[$field];
            }
        }

        if (filled($banner->button_text)) {
            $slide['cta_primary']['label'] = $banner->button_text;
        }

        if (filled($banner->button_url)) {
            $slide['cta_primary']['url'] = $banner->button_url;
        }

        if (filled($meta['secondary_button_text'] ?? null)) {
            $slide['cta_secondary']['label'] = $meta['secondary_button_text'];
        }

        if (filled($meta['secondary_button_url'] ?? null)) {
            $slide['cta_secondary']['url'] = $meta['secondary_button_url'];
        }

        if (filled($meta['background'] ?? null) && ! filled($banner->image)) {
            $slide['bg'] = $meta['background'];
        }

        if (filled($meta['orb_a'] ?? null)) {
            $slide['orb_a'] = $meta['orb_a'];
        }

        if (filled($meta['orb_b'] ?? null)) {
            $slide['orb_b'] = $meta['orb_b'];
        }

        $slide['key'] = 'banner-'.$banner->id;

        return $slide;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            [
                'key' => 'new-collection',
                'eyebrow' => 'Just dropped',
                'title' => 'New Collection',
                'subtitle' => 'Discover the latest basins, mixers, and bathroom essentials curated for modern spaces.',
                'badge' => 'New arrivals',
                'badge_class' => 'ds-badge-new !bg-white/15 !text-white border border-white/20',
                'promo' => 'Up to 15% off launch styles',
                'promo_detail' => 'Limited time on select new products',
                'cta_primary' => ['label' => 'Shop new in', 'url' => route('shop.products.index', ['collection' => 'new'])],
                'cta_secondary' => ['label' => 'Explore lookbook', 'url' => route('shop.about')],
                'image_url' => null,
                'bg' => 'linear-gradient(135deg, #0b0b0f 0%, #1c1c1e 42%, #003566 100%)',
                'orb_a' => 'bg-accent/40 w-72 h-72 -top-16 -right-10',
                'orb_b' => 'bg-white/10 w-56 h-56 bottom-0 left-1/4',
            ],
            [
                'key' => 'best-sellers',
                'eyebrow' => 'Customer favorites',
                'title' => 'Best Sellers',
                'subtitle' => 'Top-rated fixtures trusted by homeowners, designers, and contractors across Pakistan.',
                'badge' => 'Best sellers',
                'badge_class' => 'ds-badge-neutral !bg-white/15 !text-white border border-white/20',
                'promo' => '4.8★ average rating',
                'promo_detail' => 'Proven quality. Fast delivery.',
                'cta_primary' => ['label' => 'View best sellers', 'url' => route('shop.products.index', ['collection' => 'best-sellers'])],
                'cta_secondary' => ['label' => 'Browse all shop', 'url' => route('shop.products.index')],
                'image_url' => null,
                'bg' => 'linear-gradient(135deg, #050507 0%, #111111 45%, #2c2c2e 100%)',
                'orb_a' => 'bg-amber-400/20 w-80 h-80 -top-20 right-1/4',
                'orb_b' => 'bg-white/10 w-64 h-64 -bottom-10 -left-8',
            ],
            [
                'key' => 'flash-sale',
                'eyebrow' => 'Limited time',
                'title' => 'Flash Sale',
                'subtitle' => 'Save big on premium sanitary ware — while stocks last. Ends soon.',
                'badge' => 'Hot deal',
                'badge_class' => 'ds-badge-sale !text-white border border-white/20',
                'promo' => 'Extra 20% off sale items',
                'promo_detail' => 'Auto-applied at checkout on eligible products',
                'cta_primary' => ['label' => 'Shop flash sale', 'url' => route('shop.products.index', ['collection' => 'sale'])],
                'cta_secondary' => ['label' => 'View cart', 'url' => route('shop.cart.index')],
                'image_url' => null,
                'bg' => 'linear-gradient(135deg, #3b0a0a 0%, #7f1d1d 38%, #0b0b0f 100%)',
                'orb_a' => 'bg-commerce-sale/35 w-96 h-96 -top-24 -right-16',
                'orb_b' => 'bg-orange-400/15 w-52 h-52 bottom-8 left-8',
            ],
            [
                'key' => 'seasonal-collection',
                'eyebrow' => 'Summer refresh',
                'title' => 'Seasonal Collection',
                'subtitle' => 'Elevate bathrooms and kitchens with seasonal picks — elegant, durable, project-ready.',
                'badge' => 'Seasonal edit',
                'badge_class' => 'ds-badge-accent !bg-white/15 !text-white border border-white/20',
                'promo' => 'Free shipping over '.config('shop.currency_symbol').' 10,000',
                'promo_detail' => 'Perfect for renovations & new builds',
                'cta_primary' => ['label' => 'Shop seasonal', 'url' => route('shop.products.index', ['collection' => 'seasonal'])],
                'cta_secondary' => ['label' => 'Get expert advice', 'url' => route('shop.contact')],
                'image_url' => null,
                'bg' => 'linear-gradient(135deg, #0f172a 0%, #134e4a 42%, #0b0b0f 100%)',
                'orb_a' => 'bg-teal-400/25 w-72 h-72 top-10 -left-12',
                'orb_b' => 'bg-sky-400/20 w-64 h-64 -bottom-12 right-10',
            ],
        ];
    }
}
