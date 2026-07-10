<?php

namespace App\Http\Requests\Admin;

class UpdateHeroBannerRequest extends StoreHeroBannerRequest
{
    public function rules(): array
    {
        return array_merge($this->heroRules(requireImage: false), [
            'remove_image' => ['sometimes', 'boolean'],
        ]);
    }
}
