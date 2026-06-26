<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Services\ProductCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private readonly ProductCatalogService $catalog) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = $this->catalog->applyFilters($this->catalog->baseQuery(), $request);
        $products = $this->catalog->paginate($query, $request, (int) $request->integer('per_page', 12));

        return ProductResource::collection($products);
    }

    public function show(string $slug): JsonResponse|ProductResource
    {
        $product = Product::query()
            ->active()
            ->with(['brand', 'defaultVariant', 'variants' => fn ($q) => $q->active()])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProductResource($product);
    }
}
