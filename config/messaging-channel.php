<?php

return [
    'url' => env('MESSAGING_SERVICE_URL'),
    'app_id' => env('MESSAGING_APP_ID'),
    'secret' => env('MESSAGING_APP_SECRET'),
    'timeout' => (int) env('MESSAGING_SERVICE_TIMEOUT', 10),
];
