<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'search' => [
        'engine' => env('SEARCH_ENGINE', 'postgres'),
        'meilisearch_host' => env('MEILISEARCH_HOST'),
        'meilisearch_key' => env('MEILISEARCH_KEY'),
    ],

    'google_analytics' => [
        'measurement_id' => env('GOOGLE_ANALYTICS_MEASUREMENT_ID'),
    ],

    'inventree' => [
        'base_url' => env('INVENTREE_BASE_URL'),
        'api_token' => env('INVENTREE_API_TOKEN'),
        'sku_field' => env('INVENTREE_SKU_FIELD', 'IPN'),
        'timeout' => env('INVENTREE_TIMEOUT', 30),
        'retry_times' => env('INVENTREE_RETRY_TIMES', 3),
        'retry_sleep' => env('INVENTREE_RETRY_SLEEP', 100),
        'verify' => env('INVENTREE_VERIFY_SSL', true),
    ],

];
