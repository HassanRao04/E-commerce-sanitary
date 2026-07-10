<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Support\ProductVariationBuilder;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Services\Admin\ProductImageService;
use App\Services\Admin\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductImageService $imageService,
        private readonly BrandRepositoryInterface $brands,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        return view('admin.products.index', [
            'products' => $this->productService->paginatedList($request->only('q', 'status', 'brand_id')),
            'brands' => $this->brands->activeList(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', $this->formData(new Product([
            'status' => 'draft',
            'product_type' => 'simple',
        ])));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->productService->create($request->validated());

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.form', $this->formData(
            $this->productService->findForEdit($product->id)
        ));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update($product, $request->validated());

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($image->product_id === $product->id, 404);

        $this->imageService->delete($image);

        return back()->with('success', 'Image removed.');
    }

    /** @return array<string, mixed> */
    private function formData(Product $product): array
    {
        $attributes = Attribute::query()->with('values')->orderBy('sort_order')->get();

        return [
            'product' => $product,
            'brands' => $this->brands->activeList(),
            'categories' => Category::query()->with('parent')->orderBy('name')->get(),
            'attributes' => $attributes,
            'variantAttributes' => $attributes->where('is_variant_attribute', true)->values(),
            'variationAttributes' => ProductVariationBuilder::attributesFromProduct($product->exists ? $product : null),
            'existingVariants' => ProductVariationBuilder::variantsFromProduct($product->exists ? $product : null),
        ];
    }
}
