<?php

return [

    'types' => [
        'daily-sales' => [
            'label' => 'Daily Sales',
            'description' => 'Paid revenue and order count by day.',
            'group' => 'sales',
            'period' => 'daily',
            'default_days' => 30,
        ],
        'weekly-sales' => [
            'label' => 'Weekly Sales',
            'description' => 'Paid revenue grouped by week.',
            'group' => 'sales',
            'period' => 'weekly',
            'default_days' => 90,
        ],
        'monthly-sales' => [
            'label' => 'Monthly Sales',
            'description' => 'Paid revenue grouped by month.',
            'group' => 'sales',
            'period' => 'monthly',
            'default_days' => 365,
        ],
        'yearly-sales' => [
            'label' => 'Yearly Sales',
            'description' => 'Paid revenue grouped by year.',
            'group' => 'sales',
            'period' => 'yearly',
            'default_days' => 1825,
        ],
        'product-sales' => [
            'label' => 'Product Sales',
            'description' => 'Units sold and revenue by product.',
            'group' => 'catalog',
            'default_days' => 90,
        ],
        'category-sales' => [
            'label' => 'Category Sales',
            'description' => 'Revenue and units by product category.',
            'group' => 'catalog',
            'default_days' => 90,
        ],
        'customers' => [
            'label' => 'Customer Reports',
            'description' => 'Customer order activity and lifetime value.',
            'group' => 'people',
            'default_days' => 365,
        ],
        'inventory' => [
            'label' => 'Inventory Reports',
            'description' => 'Stock levels, availability, and valuation.',
            'group' => 'operations',
        ],
        'revenue' => [
            'label' => 'Revenue Reports',
            'description' => 'Revenue breakdown by payment method and components.',
            'group' => 'finance',
            'default_days' => 90,
        ],
    ],

];
