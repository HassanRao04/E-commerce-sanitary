<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Recoverable Entities (Tier 1)
    |--------------------------------------------------------------------------
    |
    | Soft-deleted records listed in the Deleted Records admin area.
    | Immutable business-history models must NOT appear here.
    |
    */
    'entities' => [
        'user' => [
            'model' => User::class,
            'label' => 'User',
            'delete_action' => 'user.deleted',
            'restore_action' => 'user.restored',
            'staff_only' => true,
            'identifier' => 'full_name',
            'subtitle' => 'email',
        ],
        'product' => [
            'model' => Product::class,
            'label' => 'Product',
            'delete_action' => 'product.deleted',
            'restore_action' => 'product.restored',
            'staff_only' => false,
            'identifier' => 'name',
            'subtitle' => 'base_sku',
        ],
        'category' => [
            'model' => Category::class,
            'label' => 'Category',
            'delete_action' => 'category.deleted',
            'restore_action' => 'category.restored',
            'staff_only' => false,
            'identifier' => 'name',
            'subtitle' => 'slug',
        ],
        'brand' => [
            'model' => Brand::class,
            'label' => 'Brand',
            'delete_action' => 'brand.deleted',
            'restore_action' => 'brand.restored',
            'staff_only' => false,
            'identifier' => 'name',
            'subtitle' => 'slug',
        ],
        'coupon' => [
            'model' => Coupon::class,
            'label' => 'Coupon',
            'delete_action' => 'coupon.deleted',
            'restore_action' => 'coupon.restored',
            'staff_only' => false,
            'identifier' => 'code',
            'subtitle' => null,
        ],
    ],

];
