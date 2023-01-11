<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\Concerns\Limit;
use App\Utils\Iterators\Concerns\Offset;
use App\Utils\Iterators\Contracts\IteratorFatalError;
use App\Utils\Iterators\Contracts\Limitable;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\Contracts\Offsetable;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Iterators\OneChunkOffsetBasedObjectIterator;
use App\Utils\Processor\Contracts\StateStore;
use Closure;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;
use LogicException;
use Mockery;
use stdClass;
use Tests\TestCase;
use Throwable;

/**
 * @internal
 * @covers \App\Utils\Processor\Processor
 */
class ProcessorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testStart(): void {
        $state     = new State(['offset' => $this->faker->uuid()]);
        $config    = $this->app->make(Repository::class);
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getConfig')
            ->once()
            ->andReturn($config);
        $processor
            ->shouldReceive('getState')
            ->once()
            ->andReturn($state);
        $processor
            ->shouldReceive('invoke')
            ->withArgs(static function (State $actual) use ($processor, $state): bool {
                self::assertTrue($processor->isRunning());
                self::assertFalse($processor->isStopped());
                self::assertEquals($state, $actual);

                return true;
            })
            ->once()
            ->andReturns();

        self::assertTrue($processor->start());
    }

    public function testStop(): void {
        $config    = $this->app->make(Repository::class);
        $processor = Mockery::mock(Processor::class);
        $iterator  = new OneChunkOffsetBasedObjectIterator(
            static function () use ($processor): array {
                $processor->stop();

                return [];
            },
        );
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getConfig')
            ->once()
            ->andReturn($config);
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

        self::assertTrue($processor->isStopped());
    }

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

    public function testResetRunning(): void {
        self::expectExceptionObject(new LogicException('Reset is not possible while running.'));

        $config    = $this->app->make(Repository::class);
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getConfig')
            ->once()
            ->andReturn($config);
        $processor
            ->shouldReceive('getState')
            ->once()
            ->andReturn(new State());
        $processor
            ->shouldReceive('invoke')
            ->once()
            ->andReturnUsing(static function () use ($processor): void {
                $processor->reset();
            });
        $processor
            ->shouldReceive('resetState')
            ->never();

        $processor->start();
    }

    public function testInvoke(): void {
        $a         = new class() extends stdClass {
            // empty
        };
        $b         = new class() extends stdClass {
            // empty
        };
        $chunk     = $this->faker->randomElement([10, 100, 250, 500]);
        $state     = new State([
            'index'  => $this->faker->numberBetween(10, 100),
            'limit'  => $this->faker->numberBetween(1, 100),
            'offset' => $this->faker->uuid(),
        ]);
        $exception = new Exception();

        $iterator = Mockery::mock(ObjectsIterator::class, [
            [$a, $b],
        ]);
        $iterator->shouldAllowMockingProtectedMethods();
        $iterator->makePartial();
        $iterator
            ->shouldReceive('setIndex')
            ->with($state->index)
            ->atLeast()
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

        $data       = new stdClass();
        $config     = $this->app->make(Repository::class);
        $handler    = $this->app->make(ExceptionHandler::class);
        $dispatcher = $this->app->make(Dispatcher::class);
        $processor  = Mockery::mock(ProcessorTest__Processor::class, [$handler, $dispatcher, $config]);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getIterator')
            ->once()
            ->andReturn($iterator);
        $processor
            ->shouldReceive('process')
            ->with($state, $data, $a)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('process')
            ->with($state, $data, $b)
            ->once()
            ->andThrow($exception);
        $processor
            ->shouldReceive('report')
            ->with($exception, $b)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('prefetch')
            ->once()
            ->andReturn($data);
        $processor
            ->shouldReceive('getOnChangeEvent')
            ->once()
            ->andReturnNull();
        $processor
            ->shouldReceive('notifyOnReport')
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('notifyOnProcess')
            ->with($state)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('dispatchOnInit')
            ->with($state)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('dispatchOnFinish')
            ->with($state)
            ->once()
            ->andReturns();
        $processor
            ->shouldReceive('dispatchOnProcess')
            ->with($a)
            ->once()
            ->andReturns();

        $finish   = null;
        $onInit   = Mockery::spy(static function (): void {
            // empty
        });
        $onChange = Mockery::spy(static function (): void {
            // empty
        });
        $onFinish = Mockery::spy(static function (State $state) use (&$finish): void {
            $finish = $state;
        });

        $processor
            ->onInit(Closure::fromCallable($onInit))
            ->onChange(Closure::fromCallable($onChange))
            ->onFinish(Closure::fromCallable($onFinish))
            ->setChunkSize($chunk)
            ->invoke($state);

        self::assertEquals(
            new State([
                'offset'    => 2,
                'index'     => 2,
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

    public function testInvokeFatalError(): void {
        $item       = new class() extends stdClass {
            // empty
        };
        $iterator   = new ObjectsIterator([$item]);
        $exception  = new class() extends Exception implements IteratorFatalError {
            // empty
        };
        $config     = Mockery::mock(Repository::class);
        $handler    = Mockery::mock(ExceptionHandler::class);
        $dispatcher = Mockery::mock(Dispatcher::class);

        $processor = Mockery::mock(ProcessorTest__Processor::class, [$handler, $dispatcher, $config]);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getIterator')
            ->once()
            ->andReturn($iterator);
        $processor
            ->shouldReceive('item')
            ->with(Mockery::any(), Mockery::any(), $item)
            ->once()
            ->andThrow($exception);

        self::expectExceptionObject($exception);

        $processor->invoke(new State());
    }

    public function testGetDefaultState(): void {
        $processor = new class() extends Processor implements Limitable, Offsetable {
            use Limit;
            use Offset;

            private ?int $total = null;

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getDefaultState(): State {
                return parent::getDefaultState();
            }

            protected function getTotal(State $state): ?int {
                return $this->total;
            }

            public function setTotal(?int $total): static {
                $this->total = $total;

                return $this;
            }

            protected function invoke(State $state): void {
                // TODO: Implement invoke() method.
            }

            protected function report(Throwable $exception, mixed $item = null): void {
                throw $exception;
            }

            /**
             * @inheritDoc
             */
            protected function getOnChangeEvent(State $state, array $items, mixed $data): ?object {
                return null;
            }

            /**
             * @inheritDoc
             */
            protected function prefetch(State $state, array $items): mixed {
                return null;
            }

            protected function process(State $state, mixed $data, mixed $item): void {
                // empty
            }

            protected function getIterator(State $state): ObjectIterator {
                throw new Exception('Not implemented.');
            }
        };

        Date::setTestNow(Date::now());

        // Offset
        self::assertEquals(
            new State([
                'index'  => 0,
                'limit'  => null,
                'total'  => null,
                'offset' => 'abc',
            ]),
            (clone $processor)->setOffset('abc')->getDefaultState(),
        );

        // No Limit & No Total
        self::assertEquals(
            new State([
                'index'  => 0,
                'limit'  => null,
                'total'  => null,
                'offset' => null,
            ]),
            $processor->getDefaultState(),
        );

        // Limit & No Total
        self::assertEquals(
            new State([
                'index'  => 0,
                'limit'  => 123,
                'total'  => 123,
                'offset' => null,
            ]),
            (clone $processor)->setLimit(123)->getDefaultState(),
        );

        // No Limit & Total
        self::assertEquals(
            new State([
                'index'  => 0,
                'limit'  => null,
                'total'  => 321,
                'offset' => null,
            ]),
            (clone $processor)->setTotal(321)->getDefaultState(),
        );

        // Limit & Total
        self::assertEquals(
            new State([
                'index'  => 0,
                'limit'  => 456,
                'total'  => 321,
                'offset' => null,
            ]),
            (clone $processor)->setTotal(321)->setLimit(456)->getDefaultState(),
        );
    }

    public function testChunkProcessed(): void {
        $data      = null;
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
            ->with($state, $items, $data)
            ->once()
            ->andReturns();

        $processor->chunkProcessed($state, $items, $data);
    }

    public function testChunkProcessedStopped(): void {
        self::expectException(Interrupt::class);

        $data      = null;
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
            ->with($state, $items, $data)
            ->once()
            ->andReturns();

        $processor->stop();
        $processor->chunkProcessed($state, $items, $data);
    }

    public function testGetState(): void {
        $state = [];
        $store = Mockery::mock(StateStore::class);
        $store
            ->shouldReceive('get')
            ->once()
            ->andReturn($state);

        $expected = new State($state);
        $actual   = $this->app->make(ProcessorTest__Processor::class)
            ->setStore($store)
            ->getState();

        self::assertEquals($expected, $actual);
    }

    public function testGetStateRestorationFailed(): void {
        $store = Mockery::mock(StateStore::class);
        $store
            ->shouldReceive('get')
            ->once()
            ->andReturnUsing(static function (): array {
                return ['invalid state'];
            });
        $store
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $actual = $this->app->make(ProcessorTest__Processor::class)
            ->setStore($store)
            ->getState();

        self::assertNull($actual);
    }

    public function testSaveState(): void {
        $state = new State();
        $store = Mockery::mock(StateStore::class);
        $store
            ->shouldReceive('save')
            ->once()
            ->with($state)
            ->andReturn($state);

        $this->app->make(ProcessorTest__Processor::class)
            ->setStore($store)
            ->saveState($state);
    }

    public function testResetState(): void {
        $store = Mockery::mock(StateStore::class);
        $store
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $this->app->make(ProcessorTest__Processor::class)
            ->setStore($store)
            ->resetState();
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends Processor<mixed,mixed,State>
 */
class ProcessorTest__Processor extends Processor {
    /**
     * @inheritDoc
     */
    public function chunkProcessed(State $state, array $items, mixed $data): void {
        parent::chunkProcessed($state, $items, $data);
    }

    protected function getTotal(State $state): ?int {
        return null;
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        // empty
    }

    public function saveState(State $state): void {
        parent::saveState($state);
    }

    public function resetState(): void {
        parent::resetState();
    }

    public function invoke(State $state): void {
        parent::invoke($state);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return null;
    }

    protected function process(State $state, mixed $data, mixed $item): void {
        // empty
    }

    protected function getIterator(State $state): ObjectIterator {
        throw new Exception('Not implemented');
    }
}
