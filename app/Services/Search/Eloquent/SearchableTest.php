<?php declare(strict_types = 1);

namespace App\Services\Search\Eloquent;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Search\Builder as SearchBuilder;
use App\Services\Search\ScopeWithMetadata;
use Closure;
use DateTime;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use LogicException;
use Mockery;
use PHPUnit\Framework\Assert;
use stdClass;
use Tests\TestCase;

use function config;
use function count;
use function sprintf;

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
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return ['a' => 'a'];
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
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return ['a.b' => 'a'];
            }
        };

        $this->assertFalse($model->searchIndexShouldBeUpdated());
    }

    /**
     * @covers ::toSearchableArray
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
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return [
                    'sku' => 'sku',
                    'oem' => [
                        'id' => 'oem.id',
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
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            public function getSearchMetadata(Model $model): array {
                return [
                    'sku' => 'sku',
                ];
            }
        };

        $model->addGlobalScope($scope);

        // Test
        $actual   = $model->find($group->getKey())->toSearchableArray();
        $expected = [
            SearchBuilder::METADATA   => [
                'sku' => $sku,
            ],
            SearchBuilder::PROPERTIES => [
                'sku' => $sku,
                'oem' => [
                    'id' => $oem->getKey(),
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }


    /**
     * @covers ::makeAllSearchable
     */
    public function testMakeAllSearchable(): void {
        // Prepare
        $key    = 'scout.queue';
        $config = $this->app->make(Repository::class);

        $config->set($key, true);

        $this->assertTrue($config->get($key));

        // Spy
        $spy = Mockery::spy(function () use ($config, $key): void {
            $this->assertFalse($config->get($key));
        });

        // Model
        $model = new class() extends Model {
            use OwnedByOrganization;
            use Searchable;

            public static Closure $spy;

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return [];
            }

            public static function scoutMakeAllSearchable(): void {
                Assert::assertFalse(config('scout.queue'));

                (self::$spy)();
            }
        };

        $model::$spy = Closure::fromCallable($spy);

        // Test
        $model->makeAllSearchable();

        $spy->shouldHaveBeenCalled();
    }

    /**
     * @covers ::makeAllSearchableUsing
     */
    public function testMakeAllSearchableUsing(): void {
        // Model
        $model = new class() extends Model {
            use Searchable {
                makeAllSearchableUsing as public;
            }

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return [];
            }

            /**
             * @return array<string>
             */
            protected static function getSearchableRelations(): array {
                return ['a', 'b'];
            }
        };

        // Builder
        $builder = Mockery::mock(Builder::class);
        $builder
            ->shouldReceive('with')
            ->with(['a', 'b'])
            ->once()
            ->andReturnSelf();

        // Test
        $model->makeAllSearchableUsing($builder);
    }

    /**
     * @covers ::getSearchableRelations
     */
    public function testGetSearchableRelations(): void {
        // Prepare
        $model = new class() extends Model {
            use Searchable {
                getSearchableRelations as public;
            }

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return [
                    'sku' => 'sku',
                    'oem' => [
                        'id' => 'oem.id',
                    ],
                ];
            }

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchMetadata(): array {
                return [
                    'meta' => 'meta.data',
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
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            public function getSearchMetadata(Model $model): array {
                return [
                    'sku' => 'abc.sku',
                    'id'  => 'oem.id',
                ];
            }
        };

        $model->addGlobalScope($scope);

        // Test
        $this->assertEquals(['meta', 'abc', 'oem'], $model->getSearchableRelations());
    }

    /**
     * @covers ::getSearchableProperties
     */
    public function testGetSearchableProperties(): void {
        // Prepare
        $model = new class() extends Model {
            use Searchable {
                getSearchableProperties as public;
            }

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return [
                    'sku' => 'sku',
                    'oem' => [
                        'id' => 'oem.id',
                    ],
                ];
            }

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchMetadata(): array {
                return [
                    'meta' => 'meta.data',
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
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            public function getSearchMetadata(Model $model): array {
                return [
                    'sku' => 'abc.sku',
                    'id'  => 'oem.id',
                ];
            }
        };

        $model->addGlobalScope($scope);

        // Test
        $this->assertEquals([
            SearchBuilder::METADATA   => [
                'sku'  => 'abc.sku',
                'id'   => 'oem.id',
                'meta' => 'meta.data',
            ],
            SearchBuilder::PROPERTIES => [
                'sku' => 'sku',
                'oem' => [
                    'id' => 'oem.id',
                ],
            ],
        ], $model->getSearchableProperties());
    }

    /**
     * @covers ::getSearchableProperties
     */
    public function testGetSearchablePropertiesMetadataConflict(): void {
        // Prepare
        $model = new class() extends Model {
            use Searchable {
                getSearchableProperties as public;
            }

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return [
                    'sku' => 'sku',
                    'oem' => [
                        'id' => 'oem.id',
                    ],
                ];
            }

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchMetadata(): array {
                return [
                    'meta' => 'meta.data',
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
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            public function getSearchMetadata(Model $model): array {
                return [
                    'meta' => 'meta.data',
                ];
            }
        };

        $model->addGlobalScope($scope);

        // Test
        $this->expectExceptionObject(new LogicException(sprintf(
            'The `%s` trying to redefine `%s` in metadata.',
            $scope::class,
            'meta',
        )));

        $model->getSearchableProperties();
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
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
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
     * @covers ::callWithoutScoutQueue
     */
    public function testCallWithoutScoutQueue(): void {
        // Prepare
        $key    = 'scout.queue';
        $config = $this->app->make(Repository::class);

        $config->set($key, true);

        $this->assertTrue($config->get($key));

        // Model
        $model = new class() extends Model {
            use Searchable {
                callWithoutScoutQueue as public;
            }

            /**
             * @return array<string,string|array<string,string|array<string,string|array<string,string>>>>
             */
            protected static function getSearchProperties(): array {
                return [];
            }
        };

        // Test
        $spy = Mockery::spy(function () use ($config, $key): int {
            $this->assertFalse($config->get($key));

            return 123;
        });

        $this->assertEquals(123, $model->callWithoutScoutQueue(Closure::fromCallable($spy)));

        $spy->shouldHaveBeenCalled();
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
