<?php

return [
    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', false),

    'access_users' => [
        'admin' => [
            'name' => env('ACCESS_USERS_ADMIN_NAME', 'Admin Import Corporal'),
            'email' => env('ACCESS_USERS_ADMIN_EMAIL'),
            'password' => env('ACCESS_USERS_ADMIN_PASSWORD'),
        ],

        'distributor' => [
            'name' => env('ACCESS_USERS_DISTRIBUTOR_NAME', 'Usuario Distribuidor Demo'),
            'email' => env('ACCESS_USERS_DISTRIBUTOR_EMAIL'),
            'password' => env('ACCESS_USERS_DISTRIBUTOR_PASSWORD'),
        ],
    ],
];
