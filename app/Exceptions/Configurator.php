<?php declare(strict_types = 1);

namespace App\Exceptions;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FormattableHandlerInterface;

/**
 * @see https://laravel.com/docs/8.x/logging#customizing-monolog-for-channels
 */
class Configurator {
    public function __invoke(Logger $logger): void {
        foreach ($logger->getHandlers() as $handler) {
            // Possible?
            if (!($handler instanceof FormattableHandlerInterface)) {
                continue;
            }

            // Configure
            $formatter = $handler->getFormatter();

            if ($formatter instanceof LineFormatter) {
                // Stacktrace already dumped in Handler.
                $formatter->includeStacktraces(false);

                // Line breaks are not allowed in JSON
                $formatter->allowInlineLineBreaks(false);
            }
        }
    }
}
