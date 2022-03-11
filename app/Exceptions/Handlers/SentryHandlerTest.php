<?php declare(strict_types = 1);

namespace App\Exceptions\Handlers;

use App\Services\Maintenance\ApplicationInfo;
use Exception;
use Mockery\MockInterface;
use Sentry\Breadcrumb;
use Sentry\ExceptionDataBag;
use Tests\TestCase;

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

    /**
     * @covers ::getRelease
     */
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

        $this->assertEquals('package@1.2.3', $handler::getRelease());
        $this->assertEquals('package@1.2.3', $handler::getRelease()); // should be cached
    }
}
