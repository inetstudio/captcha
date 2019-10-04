<?php

return [
    'secret' => env('NOCAPTCHA_SECRET'),
    'sitekey' => env('NOCAPTCHA_SITEKEY'),
    'http' => [
        'timeout' => 30,
    ],
    'skip-ips' => [],
];
