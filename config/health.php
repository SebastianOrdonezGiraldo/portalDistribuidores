<?php

return [
    'revision_path' => env('APP_REVISION_PATH', base_path('REVISION')),
    'tree_path' => env('APP_TREE_PATH', base_path('TREE')),
    'storage_path' => env('APP_HEALTH_STORAGE_PATH', storage_path()),
];
