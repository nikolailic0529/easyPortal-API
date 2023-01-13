<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\Exceptions\Contracts\GenericException;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Mockery;
use Nuwave\Lighthouse\Exceptions\RendersErrorsExtensions;
use Psr\Log\LogLevel;
use Tests\TestCase;
use Tests\WithSettings;
use Throwable;

/**
 * @internal
 * @covers \App\Exceptions\Handler
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class HandlerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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

        self::assertEquals([1, 2, 3, 'a', 4, 5, 6], $handler->exceptionContext($exception));
    }

    /**
     * @dataProvider dataProviderReport
     *
     * @param array{level: string, channel: string, message: string, context: array<mixed>} $expected
     * @param SettingsFactory                                                               $settingsFactory
     */
    public function testReport(array $expected, mixed $settingsFactory, Throwable $exception): void {
        Event::fake(ErrorReport::class);

        $this->setSettings($settingsFactory);

        $config  = $this->app->make(Repository::class);
        $handler = $this->app->make(Handler::class);
        $context = null;
        $logger  = Mockery::mock(LogManager::class);
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

        $this->instance('log', $logger);

        $handler->report($exception);

        unset($context['stacktrace'][0]['line']);
        unset($context['stacktrace'][0]['trace']);

        self::assertEquals($expected['context'], $context);

        Event::assertDispatched(ErrorReport::class);
    }

    public function testGetExceptionStacktrace(): void {
        $a       = (static function (): Exception {
            return new Exception('a');
        })();
        $b       = new Exception('b', 0, $a);
        $handler = new class() extends Handler {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getExceptionStacktrace(Throwable $exception): array {
                return parent::getExceptionStacktrace($exception);
            }
        };
        $trace   = $handler->getExceptionStacktrace($b);

        self::assertEquals([
            [
                'class'   => $b::class,
                'message' => $b->getMessage(),
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

    public function testGetExceptionContext(): void {
        $a       = (static function (): Exception {
            return new class('a') extends Exception {
                /**
                 * @return array<mixed>
                 */
                public function context(): array {
                    return [
                        'a' => 'exception',
                    ];
                }
            };
        })();
        $b       = new class('b', 0, $a) extends Exception {
            /**
             * @return array<mixed>
             */
            public function context(): array {
                return [
                    'b' => 'exception',
                ];
            }
        };
        $handler = new class() extends Handler {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getExceptionContext(Throwable $exception): array {
                return parent::getExceptionContext($exception);
            }
        };
        $context = $handler->getExceptionContext($b);

        self::assertEquals([
            [
                'class'   => $b::class,
                'message' => $b->getMessage(),
                'context' => [
                    'b' => 'exception',
                ],
                'level'   => 'error',
            ],
            [
                'class'   => $a::class,
                'message' => $a->getMessage(),
                'context' => [
                    'a' => 'exception',
                ],
                'level'   => 'error',
            ],
        ], $context);
    }

    /**
     * @dataProvider dataProviderGetExceptionFingerprint
     *
     * @param array<string> $expected
     */
    public function testGetExceptionFingerprint(array $expected, Throwable $exception): void {
        $handler = new class() extends Handler {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getExceptionFingerprint(Throwable $exception): array {
                return parent::getExceptionFingerprint($exception);
            }
        };

        self::assertEquals($expected, $handler->getExceptionFingerprint($exception));
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
                                'level'   => 'error',
                            ],
                        ],
                        'stacktrace'  => [
                            [
                                'class'   => $exception::class,
                                'message' => 'test',
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
                                'level'   => 'alert',
                            ],
                        ],
                        'stacktrace'  => [
                            [
                                'class'   => $application::class,
                                'message' => 'test',
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
                                'level'   => 'alert',
                            ],
                        ],
                        'stacktrace'  => [
                            [
                                'class'   => $application::class,
                                'message' => 'test',
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

    /**
     * @return array<string, mixed>
     */
    public function dataProviderGetExceptionFingerprint(): array {
        $a = new class('a') extends Exception {
            // empty
        };
        $b = new class('b', 0, $a) extends Exception implements GenericException {
            // empty
        };
        $c = new class('c', 0, $b) extends Exception {
            // empty
        };

        return [
            'a' => [
                [
                    $a::class,
                    $a->getMessage(),
                ],
                $a,
            ],
            'b' => [
                [
                    $b::class,
                    $b->getMessage(),
                    $a::class,
                    $a->getMessage(),
                ],
                $b,
            ],
            'c' => [
                [
                    $c::class,
                    $c->getMessage(),
                ],
                $c,
            ],
        ];
    }
    // </editor-fold>
}
