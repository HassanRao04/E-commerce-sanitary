<?php

namespace App\Services\Storefront;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Services\Admin\HomepageContentService;
use App\Services\ReviewSettingsService;
use App\Support\HomepageHeroSlides;
use App\Support\HomepageSections;
use App\Support\StorefrontContact;
use App\Support\StorefrontFooter;
use Illuminate\Support\Collection;

class StorefrontContentService
{
    public function __construct(
        private readonly HomeCatalogService $catalog,
        private readonly ReviewSettingsService $reviewSettings,
    ) {}

    /** @return array<string, mixed> */
    public function homepage(): array
    {
        $settings = SiteSetting::current();
        $sections = HomepageSections::resolved($settings);

        return [
            'sections' => $sections,
            'carouselProducts' => $this->carouselProducts($sections),
            'categories' => $this->homepageCategories($sections[HomepageSections::CATEGORIES] ?? []),
            'brands' => $this->homepageBrands($sections[HomepageSections::BRANDS] ?? []),
            'featuredReviews' => $this->featuredReviews($sections[HomepageSections::TESTIMONIALS] ?? []),
            'trust' => $this->trustSection($sections[HomepageSections::TRUST] ?? []),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function heroSlides(): array
    {
        $banners = Banner::query()
            ->homeHero()
            ->active()
            ->ordered()
            ->get();

        if ($banners->isNotEmpty()) {
            return $banners
                ->map(fn (Banner $banner): array => $banner->heroSlideData())
                ->values()
                ->all();
        }

        return HomepageHeroSlides::defaults();
    }

    /** @return array<string, mixed> */
    public function footer(): array
    {
        return StorefrontFooter::resolved();
    }

    /** @return array<string, mixed> */
    public function contact(): array
    {
        return StorefrontContact::resolved();
    }

    /** @return \Illuminate\Support\Collection<int, Category> */
    public function footerCategories(): Collection
    {
        $footer = $this->footer();
        $config = $footer['categories'] ?? [];

        return $this->resolveCategories($config);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function trustSection(array $config): array
    {
        if (! ($config['enabled'] ?? true)) {
            return ['enabled' => false];
        }

        return $config;
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     * @return array<string, Collection<int, \App\Models\Product>>
     */
    private function carouselProducts(array $sections): array
    {
        $products = [];

        foreach (HomepageSections::carouselKeys() as $key) {
            $config = $sections[$key] ?? [];

            if (! ($config['enabled'] ?? true)) {
                continue;
            }

            $products[$key] = $this->catalog->forSection($key, $config);
        }

        return $products;
    }

    /** @param  array<string, mixed>  $config */
    private function homepageCategories(array $config): Collection
    {
        if (! ($config['enabled'] ?? true)) {
            return collect();
        }

        return $this->resolveCategories([
            'mode' => $config['mode'] ?? 'auto',
            'category_ids' => $config['category_ids'] ?? [],
            'limit' => $config['limit'] ?? 6,
        ]);
    }

    /** @param  array<string, mixed>  $config */
    private function homepageBrands(array $config): Collection
    {
        if (! ($config['enabled'] ?? true)) {
            return collect();
        }

        return Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->take((int) ($config['limit'] ?? 8))
            ->get();
    }

    /** @param  array<string, mixed>  $config */
    private function featuredReviews(array $config): Collection
    {
        if (! ($config['enabled'] ?? true) || ! $this->reviewSettings->showOnHomepage()) {
            return collect();
        }

        $limit = min(
            max(1, (int) ($config['limit'] ?? 6)),
            $this->reviewSettings->maxFeatured(),
        );

        $baseQuery = Review::query()
            ->approved()
            ->recent()
            ->whereNotNull('body')
            ->with(['user:id,name', 'product:id,name', 'images']);

        if ($this->reviewSettings->homepageMode() === 'featured') {
            $featured = (clone $baseQuery)->featured()->take($limit)->get();

            if ($featured->isNotEmpty()) {
                return $featured;
            }
        }

        return $baseQuery->take($limit)->get();
    }

    /** @param  array<string, mixed>  $config */
    private function resolveCategories(array $config): Collection
    {
        $limit = max(1, (int) ($config['limit'] ?? 6));
        $mode = ($config['mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';
        $ids = HomepageSections::normalizeProductIds($config['category_ids'] ?? []);

        if ($mode === 'manual' && $ids !== []) {
            $categories = Category::query()
                ->active()
                ->whereIn('id', $ids)
                ->withCount('products')
                ->get()
                ->keyBy('id');

            return collect($ids)
                ->map(fn (int $id) => $categories->get($id))
                ->filter()
                ->take($limit)
                ->values();
        }

        return Category::query()
            ->active()
            ->roots()
            ->ordered()
            ->withCount('products')
            ->take($limit)
            ->get();
    }
}
