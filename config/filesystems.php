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
    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
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
        ],
        ClientDisk::NAME           => [
            'driver' => 'local',
            'root'   => storage_path('client'),
        ],

        // Models
        // =====================================================================
        ModelDisk::ORGANIZATIONS   => [
            'driver'     => 'local',
            'root'       => storage_path('app/models/Organization'),
            'url'        => env('APP_URL').'/storage/models/Organization',
            'visibility' => 'public',
        ],
        ModelDisk::USERS           => [
            'driver'     => 'local',
            'root'       => storage_path('app/models/User'),
            'url'        => env('APP_URL').'/storage/models/User',
            'visibility' => 'public',
        ],
        ModelDisk::NOTES           => [
            'driver' => 'local',
            'root'   => storage_path('app/models/Note'),
        ],
        ModelDisk::CHANGE_REQUESTS => [
            'driver' => 'local',
            'root'   => storage_path('app/models/ChangeRequest'),
        ],
        ModelDisk::QUOTE_REQUESTS  => [
            'driver' => 'local',
            'root'   => storage_path('app/models/QuoteRequest'),
        ],

        // Default
        // =====================================================================
        'local'                    => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],
        'public'                   => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
        's3'                       => [
            'driver'   => 's3',
            'key'      => env('AWS_ACCESS_KEY_ID'),
            'secret'   => env('AWS_SECRET_ACCESS_KEY'),
            'region'   => env('AWS_DEFAULT_REGION'),
            'bucket'   => env('AWS_BUCKET'),
            'url'      => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
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
