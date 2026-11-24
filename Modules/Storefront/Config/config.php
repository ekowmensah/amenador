<?php

return [
    'name' => 'Storefront',
    'module_version' => '1.3.1',
    'default_business_id' => (int) env('STOREFRONT_BUSINESS_ID', 1),
    'paystack_secret' => env('PAYSTACK_SECRET_KEY', ''),
    'paystack_webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET', ''),
];
