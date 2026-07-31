<?php

return [
    'enabled' => env('CONTAPYME_SYNC_ENABLED', false),
    'base_url' => env('CONTAPYME_URL', env('CONTAPYME_BASE_URL', '')),
    'email' => env('CONTAPYME_EMAIL', ''),
    'password' => env('CONTAPYME_PASSWORD', ''),
    'password_hash' => env('CONTAPYME_PASSWORD_MD5', env('CONTAPYME_PASSWORD_HASH', '')),
    'idmaquina' => env('CONTAPYME_IDMAQUINA', ''),
    'iapp' => env('CONTAPYME_IAPP', '1003'),
    'timeout' => env('CONTAPYME_TIMEOUT', 10),
    'stock_stale_after' => (int) env('CONTAPYME_STOCK_STALE_AFTER', 900),

    // Legacy keys kept so existing .env values do not break config lookups.
    // The sync no longer filters by warehouse or reconciles via GetListaElemInv.
    'warehouse' => env('CONTAPYME_BODEGA_ID', env('CONTAPYME_WAREHOUSE', '1')),
    'catalog_reconciliation' => (bool) env('CONTAPYME_CATALOG_RECONCILIATION', false),
    'catalog_page_size' => (int) env('CONTAPYME_CATALOG_PAGE_SIZE', 200),
];
