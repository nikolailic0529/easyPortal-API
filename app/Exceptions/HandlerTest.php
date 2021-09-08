<?php declare(strict_types = 1);

namespace App\Exceptions;

use Exception;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Event;
use Mockery;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tests\TestCase;
use Throwable;

/**
 * @internal
 * @coversDefaultClass \App\Exceptions\Handler
 */
class HandlerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::exceptionContext
     */
    public function testExceptionContext(): void {
        $exception = new class('test') extends ApplicationException {
            public function __construct() {
                parent::__construct('');
            }

            /**
             * @inheritDoc
             */
            public function context(): array {
                return [1, 2, 3];
            }

            /**
             * @inheritDoc
             */
            public function getContext(): array {
                return [4, 5, 6];
            }
        };
        $handler   = new class() extends Handler {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function exceptionContext(Throwable $e): array {
                return parent::exceptionContext($e);
            }
        };

        $this->assertEquals([1, 2, 3, 4, 5, 6], $handler->exceptionContext($exception));
        $this->assertEquals([1, 2, 3, 4, 5, 6], $handler->getExceptionContext($exception));
    }

    /**
     * @covers ::report
     *
     * @dataProvider dataProviderReport
     *
     * @param array{level: string, channel: string, message: string, context: array<mixed>} $expected
     */
    public function testReport(array $expected, Throwable $exception): void {
        Event::fake(ErrorReport::class);

        $handler = $this->app->make(Handler::class);
        $context = null;
        $logger  = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('log')
            ->with($expected['level'], $expected['message'], Mockery::any())
            ->once()
            ->andReturnUsing(static function (mixed $level, string $message, array $c) use (&$context): void {
                $context = $c;
            });

        if ($exception instanceof ApplicationException) {
            $manager = Mockery::mock(LogManager::class);
            $manager
                ->shouldReceive('channel')
                ->with($expected['channel'])
                ->once()
                ->andReturn($logger);

            $logger = $manager;
        }

        $this->override('log', static function () use ($logger): mixed {
            return $logger;
        });

        $handler->report($exception);

        unset($context['stack'][0]['line']);
        unset($context['stack'][0]['trace']);

        $this->assertEquals($expected['context'], $context);

        Event::assertDispatched(ErrorReport::class);
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function dataProviderReport(): array {
        $exception   = new class('test') extends Exception {
            /**
             * @inheritDoc
             */
            public function context(): array {
                return [1, 2, 3];
            }
        };
        $application = new class('test') extends ApplicationException {
            public function __construct(string $message) {
                parent::__construct($message);
            }

            public function getLevel(): ?string {
                return LogLevel::ALERT;
            }

            public function getChannel(): ?string {
                return 'test';
            }

            /**
             * @inheritDoc
             */
            public function getContext(): array {
                return [1, 2, 3];
            }
        };

        return [
            'Exception'            => [
                [
                    'level'   => LogLevel::ERROR,
                    'channel' => null,
                    'message' => 'test',
                    'context' => [
                        'message' => 'Server Error.',
                        'stack'   => [
                            [
                                'class'   => $exception::class,
                                'message' => 'test',
                                'context' => [1, 2, 3],
                                'file'    => __FILE__,
                            ],
                        ],
                    ],
                ],
                $exception,
            ],
            'ApplicationException' => [
                [
                    'level'   => $application->getLevel(),
                    'channel' => $application->getChannel(),
                    'message' => $application->getMessage(),
                    'context' => [
                        'message' => 'Server Error.',
                        'stack'   => [
                            [
                                'class'   => $application::class,
                                'message' => 'test',
                                'context' => [1, 2, 3],
                                'file'    => __FILE__,
                            ],
                        ],
                    ],
                ],
                $application,
            ],
        ];
    }
    // </editor-fold>
}
