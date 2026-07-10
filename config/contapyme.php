<?php

return [
    'enabled' => env('CONTAPYME_SYNC_ENABLED', false),
    'base_url' => env('CONTAPYME_URL', env('CONTAPYME_BASE_URL', '')),
    'email' => env('CONTAPYME_EMAIL', ''),
    'password' => env('CONTAPYME_PASSWORD', ''),
    'password_hash' => env('CONTAPYME_PASSWORD_MD5', env('CONTAPYME_PASSWORD_HASH', '')),
    'idmaquina' => env('CONTAPYME_IDMAQUINA', ''),
    'iapp' => env('CONTAPYME_IAPP', '1003'),
    'warehouse' => env('CONTAPYME_BODEGA_ID', env('CONTAPYME_WAREHOUSE', '1')),
    'timeout' => env('CONTAPYME_TIMEOUT', 10),
];
