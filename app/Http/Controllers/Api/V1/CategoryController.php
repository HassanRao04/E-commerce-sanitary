<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function show(Request $request, string $slug): JsonResponse|AnonymousResourceCollection
    {
        $category = Category::query()->where('slug', $slug)->firstOrFail();

        $products = $category->products()
            ->active()
            ->with(['brand', 'defaultVariant'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 12));

        return ProductResource::collection($products)->additional([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
        ]);
    }
}
