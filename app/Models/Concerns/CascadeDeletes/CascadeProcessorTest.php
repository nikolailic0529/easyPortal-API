<?php declare(strict_types = 1);

namespace App\Models\Concerns\CascadeDeletes;

use App\Models\Pivot;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\Models\Concerns\CascadeDeletes\CascadeProcessor
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
            ->shouldReceive('runDelete')
            ->times(count($relations))
            ->andReturns();

        $processor->delete($model);

        $this->assertTrue(true);
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

            protected function isRelation(Model $model, string $name, Relation $relation): bool {
                return true;
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

            public function relationWithTypehint(): BelongsTo {
                return Mockery::mock(BelongsTo::class);
            }

            protected function relationProtected(): BelongsTo {
                return Mockery::mock(BelongsTo::class);
            }

            public function relationWithUnionTypehint(): BelongsTo|HasOne {
                return Mockery::mock(BelongsTo::class);
            }
        };

        $this->assertEquals(
            [
                'relationWithTypehint',
            ],
            array_keys($processor->getRelations($model)),
        );
    }

    /**
     * @covers ::isRelation
     *
     * @dataProvider dataProviderIsRelation
     */
    public function testIsRelation(bool $expected, Closure $modelFactory, Closure $relationFactory): void {
        $processor = new class() extends CascadeProcessor {
            public function isRelation(Model $model, string $name, Relation $relation): bool {
                return parent::isRelation($model, $name, $relation);
            }
        };


        $this->assertEquals($expected, $processor->isRelation($modelFactory($this), 'name', $relationFactory($this)));
    }

    /**
     * @covers ::runDelete
     */
    public function testRunDelete(): void {
        $relation = Mockery::mock(Relation::class);

        $model = Mockery::mock(Model::class);
        $model->makePartial();
        $model->forceDeleting = true;

        $child = Mockery::mock(Model::class);
        $child->makePartial();
        $child
            ->shouldReceive('delete')
            ->once()
            ->andReturnUsing(function () use ($child): bool {
                $this->assertTrue($child->forceDeleting ?? null);

                return true;
            });

        $processor = Mockery::mock(CascadeProcessor::class);
        $processor->makePartial();
        $processor->shouldAllowMockingProtectedMethods();
        $processor
            ->shouldReceive('getRelatedObjects')
            ->once()
            ->andReturn([$child]);

        $processor->runDelete($model, 'relation', $relation);
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

        $this->assertEquals([$item], $processor->getRelatedObjects($model, 'collection', $relation));
        $this->assertEquals([$item], $processor->getRelatedObjects($model, 'model', $relation));
        $this->assertEquals([], $processor->getRelatedObjects($model, 'null', $relation));
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
            //
        };

        $relation = Mockery::mock(BelongsToMany::class);
        $relation
            ->shouldReceive('getPivotAccessor')
            ->once()
            ->andReturn('pivot');

        $item = Mockery::mock(Model::class);
        $item->makePartial();
        $item
            ->shouldReceive('getRelationValue')
            ->with('pivot')
            ->once()
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

        $this->assertEquals([$pivot], $processor->getRelatedObjects($model, 'collection', $relation));
        $this->assertEquals([$pivot], $processor->getRelatedObjects($model, 'model', $relation));
        $this->assertEquals([], $processor->getRelatedObjects($model, 'null', $relation));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderIsRelation(): array {
        return [
            'Model + MorphMany'            => [
                true,
                static function (): Model {
                    return new class() extends Model {
                        // empty
                    };
                },
                static function (): Relation {
                    return Mockery::mock(MorphMany::class);
                },
            ],
            'CascadeDeletable + MorphMany' => [
                false,
                static function (): Model {
                    return new class() extends Model implements CascadeDeletable {
                        public function isCascadeDeletableRelation(
                            string $name,
                            Relation $relation,
                            bool $default,
                        ): bool {
                            return false;
                        }
                    };
                },
                static function (): Relation {
                    return Mockery::mock(MorphMany::class);
                },
            ],
            'Model + Relation'             => [
                false,
                static function (): Model {
                    return new class() extends Model {
                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                         *
                         * @var string
                         */
                        protected $table = 'items';
                    };
                },
                static function (): Relation {
                    $relation = Mockery::mock(Relation::class);
                    $relation
                        ->shouldReceive('newModelInstance')
                        ->once()
                        ->andReturn(new class() extends Model {
                            /**
                             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                             *
                             * @var string
                             */
                            protected $table = 'objects';
                        });

                    return $relation;
                },
            ],
            'CascadeDeletable + Relation'  => [
                true,
                static function (): Model {
                    return new class() extends Model implements CascadeDeletable {
                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                         *
                         * @var string
                         */
                        protected $table = 'items';

                        public function isCascadeDeletableRelation(
                            string $name,
                            Relation $relation,
                            bool $default,
                        ): bool {
                            return true;
                        }
                    };
                },
                static function (): Relation {
                    $relation = Mockery::mock(Relation::class);
                    $relation
                        ->shouldReceive('newModelInstance')
                        ->once()
                        ->andReturn(new class() extends Model {
                            /**
                             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                             *
                             * @var string
                             */
                            protected $table = 'objects';
                        });

                    return $relation;
                },
            ],
            'Model + Child'                => [
                true,
                static function (): Model {
                    return new class() extends Model {
                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                         *
                         * @var string
                         */
                        protected $table = 'items';
                    };
                },
                static function (): Relation {
                    $relation = Mockery::mock(Relation::class);
                    $relation
                        ->shouldReceive('newModelInstance')
                        ->once()
                        ->andReturn(new class() extends Model {
                            /**
                             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                             *
                             * @var string
                             */
                            protected $table = 'item_objects';
                        });

                    return $relation;
                },
            ],
            'Model + BelongsToMany'        => [
                true,
                static function (): Model {
                    return new class() extends Model {
                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                         *
                         * @var string
                         */
                        protected $table = 'items';
                    };
                },
                static function (): Relation {
                    return Mockery::mock(BelongsToMany::class);
                },
            ],
            'Model + MorphToMany'          => [
                false,
                static function (): Model {
                    return new class() extends Model {
                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                         *
                         * @var string
                         */
                        protected $table = 'items';
                    };
                },
                static function (): Relation {
                    $relation = Mockery::mock(MorphToMany::class);
                    $relation
                        ->shouldReceive('newModelInstance')
                        ->once()
                        ->andReturn(new class() extends Model {
                            /**
                             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                             *
                             * @var string
                             */
                            protected $table = 'objects';
                        });

                    return $relation;
                },
            ],
        ];
    }
    // </editor-fold>
}
