<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\Eloquent\EloquentIterator;
use App\Utils\Iterators\Eloquent\ModelsIterator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LastDragon_ru\LaraASP\Eloquent\Iterators\Iterator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function array_fill;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Processor\EloquentProcessor
 */
class EloquentProcessorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getBuilder
     */
    public function testGetBuilderDefault(): void {
        $builder   = $this->getBuilderMock();
        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();

        self::assertSame($builder, $processor->getBuilder(new EloquentState([
            'model' => $builder->getModel()::class,
        ])));
    }

    /**
     * @covers ::getBuilder
     */
    public function testGetBuilderWithKeys(): void {
        $keys    = [$this->faker->uuid(), $this->faker->uuid()];
        $builder = $this->getBuilderMock();

        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();

        self::assertSame($builder, $processor->getBuilder(new EloquentState([
            'model' => $builder->getModel()::class,
            'keys'  => $keys,
        ])));
    }

    /**
     * @covers ::getBuilder
     */
    public function testGetBuilderWithTrashedNonSoftDeletes(): void {
        $builder   = $this->getBuilderMock();
        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();

        self::assertSame($builder, $processor->getBuilder(new EloquentState([
            'model'       => $builder->getModel()::class,
            'withTrashed' => true,
        ])));
    }

    /**
     * @covers ::getBuilder
     */
    public function testGetBuilderWithTrashedTrue(): void {
        $builder = $this->getBuilderMockSoftDeletes();
        $builder
            ->shouldReceive('withTrashed')
            ->once()
            ->andReturnSelf();

        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();

        self::assertSame($builder, $processor->getBuilder(new EloquentState([
            'model'       => $builder->getModel()::class,
            'withTrashed' => true,
        ])));
    }

    /**
     * @covers ::getBuilder
     */
    public function testGetBuilderWithTrashedFalse(): void {
        $builder = $this->getBuilderMockSoftDeletes();
        $builder
            ->shouldReceive('withoutTrashed')
            ->once()
            ->andReturnSelf();

        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();

        self::assertSame($builder, $processor->getBuilder(new EloquentState([
            'model'       => $builder->getModel()::class,
            'withTrashed' => false,
        ])));
    }

    /**
     * @covers ::getBuilder
     */
    public function testGetBuilderWithTrashedDefault(): void {
        $builder = $this->getBuilderMockSoftDeletes();
        $builder
            ->shouldReceive('withoutTrashed')
            ->once()
            ->andReturnSelf();

        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();

        self::assertSame($builder, $processor->getBuilder(new EloquentState([
            'model' => $builder->getModel()::class,
        ])));
    }

    /**
     * @covers ::getTotal
     */
    public function testGetTotal(): void {
        $count   = $this->faker->randomDigit();
        $state   = new EloquentState();
        $builder = Mockery::mock(Builder::class);
        $builder
            ->shouldReceive('count')
            ->once()
            ->andReturn($count);

        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getBuilder')
            ->with($state)
            ->once()
            ->andReturn($builder);

        self::assertEquals($count, $processor->getTotal($state));
    }

    /**
     * @covers ::getTotal
     */
    public function testGetTotalWithKeys(): void {
        $keys      = array_fill(0, $this->faker->randomDigit(), $this->faker->uuid());
        $state     = new EloquentState(['keys' => $keys]);
        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();

        self::assertEquals(count($keys), $processor->getTotal($state));
    }

    /**
     * @covers ::getIterator
     */
    public function testGetIterator(): void {
        $state   = new EloquentState();
        $builder = Mockery::mock(Builder::class);
        $builder
            ->shouldReceive('getChangeSafeIterator')
            ->once()
            ->andReturn(
                Mockery::mock(Iterator::class),
            );

        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getBuilder')
            ->with($state)
            ->once()
            ->andReturn($builder);

        self::assertInstanceOf(EloquentIterator::class, $processor->getIterator($state));
    }

    /**
     * @covers ::getIterator
     */
    public function testGetIteratorWithKeys(): void {
        $state     = new EloquentState(['keys' => [$this->faker->uuid()]]);
        $builder   = Mockery::mock(Builder::class);
        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getBuilder')
            ->with($state)
            ->once()
            ->andReturn($builder);

        self::assertInstanceOf(ModelsIterator::class, $processor->getIterator($state));
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @return MockInterface&Builder
     */
    protected function getBuilderMock(): MockInterface {
        $model = new class() extends Model {
            /**
             * @var Builder<static>
             */
            public static Builder $builder;

            /**
             * @return Builder<static>
             */
            public static function query(): Builder {
                return self::$builder;
            }
        };

        $builder         = Mockery::mock(Builder::class);
        $model::$builder = $builder;
        $builder
            ->shouldReceive('getModel')
            ->atLeast()
            ->once()
            ->andReturn($model);

        return $builder;
    }

    /**
     * @return MockInterface&Builder
     */
    protected function getBuilderMockSoftDeletes(): MockInterface {
        $model = new class() extends Model {
            use SoftDeletes;

            /**
             * @var Builder<static>
             */
            public static Builder $builder;

            /**
             * @return Builder<static>
             */
            public static function query(): Builder {
                return self::$builder;
            }
        };

        $builder         = Mockery::mock(Builder::class);
        $model::$builder = $builder;
        $builder
            ->shouldReceive('getModel')
            ->atLeast()
            ->once()
            ->andReturn($model);

        return $builder;
    }
    // </editor-fold>
}
