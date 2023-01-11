<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Configuration;
use App\Services\Search\Contracts\ScopeWithMetadata;
use App\Services\Search\Indexer;
use App\Services\Search\Processors\ModelProcessor;
use App\Services\Search\Properties\Properties;
use App\Services\Search\Properties\Property;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use App\Services\Search\Properties\Uuid;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Date;
use Laravel\Scout\Engines\Engine;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Search\Eloquent\SearchableImpl
 */
class SearchableImplTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testSearchIndexShouldBeUpdated(): void {
        // Model should be updated if property was changed
        $model = new class() extends Model implements Searchable {
            use SearchableImpl;

            public function getDirty(): mixed {
                return ['a' => 'b'];
            }

            /**
             * @inheritDoc
             */
            public static function getSearchProperties(): array {
                return ['a' => new Text('a')];
            }
        };

        self::assertTrue($model->searchIndexShouldBeUpdated());

        // Relations should be ignored
        $model = new class() extends Model implements Searchable {
            use SearchableImpl;

            public function getDirty(): mixed {
                return ['a.b' => 'a.b'];
            }

            /**
             * @inheritDoc
             */
            public static function getSearchProperties(): array {
                return ['a.b' => new Text('a')];
            }
        };

        self::assertFalse($model->searchIndexShouldBeUpdated());
    }

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
        $model = new class() extends ServiceGroup implements Searchable {
            use SearchableImpl;

            public function getForeignKey(): string {
                return 'service_group_id';
            }

            /**
             * @inheritDoc
             */
            public static function getSearchProperties(): array {
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
                    'array'   => new Properties([
                        'id'  => new Uuid('id'),
                        'sku' => new Text('sku'),
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
        $actual   = $model::query()->findOrFail($group->getKey())->toSearchableArray();
        $expected = [
            Configuration::getId()           => $group->getKey(),
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
                'array'   => [
                    'id'  => $group->getKey(),
                    'sku' => $sku,
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
    }

    public function testToSearchableArrayEagerLoading(): void {
        $model = Mockery::mock(Model::class, SearchableImpl::class);
        $model->shouldAllowMockingProtectedMethods();
        $model->makePartial();
        $model
            ->shouldReceive('loadMissing')
            ->with(['b', 'c.b', 'db'])
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
                'd' => new Properties([
                    'da' => new Text('da'),
                    'db' => new Relation('db', [
                        'a' => new Uuid('a'),
                    ]),
                ]),
            ]);

        // Test
        $model->toSearchableArray();
    }

    public function testMakeAllSearchable(): void {
        $chunk = $this->faker->randomDigitNotNull();
        $model = new SearchableImplTest_Model();

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

    public function testMakeAllSearchableUsing(): void {
        // Model
        $model = new class() extends Model implements Searchable {
            use SearchableImpl;

            /**
             * @inheritDoc
             */
            public static function getSearchProperties(): array {
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
     * @dataProvider dataProviderToSearchableValue
     */
    public function testToSearchableValue(mixed $expected, mixed $value): void {
        $model = new class() extends Model implements Searchable {
            use SearchableImpl {
                toSearchableValue as public;
            }

            /**
             * @inheritDoc
             */
            public static function getSearchProperties(): array {
                return [];
            }
        };

        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        self::assertEquals($expected, $model->toSearchableValue($value));
    }

    public function testQueueMakeSearchableQueueEnabled(): void {
        // Prepare
        $this->setSettings([
            'scout.queue' => true,
        ]);

        // Model
        $model = new SearchableImplTest_Model();

        $model->setAttribute($model->getKeyName(), $this->faker->randomNumber());

        $models = new Collection([$model]);

        // Mock
        $this->override(Indexer::class, static function (MockInterface $mock) use ($models): void {
            $mock
                ->shouldReceive('dispatch')
                ->with($models)
                ->once();
        });

        // Test
        $model->queueMakeSearchable($models);
    }

    public function testQueueMakeSearchableQueueDisabled(): void {
        // Prepare
        $this->setSettings([
            'scout.queue' => false,
        ]);

        // Mock
        $engine = Mockery::mock(Engine::class);
        $engine
            ->shouldReceive('update')
            ->once()
            ->andReturns();

        $model = Mockery::mock(SearchableImplTest_Model::class, Searchable::class);
        $model->makePartial();
        $model
            ->shouldReceive('searchableUsing')
            ->once()
            ->andReturn($engine);

        $model->setAttribute($model->getKeyName(), $this->faker->uuid());

        // Test
        $model->queueMakeSearchable(new Collection([$model]));
    }

    public function testQueueRemoveFromSearchQueueEnabled(): void {
        // Prepare
        $this->setSettings([
            'scout.queue' => true,
        ]);

        // Model
        $model = new SearchableImplTest_Model();

        $model->setAttribute($model->getKeyName(), $this->faker->randomNumber());

        $models = new Collection([$model]);

        // Mock
        $this->override(Indexer::class, static function (MockInterface $mock) use ($models): void {
            $mock
                ->shouldReceive('dispatch')
                ->with($models)
                ->once();
        });

        $model->setAttribute($model->getKeyName(), $this->faker->uuid());

        // Test
        $model->queueRemoveFromSearch($models);
    }

    public function testQueueRemoveFromSearchQueueDisable(): void {
        // Prepare
        $this->setSettings([
            'scout.queue' => false,
        ]);

        // Mock
        $engine = Mockery::mock(Engine::class);
        $engine
            ->shouldReceive('delete')
            ->once()
            ->andReturns();

        $model = Mockery::mock(SearchableImplTest_Model::class);
        $model->makePartial();
        $model
            ->shouldReceive('searchableUsing')
            ->once()
            ->andReturn($engine);

        $model->queueRemoveFromSearch(new Collection([$model]));
    }

    public function testShouldBeSearchable(): void {
        $model = new class() extends Model implements Searchable {
            use SearchableImpl;

            /**
             * @var array<Property>
             */
            public static array $searchProperties;

            /**
             * @inheritDoc
             */
            public static function getSearchProperties(): array {
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

    public function testGetSearchConfiguration(): void {
        $model  = new class() extends Model implements Searchable {
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
            public static function getSearchProperties(): array {
                return ['a' => new Text('a')];
            }
        };
        $config = $model->getSearchConfiguration();

        self::assertEquals(
            [
                Configuration::getId()           => new Uuid($model->getKeyName(), false),
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

    public function testSearchableAs(): void {
        $model = new class() extends Model implements Searchable {
            use SearchableImpl {
                scoutSearchableAs as public;
            }

            /**
             * @inheritDoc
             */
            public static function getSearchProperties(): array {
                return [];
            }
        };

        self::assertEquals('test', $model->setSearchableAs('test')->searchableAs());
        self::assertEquals(
            $model->scoutSearchableAs(),
            $model->setSearchableAs(null)->searchableAs(),
        );
    }

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

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class SearchableImplTest_Model extends Model implements Searchable {
    use SearchableImpl;

    /**
     * @inheritDoc
     */
    public static function getSearchProperties(): array {
        return [
            'a' => new Uuid('a'),
        ];
    }
}
