<?php declare(strict_types = 1);

use App\Exceptions\Configurator;
use App\Exceptions\Handlers\MailableHandler;
use App\Services\Auth\Service as AuthService;
use App\Services\DataLoader\Service as DataLoaderService;
use App\Services\KeyCloak\Service as KeyCloakService;
use App\Services\Settings\Settings;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

// Default Settings
$tap   = [Configurator::class];
$days  = 365;
$level = env('LOG_LEVEL', 'debug');

// Helpers
$mailChannel    = static function (string $channel = null, string $recipients = null) use ($tap, $level): ?array {
    return env('EP_LOG_EMAIL_ENABLED') ? [
        'driver'    => 'monolog',
        'handler'   => MailableHandler::class,
        'formatter' => HtmlFormatter::class,
        'level'     => env('EP_LOG_EMAIL_LEVEL') ?: $level,
        'with'      => [
            'channel'    => $channel,
            'recipients' => explode(
                Settings::DELIMITER,
                (string) ($recipients ?: env('EP_LOG_EMAIL_RECIPIENTS')),
            ),
        ],
        'tap'       => $tap,
    ] : null;
};

$sentryChannel  = static function (string $channel = null) use ($tap, $level): ?array {
    return env('EP_LOG_SENTRY_ENABLED') ? [
        'driver' => 'sentry',
        'level'  => env('EP_LOG_SENTRY_LEVEL') ?: $level,
        'name'   => $channel,
        'tap'    => $tap,
    ] : null;
};

$serviceChannel = static function (
    string $service,
    string $recipients,
) use (
    $tap,
    $days,
    $level,
    $mailChannel,
    $sentryChannel,
): array {
    $channel  = array_slice(explode('\\', $service), -2, 1);
    $channel  = reset($channel);
    $channels = array_filter([
        "{$service}@daily"  => [
            'driver' => 'daily',
            'path'   => storage_path("logs/{$channel}/EAP-{$channel}.log"),
            'level'  => $level,
            'days'   => $days,
            'tap'    => $tap,
        ],
        "{$service}@mail"   => $mailChannel($channel, $recipients),
        "{$service}@sentry" => $sentryChannel($channel),
    ]);

    return array_merge(
        [
            "{$service}" => [
                'driver'   => 'stack',
                'channels' => array_keys($channels),
            ],
        ],
        $channels,
    );
};

// Settings
$channels = [
    'daily'  => [
        'driver' => 'daily',
        'path'   => storage_path('logs/laravel.log'),
        'level'  => $level,
        'days'   => $days,
        'tap'    => $tap,
    ],
    'mail'   => $mailChannel(),
    'sentry' => $sentryChannel(),
];

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
        $serviceChannel(AuthService::class, (string) env('EP_AUTH_LOG_EMAIL_RECIPIENTS')),
        $serviceChannel(DataLoaderService::class, (string) env('EP_DATA_LOADER_LOG_EMAIL_RECIPIENTS')),
        $serviceChannel(KeyCloakService::class, (string) env('EP_KEYCLOAK_LOG_EMAIL_RECIPIENTS')),
        $channels,
        [
            'stack'      => [
                'driver'   => 'stack',
                'channels' => array_keys($channels),
                'tap'      => $tap,
            ],

            'single'     => [
                'driver' => 'single',
                'path'   => storage_path('logs/laravel.log'),
                'level'  => $level,
                'tap'    => $tap,
            ],

            // Default
            'slack'      => [
                'driver'   => 'slack',
                'url'      => env('LOG_SLACK_WEBHOOK_URL'),
                'username' => 'Laravel Log',
                'emoji'    => ':boom:',
                'level'    => env('LOG_LEVEL', 'critical'),
                'tap'      => $tap,
            ],

            'papertrail' => [
                'driver'       => 'monolog',
                'level'        => $level,
                'handler'      => SyslogUdpHandler::class,
                'handler_with' => [
                    'host' => env('PAPERTRAIL_URL'),
                    'port' => env('PAPERTRAIL_PORT'),
                ],
                'tap'          => $tap,
            ],

            'stderr'     => [
                'driver'    => 'monolog',
                'handler'   => StreamHandler::class,
                'formatter' => env('LOG_STDERR_FORMATTER'),
                'with'      => [
                    'stream' => 'php://stderr',
                ],
                'tap'       => $tap,
            ],

            'syslog'     => [
                'driver' => 'syslog',
                'level'  => $level,
                'tap'    => $tap,
            ],

            'errorlog'   => [
                'driver' => 'errorlog',
                'level'  => $level,
                'tap'    => $tap,
            ],

            'null'       => [
                'driver'  => 'monolog',
                'handler' => NullHandler::class,
            ],

            // Emergency
            'emergency'  => [
                'driver' => 'single',
                'path'   => storage_path('logs/laravel.log'),
            ],
        ],
    ),
];
