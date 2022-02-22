<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

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
        $processor
            ->shouldReceive('getModel')
            ->once()
            ->andReturn($builder->getModel()::class);

        $this->assertSame($builder, $processor->getBuilder(new EloquentState()));
    }

    /**
     * @covers ::getBuilder
     */
    public function testGetBuilderWithKeys(): void {
        $keys    = [$this->faker->uuid, $this->faker->uuid];
        $builder = $this->getBuilderMock();
        $builder
            ->shouldReceive('whereIn')
            ->with($builder->getModel()->getKeyName(), $keys)
            ->once()
            ->andReturnSelf();

        $processor = Mockery::mock(EloquentProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getModel')
            ->once()
            ->andReturn($builder->getModel()::class);

        $this->assertSame($builder, $processor->getBuilder(new EloquentState([
            'keys' => $keys,
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
        $processor
            ->shouldReceive('getModel')
            ->once()
            ->andReturn($builder->getModel()::class);

        $this->assertSame($builder, $processor->getBuilder(new EloquentState([
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
        $processor
            ->shouldReceive('getModel')
            ->once()
            ->andReturn($builder->getModel()::class);

        $this->assertSame($builder, $processor->getBuilder(new EloquentState([
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
        $processor
            ->shouldReceive('getModel')
            ->once()
            ->andReturn($builder->getModel()::class);

        $this->assertSame($builder, $processor->getBuilder(new EloquentState([
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
        $processor
            ->shouldReceive('getModel')
            ->once()
            ->andReturn($builder->getModel()::class);

        $this->assertSame($builder, $processor->getBuilder(new EloquentState()));
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @return \Mockery\MockInterface&\Illuminate\Database\Eloquent\Builder
     */
    protected function getBuilderMock(): MockInterface {
        $model = new class() extends Model {
            public static Builder $builder;

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
     * @return \Mockery\MockInterface&\Illuminate\Database\Eloquent\Builder
     */
    protected function getBuilderMockSoftDeletes(): MockInterface {
        $model = new class() extends Model {
            use SoftDeletes;

            public static Builder $builder;

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
