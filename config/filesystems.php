<?php declare(strict_types = 1);

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\Filesystem\Disks\ClientDisk;
use App\Services\Filesystem\Disks\ModelDisk;
use App\Services\Settings\Settings;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */
    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */
    'disks'   => [
        // Internal, must be "local".
        // =====================================================================
        AppDisk::NAME              => [
            'driver' => 'local',
            'root'   => storage_path(dirname(Settings::PATH)),
            'throw'  => false,
        ],
        ClientDisk::NAME           => [
            'driver' => 'local',
            'root'   => storage_path('client'),
            'throw'  => false,
        ],

        // Models
        // =====================================================================
        ModelDisk::ORGANIZATIONS   => [
            'driver'     => 'local',
            'root'       => storage_path('app/models/Organization'),
            'url'        => env('APP_URL').'/storage/models/Organization',
            'visibility' => 'public',
            'throw'      => false,
        ],
        ModelDisk::USERS           => [
            'driver'     => 'local',
            'root'       => storage_path('app/models/User'),
            'url'        => env('APP_URL').'/storage/models/User',
            'visibility' => 'public',
            'throw'      => false,
        ],
        ModelDisk::NOTES           => [
            'driver' => 'local',
            'root'   => storage_path('app/models/Note'),
            'throw'  => false,
        ],
        ModelDisk::CHANGE_REQUESTS => [
            'driver' => 'local',
            'root'   => storage_path('app/models/ChangeRequest'),
            'throw'  => false,
        ],
        ModelDisk::QUOTE_REQUESTS  => [
            'driver' => 'local',
            'root'   => storage_path('app/models/QuoteRequest'),
            'throw'  => false,
        ],

        // Default
        // =====================================================================
        'local'                    => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'throw'  => false,
        ],
        'public'                   => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],
        's3'                       => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
        ],
    ],

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
    'links'   => [
        public_path('storage/models/Organization') => storage_path('app/models/Organization'),
        public_path('storage/models/User')         => storage_path('app/models/User'),
    ],
];
