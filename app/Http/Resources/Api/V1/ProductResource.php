<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->base_sku,
            'short_description' => $this->short_description,
            'status' => $this->status?->value,
            'is_featured' => $this->is_featured,
            'price_from' => $this->price_from,
            'image_url' => $this->primary_image_url,
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand?->id,
                'name' => $this->brand?->name,
                'slug' => $this->brand?->slug,
            ]),
            'default_variant' => $this->whenLoaded('defaultVariant', fn () => new ProductVariantResource($this->defaultVariant)),
        ];
    }
}
