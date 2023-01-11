<?php declare(strict_types = 1);

namespace App\Exceptions\Handlers;

use App\Services\Maintenance\ApplicationInfo;
use Exception;
use Mockery\MockInterface;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\ExceptionDataBag;
use Tests\TestCase;
use Throwable;

use function reset;

/**
 * @covers \App\Exceptions\Handlers\SentryHandler
 */
class SentryHandlerTest extends TestCase {
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

        self::assertInstanceOf(ExceptionDataBag::class, reset($actual));
    }

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

        self::assertInstanceOf(Breadcrumb::class, reset($actual));
    }

    public function testGetRelease(): void {
        $this->override(ApplicationInfo::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getName')
                ->once()
                ->andReturn('package');
            $mock
                ->shouldReceive('getVersion')
                ->once()
                ->andReturn('1.2.3');
        });

        $handler = new class() extends SentryHandler {
            public static function getRelease(): string {
                return parent::getRelease();
            }
        };

        self::assertEquals('package@1.2.3', $handler::getRelease());
        self::assertEquals('package@1.2.3', $handler::getRelease()); // should be cached
    }

    public function testBeforeSendIgnoredException(): void {
        $this->setSettings([
            'ep.log.sentry.ignored_exceptions' => Exception::class,
        ]);

        $event = Event::createEvent();
        $hit   = EventHint::fromArray([
            'exception' => new Exception(),
        ]);

        self::assertNull(SentryHandler::beforeSend($event, $hit));
    }

    public function testIsIgnoredException(): void {
        $a = new Exception();
        $b = new class() extends Exception {
            // empty
        };

        $this->setSettings([
            'ep.log.sentry.ignored_exceptions' => $a::class,
        ]);

        $handler = new class() extends SentryHandler {
            public static function isIgnoredException(Throwable $exception): bool {
                return parent::isIgnoredException($exception);
            }
        };

        self::assertTrue($handler::isIgnoredException($a));
        self::assertFalse($handler::isIgnoredException($b));
    }
}
