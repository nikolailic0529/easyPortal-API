<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Models\Asset;
use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Properties\Text;
use Closure;
use DateTimeInterface;
use Elasticsearch\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Laravel\Scout\Events\ModelsImported;
use LastDragon_ru\LaraASP\Eloquent\Iterators\ChunkedChangeSafeIterator;
use Mockery;
use Tests\TestCase;
use Tests\WithSearch;

use function ceil;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Updater
 */
class UpdaterTest extends TestCase {
    use WithSearch;
    use GlobalScopes;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::update
     *
     * @dataProvider dataProviderModels
     *
     * @param null|\Closure(\Tests\TestCase): \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model> $from
     * @param array<string, mixed>                                                                       $settings
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     * @param \Closure(\Tests\TestCase): ?\DateTimeInterface|null                                        $from
     */
    public function testUpdate(
        Closure $expected,
        array $settings,
        string $model,
        Closure $from = null,
        string $continue = null,
    ): void {
        // Settings
        $this->setSettings($settings);
        $this->setSettings([
            'scout.queue' => true, // Should be used in any case.
        ]);

        // Fake
        Event::fake([
            ModelsImported::class,
        ]);

        // Mock
        $updater = Mockery::mock(Updater::class);
        $updater->shouldAllowMockingProtectedMethods();
        $updater->makePartial();
        $updater
            ->shouldReceive('getConfig')
            ->atLeast()
            ->once()
            ->andReturn($this->app->make(Repository::class));
        $updater
            ->shouldReceive('getClient')
            ->atLeast()
            ->once()
            ->andReturn($this->app->make(Client::class));
        $updater
            ->shouldReceive('getDispatcher')
            ->atLeast()
            ->once()
            ->andReturn($this->app->make(Dispatcher::class));

        // Prepare
        $expected    = $expected($this);
        $chunk       = 1;
        $from        = $from ? $from($this) : null;
        $changes     = (int) ceil(count($expected) / $chunk);
        $previous    = null;
        $spyOnInit   = Mockery::spy(function (Status $status) use ($expected, $from, $continue): void {
            $this->assertEquals(count($expected), $status->total);
            $this->assertEquals($continue, $status->continue);
            $this->assertEquals($from, $status->from);

            $this->assertFalse($this->app->get(Repository::class)->get('scout.queue'));
        });
        $spyOnChange = Mockery::spy(function (Collection $items, Status $status) use (&$previous, $chunk): void {
            $this->assertLessThanOrEqual($chunk, count($items));
            $this->assertEquals($previous?->processed + $chunk, $status->processed);

            $previous = $status;
        });
        $spyOnFinish = Mockery::spy(function (Status $status) use ($expected): void {
            $this->assertEquals(count($expected), $status->processed);
        });

        // Call
        $updater
            ->onInit(Closure::fromCallable($spyOnInit))
            ->onChange(Closure::fromCallable($spyOnChange))
            ->onFinish(Closure::fromCallable($spyOnFinish))
            ->update(
                $model,
                $from,
                $continue,
                $chunk,
            );

        // Test
        if (!$this->app->make(Repository::class)->get('scout.soft_delete', false)) {
            $expected = $expected
                ->filter(static function (Model $model): bool {
                    return !$model->trashed();
                })
                ->values();
        }

        $this->assertEquals($expected, $this->callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($model): Collection {
                return $model::search()->withTrashed()->get()->toBase();
            },
        ));

        $spyOnInit
            ->shouldHaveBeenCalled()
            ->once();
        $spyOnChange
            ->shouldHaveBeenCalled()
            ->times($changes);
        $spyOnFinish
            ->shouldHaveBeenCalled()
            ->once();

        Event::assertDispatchedTimes(ModelsImported::class, $changes);
    }

    /**
     * @covers ::getIterator
     *
     * @dataProvider dataProviderModels
     *
     * @param null|\Closure(\Tests\TestCase): \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model> $from
     * @param array<string, mixed>                                                                       $settings
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     * @param \Closure(\Tests\TestCase): ?\DateTimeInterface|null                                        $from
     */
    public function testGetIterator(
        Closure $expected,
        array $settings,
        string $model,
        Closure $from = null,
        string $continue = null,
    ): void {
        // Settings
        $this->setSettings($settings);
        $this->setSettings([
            'scout.chunk.searchable' => 1,
        ]);

        // Mock
        $updater = Mockery::mock(Updater::class);
        $updater->shouldAllowMockingProtectedMethods();
        $updater->makePartial();
        $updater
            ->shouldReceive('getConfig')
            ->once()
            ->andReturn($this->app->make(Repository::class));

        // Prepare
        $expected = $expected($this);
        $from     = $from ? $from($this) : null;
        $actual   = $this->callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($updater, $model, $from, $continue): Collection {
                return (new Collection($updater->getIterator($model, $from, null, $continue)))
                    ->map(static function (Model $model): Model {
                        return $model->withoutRelations();
                    });
            },
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getIterator
     */
    public function testGetIteratorEagerLoading(): void {
        // Mock
        $model = Mockery::mock(Model::class);
        $model->makePartial();
        $model
            ->shouldReceive('makeAllSearchableUsing')
            ->once()
            ->andReturns();

        $builder = Mockery::mock(EloquentBuilder::class);
        $builder->makePartial();
        $builder
            ->shouldReceive('newModelInstance')
            ->once()
            ->andReturn($model);
        $builder
            ->shouldReceive('getModel')
            ->once()
            ->andReturns($model);
        $builder
            ->shouldReceive('toBase')
            ->twice()
            ->andReturn(Mockery::mock(QueryBuilder::class)->makePartial());

        $updater = Mockery::mock(Updater::class);
        $updater->shouldAllowMockingProtectedMethods();
        $updater->makePartial();
        $updater
            ->shouldReceive('getConfig')
            ->once()
            ->andReturn($this->app->make(Repository::class));
        $updater
            ->shouldReceive('getBuilder')
            ->once()
            ->andReturn($builder);

        // Test
        $this->assertInstanceOf(
            ChunkedChangeSafeIterator::class,
            $updater->getIterator(Model::class, null, null, null),
        );
    }

    /**
     * @covers ::getBuilder
     *
     * @dataProvider dataProviderModels
     *
     * @param null|\Closure(\Tests\TestCase): \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model> $from
     * @param array<string, mixed>                                                                       $settings
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     * @param \Closure(\Tests\TestCase): ?\DateTimeInterface|null                                        $from
     */
    public function testGetBuilder(
        Closure $expected,
        array $settings,
        string $model,
        Closure $from = null,
        string $continue = null,
    ): void {
        // Settings
        $this->setSettings($settings);

        // Mock
        $updater = Mockery::mock(Updater::class);
        $updater->shouldAllowMockingProtectedMethods();
        $updater->makePartial();

        // Prepare
        $expected = $expected($this);
        $from     = $from ? $from($this) : null;
        $actual   = $this->callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($updater, $model, $from): Collection {
                return $updater->getBuilder($model, $from)->get()->toBase();
            },
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getTotal
     *
     * @dataProvider dataProviderModels
     *
     * @param null|\Closure(\Tests\TestCase): \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model> $from
     * @param array<string, mixed>                                                                       $settings
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     * @param \Closure(\Tests\TestCase): ?\DateTimeInterface|null                                        $from
     */
    public function testGetTotal(
        Closure $expected,
        array $settings,
        string $model,
        Closure $from = null,
        string $continue = null,
    ): void {
        // Settings
        $this->setSettings($settings);

        // Mock
        $updater = Mockery::mock(Updater::class);
        $updater->shouldAllowMockingProtectedMethods();
        $updater->makePartial();

        // Prepare
        $expected = count($expected($this));
        $from     = $from ? $from($this) : null;
        $actual   = $this->callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($updater, $model, $from): int {
                return $updater->getTotal($model, $from);
            },
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::createIndex
     *
     * @dataProvider dataProviderCreateIndex
     *
     * @param array<mixed>                                                             $expected
     * @param class-string<\App\Models\Model&\App\Services\Search\Eloquent\Searchable> $model
     * @param array<string, string|null>                                               $indexes
     */
    public function testCreateIndex(array $expected, string $model, array $indexes): void {
        // Mock
        $updater = Mockery::mock(Updater::class);
        $updater->shouldAllowMockingProtectedMethods();
        $updater->makePartial();
        $updater
            ->shouldReceive('getClient')
            ->once()
            ->andReturn($this->app->make(Client::class));

        // Prepare
        foreach ($indexes as $index => $alias) {
            $this->createSearchIndex($index, $alias);
        }

        // Run
        $updater->createIndex($model);

        // Test
        $this->assertSearchIndexes($expected);
    }

    /**
     * @covers ::switchIndex
     */
    public function testSwitchIndex(): void {
        // Mock
        $updater = Mockery::mock(Updater::class);
        $updater->shouldAllowMockingProtectedMethods();
        $updater->makePartial();
        $updater
            ->shouldReceive('getClient')
            ->once()
            ->andReturn($this->app->make(Client::class));

        // Prepare
        $model  = new Asset();
        $config = $model->getSearchConfiguration();
        $index  = "{$model->searchableAs()}@123";

        $this->createSearchIndex($config->getIndexName());
        $this->createSearchIndex($index, $model->searchableAs());

        // Run
        $updater->switchIndex($model::class);

        // Test
        $this->assertSearchIndexes([
            $config->getIndexName() => [
                'aliases' => [
                    $config->getIndexAlias() => [
                        'is_write_index' => true,
                    ],
                ],
            ],
        ]);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderCreateIndex(): array {
        $index = 'testing_test_models@84da83a20276200ffe0201417c4a35e7ffc0832f';
        $model = new class() extends Model {
            use Searchable;

            /**
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
             *
             * @var string
             */
            protected $table = 'test_models';

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [
                    'a' => new Text('a'),
                ];
            }
        };

        return [
            'no index + no alias'      => [
                [
                    $index => [
                        'aliases' => [
                            'test_models' => [
                                'is_write_index' => true,
                            ],
                        ],
                    ],
                ],
                $model::class,
                [
                    // empty
                ],
            ],
            'index without alias'      => [
                [
                    $index => [
                        'aliases' => [
                            'test_models' => [
                                'is_write_index' => true,
                            ],
                        ],
                    ],
                ],
                $model::class,
                [
                    $index => null,
                ],
            ],
            'another index with alias' => [
                [
                    'another_index' => [
                        'aliases' => [
                            'test_models' => [
                                'is_write_index' => true,
                            ],
                        ],
                    ],
                    $index          => [
                        'aliases' => [
                            // empty
                        ],
                    ],
                ],
                $model::class,
                [
                    'another_index' => 'test_models',
                ],
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderModels(): array {
        return [
            'models with `from` and `continue`'                 => [
                static function (TestCase $test): Collection {
                    // Old
                    Asset::factory()->create([
                        'id'         => '3a25b90c-9022-4eea-a3f5-be5285152794',
                        'updated_at' => Date::now()->subDay(),
                    ]);

                    // < continue
                    Asset::factory()->create([
                        'id'         => '3ea13c8b-b024-44c7-94ec-3877f5785152',
                        'updated_at' => Date::now()->subDay(),
                    ]);

                    // Soft Deleted
                    $a = Asset::factory()->create([
                        'id'         => '3ebc9cc2-6e6a-495e-8b56-e6cbdec9832b',
                        'updated_at' => Date::now()->addDay(),
                        'deleted_at' => Date::now(),
                    ]);

                    // Actual
                    $b = Asset::factory()->create([
                        'id'         => '5dc9b072-c1e2-497e-a2cd-32ae62ee5096',
                        'updated_at' => Date::now()->addDay(),
                    ]);
                    $c = Asset::factory()->create([
                        'id'         => 'c7810bc4-911e-4639-96ca-ba44344fcd6c',
                        'updated_at' => Date::now()->addDay(),
                    ]);

                    // Return
                    return new Collection([$a, $b, $c]);
                },
                [
                    // empty
                ],
                Asset::class,
                static function (TestCase $test): DateTimeInterface {
                    return Date::now();
                },
                '3ea13c8b-b024-44c7-94ec-3877f5785152',
                2,
            ],
            'models without `from` and `continue`'              => [
                static function (TestCase $test): Collection {
                    // Old
                    $a = Asset::factory()->create([
                        'id'         => '3a25b90c-9022-4eea-a3f5-be5285152794',
                        'updated_at' => Date::now()->subDay(),
                    ]);

                    // < continue
                    $b = Asset::factory()->create([
                        'id'         => '3ea13c8b-b024-44c7-94ec-3877f5785152',
                        'updated_at' => Date::now()->subDay(),
                    ]);

                    // Soft Deleted
                    $c = Asset::factory()->create([
                        'id'         => '3ebc9cc2-6e6a-495e-8b56-e6cbdec9832b',
                        'updated_at' => Date::now()->addDay(),
                        'deleted_at' => Date::now(),
                    ]);

                    // Actual
                    $d = Asset::factory()->create([
                        'id'         => '5dc9b072-c1e2-497e-a2cd-32ae62ee5096',
                        'updated_at' => Date::now()->addDay(),
                    ]);
                    $e = Asset::factory()->create([
                        'id'         => 'c7810bc4-911e-4639-96ca-ba44344fcd6c',
                        'updated_at' => Date::now()->addDay(),
                    ]);

                    // Return
                    return new Collection([$a, $b, $c, $d, $e]);
                },
                [
                    // empty
                ],
                Asset::class,
                null,
                null,
                null,
            ],
            'models without `from` and `continue` + softDelete' => [
                static function (TestCase $test): Collection {
                    // Old
                    $a = Asset::factory()->create([
                        'id'         => '3a25b90c-9022-4eea-a3f5-be5285152794',
                        'updated_at' => Date::now()->subDay(),
                    ]);

                    // < continue
                    $b = Asset::factory()->create([
                        'id'         => '3ea13c8b-b024-44c7-94ec-3877f5785152',
                        'updated_at' => Date::now()->subDay(),
                    ]);

                    // Soft Deleted
                    $c = Asset::factory()->create([
                        'id'         => '3ebc9cc2-6e6a-495e-8b56-e6cbdec9832b',
                        'updated_at' => Date::now()->addDay(),
                        'deleted_at' => Date::now(),
                    ]);

                    // Actual
                    $d = Asset::factory()->create([
                        'id'         => '5dc9b072-c1e2-497e-a2cd-32ae62ee5096',
                        'updated_at' => Date::now()->addDay(),
                    ]);
                    $e = Asset::factory()->create([
                        'id'         => 'c7810bc4-911e-4639-96ca-ba44344fcd6c',
                        'updated_at' => Date::now()->addDay(),
                    ]);

                    // Return
                    return new Collection([$a, $b, $c, $d, $e]);
                },
                [
                    'scout.soft_delete' => true,
                ],
                Asset::class,
                null,
                null,
                null,
            ],
        ];
    }
    // </editor-fold>
}
