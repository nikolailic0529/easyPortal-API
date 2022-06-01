<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Configuration;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Processors\ModelProcessor;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use App\Services\Search\Properties\Uuid;
use App\Services\Search\Queue\Tasks\Index;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Eloquent\SearchableImpl
 */
class SearchableImplTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::searchIndexShouldBeUpdated
     */
    public function testSearchIndexShouldBeUpdated(): void {
        // Model should be updated if property was changed
        $model = new class() extends Model {
            use SearchableImpl;

            public function getDirty(): mixed {
                return ['a' => 'b'];
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return ['a' => new Text('a')];
            }
        };

        self::assertTrue($model->searchIndexShouldBeUpdated());

        // Relations should be ignored
        $model = new class() extends Model {
            use SearchableImpl;

            public function getDirty(): mixed {
                return ['a.b' => 'a.b'];
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return ['a.b' => new Text('a')];
            }
        };

        self::assertFalse($model->searchIndexShouldBeUpdated());
    }

    /**
     * @covers ::toSearchableArray
     * @covers ::toSearchableArrayValue
     * @covers ::toSearchableArrayCleanup
     */
    public function testToSearchableArray(): void {
        // Prepare
        $sku    = $this->faker->uuid();
        $oem    = Oem::factory()->create();
        $group  = ServiceGroup::factory()->create([
            'sku'    => $sku,
            'oem_id' => $oem,
        ]);
        $levelA = ServiceLevel::factory()->create([
            'id'               => '5cd434b1-8fa1-4a25-95f2-5d92f556cae7',
            'oem_id'           => $oem,
            'service_group_id' => $group,
        ]);
        $levelB = ServiceLevel::factory()->create([
            'id'               => 'ab4d13fb-f769-4d20-9835-b4c0b2139439',
            'oem_id'           => $oem,
            'service_group_id' => $group,
        ]);

        // Model
        $model = new class() extends ServiceGroup {
            use SearchableImpl;

            public function getForeignKey(): string {
                return 'service_group_id';
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [
                    'sku'     => new Text('sku'),
                    'oem'     => new Relation('oem', [
                        'id' => new Uuid('id'),
                    ]),
                    'unknown' => new Uuid('unknown'),
                    'levels'  => new Relation('levels', [
                        'null'    => new Uuid('unknown'),
                        'sku'     => new Uuid('sku'),
                        'oem_id'  => new Uuid('oem_id'),
                        'unknown' => new Uuid('unknown'),
                    ]),
                ];
            }
        };

        // Scope
        $scope = new class() implements Scope, ScopeWithMetadata {
            public function apply(EloquentBuilder $builder, Model $model): void {
                // empty
            }

            public function applyForSearch(SearchBuilder $builder, Model $model): void {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getSearchMetadata(Model $model): array {
                return [
                    'sku' => new Text('sku'),
                ];
            }
        };

        $model->addGlobalScope($scope);

        // Test
        $actual   = $model->find($group->getKey())->toSearchableArray();
        $expected = [
            Configuration::getMetadataName() => [
                'sku' => $sku,
            ],
            Configuration::getPropertyName() => [
                'sku'     => $sku,
                'oem'     => [
                    'id' => $oem->getKey(),
                ],
                'unknown' => null,
                'levels'  => [
                    [
                        'null'    => null,
                        'sku'     => $levelA->sku,
                        'oem_id'  => $levelA->oem_id,
                        'unknown' => null,
                    ],
                    [
                        'null'    => null,
                        'sku'     => $levelB->sku,
                        'oem_id'  => $levelB->oem_id,
                        'unknown' => null,
                    ],
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::toSearchableArray
     */
    public function testToSearchableArrayEagerLoading(): void {
        $model = Mockery::mock(Model::class, SearchableImpl::class);
        $model->shouldAllowMockingProtectedMethods();
        $model->makePartial();
        $model
            ->shouldReceive('loadMissing')
            ->with(['b', 'c.b'])
            ->once()
            ->andReturnSelf();
        $model
            ->shouldReceive('getSearchProperties')
            ->once()
            ->andReturn([
                'a' => new Text('a'),
                'b' => new Relation('b', [
                    'a' => new Uuid('a'),
                ]),
                'c' => new Relation('c', [
                    'b' => new Relation('b', [
                        'a' => new Uuid('a'),
                    ]),
                ]),
            ]);

        // Test
        $model->toSearchableArray();
    }

    /**
     * @covers ::makeAllSearchable
     */
    public function testMakeAllSearchable(): void {
        $chunk = $this->faker->randomDigitNotNull();
        $model = new class() extends Model {
            use SearchableImpl;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }
        };

        $this->override(ModelProcessor::class, static function (MockInterface $processor) use ($model, $chunk): void {
            $processor
                ->shouldReceive('setModel')
                ->with($model::class)
                ->once()
                ->andReturnSelf();
            $processor
                ->shouldReceive('setRebuild')
                ->with(true)
                ->once()
                ->andReturnSelf();
            $processor
                ->shouldReceive('setChunkSize')
                ->with($chunk)
                ->once()
                ->andReturnSelf();
            $processor
                ->shouldReceive('start')
                ->once()
                ->andReturns();
        });

        $model->makeAllSearchable($chunk);
    }

    /**
     * @covers ::makeAllSearchableUsing
     */
    public function testMakeAllSearchableUsing(): void {
        // Model
        $model = new class() extends Model {
            use SearchableImpl;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [
                    'a' => new Text('a.name'),
                    'b' => new Text('b.name'),
                ];
            }
        };

        // Builder
        $builder = Mockery::mock(EloquentBuilder::class);
        $builder
            ->shouldReceive('with')
            ->with(['a', 'b'])
            ->once()
            ->andReturnSelf();

        // Test
        $model->makeAllSearchableUsing($builder);
    }

    /**
     * @covers ::toSearchableValue
     *
     * @dataProvider dataProviderToSearchableValue
     */
    public function testToSearchableValue(mixed $expected, mixed $value): void {
        $model = new class() extends Model {
            use SearchableImpl {
                toSearchableValue as public;
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }
        };

        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        self::assertEquals($expected, $model->toSearchableValue($value));
    }

    /**
     * @covers ::queueMakeSearchable
     */
    public function testQueueMakeSearchable(): void {
        // Prepare
        $this->setSettings([
            'scout.queue' => false,
        ]);

        // Mock
        $a = Mockery::mock(Model::class);
        $a
            ->shouldReceive('shouldBeSearchable')
            ->once()
            ->andReturn(true);

        $b = Mockery::mock(Model::class);
        $b
            ->shouldReceive('shouldBeSearchable')
            ->once()
            ->andReturn(false);

        // Mockery cannot be used :(
        //
        // Method Mockery_0_Illuminate_Database_Eloquent_Model::searchableUsing()
        // does not exist on this mock object
        $model = new class() extends Model {
            use SearchableImpl;

            public Collection $models;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }

            protected function scoutQueueMakeSearchable(Collection $models): void {
                $this->models = $models;
            }
        };

        $model->queueMakeSearchable(new Collection([$a, $b]));

        self::assertCount(1, $model->models);
        self::assertSame($a, $model->models->first());
    }

    /**
     * @covers ::queueMakeSearchable
     */
    public function testQueueMakeSearchableRightJob(): void {
        // Prepare
        $this->setSettings([
            'scout.queue' => true,
        ]);

        // Mock
        $model = new class() extends Model {
            use SearchableImpl;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [
                    'a' => new Uuid('a'),
                ];
            }
        };

        // Test
        Queue::fake();

        $model->queueMakeSearchable(new Collection([$model]));

        Queue::assertPushed(Index::class);
    }

    /**
     * @covers ::queueRemoveFromSearch
     */
    public function testQueueRemoveFromSearchRightJob(): void {
        // Prepare
        $this->setSettings([
            'scout.queue' => true,
        ]);

        // Mock
        $model = new class() extends Model {
            use SearchableImpl;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [
                    'a' => new Uuid('a'),
                ];
            }
        };

        // Test
        Queue::fake();

        $model->queueRemoveFromSearch(new Collection([$model]));

        Queue::assertPushed(Index::class);
    }

    /**
     * @covers ::shouldBeSearchable
     */
    public function testShouldBeSearchable(): void {
        $model = new class() extends Model {
            use SearchableImpl;

            /**
             * @var array<mixed>
             */
            public static array $searchProperties;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return self::$searchProperties;
            }
        };

        // No properties
        $model::$searchProperties = [];

        self::assertFalse($model->shouldBeSearchable());

        // Properties
        $model::$searchProperties = ['a' => new Text('a')];

        self::assertTrue($model->shouldBeSearchable());
    }

    /**
     * @covers ::getSearchConfiguration
     */
    public function testGetSearchConfiguration(): void {
        $model  = new class() extends Model {
            use SearchableImpl;

            /**
             * @inheritDoc
             */
            public static function getSearchMetadata(): array {
                return ['m' => new Text('m')];
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return ['a' => new Text('a')];
            }
        };
        $config = $model->getSearchConfiguration();

        self::assertInstanceOf(Configuration::class, $config);
        self::assertEquals(
            [
                Configuration::getMetadataName() => [
                    'm' => new Text('m'),
                ],
                Configuration::getPropertyName() => [
                    'a' => new Text('a'),
                ],
            ],
            $config->getProperties(),
        );
    }

    /**
     * @covers ::searchableAs
     * @covers ::setSearchableAs
     * @covers ::scoutSearchableAs
     */
    public function testSearchableAs(): void {
        $model = new class() extends Model {
            use SearchableImpl {
                scoutSearchableAs as public;
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }
        };

        self::assertEquals('test', $model->setSearchableAs('test')->searchableAs());
        self::assertEquals(
            $model->scoutSearchableAs(),
            $model->setSearchableAs(null)->searchableAs(),
        );
    }

    /**
     * @covers ::isSearchSyncingEnabled
     */
    public function testIsSearchSyncingEnabled(): void {
        $model = Mockery::mock(Model::class, SearchableImpl::class)::class;

        self::assertTrue($model::isSearchSyncingEnabled());

        $model::disableSearchSyncing();

        self::assertFalse($model::isSearchSyncingEnabled());

        $model::enableSearchSyncing();

        self::assertTrue($model::isSearchSyncingEnabled());
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function dataProviderToSearchableValue(): array {
        return [
            'int'      => [1, 1],
            'bool'     => [true, true],
            'null'     => [null, null],
            'float'    => [1.23, 1.23],
            'string'   => ['abc', 'abc'],
            'Carbon'   => ['2021-07-01T00:00:00.000000Z', Date::make('2021-07-01T00:00:00+00:00')],
            'DateTime' => ['2021-07-01T00:00:00.000000Z', new DateTime('2021-07-01T00:00:00+00:00')],
            'object'   => [new LogicException('Not yet supported.'), new stdClass()],
            'Model'    => [
                new LogicException('Not yet supported.'),
                new class() extends Model {
                    // empty
                },
            ],
        ];
    }
    // </editor-fold>
}
