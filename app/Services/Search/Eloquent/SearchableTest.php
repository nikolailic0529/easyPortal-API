<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Configuration;
use App\Services\Search\Properties\Text;
use App\Services\Search\Properties\Uuid;
use App\Services\Search\ScopeWithMetadata;
use App\Services\Search\Updater;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Eloquent\Searchable
 */
class SearchableTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::searchIndexShouldBeUpdated
     */
    public function testSearchIndexShouldBeUpdated(): void {
        // Model should be updated if property was changed
        $model = new class() extends Model {
            use Searchable;

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

        $this->assertTrue($model->searchIndexShouldBeUpdated());

        // Relations should be ignored
        $model = new class() extends Model {
            use Searchable;

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

        $this->assertFalse($model->searchIndexShouldBeUpdated());
    }

    /**
     * @covers ::toSearchableArray
     * @covers ::toSearchableArrayValue
     * @covers ::toSearchableArrayCleanup
     */
    public function testToSearchableArray(): void {
        // Prepare
        $sku   = $this->faker->uuid;
        $oem   = Oem::factory()->create();
        $group = ServiceGroup::factory()->create([
            'sku'    => $sku,
            'oem_id' => $oem,
        ]);

        // Model
        $model = new class() extends ServiceGroup {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [
                    'sku'            => new Text('sku'),
                    'oem'            => [
                        'id' => new Uuid('oem.id'),
                    ],
                    'unknown'        => [
                        'id' => new Uuid('oem.unknown'),
                    ],
                    'unknown_nested' => [
                        'null'    => new Uuid('oem.unknown'),
                        'oem_id'  => new Uuid('oem.id'),
                        'unknown' => [
                            'id' => new Uuid('oem.unknown'),
                        ],
                    ],
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
                'sku'            => $sku,
                'oem'            => [
                    'id' => $oem->getKey(),
                ],
                'unknown'        => null,
                'unknown_nested' => [
                    'null'    => null,
                    'oem_id'  => $oem->getKey(),
                    'unknown' => null,
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::makeAllSearchable
     */
    public function testMakeAllSearchable(): void {
        $chunk = $this->faker->randomDigitNotNull;
        $model = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }
        };

        $this->override(Updater::class, static function (MockInterface $updater) use ($model, $chunk): void {
            $updater
                ->shouldReceive('update')
                ->with($model::class, null, null, $chunk)
                ->once();
        });

        // Test
        $model->makeAllSearchable($chunk);
    }

    /**
     * @covers ::makeAllSearchableUsing
     */
    public function testMakeAllSearchableUsing(): void {
        // Model
        $model = new class() extends Model {
            use Searchable;

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
            use Searchable {
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
            $this->expectExceptionObject($expected);
        }

        $this->assertEquals($expected, $model->toSearchableValue($value));
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
            use Searchable;

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

        $this->assertCount(1, $model->models);
        $this->assertSame($a, $model->models->first());
    }

    /**
     * @covers ::shouldBeSearchable
     */
    public function testShouldBeSearchable(): void {
        $model = new class() extends Model {
            use Searchable;

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

        $this->assertFalse($model->shouldBeSearchable());

        // Properties
        $model::$searchProperties = ['a' => new Text('a')];

        $this->assertTrue($model->shouldBeSearchable());
    }

    /**
     * @covers ::getSearchConfiguration
     */
    public function testGetSearchConfiguration(): void {
        $model  = new class() extends Model {
            use Searchable;

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

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertEquals(
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
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }
        };

        $this->assertEquals('test', $model->setSearchableAs('test')->searchableAs());
        $this->assertEquals(
            $model->scoutSearchableAs(),
            $model->setSearchableAs(null)->searchableAs(),
        );
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
