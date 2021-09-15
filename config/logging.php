<?php declare(strict_types = 1);

use App\Exceptions\Configurator;
use App\Exceptions\MailableHandler;
use App\Services\Auth\Service as AuthService;
use App\Services\DataLoader\Service as DataLoaderService;
use App\Services\KeyCloak\Service as KeyCloakService;
use App\Services\Settings\Settings;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

// Default Settings
$tap  = [Configurator::class];
$days = 365;

// Helpers
$serviceChannel = static function (string $service, string $recipients) use ($tap, $days): array {
    $channel = array_slice(explode('\\', $service), -2, 1);
    $channel = reset($channel);

    return [
        "{$service}"       => [
            'driver'            => 'stack',
            'channels'          => [
                "{$service}@daily",
                "{$service}@mail",
            ],
            'ignore_exceptions' => false,
        ],
        "{$service}@daily" => [
            'driver' => 'daily',
            'path'   => storage_path("logs/{$channel}/EAP-{$channel}.log"),
            'level'  => env('LOG_LEVEL', 'debug'),
            'days'   => $days,
            'tap'    => $tap,
        ],
        "{$service}@mail"  => [
            'driver'    => 'monolog',
            'handler'   => MailableHandler::class,
            'formatter' => HtmlFormatter::class,
            'level'     => env('LOG_LEVEL', 'debug'),
            'with'      => [
                'channel'    => $channel,
                'recipients' => explode(Settings::DELIMITER, $recipients),
            ],
        ],
    ];
};

// Settings
return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default'  => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => array_merge(
        $serviceChannel(AuthService::class, (string) env('EP_AUTH_LOGGING_EMAILS')),
        $serviceChannel(DataLoaderService::class, (string) env('EP_DATA_LOADER_LOGGING_EMAILS')),
        $serviceChannel(KeyCloakService::class, (string) env('EP_KEYCLOAK_LOGGING_EMAILS')),
        [
            'stack'      => [
                'driver'            => 'stack',
                'channels'          => ['daily'],
                'ignore_exceptions' => false,
            ],

            'single'     => [
                'driver' => 'single',
                'path'   => storage_path('logs/laravel.log'),
                'level'  => env('LOG_LEVEL', 'debug'),
                'tap'    => $tap,
            ],

            'daily'      => [
                'driver' => 'daily',
                'path'   => storage_path('logs/laravel.log'),
                'level'  => env('LOG_LEVEL', 'debug'),
                'days'   => $days,
                'tap'    => $tap,
            ],

            // Default
            'slack'      => [
                'driver'   => 'slack',
                'url'      => env('LOG_SLACK_WEBHOOK_URL'),
                'username' => 'Laravel Log',
                'emoji'    => ':boom:',
                'level'    => env('LOG_LEVEL', 'critical'),
            ],

            'papertrail' => [
                'driver'       => 'monolog',
                'level'        => env('LOG_LEVEL', 'debug'),
                'handler'      => SyslogUdpHandler::class,
                'handler_with' => [
                    'host' => env('PAPERTRAIL_URL'),
                    'port' => env('PAPERTRAIL_PORT'),
                ],
            ],

            'stderr'     => [
                'driver'    => 'monolog',
                'handler'   => StreamHandler::class,
                'formatter' => env('LOG_STDERR_FORMATTER'),
                'with'      => [
                    'stream' => 'php://stderr',
                ],
            ],

            'syslog'     => [
                'driver' => 'syslog',
                'level'  => env('LOG_LEVEL', 'debug'),
            ],

            'errorlog'   => [
                'driver' => 'errorlog',
                'level'  => env('LOG_LEVEL', 'debug'),
            ],

            'null'       => [
                'driver'  => 'monolog',
                'handler' => NullHandler::class,
            ],

            // Emergency
            'emergency'  => [
                'path' => storage_path('logs/laravel.log'),
            ],
        ],
    ),
];
