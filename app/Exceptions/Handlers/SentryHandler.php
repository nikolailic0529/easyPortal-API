<?php declare(strict_types = 1);

namespace App\Exceptions\Handlers;

use App\Services\Maintenance\ApplicationInfo;
use Illuminate\Config\Repository;
use ReflectionClass;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\ExceptionDataBag;
use Sentry\ExceptionMechanism;
use Sentry\Options;
use Sentry\SentrySdk;
use Sentry\Serializer\RepresentationSerializer;
use Sentry\StacktraceBuilder;
use Throwable;

use function app;
use function array_is_list;
use function array_merge;
use function in_array;
use function is_a;
use function is_array;
use function strtolower;

class SentryHandler {
    protected static string $release;

    public static function beforeSend(Event $event, ?EventHint $hint): ?Event {
        // Ignored?
        if ($hint && $hint->exception && static::isIgnoredException($hint->exception)) {
            return null;
        }

        // Prepare
        $key   = 'log_context';
        $extra = $event->getExtra();

        // Breadcrumbs?
        $breadcrumbs = static::getContextBreadcrumbs($extra[$key]['context'] ?? null);

        if ($breadcrumbs) {
            $event->setBreadcrumb(array_merge(
                $event->getBreadcrumbs(),
                $breadcrumbs,
            ));

            unset($extra[$key]['context']);
        }

        // Exceptions?
        $exceptions = static::getContextExceptions($extra[$key]['stacktrace'] ?? null);

        if ($exceptions) {
            $event->setExceptions(array_merge(
                $event->getExceptions(),
                $exceptions,
            ));

            unset($extra[$key]['stacktrace']);
        }

        // Cleanup
        unset($extra[$key]['tags']);

        if (isset($extra[$key]['context']) && !$extra[$key]['context']) {
            unset($extra[$key]['context']);
        }

        // Update
        $event->setExtra($extra);

        // Release
        $event->setRelease(static::getRelease());

        // Return
        return $event;
    }

    /**
     * @return array<ExceptionDataBag>
     */
    protected static function getContextExceptions(mixed $stacktrace): array {
        // Empty?
        if (!$stacktrace) {
            return [];
        }

        // Convert
        $options    = SentrySdk::getCurrentHub()->getClient()?->getOptions() ?? new Options();
        $builder    = new StacktraceBuilder($options, new RepresentationSerializer($options));
        $exceptions = [];

        foreach ($stacktrace as $item) {
            $exception = (new ReflectionClass($item['class']))->newInstanceWithoutConstructor();
            $mechanism = new ExceptionMechanism(ExceptionMechanism::TYPE_GENERIC, true);
            $trace     = $builder->buildFromBacktrace($item['trace'], $item['file'], $item['line']);
            $bag       = new ExceptionDataBag($exception, $trace, $mechanism);

            $bag->setValue($item['message']);

            $exceptions[] = $bag;
        }

        // Return
        return $exceptions;
    }

    /**
     * @see \App\Exceptions\Handler::getExceptionContext()
     *
     * @return array<Breadcrumb>
     */
    protected static function getContextBreadcrumbs(mixed $context): array {
        // Breadcrumbs?
        if (!is_array($context) || !array_is_list($context) || !is_array($context[0] ?? null)) {
            return [];
        }

        // Convert
        $breadcrumbs = [];

        foreach ($context as $item) {
            $isException   = is_a($item['class'] ?? null, Throwable::class, true);
            $breadcrumbs[] = new Breadcrumb(
                static::getBreadcrumbLevel($item['level'] ?? null),
                $isException ? Breadcrumb::TYPE_ERROR : Breadcrumb::TYPE_DEFAULT,
                'context',
                null,
                $item,
            );
        }

        // Return
        return $breadcrumbs;
    }

    /**
     * @see \Sentry\Laravel\EventHandler::logLevelToBreadcrumbLevel()
     */
    protected static function getBreadcrumbLevel(?string $level): string {
        switch (strtolower((string) $level)) {
            case 'debug':
                return Breadcrumb::LEVEL_DEBUG;
            case 'warning':
                return Breadcrumb::LEVEL_WARNING;
            case 'error':
                return Breadcrumb::LEVEL_ERROR;
            case 'critical':
            case 'alert':
            case 'emergency':
                return Breadcrumb::LEVEL_FATAL;
            case 'info':
            case 'notice':
            default:
                return Breadcrumb::LEVEL_INFO;
        }
    }

    protected static function getRelease(): string {
        if (!isset(static::$release)) {
            $info            = app()->make(ApplicationInfo::class);
            $package         = $info->getName();
            $version         = $info->getVersion();
            static::$release = "{$package}@{$version}";
        }

        return static::$release;
    }

    protected static function isIgnoredException(Throwable $exception): bool {
        $config    = app()->make(Repository::class);
        $ignored   = (array) $config->get('ep.log.sentry.ignored_exceptions') ?: [];
        $isIgnored = in_array($exception::class, $ignored, true);

        return $isIgnored;
    }
}
