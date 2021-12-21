<?php declare(strict_types = 1);

namespace App\Exceptions\Handlers;

use Exception;
use PHPUnit\Framework\TestCase;
use Sentry\Breadcrumb;
use Sentry\ExceptionDataBag;

use function reset;

/**
 * @coversDefaultClass \App\Exceptions\Handlers\SentryHandler
 */
class SentryHandlerTest extends TestCase {
    /**
     * @covers ::getContextBreadcrumbs
     */
    public function testGetContextBreadcrumbs(): void {
        $handler   = new class() extends SentryHandler {
            /**
             * @inheritDoc
             */
            public static function getContextExceptions(mixed $stacktrace): array {
                return parent::getContextExceptions($stacktrace);
            }
        };
        $exception = new Exception('test');
        $context   = [
            [
                'class'   => $exception::class,
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTrace(),
            ],
        ];
        $actual    = $handler::getContextExceptions($context);

        $this->assertInstanceOf(ExceptionDataBag::class, reset($actual));
    }

    /**
     * @covers ::getContextExceptions
     */
    public function testGetContextExceptions(): void {
        $handler = new class() extends SentryHandler {
            /**
             * @inheritDoc
             */
            public static function getContextBreadcrumbs(mixed $context): array {
                return parent::getContextBreadcrumbs($context);
            }
        };
        $context = [
            [
                'class'   => $this::class,
                'level'   => 'error',
                'message' => 'message',
                'context' => [
                    'key' => 'value',
                ],
            ],
        ];
        $actual  = $handler::getContextBreadcrumbs($context);

        $this->assertInstanceOf(Breadcrumb::class, reset($actual));
    }
}
