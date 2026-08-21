<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product image pipeline
    |--------------------------------------------------------------------------
    |
    | Controls how newly uploaded product images are stored. Existing files on
    | disk are never modified or deleted automatically by these settings.
    |
    */

    'product' => [
        'max_dimension' => (int) env('PRODUCT_IMAGE_MAX_DIMENSION', 1600),
        'webp_quality' => (int) env('PRODUCT_IMAGE_WEBP_QUALITY', 82),
        'format' => 'webp',
    ],

];
