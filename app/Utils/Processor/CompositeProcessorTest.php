<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\Contracts\Processor;
use Closure;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Tests\TestCase;
use Throwable;

use function array_fill;
use function array_map;
use function ceil;
use function count;
use function range;

/**
 * @internal
 * @covers \App\Utils\Processor\CompositeProcessor
 */
class CompositeProcessorTest extends TestCase {
    public function testInvoke(): void {
        $exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $dispatcher       = Mockery::mock(Dispatcher::class);
        $config           = $this->app->make(Repository::class);
        $chunk            = $this->faker->numberBetween(1, 5);
        $countA           = $this->faker->numberBetween(1, 5);
        $nestedA          = new CompositeProcessorTest__Processor($exceptionHandler, $dispatcher, $config, range(
            1,
            $countA,
        ));
        $handlerA         = null;
        $countB           = $this->faker->numberBetween(1, 5);
        $nestedB          = new CompositeProcessorTest__Processor($exceptionHandler, $dispatcher, $config, range(
            1,
            $countB,
        ));
        $handlerB         = Mockery::spy(static function (): void {
            // empty
        });
        $countC           = $this->faker->numberBetween(1, 5);
        $nestedC          = new CompositeProcessorTest__Processor(
            $exceptionHandler,
            $dispatcher,
            $config,
            array_map(
                static function (int $index): Exception {
                    return new Exception("{$index}");
                },
                range(1, $countC),
            ),
        );
        $handlerC         = Mockery::spy(static function (): void {
            // empty
        });
        $operations       = [
            new CompositeOperation(
                'A',
                static function () use ($nestedA): Processor {
                    return $nestedA;
                },
                $handlerA,
            ),
            new CompositeOperation(
                'B',
                static function () use ($nestedB): Processor {
                    return $nestedB;
                },
                Closure::fromCallable($handlerB),
            ),
            new CompositeOperation(
                'C',
                static function () use ($nestedC): Processor {
                    return $nestedC;
                },
                Closure::fromCallable($handlerC),
            ),
        ];

        $processor = Mockery::mock(CompositeProcessor::class, [$exceptionHandler, $dispatcher, $config]);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getOperations')
            ->twice()
            ->andReturn($operations);

        $finish    = null;
        $onInit    = Mockery::spy(static function (): void {
            // empty
        });
        $onChange  = Mockery::spy(static function (): void {
            // empty
        });
        $onFinish  = Mockery::spy(static function (State $state) use (&$finish): void {
            $finish = $state;
        });
        $onProcess = Mockery::spy(static function (State $state): void {
            // empty
        });
        $onReport  = Mockery::spy(static function (State $state): void {
            // empty
        });

        $processor
            ->onInit(Closure::fromCallable($onInit))
            ->onChange(Closure::fromCallable($onChange))
            ->onFinish(Closure::fromCallable($onFinish))
            ->onProcess(Closure::fromCallable($onProcess))
            ->onReport(Closure::fromCallable($onReport))
            ->setChunkSize($chunk)
            ->start();

        self::assertEquals(
            new CompositeState([
                'offset'     => count($operations),
                'index'      => count($operations),
                'limit'      => null,
                'total'      => count($operations),
                'processed'  => count($operations),
                'success'    => count($operations),
                'failed'     => 0,
                'operations' => [
                    [
                        'offset'    => $countA,
                        'index'     => $countA,
                        'limit'     => null,
                        'total'     => $countA,
                        'processed' => $countA,
                        'success'   => $countA,
                        'failed'    => 0,
                    ],
                    [
                        'offset'    => $countB,
                        'index'     => $countB,
                        'limit'     => null,
                        'total'     => $countB,
                        'processed' => $countB,
                        'success'   => $countB,
                        'failed'    => 0,
                    ],
                    [
                        'offset'    => $countC,
                        'index'     => $countC,
                        'limit'     => null,
                        'total'     => $countC,
                        'processed' => $countC,
                        'success'   => 0,
                        'failed'    => $countC,
                    ],
                ],
            ]),
            $finish,
        );

        $onInit
            ->shouldHaveBeenCalled()
            ->once();
        $onChange
            ->shouldHaveBeenCalled()
            ->times(
                0
                + count($operations)
                + (int) ceil($countA / $chunk)
                + (int) ceil($countB / $chunk)
                + (int) ceil($countC / $chunk),
            );
        $onFinish
            ->shouldHaveBeenCalled()
            ->once();
        $onProcess
            ->shouldHaveBeenCalled()
            ->times(count($operations) + $countA + $countB);
        $onReport
            ->shouldHaveBeenCalled()
            ->times($countC);

        $handlerB
            ->shouldHaveBeenCalled()
            ->with(Mockery::any(), true)
            ->once();
        $handlerC
            ->shouldHaveBeenCalled()
            ->with(Mockery::any(), false)
            ->once();
    }

    public function testInvokeStop(): void {
        $exceptionHandler = Mockery::mock(ExceptionHandler::class);
        $dispatcher       = Mockery::mock(Dispatcher::class);
        $config           = $this->app->make(Repository::class);
        $chunk            = $this->faker->numberBetween(1, 5);
        $count            = $this->faker->numberBetween(5, 10);
        $nested           = new CompositeProcessorTest__Processor($exceptionHandler, $dispatcher, $config, range(
            1,
            $count,
        ));
        $handler          = Mockery::spy(static function (): void {
            // empty
        });
        $operations       = [
            new CompositeOperation(
                'A',
                static function () use ($nested): Processor {
                    return $nested;
                },
                Closure::fromCallable($handler),
            ),
        ];

        $processor = Mockery::mock(CompositeProcessor::class, [$exceptionHandler, $dispatcher, $config]);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getOperations')
            ->twice()
            ->andReturn($operations);

        $onInit    = Mockery::spy(static function (): void {
            // empty
        });
        $onChange  = Mockery::spy(static function (): void {
            // empty
        });
        $onFinish  = Mockery::spy(static function (State $state): void {
            // empty
        });
        $onProcess = Mockery::spy(static function (State $state) use ($processor): void {
            $processor->stop();
        });
        $onReport  = Mockery::spy(static function (State $state): void {
            // empty
        });

        $processor
            ->onInit(Closure::fromCallable($onInit))
            ->onChange(Closure::fromCallable($onChange))
            ->onFinish(Closure::fromCallable($onFinish))
            ->onProcess(Closure::fromCallable($onProcess))
            ->onReport(Closure::fromCallable($onReport))
            ->setChunkSize($chunk)
            ->start();

        $onInit
            ->shouldHaveBeenCalled()
            ->once();
        $onChange
            ->shouldHaveBeenCalled()
            ->times(1);
        $onFinish
            ->shouldNotHaveBeenCalled();
        $onProcess
            ->shouldHaveBeenCalled()
            ->times($chunk);
        $onReport
            ->shouldNotHaveBeenCalled();

        $handler
            ->shouldNotHaveBeenCalled();
    }

    public function testGetTotal(): void {
        $count     = $this->faker->numberBetween(1, 5);
        $state     = Mockery::mock(CompositeState::class);
        $processor = new class($count) extends CompositeProcessor {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                private int $count,
            ) {
                // empty
            }

            /**
             * @inheritDoc
             */
            protected function getOperations(CompositeState $state): array {
                return array_fill(0, $this->count, Mockery::mock(CompositeOperation::class));
            }

            public function getTotal(State $state): ?int {
                return parent::getTotal($state);
            }
        };

        self::assertEquals($count, $processor->getTotal($state));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends IteratorProcessor<mixed,mixed,State>
 */
class CompositeProcessorTest__Processor extends IteratorProcessor {
    /**
     * @param array<mixed> $items
     */
    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        Repository $config,
        protected array $items,
    ) {
        parent::__construct($exceptionHandler, $dispatcher, $config);
    }

    protected function getIterator(State $state): ObjectIterator {
        return new ObjectsIterator($this->items);
    }

    protected function process(State $state, mixed $data, mixed $item): void {
        if ($item instanceof Exception) {
            throw $item;
        }
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return null;
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        // empty
    }

    protected function getTotal(State $state): ?int {
        return count($this->items);
    }
}
