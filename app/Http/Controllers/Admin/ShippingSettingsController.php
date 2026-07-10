<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateShippingSettingsRequest;
use App\Models\Category;
use App\Models\CategoryShippingRate;
use App\Models\Product;
use App\Models\ProductShippingRate;
use App\Models\ShippingSetting;
use App\Services\ActivityLogService;
use App\Services\ShippingSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingSettingsController extends Controller
{
    public function __construct(
        private readonly ShippingSettingsService $shippingSettings,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function edit(): View
    {
        $this->authorize('viewAny', ShippingSetting::class);

        $settings = ShippingSetting::current();

        return view('admin.shipping.settings', [
            'settings' => $settings,
            'productRates' => ProductShippingRate::query()
                ->with('product:id,name,base_sku')
                ->orderBy('id')
                ->get(),
            'categoryRates' => CategoryShippingRate::query()
                ->with('category:id,name')
                ->orderBy('id')
                ->get(),
            'categories' => Category::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateShippingSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->shippingSettings->sync(
            [
                'flat_rate_enabled' => $validated['flat_rate_enabled'] ?? false,
                'flat_rate_amount' => $validated['flat_rate_amount'] ?? 0,
                'product_rate_enabled' => $validated['product_rate_enabled'] ?? false,
                'category_rate_enabled' => $validated['category_rate_enabled'] ?? false,
                'free_shipping_enabled' => $validated['free_shipping_enabled'] ?? false,
                'free_shipping_threshold' => $validated['free_shipping_threshold'] ?? 0,
                'default_method' => $validated['default_method'],
            ],
            $validated['product_rates'] ?? [],
            $validated['category_rates'] ?? [],
        );

        $this->activityLog->log('shipping.settings.updated', ShippingSetting::current(), [], [
            'default_method' => $validated['default_method'],
        ]);

        return back()->with('success', 'Shipping settings saved successfully.');
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ShippingSetting::class);

        $term = (string) $request->input('q', '');

        $products = Product::query()
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('base_sku', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'base_sku']);

        return response()->json([
            'products' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => $product->name.' ('.$product->base_sku.')',
            ])->values(),
        ]);
    }
}
