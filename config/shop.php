<?php

return [
    'currency' => env('SHOP_CURRENCY', 'PKR'),
    'currency_symbol' => env('SHOP_CURRENCY_SYMBOL', 'Rs.'),
    'tax_rate' => (float) env('SHOP_TAX_RATE', 0),
    'tax_label' => env('SHOP_TAX_LABEL', 'Tax'),
    'shipping_flat_rate' => (float) env('SHOP_SHIPPING_FLAT_RATE', 0),
    'free_shipping_threshold' => (float) env('SHOP_FREE_SHIPPING_THRESHOLD', 10000),
    'low_stock_threshold' => (int) env('SHOP_LOW_STOCK_THRESHOLD', 5),
    'admin_email' => env('SHOP_ADMIN_EMAIL', 'admin@sanitarystore.pk'),
    'estimated_delivery_days' => (int) env('SHOP_ESTIMATED_DELIVERY_DAYS', 5),
];
