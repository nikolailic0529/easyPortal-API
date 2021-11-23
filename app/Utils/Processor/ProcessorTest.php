<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\ObjectIterator;
use App\Utils\Iterators\OneChunkOffsetBasedObjectIterator;
use Closure;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use LogicException;
use Mockery;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Processor\Processor
 */
class ProcessorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::start
     * @covers ::prepare
     */
    public function testStart(): void {
        $state     = new State(['offset' => $this->faker->uuid]);
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getState')
            ->once()
            ->andReturn($state);
        $processor
            ->shouldReceive('run')
            ->withArgs(function (State $actual) use ($processor, $state): bool {
                $this->assertTrue($processor->isRunning());
                $this->assertFalse($processor->isStopped());
                $this->assertEquals($state, $actual);

                return true;
            })
            ->once()
            ->andReturns();

        $processor->start();
    }

    /**
     * @covers ::stop
     */
    public function testStop(): void {
        $processor = Mockery::mock(Processor::class);
        $iterator  = new OneChunkOffsetBasedObjectIterator(static function () use ($processor): array {
            $processor->stop();

            return [];
        });
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getState')
            ->once()
            ->andReturn(new State());
        $processor
            ->shouldReceive('getIterator')
            ->once()
            ->andReturn($iterator);
        $processor
            ->shouldReceive('init')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('finish')
            ->once()
            ->andReturnSelf();
        $processor
            ->shouldReceive('process')
            ->never();

        $processor->start();

        $this->assertTrue($processor->isStopped());
    }

    /**
     * @covers ::reset
     */
    public function testReset(): void {
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('resetState')
            ->once()
            ->andReturns();

        $processor->reset();
    }

    /**
     * @covers ::reset
     */
    public function testResetRunning(): void {
        $this->expectExceptionObject(new LogicException('Reset is not possible while running.'));

        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getState')
            ->once()
            ->andReturn(new State());
        $processor
            ->shouldReceive('prepare')
            ->once()
            ->andReturnUsing(static function () use ($processor): void {
                $processor->reset();
            });
        $processor
            ->shouldReceive('resetState')
            ->never();

        $processor->start();
    }

    /**
     * @covers ::run
     * @covers ::process
     * @covers ::report
     * @covers ::chunkLoaded
     * @covers ::chunkProcessed
     */
    public function testRun(): void {
        $a         = new class() extends stdClass {
            // empty
        };
        $b         = new class() extends stdClass {
            // empty
        };
        $chunk     = $this->faker->randomNumber(3);
        $state     = new State([
            'index'  => $this->faker->randomNumber(),
            'limit'  => $this->faker->randomNumber(),
            'offset' => $this->faker->uuid,
        ]);
        $exception = new Exception();

        $iterator = Mockery::mock(OneChunkOffsetBasedObjectIterator::class, [
            static function () use ($a, $b): array {
                return [$a, $b];
            },
        ]);
        $iterator->shouldAllowMockingProtectedMethods();
        $iterator->makePartial();
        $iterator
            ->shouldReceive('setIndex')
            ->with($state->index)
            ->once()
            ->andReturnSelf();
        $iterator
            ->shouldReceive('setLimit')
            ->with($state->limit)
            ->once()
            ->andReturnSelf();
        $iterator
            ->shouldReceive('setOffset')
            ->with($state->offset)
            ->once()
            ->andReturnSelf();
        $iterator
            ->shouldReceive('setChunkSize')
            ->with($chunk)
            ->once()
            ->andReturnSelf();
        $iterator
            ->shouldReceive('getChunkVariables')
            ->once()
            ->andReturn([]);

        $handler    = $this->app->make(ExceptionHandler::class);
        $dispatcher = $this->app->make(Dispatcher::class);
        $processor  = Mockery::mock(ProcessorTest__Processor::class, [$handler, $dispatcher]);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getIterator')
            ->once()
            ->andReturn($iterator);
        $processor
            ->shouldReceive('process')
            ->with($a)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('process')
            ->with($b)
            ->once()
            ->andThrow($exception);
        $processor
            ->shouldReceive('report')
            ->with($exception, $b)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('getOnChangeEvent')
            ->once()
            ->andReturnNull();

        $finish   = null;
        $onInit   = Mockery::spy(function (): void {
            // empty
        });
        $onChange = Mockery::spy(function (): void {
            // empty
        });
        $onFinish = Mockery::spy(function (State $state) use (&$finish): void {
            $finish = $state;
        });

        $processor
            ->onInit(Closure::fromCallable($onInit))
            ->onChange(Closure::fromCallable($onChange))
            ->onFinish(Closure::fromCallable($onFinish))
            ->setChunkSize($chunk)
            ->run($state);

        $this->assertEquals(
            new State([
                'index'     => 1,
                'limit'     => $state->limit,
                'failed'    => 1,
                'success'   => 1,
                'processed' => 2,
            ]),
            $finish,
        );

        $onInit
            ->shouldHaveBeenCalled()
            ->once();
        $onChange
            ->shouldHaveBeenCalled()
            ->once();
        $onFinish
            ->shouldHaveBeenCalled()
            ->once();
    }

    /**
     * @covers ::getDefaultState
     */
    public function testGetDefaultState(): void {
        $processor = new class() extends Processor {
            private ?int $total = null;

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getDefaultState(): State {
                return parent::getDefaultState();
            }

            protected function getTotal(): ?int {
                return $this->total;
            }

            public function setTotal(?int $total): static {
                $this->total = $total;

                return $this;
            }

            protected function getIterator(): ObjectIterator {
                throw new Exception();
            }

            protected function process(mixed $item): void {
                throw new Exception();
            }

            protected function getOnChangeEvent(State $state, array $items): ?object {
                return null;
            }
        };

        // Offset
        $this->assertEquals(
            new State([
                'index'  => 0,
                'limit'  => null,
                'total'  => null,
                'offset' => 'abc',
            ]),
            (clone $processor)->setOffset('abc')->getDefaultState(),
        );

        // No Limit & No Total
        $this->assertEquals(
            new State([
                'index'  => 0,
                'limit'  => null,
                'total'  => null,
                'offset' => null,
            ]),
            $processor->getDefaultState(),
        );

        // Limit & No Total
        $this->assertEquals(
            new State([
                'index'  => 0,
                'limit'  => 123,
                'total'  => 123,
                'offset' => null,
            ]),
            (clone $processor)->setLimit(123)->getDefaultState(),
        );

        // No Limit & Total
        $this->assertEquals(
            new State([
                'index'  => 0,
                'limit'  => null,
                'total'  => 321,
                'offset' => null,
            ]),
            (clone $processor)->setTotal(321)->getDefaultState(),
        );

        // Limit & Total
        $this->assertEquals(
            new State([
                'index'  => 0,
                'limit'  => 456,
                'total'  => 321,
                'offset' => null,
            ]),
            (clone $processor)->setTotal(321)->setLimit(456)->getDefaultState(),
        );
    }

    /**
     * @covers ::chunkProcessed
     */
    public function testChunkProcessed(): void {
        $state     = new State();
        $items     = [new stdClass()];
        $processor = Mockery::mock(ProcessorTest__Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('saveState')
            ->with($state)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('notifyOnChange')
            ->with($state)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('dispatchOnChange')
            ->with($state, $items)
            ->once()
            ->andReturns();

        $processor->chunkProcessed($state, $items);
    }

    /**
     * @covers ::chunkProcessed
     */
    public function testChunkProcessedStopped(): void {
        $this->expectException(Interrupt::class);

        $state     = new State();
        $items     = [new stdClass()];
        $processor = Mockery::mock(ProcessorTest__Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('saveState')
            ->with($state)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('notifyOnChange')
            ->with($state)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('dispatchOnChange')
            ->with($state, $items)
            ->once()
            ->andReturns();

        $processor->stop();
        $processor->chunkProcessed($state, $items);
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
abstract class ProcessorTest__Processor extends Processor {
    public function __construct(ExceptionHandler $exceptionHandler, Dispatcher $dispatcher) {
        parent::__construct($exceptionHandler, $dispatcher);
    }

    public function run(State $state): void {
        parent::run($state);
    }

    public function chunkProcessed(State $state, array $items): void {
        parent::chunkProcessed($state, $items);
    }
}
