<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\CascadeDeletes;

use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

use function array_keys;
use function count;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Eloquent\CascadeDeletes\CascadeProcessor
 */
class CascadeProcessorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        $model     = new class() extends Model {
            // empty
        };
        $relations = [
            'a' => Mockery::mock(Relation::class),
            'b' => Mockery::mock(Relation::class),
        ];

        $processor = Mockery::mock(CascadeProcessor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getRelations')
            ->once()
            ->andReturn($relations);
        $processor
            ->shouldReceive('run')
            ->times(count($relations))
            ->andReturns();

        $processor->delete($model);

        self::assertTrue(true);
    }

    /**
     * @covers ::getRelations
     */
    public function testGetRelations(): void {
        $processor = new class() extends CascadeProcessor {
            /**
             * @inheritDoc
             */
            public function getRelations(Model $model): array {
                return parent::getRelations($model);
            }
        };
        $model     = new class() extends Model {
            /**
             * @noinspection PhpMissingReturnTypeInspection
             * @inheritDoc
             */
            public function relationWithoutTypehint() {
                return Mockery::mock(BelongsTo::class);
            }

            #[CascadeDelete(true)]
            public function relationWithTypehint(): BelongsTo {
                return Mockery::mock(BelongsTo::class);
            }

            #[CascadeDelete(false)]
            public function relationWithTypehintIgnored(): BelongsTo {
                return Mockery::mock(BelongsTo::class);
            }

            #[CascadeDelete(true)]
            protected function relationProtected(): BelongsTo {
                return Mockery::mock(BelongsTo::class);
            }

            #[CascadeDelete(true)]
            public function relationWithUnionTypehint(): BelongsTo|HasOne {
                return Mockery::mock(BelongsTo::class);
            }
        };

        self::assertEquals(
            [
                'relationWithTypehint',
                'relationWithUnionTypehint',
            ],
            array_keys($processor->getRelations($model)),
        );
    }

    /**
     * @covers ::getRelations
     */
    public function testGetRelationsNoAttribute(): void {
        $processor = new class() extends CascadeProcessor {
            /**
             * @inheritDoc
             */
            public function getRelations(Model $model): array {
                return parent::getRelations($model);
            }
        };
        $model     = new class() extends Model {
            public function relationWithoutAttribute(): BelongsTo {
                return Mockery::mock(BelongsTo::class);
            }
        };

        self::expectErrorMessage(sprintf(
            'Relation `%s::%s()` must have `%s` attribute.',
            $model::class,
            'relationWithoutAttribute',
            CascadeDelete::class,
        ));

        $processor->getRelations($model);
    }

    /**
     * @covers ::run
     */
    public function testRun(): void {
        $relation = Mockery::mock(Relation::class);

        $model = Mockery::mock(Model::class);
        $model->makePartial();

        $child = Mockery::mock(Model::class);
        $child->makePartial();
        $child
            ->shouldReceive('delete')
            ->once()
            ->andReturnUsing(static function (): bool {
                return true;
            });

        $processor = Mockery::mock(CascadeProcessor::class);
        $processor->makePartial();
        $processor->shouldAllowMockingProtectedMethods();
        $processor
            ->shouldReceive('getRelatedObjects')
            ->once()
            ->andReturn([$child]);

        $processor->run($model, 'relation', $relation);
    }

    /**
     * @covers ::getRelatedObjects
     */
    public function testGetRelatedObjects(): void {
        $processor = new class() extends CascadeProcessor {
            /**
             * @inheritDoc
             */
            public function getRelatedObjects(Model $model, string $name, Relation $relation): array {
                return parent::getRelatedObjects($model, $name, $relation);
            }
        };
        $relation  = Mockery::mock(Relation::class);
        $model     = Mockery::mock(Model::class);
        $item      = new class() extends Model {
            // empty
        };

        $model
            ->shouldReceive('getRelationValue')
            ->with('collection')
            ->once()
            ->andReturn(new Collection([$item]));
        $model
            ->shouldReceive('getRelationValue')
            ->with('model')
            ->once()
            ->andReturn($item);
        $model
            ->shouldReceive('getRelationValue')
            ->with('null')
            ->once()
            ->andReturn(null);

        self::assertEquals([$item], $processor->getRelatedObjects($model, 'collection', $relation));
        self::assertEquals([$item], $processor->getRelatedObjects($model, 'model', $relation));
        self::assertEquals([], $processor->getRelatedObjects($model, 'null', $relation));
    }

    /**
     * @covers ::getRelatedObjects
     */
    public function testGetRelatedObjectsBelongsToMany(): void {
        $processor = new class() extends CascadeProcessor {
            /**
             * @inheritDoc
             */
            public function getRelatedObjects(Model $model, string $name, Relation $relation): array {
                return parent::getRelatedObjects($model, $name, $relation);
            }
        };
        $pivot     = new class() extends Pivot {
            // empty
        };

        $relation = Mockery::mock(BelongsToMany::class);
        $relation
            ->shouldReceive('getPivotAccessor')
            ->twice()
            ->andReturn('pivot');

        $item = Mockery::mock(Model::class);
        $item->makePartial();
        $item
            ->shouldReceive('getRelationValue')
            ->with('pivot')
            ->twice()
            ->andReturn($pivot);

        $model = Mockery::mock(Model::class);
        $model
            ->shouldReceive('getRelationValue')
            ->with('collection')
            ->once()
            ->andReturn(new Collection([$item]));
        $model
            ->shouldReceive('getRelationValue')
            ->with('model')
            ->once()
            ->andReturn($item);
        $model
            ->shouldReceive('getRelationValue')
            ->with('null')
            ->once()
            ->andReturn(null);

        self::assertEquals([$pivot], $processor->getRelatedObjects($model, 'collection', $relation));
        self::assertEquals([$pivot], $processor->getRelatedObjects($model, 'model', $relation));
        self::assertEquals([], $processor->getRelatedObjects($model, 'null', $relation));
    }
    // </editor-fold>
}
