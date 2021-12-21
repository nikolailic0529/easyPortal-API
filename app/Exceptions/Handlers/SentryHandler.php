<?php declare(strict_types = 1);

namespace App\Exceptions\Handlers;

use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Throwable;

use function array_is_list;
use function array_merge;
use function is_a;
use function is_array;
use function strtolower;

class SentryHandler {
    public static function beforeSend(Event $event, ?EventHint $hint): Event {
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

        // Cleanup
        unset($extra[$key]['tags']);

        if (isset($extra[$key]['context']) && !$extra[$key]['context']) {
            unset($extra[$key]['context']);
        }

        // Update
        $event->setExtra($extra);

        // Return
        return $event;
    }

    /**
     * @see \App\Exceptions\Handler::getExceptionContext()
     *
     * @return array<\Sentry\Breadcrumb>
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
}
