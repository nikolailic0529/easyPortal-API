<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\Exceptions\Handlers\SafeHandler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Logger as MonologLogger;

/**
 * @see https://laravel.com/docs/8.x/logging#customizing-monolog-for-channels
 */
class Configurator {
    public function __construct(
        protected ExceptionHandler $handler,
    ) {
        // empty
    }

    public function __invoke(Logger|MonologLogger $logger): void {
        // Fix formatters settings
        foreach ($logger->getHandlers() as $handler) {
            // Possible?
            if (!($handler instanceof FormattableHandlerInterface)) {
                continue;
            }

            // Configure
            $formatter = $handler->getFormatter();

            if ($formatter instanceof NormalizerFormatter) {
                $formatter->setMaxNormalizeDepth(50);
            }

            if ($formatter instanceof LineFormatter) {
                // Stacktrace already dumped in Handler.
                $formatter->includeStacktraces(false);

                // Line breaks are not allowed in JSON
                $formatter->allowInlineLineBreaks(false);
            }
        }

        /**
         * By default, if the handler failed other handlers will not be called,
         * for some channels like `stack` there is `ignore_exceptions` setting
         * that will wrap handlers to {@link \Monolog\Handler\WhatFailureGroupHandler},
         * but in this case, the exception will be lost, so we will never know
         * that handler is broken. To avoid it we wrap handlers by our handler
         * that will log all errors.
         */
        $logger->setHandlers([
            new SafeHandler($this->handler, $logger->getHandlers()),
        ]);
    }
}
