<?php

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Envato Author API — used by App\Services\PurchaseCodeService during
    // the installer's purchase-code verification step.
    'envato' => [
        'item_id' => env('ENVATO_ITEM_ID'),
    ],
];
