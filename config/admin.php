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
                    'label' => 'Influencers',
                    'route' => 'admin.influencers.index',
                    'permission' => 'users.view',
                    'icon' => 'users',
                    'active' => 'admin.influencers.*',
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
                    'label' => 'Order Workflow',
                    'route' => 'admin.orders.workflow.index',
                    'permission' => 'orders.workflow.view',
                    'icon' => 'cog-6-tooth',
                    'active' => 'admin.orders.workflow.*',
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
                    'label' => 'Checkout Rules',
                    'route' => 'admin.checkout.rules.edit',
                    'permission' => 'checkout_rules.view',
                    'icon' => 'cog',
                    'active' => 'admin.checkout.rules.*',
                ],
                [
                    'label' => 'Coupons',
                    'route' => 'admin.coupons.index',
                    'permission' => 'coupons.view',
                    'icon' => 'ticket',
                    'active' => 'admin.coupons.*',
                ],
                [
                    'label' => 'Influencer Performance',
                    'route' => 'admin.influencer-performance.index',
                    'permission' => 'coupons.view',
                    'icon' => 'chart-bar',
                    'active' => 'admin.influencer-performance.*',
                ],
                [
                    'label' => 'Shipments',
                    'route' => 'admin.shipping.index',
                    'permission' => 'shipping.view',
                    'icon' => 'truck',
                    'active' => 'admin.shipping.index',
                ],
                [
                    'label' => 'Courier Providers',
                    'route' => 'admin.courier-providers.index',
                    'permission' => 'shipping.view',
                    'icon' => 'truck',
                    'active' => 'admin.courier-providers.*',
                ],
                [
                    'label' => 'Shipping Settings',
                    'route' => 'admin.shipping.settings.edit',
                    'permission' => 'shipping.view',
                    'icon' => 'cog-6-tooth',
                    'active' => 'admin.shipping.settings.*',
                ],
                [
                    'label' => 'Tax & Charges',
                    'route' => 'admin.tax.settings.edit',
                    'permission' => 'tax.view',
                    'icon' => 'cog',
                    'active' => 'admin.tax.settings.*',
                ],
            ],
        ],
        [
            'section' => 'Engagement',
            'items' => [
                [
                    'label' => 'Website Content',
                    'route' => 'admin.homepage.index',
                    'permission' => 'homepage.view',
                    'icon' => 'photo',
                    'active' => 'admin.homepage.*',
                ],
                [
                    'label' => 'Reviews',
                    'route' => 'admin.reviews.index',
                    'permission' => 'reviews.view',
                    'icon' => 'star',
                    'active' => 'admin.reviews.*',
                ],
                [
                    'label' => 'Contact Messages',
                    'route' => 'admin.inquiries.index',
                    'permission' => 'notifications.view',
                    'icon' => 'document-text',
                    'active' => 'admin.inquiries.*',
                ],
            ],
        ],
        [
            'section' => 'Insights',
            'items' => [
                [
                    'label' => 'Reporting',
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
                    'label' => 'Activity Log',
                    'route' => 'admin.activity.index',
                    'permission' => 'activity.view',
                    'icon' => 'document-text',
                    'active' => 'admin.activity.*',
                ],
                [
                    'label' => 'Deleted Records',
                    'route' => 'admin.deleted-records.index',
                    'permission' => 'records.view',
                    'icon' => 'archive-box',
                    'active' => 'admin.deleted-records.*',
                ],
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
            'view' => ['super-admin', 'admin', 'manager'],
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
            'workflow.view' => ['super-admin', 'admin', 'manager', 'sales-staff'],
            'workflow.manage' => ['super-admin', 'admin', 'manager'],
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
        'checkout_rules' => [
            'view' => ['super-admin', 'admin', 'manager', 'sales-staff'],
            'manage' => ['super-admin', 'admin', 'manager'],
        ],
        'shipping' => [
            'view' => ['super-admin', 'admin', 'manager', 'inventory-staff', 'sales-staff'],
            'manage' => ['super-admin', 'admin', 'manager', 'inventory-staff'],
        ],
        'tax' => [
            'view' => ['super-admin', 'admin', 'manager', 'sales-staff'],
            'manage' => ['super-admin', 'admin', 'manager'],
        ],
        'reviews' => [
            'view' => ['super-admin', 'admin', 'manager', 'content-manager'],
            'moderate' => ['super-admin', 'admin', 'manager', 'content-manager'],
        ],
        'notifications' => [
            'view' => ['super-admin', 'admin', 'manager', 'sales-staff', 'content-manager'],
            'manage' => ['super-admin', 'admin', 'manager', 'content-manager'],
        ],
        'reports' => [
            'view' => ['super-admin', 'admin', 'manager', 'sales-staff'],
        ],
        'settings' => [
            'view' => ['super-admin', 'admin', 'content-manager'],
            'manage' => ['super-admin', 'admin'],
        ],
        'homepage' => [
            'view' => ['super-admin', 'admin', 'manager', 'content-manager'],
            'manage' => ['super-admin', 'admin', 'content-manager'],
        ],
        'activity' => [
            'view' => ['super-admin', 'admin'],
        ],
        'records' => [
            'view' => ['super-admin', 'admin'],
            'restore' => ['super-admin'],
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
