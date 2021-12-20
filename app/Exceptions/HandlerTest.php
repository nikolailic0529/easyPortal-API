<?php declare(strict_types = 1);

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Mockery;
use Nuwave\Lighthouse\Exceptions\RendersErrorsExtensions;
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
        $exception = new class('test') extends ApplicationException implements RendersErrorsExtensions {
            public function __construct(string $message) {
                parent::__construct($message);
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

            public function isClientSafe(): mixed {
                return false;
            }

            public function getCategory(): string {
                return __FUNCTION__;
            }

            /**
             * @inheritDoc
             */
            public function extensionsContent(): array {
                return ['a'];
            }
        };
        $handler   = new class($this->app) extends Handler {
            /**
             * @inheritDoc
             */
            public function exceptionContext(Throwable $e): array {
                return parent::exceptionContext($e);
            }
        };

        $this->assertEquals([1, 2, 3, 'a', 4, 5, 6], $handler->exceptionContext($exception));
    }

    /**
     * @covers ::report
     *
     * @dataProvider dataProviderReport
     *
     * @param array{level: string, channel: string, message: string, context: array<mixed>} $expected
     * @param array<string, mixed>                                                          $settings
     */
    public function testReport(array $expected, array $settings, Throwable $exception): void {
        Event::fake(ErrorReport::class);

        $this->setSettings($settings);

        $config  = $this->app->make(Repository::class);
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
            if ($config->get("logging.channels.{$expected['channel']}")) {
                $manager = Mockery::mock(LogManager::class);
                $manager
                    ->shouldReceive('channel')
                    ->with($expected['channel'])
                    ->once()
                    ->andReturn($logger);

                $logger = $manager;
            } else {
                $logger
                    ->shouldReceive('channel')
                    ->never();
            }
        }

        $this->override('log', static function () use ($logger): mixed {
            return $logger;
        });

        $handler->report($exception);

        unset($context['context'][0]['line']);
        unset($context['context'][0]['trace']);

        $this->assertEquals($expected['context'], $context);

        Event::assertDispatched(ErrorReport::class);
    }

    /**
     * @covers ::getExceptionTrace
     */
    public function testGetExceptionTrace(): void {
        $a       = (static function (): Exception {
            return new Exception('a');
        })();
        $b       = new Exception('b', 0, $a);
        $handler = $this->app->make(Handler::class);
        $trace   = $handler->getExceptionTrace($b);

        $this->assertEquals([
            [
                'class'   => $b::class,
                'message' => $b->getMessage(),
                'context' => [],
                'code'    => $b->getCode(),
                'file'    => $b->getFile(),
                'line'    => $b->getLine(),
                'trace'   => (new Collection($b->getTrace()))
                    ->map(static function (array $trace): array {
                        return Arr::except($trace, ['args']);
                    })
                    ->all(),
            ],
            [
                'class'   => $a::class,
                'message' => $a->getMessage(),
                'context' => [],
                'code'    => $a->getCode(),
                'file'    => $a->getFile(),
                'line'    => $a->getLine(),
                'trace'   => (new Collection($a->getTrace()))
                    ->slice(0, 2)
                    ->map(static function (array $trace): array {
                        return Arr::except($trace, ['args']);
                    })
                    ->all(),
            ],
        ], $trace);
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
            'Exception'                              => [
                [
                    'level'   => LogLevel::ERROR,
                    'channel' => null,
                    'message' => 'test',
                    'context' => [
                        'message'     => 'Server Error.',
                        'tags'        => [],
                        'fingerprint' => [
                            $exception::class,
                            $exception->getMessage(),
                        ],
                        'context'     => [
                            [
                                'class'   => $exception::class,
                                'message' => 'test',
                                'context' => [1, 2, 3],
                                'file'    => __FILE__,
                                'code'    => 0,
                            ],
                        ],
                    ],
                ],
                [
                    // empty
                ],
                $exception,
            ],
            'ApplicationException + channel defined' => [
                [
                    'level'   => $application->getLevel(),
                    'channel' => $application->getChannel(),
                    'message' => $application->getMessage(),
                    'context' => [
                        'message'     => 'Server Error.',
                        'tags'        => [],
                        'fingerprint' => [
                            $application::class,
                            $application->getMessage(),
                        ],
                        'context'     => [
                            [
                                'class'   => $application::class,
                                'message' => 'test',
                                'context' => [1, 2, 3],
                                'file'    => __FILE__,
                                'code'    => 0,
                            ],
                        ],
                    ],
                ],
                [
                    "logging.channels.{$application->getChannel()}" => [''],
                ],
                $application,
            ],
            'ApplicationException + no channel'      => [
                [
                    'level'   => $application->getLevel(),
                    'channel' => $application->getChannel(),
                    'message' => $application->getMessage(),
                    'context' => [
                        'message'     => 'Server Error.',
                        'tags'        => [],
                        'fingerprint' => [
                            $application::class,
                            $application->getMessage(),
                        ],
                        'context'     => [
                            [
                                'class'   => $application::class,
                                'message' => 'test',
                                'context' => [1, 2, 3],
                                'file'    => __FILE__,
                                'code'    => 0,
                            ],
                        ],
                    ],
                ],
                [
                    // empty
                ],
                $application,
            ],
        ];
    }
    // </editor-fold>
}
