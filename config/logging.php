<?php declare(strict_types = 1);

use App\Exceptions\Configurator;
use App\Exceptions\Handlers\MailableHandler;
use App\Services\Auth\Service as AuthService;
use App\Services\DataLoader\Service as DataLoaderService;
use App\Services\Keycloak\Service as KeycloakService;
use App\Services\Search\Service as SearchService;
use App\Services\Settings\Settings;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

// Default Settings
$tap      = [Configurator::class];
$days     = 365;
$logLevel = env('LOG_LEVEL', 'debug');

// Helpers
$mailChannel = static function (
    string $channel = null,
    string $recipients = null,
    string $level = null,
) use (
    $tap,
    $logLevel
): ?array {
    return env('EP_LOG_EMAIL_ENABLED') ? [
        'name'      => $channel,
        'driver'    => 'monolog',
        'handler'   => MailableHandler::class,
        'formatter' => HtmlFormatter::class,
        'level'     => $level ?: env('EP_LOG_EMAIL_LEVEL') ?: $logLevel,
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

$sentryChannel = static function (string $channel = null, string $level = null) use ($tap, $logLevel): ?array {
    return env('EP_LOG_SENTRY_ENABLED') ? [
        'name'   => $channel,
        'driver' => 'sentry',
        'level'  => $level ?: env('EP_LOG_SENTRY_LEVEL') ?: $logLevel,
        'tap'    => $tap,
    ] : null;
};

$serviceChannel = static function (
    string $service,
    string $recipients,
    string $level = null,
) use (
    $tap,
    $days,
    $logLevel,
    $mailChannel,
    $sentryChannel,
): array {
    $env      = env('APP_ENV', 'production');
    $base     = array_slice(explode('\\', $service), -2, 1);
    $base     = reset($base);
    $name     = "{$env}.{$base}";
    $channels = array_filter([
        "{$service}@daily"  => [
            'driver' => 'daily',
            'path'   => storage_path("logs/{$base}/EAP-{$base}.log"),
            'level'  => $level ?: $logLevel,
            'days'   => $days,
            'tap'    => $tap,
        ],
        "{$service}@mail"   => $mailChannel($name, $recipients, $level),
        "{$service}@sentry" => $sentryChannel($name, $level),
    ]);

    return array_merge(
        [
            "{$service}" => [
                'name'     => $name,
                'driver'   => 'stack',
                'channels' => array_keys($channels),
            ],
        ],
        $channels,
    );
};

// Settings
$channels = array_filter([
    'daily'  => [
        'driver' => 'daily',
        'path'   => storage_path('logs/laravel.log'),
        'level'  => $logLevel,
        'days'   => $days,
        'tap'    => $tap,
    ],
    'mail'   => $mailChannel(),
    'sentry' => $sentryChannel(),
]);

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

    'default'      => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

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

    'channels'     => array_merge(
        $serviceChannel(AuthService::class, (string) env('EP_AUTH_LOG_EMAIL_RECIPIENTS')),
        $serviceChannel(DataLoaderService::class, (string) env('EP_DATA_LOADER_LOG_EMAIL_RECIPIENTS')),
        $serviceChannel(KeycloakService::class, (string) env('EP_KEYCLOAK_LOG_EMAIL_RECIPIENTS')),
        $serviceChannel(
            SearchService::class,
            (string) env('EP_SEARCH_LOG_EMAIL_RECIPIENTS'),
            env('EP_SEARCH_LOG_LEVEL'),
        ),
        $channels,
        [
            'stack'        => [
                'driver'            => 'stack',
                'channels'          => array_keys($channels),
                'tap'               => $tap,
                'ignore_exceptions' => false,
            ],

            'deprecations' => [
                'driver' => 'daily',
                'path'   => storage_path('logs/deprecations.log'),
                'level'  => 'debug',
                'days'   => 3,
                'tap'    => $tap,
            ],

            // Default
            'single'       => [
                'driver' => 'single',
                'path'   => storage_path('logs/laravel.log'),
                'level'  => $logLevel,
                'tap'    => $tap,
            ],

            'slack'        => [
                'driver'   => 'slack',
                'url'      => env('LOG_SLACK_WEBHOOK_URL'),
                'username' => 'Laravel Log',
                'emoji'    => ':boom:',
                'level'    => env('LOG_LEVEL', 'critical'),
                'tap'      => $tap,
            ],

            'papertrail'   => [
                'driver'       => 'monolog',
                'level'        => $logLevel,
                'handler'      => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
                'handler_with' => [
                    'host'             => env('PAPERTRAIL_URL'),
                    'port'             => env('PAPERTRAIL_PORT'),
                    'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
                ],
                'tap'          => $tap,
            ],

            'stderr'       => [
                'driver'    => 'monolog',
                'level'     => $logLevel,
                'handler'   => StreamHandler::class,
                'formatter' => env('LOG_STDERR_FORMATTER'),
                'with'      => [
                    'stream' => 'php://stderr',
                ],
                'tap'       => $tap,
            ],

            'syslog'       => [
                'driver' => 'syslog',
                'level'  => $logLevel,
                'tap'    => $tap,
            ],

            'errorlog'     => [
                'driver' => 'errorlog',
                'level'  => $logLevel,
                'tap'    => $tap,
            ],

            'null'         => [
                'driver'  => 'monolog',
                'handler' => NullHandler::class,
            ],

            // Emergency
            'emergency'    => [
                'path' => storage_path('logs/laravel.log'),
            ],
        ],
    ),
];
