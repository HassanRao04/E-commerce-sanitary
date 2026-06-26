<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Navigation
    |--------------------------------------------------------------------------
    |
    | Each item requires a Spatie permission. Items are hidden when the
    | authenticated user lacks the permission. Route patterns drive active state.
    |
    */
    'menu' => [
        [
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'admin.dashboard',
                    'permission' => 'dashboard.view',
                    'icon' => 'chart-pie',
                    'active' => 'admin.dashboard',
                ],
            ],
        ],
        [
            'section' => 'People',
            'items' => [
                [
                    'label' => 'Users',
                    'route' => 'admin.users.index',
                    'permission' => 'users.view',
                    'icon' => 'users',
                    'active' => 'admin.users.*',
                ],
                [
                    'label' => 'Customers',
                    'route' => 'admin.customers.index',
                    'permission' => 'customers.view',
                    'icon' => 'user-group',
                    'active' => 'admin.customers.*',
                ],
            ],
        ],
        [
            'section' => 'Catalog',
            'items' => [
                [
                    'label' => 'Products',
                    'route' => 'admin.products.index',
                    'permission' => 'products.view',
                    'icon' => 'cube',
                    'active' => 'admin.products.*',
                ],
                [
                    'label' => 'Categories',
                    'route' => 'admin.categories.index',
                    'permission' => 'categories.view',
                    'icon' => 'folder',
                    'active' => 'admin.categories.*',
                ],
                [
                    'label' => 'Brands',
                    'route' => 'admin.brands.index',
                    'permission' => 'brands.view',
                    'icon' => 'tag',
                    'active' => 'admin.brands.*',
                ],
                [
                    'label' => 'Inventory',
                    'route' => 'admin.inventory.index',
                    'permission' => 'inventory.view',
                    'icon' => 'archive-box',
                    'active' => 'admin.inventory.*',
                ],
            ],
        ],
        [
            'section' => 'Commerce',
            'items' => [
                [
                    'label' => 'Orders',
                    'route' => 'admin.orders.index',
                    'permission' => 'orders.view',
                    'icon' => 'shopping-cart',
                    'active' => 'admin.orders.*',
                ],
                [
                    'label' => 'Invoices',
                    'route' => 'admin.invoices.index',
                    'permission' => 'billing.view',
                    'icon' => 'document-text',
                    'active' => 'admin.invoices.*',
                ],
                [
                    'label' => 'Payments',
                    'route' => 'admin.payments.index',
                    'permission' => 'payments.view',
                    'icon' => 'credit-card',
                    'active' => 'admin.payments.*',
                ],
                [
                    'label' => 'Coupons',
                    'route' => 'admin.coupons.index',
                    'permission' => 'coupons.view',
                    'icon' => 'ticket',
                    'active' => 'admin.coupons.*',
                ],
            ],
        ],
        [
            'section' => 'Engagement',
            'items' => [
                [
                    'label' => 'Reviews',
                    'route' => 'admin.reviews.index',
                    'permission' => 'reviews.view',
                    'icon' => 'star',
                    'active' => 'admin.reviews.*',
                ],
            ],
        ],
        [
            'section' => 'Insights',
            'items' => [
                [
                    'label' => 'Reports',
                    'route' => 'admin.reports.index',
                    'permission' => 'reports.view',
                    'icon' => 'chart-bar',
                    'active' => 'admin.reports.*',
                ],
            ],
        ],
        [
            'section' => 'System',
            'items' => [
                [
                    'label' => 'Settings',
                    'route' => 'admin.settings.index',
                    'permission' => 'settings.view',
                    'icon' => 'cog',
                    'active' => 'admin.settings.*',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Matrix
    |--------------------------------------------------------------------------
    |
    | Module → permission → roles allowed. Used for documentation and seeding.
    | super-admin always receives all permissions via RolesAndPermissionsSeeder.
    |
    */
    'permissions_matrix' => [
        'dashboard' => [
            'view' => ['super-admin', 'admin', 'manager', 'inventory-staff', 'sales-staff', 'content-manager'],
        ],
        'users' => [
            'view' => ['super-admin', 'admin'],
            'create' => ['super-admin', 'admin'],
            'update' => ['super-admin', 'admin'],
            'delete' => ['super-admin'],
        ],
        'customers' => [
            'view' => ['super-admin', 'admin', 'manager', 'sales-staff'],
            'manage' => ['super-admin', 'admin', 'manager', 'sales-staff'],
        ],
        'products' => [
            'view' => ['super-admin', 'admin', 'manager', 'inventory-staff', 'content-manager'],
            'create' => ['super-admin', 'admin', 'manager', 'content-manager'],
            'update' => ['super-admin', 'admin', 'manager', 'content-manager'],
            'delete' => ['super-admin', 'admin'],
        ],
        'categories' => [
            'view' => ['super-admin', 'admin', 'manager', 'content-manager'],
            'manage' => ['super-admin', 'admin', 'manager', 'content-manager'],
        ],
        'brands' => [
            'view' => ['super-admin', 'admin', 'manager', 'content-manager'],
            'manage' => ['super-admin', 'admin', 'manager', 'content-manager'],
        ],
        'inventory' => [
            'view' => ['super-admin', 'admin', 'manager', 'inventory-staff'],
            'manage' => ['super-admin', 'admin', 'manager', 'inventory-staff'],
        ],
        'orders' => [
            'view' => ['super-admin', 'admin', 'manager', 'inventory-staff', 'sales-staff'],
            'update' => ['super-admin', 'admin', 'manager', 'sales-staff'],
            'cancel' => ['super-admin', 'admin', 'manager'],
        ],
        'billing' => [
            'view' => ['super-admin', 'admin', 'manager', 'sales-staff'],
            'manage' => ['super-admin', 'admin', 'manager'],
        ],
        'payments' => [
            'view' => ['super-admin', 'admin', 'manager', 'sales-staff'],
            'manage' => ['super-admin', 'admin', 'manager'],
        ],
        'coupons' => [
            'view' => ['super-admin', 'admin', 'manager', 'sales-staff'],
            'manage' => ['super-admin', 'admin', 'manager'],
        ],
        'reviews' => [
            'view' => ['super-admin', 'admin', 'manager', 'content-manager'],
            'moderate' => ['super-admin', 'admin', 'manager', 'content-manager'],
        ],
        'reports' => [
            'view' => ['super-admin', 'admin', 'manager', 'sales-staff'],
        ],
        'settings' => [
            'view' => ['super-admin', 'admin', 'content-manager'],
            'manage' => ['super-admin', 'admin'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Shell / Sidebar
    |--------------------------------------------------------------------------
    */
    'sidebar' => [
        'width' => '16rem',
        'transition_ms' => 250,
        'storage_key' => 'admin_sidebar_collapsed',
    ],

];
