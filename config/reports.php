<?php

return [

    'groups' => [
        'sales' => 'Sales Reports',
        'product' => 'Product Reports',
        'inventory' => 'Inventory Reports',
        'shipping' => 'Shipping Reports',
        'customer' => 'Customer Reports',
    ],

    'types' => [
        'daily-sales' => [
            'label' => 'Daily Sales',
            'description' => 'Paid order revenue and count by day from ERP transactions.',
            'group' => 'sales',
            'period' => 'daily',
            'default_days' => 30,
        ],
        'weekly-sales' => [
            'label' => 'Weekly Sales',
            'description' => 'Paid order revenue grouped by week.',
            'group' => 'sales',
            'period' => 'weekly',
            'default_days' => 90,
        ],
        'monthly-sales' => [
            'label' => 'Monthly Sales',
            'description' => 'Paid order revenue grouped by month.',
            'group' => 'sales',
            'period' => 'monthly',
            'default_days' => 365,
        ],
        'yearly-sales' => [
            'label' => 'Yearly Sales',
            'description' => 'Paid order revenue grouped by year.',
            'group' => 'sales',
            'period' => 'yearly',
            'default_days' => 1825,
        ],
        'revenue' => [
            'label' => 'Revenue Breakdown',
            'description' => 'Order revenue by payment method with subtotal, tax, shipping, and discounts.',
            'group' => 'sales',
            'default_days' => 90,
        ],
        'product-sales' => [
            'label' => 'Product Sales',
            'description' => 'Units sold and revenue by product from order line items.',
            'group' => 'product',
            'default_days' => 90,
        ],
        'category-sales' => [
            'label' => 'Category Sales',
            'description' => 'Revenue and units by product category.',
            'group' => 'product',
            'default_days' => 90,
        ],
        'inventory' => [
            'label' => 'Stock Levels',
            'description' => 'On-hand, reserved, and available inventory with valuation.',
            'group' => 'inventory',
        ],
        'shipping-status' => [
            'label' => 'Shipment Status',
            'description' => 'Shipment counts and shipping fees by fulfillment status.',
            'group' => 'shipping',
            'default_days' => 90,
        ],
        'shipping-courier' => [
            'label' => 'Courier Performance',
            'description' => 'Shipments and average delivery time by courier.',
            'group' => 'shipping',
            'default_days' => 90,
        ],
        'shipping-fulfillment' => [
            'label' => 'Fulfillment Detail',
            'description' => 'Individual shipment records with tracking and delivery dates.',
            'group' => 'shipping',
            'default_days' => 30,
        ],
        'customers' => [
            'label' => 'Customer Activity',
            'description' => 'Customer order activity, period revenue, and lifetime spend.',
            'group' => 'customer',
            'default_days' => 365,
        ],
    ],

];
