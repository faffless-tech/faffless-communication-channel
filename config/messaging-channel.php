<?php

return [
    'url' => env('MESSAGING_SERVICE_URL'),
    'app_id' => env('MESSAGING_APP_ID'),
    'secret' => env('MESSAGING_APP_SECRET'),
    'timeout' => (int) env('MESSAGING_SERVICE_TIMEOUT', 10),
    'brand_key' => env('MESSAGING_BRAND_KEY', env('VITE_BRAND_KEY')),
    'brand_name' => env('MESSAGING_BRAND_NAME', env('VITE_BRAND_NAME')),
    'brand_region' => env('MESSAGING_BRAND_REGION', env('VITE_BRAND_REGION')),
];
