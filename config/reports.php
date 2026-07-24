<?php

return [

    'groups' => [
        'sales' => 'Sales Reports',
        'product' => 'Product Reports',
        'inventory' => 'Inventory Reports',
        'shipping' => 'Shipping Reports',
        'customer' => 'Customer Reports',
        'influencer' => 'Influencer Reports',
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
        'influencer-monthly-sales' => [
            'label' => 'Monthly Sales',
            'description' => 'Influencer-attributed paid sales grouped by month.',
            'group' => 'influencer',
            'period' => 'monthly',
            'default_days' => 365,
        ],
        'influencer-yearly-sales' => [
            'label' => 'Yearly Sales',
            'description' => 'Influencer-attributed paid sales grouped by year.',
            'group' => 'influencer',
            'period' => 'yearly',
            'default_days' => 1825,
        ],
        'influencer-top' => [
            'label' => 'Top Influencers',
            'description' => 'Highest sales from influencer coupon orders.',
            'group' => 'influencer',
            'default_days' => 90,
        ],
        'influencer-lowest' => [
            'label' => 'Lowest Performing Influencers',
            'description' => 'Lowest sales from influencers with tracked orders.',
            'group' => 'influencer',
            'default_days' => 90,
        ],
        'influencer-commission' => [
            'label' => 'Highest Commission',
            'description' => 'Influencers ranked by commission generated.',
            'group' => 'influencer',
            'default_days' => 90,
        ],
        'influencer-pending-payout' => [
            'label' => 'Pending Payout',
            'description' => 'Attributed commissions not yet marked paid.',
            'group' => 'influencer',
            'default_days' => 90,
        ],
        'influencer-paid-payout' => [
            'label' => 'Paid Payout',
            'description' => 'Completed commission payouts from the ledger.',
            'group' => 'influencer',
            'default_days' => 90,
        ],
        'influencer-coupon-usage' => [
            'label' => 'Coupon Usage',
            'description' => 'Usage, sales, and discount by influencer coupon.',
            'group' => 'influencer',
            'default_days' => 90,
        ],
        'influencer-monthly-commission' => [
            'label' => 'Monthly Commission',
            'description' => 'Influencer commission generated, grouped by month.',
            'group' => 'influencer',
            'period' => 'monthly',
            'default_days' => 365,
        ],
        'influencer-yearly-commission' => [
            'label' => 'Yearly Commission',
            'description' => 'Influencer commission generated, grouped by year.',
            'group' => 'influencer',
            'period' => 'yearly',
            'default_days' => 1825,
        ],
        'influencer-aov' => [
            'label' => 'Average Order Value',
            'description' => 'Average order value by influencer from attributed sales.',
            'group' => 'influencer',
            'default_days' => 90,
        ],
        'influencer-repeat-customers' => [
            'label' => 'Repeat Customers',
            'description' => 'Customers with more than one influencer-attributed order.',
            'group' => 'influencer',
            'default_days' => 365,
        ],
    ],

];
