<?php

return [
    'enabled' => [
        'cod' => env('PAYMENT_COD_ENABLED', true),
        'bank_transfer' => env('PAYMENT_BANK_TRANSFER_ENABLED', true),
        'stripe' => env('PAYMENT_STRIPE_ENABLED', false),
        'jazzcash' => env('PAYMENT_JAZZCASH_ENABLED', false),
        'easypaisa' => env('PAYMENT_EASYPAISA_ENABLED', false),
        'payfast' => env('PAYMENT_PAYFAST_ENABLED', false),
    ],

    'bank_transfer' => [
        'account_name' => env('BANK_ACCOUNT_NAME', 'Sanitary Store Pvt Ltd'),
        'account_number' => env('BANK_ACCOUNT_NUMBER', '1234567890'),
        'bank_name' => env('BANK_NAME', 'HBL'),
        'iban' => env('BANK_IBAN', 'PK00HABB0000000000123456'),
        'instructions' => env('BANK_TRANSFER_INSTRUCTIONS', 'Please transfer the exact order amount and use your order number as payment reference.'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'jazzcash' => [
        'merchant_id' => env('JAZZCASH_MERCHANT_ID'),
        'password' => env('JAZZCASH_PASSWORD'),
        'integrity_salt' => env('JAZZCASH_INTEGRITY_SALT'),
    ],

    'easypaisa' => [
        'store_id' => env('EASYPAISA_STORE_ID'),
        'hash_key' => env('EASYPAISA_HASH_KEY'),
    ],

    'payfast' => [
        'merchant_id' => env('PAYFAST_MERCHANT_ID'),
        'merchant_key' => env('PAYFAST_MERCHANT_KEY'),
        'passphrase' => env('PAYFAST_PASSPHRASE'),
    ],
];
