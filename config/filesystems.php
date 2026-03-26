<?php

$publicDiskDriver = env('PUBLIC_DISK_DRIVER');
$privateDiskDriver = env('PRIVATE_DISK_DRIVER');
$hasS3Bucket = trim((string) env('AWS_BUCKET', '')) !== '';
$hasPrivateS3Bucket = trim((string) env('PRIVATE_BUCKET', '')) !== '';
$useS3ForPublicDisk = $publicDiskDriver === 's3'
    || ($publicDiskDriver !== 'local' && env('APP_ENV') === 'production' && $hasS3Bucket);
$useS3ForPrivateDisk = $privateDiskDriver === 's3'
    || ($privateDiskDriver !== 'local' && env('APP_ENV') === 'production' && $hasPrivateS3Bucket);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'private' => $useS3ForPrivateDisk
            ? [
                'driver' => 's3',
                'key' => env('PRIVATE_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
                'secret' => env('PRIVATE_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
                'region' => env('PRIVATE_DEFAULT_REGION', env('AWS_DEFAULT_REGION', 'auto')),
                'bucket' => env('PRIVATE_BUCKET'),
                'endpoint' => env('PRIVATE_ENDPOINT', env('AWS_ENDPOINT')),
                'use_path_style_endpoint' => env('PRIVATE_USE_PATH_STYLE_ENDPOINT', env('AWS_USE_PATH_STYLE_ENDPOINT', true)),
                'visibility' => 'private',
                'throw' => false,
                'report' => false,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/private'),
                'visibility' => 'private',
                'throw' => false,
                'report' => false,
            ],

        'public' => $useS3ForPublicDisk
            ? [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION', 'auto'),
                'bucket' => env('AWS_BUCKET'),
                'url' => env('AWS_URL'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    'public_media_signed_url_ttl' => (int) env('PUBLIC_MEDIA_SIGNED_URL_TTL', 20),
    'order_pdfs_disk' => env('ORDER_PDFS_DISK', 'private'),
    'tech_sheets_disk' => env('TECH_SHEETS_DISK', 'private'),

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
