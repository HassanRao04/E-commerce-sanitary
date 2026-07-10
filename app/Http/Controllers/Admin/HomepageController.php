<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHeroBannerRequest;
use App\Http\Requests\Admin\UpdateHeroBannerRequest;
use App\Http\Requests\Admin\UpdateHomepageBrandingRequest;
use App\Http\Requests\Admin\UpdateHomepageSectionsRequest;
use App\Http\Requests\Admin\UpdateSocialLinksRequest;
use App\Http\Requests\Admin\UpdateStorefrontContactRequest;
use App\Http\Requests\Admin\UpdateStorefrontFooterRequest;
use App\Http\Requests\Admin\UpdateStorefrontHeaderRequest;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\Admin\HomepageContentService;
use App\Support\HomepageSections;
use App\Support\StorefrontContact;
use App\Support\StorefrontFooter;
use App\Support\StorefrontHeader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function __construct(private readonly HomepageContentService $homepage) {}

    public function index(): View
    {
        $this->authorize('viewAny', Banner::class);

        $sections = HomepageSections::resolved();

        return view('admin.homepage.index', [
            'settings' => SiteSetting::current(),
            'heroBanners' => $this->homepage->heroBanners(),
            'sections' => $sections,
            'sectionKeys' => HomepageSections::orderedKeys(),
            'sectionProducts' => $this->sectionProducts($sections),
            'sectionCategories' => $this->sectionCategories($sections),
            'storefrontHeader' => StorefrontHeader::resolved(),
            'storefrontFooter' => StorefrontFooter::resolved(),
            'storefrontContact' => StorefrontContact::resolved(),
            'routeOptions' => StorefrontHeader::routeOptions(),
            'categoryOptions' => Category::query()->active()->roots()->ordered()->get(['id', 'name']),
        ]);
    }

    public function updateHeader(UpdateStorefrontHeaderRequest $request): RedirectResponse
    {
        $this->homepage->updateHeader(
            SiteSetting::current(),
            $request->validated('header'),
        );

        return back()->with('success', 'Header and announcement bar updated.');
    }

    public function updateSocial(UpdateSocialLinksRequest $request): RedirectResponse
    {
        $this->homepage->updateSocial(
            SiteSetting::current(),
            $request->validated(),
        );

        return back()->with('success', 'Social media links updated.');
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     * @return array<string, \Illuminate\Support\Collection<int, \App\Models\Product>>
     */
    private function sectionProducts(array $sections): array
    {
        $allIds = collect(HomepageSections::carouselKeys())
            ->flatMap(fn (string $key) => $sections[$key]['product_ids'] ?? [])
            ->unique()
            ->values();

        $productsById = Product::query()
            ->whereIn('id', $allIds)
            ->get(['id', 'name', 'base_sku'])
            ->keyBy('id');

        $mapped = [];

        foreach (HomepageSections::carouselKeys() as $key) {
            $mapped[$key] = collect($sections[$key]['product_ids'] ?? [])
                ->map(fn (int $id) => $productsById->get($id))
                ->filter()
                ->values();
        }

        return $mapped;
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     * @return array<string, \Illuminate\Support\Collection<int, \App\Models\Category>>
     */
    private function sectionCategories(array $sections): array
    {
        $categoryIds = HomepageSections::normalizeProductIds($sections[HomepageSections::CATEGORIES]['category_ids'] ?? []);

        $categoriesById = Category::query()
            ->whereIn('id', $categoryIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        return [
            HomepageSections::CATEGORIES => collect($categoryIds)
                ->map(fn (int $id) => $categoriesById->get($id))
                ->filter()
                ->values(),
        ];
    }

    public function updateSections(UpdateHomepageSectionsRequest $request): RedirectResponse
    {
        $this->homepage->updateSections(
            SiteSetting::current(),
            $request->validated('sections'),
        );

        return back()->with('success', 'Homepage sections updated.');
    }

    public function updateFooter(UpdateStorefrontFooterRequest $request): RedirectResponse
    {
        $this->homepage->updateFooter(
            SiteSetting::current(),
            $request->validated('footer'),
        );

        return back()->with('success', 'Footer content updated.');
    }

    public function updateContact(UpdateStorefrontContactRequest $request): RedirectResponse
    {
        $validated = $request->validated('contact');
        $validated['show_order_tracking'] = $request->boolean('contact.show_order_tracking');

        $this->homepage->updateContact(
            SiteSetting::current(),
            $validated,
        );

        return back()->with('success', 'Contact information updated.');
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Banner::class);

        $term = $request->string('q')->trim()->toString();

        $products = Product::query()
            ->active()
            ->search($term !== '' ? $term : null)
            ->with('brand:id,name')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'base_sku', 'brand_id']);

        return response()->json($products->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->base_sku,
            'brand' => $product->brand?->name,
        ]));
    }

    public function updateBranding(UpdateHomepageBrandingRequest $request): RedirectResponse
    {
        $this->homepage->updateBranding(
            SiteSetting::current(),
            $request->validated(),
            $request->file('logo'),
            $request->file('favicon'),
        );

        return back()->with('success', 'Logo and favicon updated.');
    }

    public function createHero(): View
    {
        $this->authorize('create', Banner::class);

        return view('admin.homepage.hero-form', [
            'banner' => new Banner([
                'placement' => HomepageContentService::PLACEMENT_HOME_HERO,
                'is_active' => true,
                'sort_order' => 0,
            ]),
        ]);
    }

    public function storeHero(StoreHeroBannerRequest $request): RedirectResponse
    {
        $this->homepage->createHeroBanner(
            $request->validated(),
            $request->file('image'),
        );

        return redirect()
            ->route('admin.homepage.index')
            ->with('success', 'Hero slide created.');
    }

    public function editHero(Banner $banner): View
    {
        $this->ensureHomeHero($banner);
        $this->authorize('update', $banner);

        return view('admin.homepage.hero-form', [
            'banner' => $banner,
        ]);
    }

    public function updateHero(UpdateHeroBannerRequest $request, Banner $banner): RedirectResponse
    {
        $this->ensureHomeHero($banner);

        $this->homepage->updateHeroBanner(
            $banner,
            $request->validated(),
            $request->file('image'),
        );

        return redirect()
            ->route('admin.homepage.index')
            ->with('success', 'Hero slide updated.');
    }

    public function destroyHero(Banner $banner): RedirectResponse
    {
        $this->ensureHomeHero($banner);
        $this->authorize('delete', $banner);

        $this->homepage->deleteHeroBanner($banner);

        return redirect()
            ->route('admin.homepage.index')
            ->with('success', 'Hero slide deleted.');
    }

    private function ensureHomeHero(Banner $banner): void
    {
        if ($banner->placement !== HomepageContentService::PLACEMENT_HOME_HERO) {
            abort(404);
        }
    }
}
