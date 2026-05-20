<?php

return [
    'url' => env('FAFFLESS_HUB_URL', 'https://communication.faffless.ai'),
    'api_key' => env('FAFFLESS_HUB_API_KEY', env('FAFFLESS_HUB_APP_ID')),
    'api_secret' => env('FAFFLESS_HUB_API_SECRET', env('FAFFLESS_HUB_APP_SECRET')),
    'app_id' => env('FAFFLESS_HUB_APP_ID'),
    'secret' => env('FAFFLESS_HUB_APP_SECRET'),
    'timeout' => 10,
];
