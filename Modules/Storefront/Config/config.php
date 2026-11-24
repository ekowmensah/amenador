<?php

return [
    'name' => 'Storefront',
    'module_version' => json_decode(file_get_contents(__DIR__.'/../module.json'), true)['version'],
    'default_business_id' => (int) env('STOREFRONT_BUSINESS_ID', 1),
    'paystack_secret' => env('PAYSTACK_SECRET_KEY', ''),
    'paystack_webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET', ''),
];
