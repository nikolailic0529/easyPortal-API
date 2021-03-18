<?php declare(strict_types = 1);

use App\Services\DataLoader\Jobs\LocationsCleanupCronJob;
use App\Services\DataLoader\Jobs\ResellersImporterCronJob;
use App\Services\DataLoader\Jobs\ResellersUpdaterCronJob;
use App\Services\DataLoader\Jobs\ResellerUpdate;
use App\Setting;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default'     => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync'       => [
            'driver' => 'sync',
        ],

        'database'   => [
            'driver'      => 'database',
            'table'       => 'jobs',
            'queue'       => 'default',
            'retry_after' => 90,
        ],

        'beanstalkd' => [
            'driver'      => 'beanstalkd',
            'host'        => 'localhost',
            'queue'       => 'default',
            'retry_after' => 90,
            'block_for'   => 0,
        ],

        'sqs'        => [
            'driver' => 'sqs',
            'key'    => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue'  => env('SQS_QUEUE', 'your-queue-name'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],

        'redis'      => [
            'driver'      => 'redis',
            'connection'  => 'default',
            'queue'       => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for'   => null,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed'      => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table'    => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Batches Queue Jobs
    |--------------------------------------------------------------------------
    */
    'batching'    => [
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queued Jobs Settings
    |--------------------------------------------------------------------------
    */
    'queueables'  => [
        ResellersImporterCronJob::class => [
            'enabled' => Setting::get('DATA_LOADER_RESELLERS_IMPORTER_ENABLED', 'DATA_LOADER_ENABLED'),
            'cron'    => Setting::get('DATA_LOADER_RESELLERS_IMPORTER_CRON'),
            'queue'   => Setting::get('DATA_LOADER_RESELLERS_IMPORTER_QUEUE'),
        ],
        ResellersUpdaterCronJob::class  => [
            'enabled'  => Setting::get('DATA_LOADER_RESELLERS_UPDATER_ENABLED', 'DATA_LOADER_ENABLED'),
            'cron'     => Setting::get('DATA_LOADER_RESELLERS_UPDATER_CRON'),
            'queue'    => Setting::get('DATA_LOADER_RESELLERS_UPDATER_QUEUE'),
            'settings' => [
                'expire' => Setting::get('DATA_LOADER_RESELLERS_UPDATER_EXPIRE'),
            ],
        ],
        LocationsCleanupCronJob::class  => [
            'enabled' => Setting::get('DATA_LOADER_LOCATIONS_CLEANUP_ENABLED', 'DATA_LOADER_ENABLED'),
            'cron'    => Setting::get('DATA_LOADER_LOCATIONS_CLEANUP_CRON'),
            'queue'   => Setting::get('DATA_LOADER_LOCATIONS_CLEANUP_QUEUE'),
        ],
        ResellerUpdate::class           => [
            'queue' => Setting::get('DATA_LOADER_RESELLER_UPDATE_QUEUE'),
        ],
    ],
];
