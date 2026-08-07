<?php

use App\Services\Couriers\ManualCourierProvider;
use App\Services\Couriers\Providers\CallCourierService;
use App\Services\Couriers\Providers\LeopardsCourierService;
use App\Services\Couriers\Providers\MnpCourierService;
use App\Services\Couriers\Providers\TcsCourierService;
use App\Services\Couriers\Providers\TraxCourierService;

return [
    /*
    |--------------------------------------------------------------------------
    | Courier Provider Enablement
    |--------------------------------------------------------------------------
    |
    | API-backed providers are disabled until credentials and implementations
    | are added. Manual fulfillment remains always available.
    |
    */
    'enabled' => [
        'manual' => true,
        'tcs' => env('COURIER_TCS_ENABLED', false),
        'leopards' => env('COURIER_LEOPARDS_ENABLED', false),
        'mnp' => env('COURIER_MNP_ENABLED', false),
        'trax' => env('COURIER_TRAX_ENABLED', false),
        'call_courier' => env('COURIER_CALL_COURIER_ENABLED', false),
        'pakistan_post' => env('COURIER_PAKISTAN_POST_ENABLED', false),
    ],

    'defaults' => [
        'provider_slug' => env('COURIER_DEFAULT_PROVIDER', 'manual'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Courier Provider Services
    |--------------------------------------------------------------------------
    |
    | Register service classes here. Adding a provider requires only a new
    | service class and an entry in this array — no order logic changes.
    |
    */
    'providers' => [
        'manual' => ManualCourierProvider::class,
        'tcs' => TcsCourierService::class,
        'leopards' => LeopardsCourierService::class,
        'mnp' => MnpCourierService::class,
        'trax' => TraxCourierService::class,
        'call_courier' => CallCourierService::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider API Configuration
    |--------------------------------------------------------------------------
    |
    | Endpoint paths and defaults for integrated courier APIs. Credentials and
    | base URL are stored per provider in the courier_providers table.
    |
    */
    'providers_config' => [
        'tcs' => [
            'sandbox_base' => 'https://devconnect.tcscourier.com',
            'production_base' => 'https://ociconnect.tcscourier.com',
            'paths' => [
                'authenticate' => '/ecom/api/authentication/token',
                'booking_create' => '/ecom/api/booking/create',
                'print_label' => '/ecom/api/print/label',
                'tracking' => '/tracking/api/Tracking/GetDynamicTrackDetail',
            ],
            'defaults' => [
                'servicecode' => 'O',
                'currency' => 'PKR',
                'costcentercode' => 'DEFAULT',
                'account_number' => null,
            ],
        ],
    ],
];
