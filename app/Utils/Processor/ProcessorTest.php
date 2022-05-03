<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Processor\Contracts\StateStore;
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
 * @coversDefaultClass \App\Utils\Processor\Processor
 */
class ProcessorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::start
     * @covers ::invoke
     */
    public function testStart(): void {
        $state     = new State(['offset' => $this->faker->uuid()]);
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
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
        self::expectExceptionObject(new LogicException('Reset is not possible while running.'));

        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
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

    /**
     * @covers ::chunkProcessed
     */
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

    /**
     * @covers ::chunkProcessed
     */
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

    /**
     * @covers ::getState
     */
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

    /**
     * @covers ::getState
     */
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

    /**
     * @covers ::saveState
     */
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

    /**
     * @covers ::resetState
     */
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
    public function __construct(ExceptionHandler $exceptionHandler, Dispatcher $dispatcher) {
        parent::__construct($exceptionHandler, $dispatcher);
    }

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

    protected function invoke(State $state): void {
        // TODO: Implement invoke() method.
    }
}
