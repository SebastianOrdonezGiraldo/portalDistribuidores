<?php

return [
    'login' => [
        // Secondary network-level throttle for /login (LoginRequest keeps credential-level lockout).
        'per_minute' => (int) env('ABUSE_LOGIN_PER_MINUTE', 20),
        'per_hour' => (int) env('ABUSE_LOGIN_PER_HOUR', 250),
    ],

    'registration' => [
        'per_hour' => (int) env('ABUSE_REGISTER_PER_HOUR', 5),
        'per_day' => (int) env('ABUSE_REGISTER_PER_DAY', 25),
    ],

    'api' => [
        'guest_per_minute' => (int) env('ABUSE_API_GUEST_PER_MINUTE', 60),
        'auth_per_minute' => (int) env('ABUSE_API_AUTH_PER_MINUTE', 180),
        'guest_per_hour' => (int) env('ABUSE_API_GUEST_PER_HOUR', 600),
        'auth_per_hour' => (int) env('ABUSE_API_AUTH_PER_HOUR', 3600),
    ],

    'catalog' => [
        // Public catalog/product pages and their AJAX pagination payloads.
        'guest_per_minute' => (int) env('ABUSE_CATALOG_GUEST_PER_MINUTE', 40),
        'auth_per_minute' => (int) env('ABUSE_CATALOG_AUTH_PER_MINUTE', 120),
        'guest_per_hour' => (int) env('ABUSE_CATALOG_GUEST_PER_HOUR', 450),
        'auth_per_hour' => (int) env('ABUSE_CATALOG_AUTH_PER_HOUR', 2400),
    ],

    'ai_generation' => [
        'guest_per_minute' => (int) env('ABUSE_AI_GUEST_PER_MINUTE', 3),
        'auth_per_minute' => (int) env('ABUSE_AI_AUTH_PER_MINUTE', 30),
        'guest_per_hour' => (int) env('ABUSE_AI_GUEST_PER_HOUR', 30),
        'auth_per_hour' => (int) env('ABUSE_AI_AUTH_PER_HOUR', 500),
    ],

    'automation' => [
        'enabled' => filter_var(env('ABUSE_AUTOMATION_GUARD_ENABLED', true), FILTER_VALIDATE_BOOL),
        'max_per_minute' => (int) env('ABUSE_AUTOMATION_MAX_PER_MINUTE', 12),

        // Common non-browser automation signatures used for brute-force/scraping traffic.
        'signatures' => array_values(array_filter(array_map(
            static fn (string $value): string => trim(strtolower($value)),
            explode(',', (string) env(
                'ABUSE_AUTOMATION_USER_AGENTS',
                'curl,wget,python-requests,scrapy,httpclient,libwww-perl,go-http-client,postmanruntime,powershell,axios,node-fetch',
            )),
        ))),
    ],
];
