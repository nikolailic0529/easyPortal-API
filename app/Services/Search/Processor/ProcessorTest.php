<?php declare(strict_types = 1);

namespace App\Services\Search\Processor;

use App\Models\Asset;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Properties\Property;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Processor\State as ProcessorState;
use Closure;
use Database\Factories\AssetFactory;
use Elasticsearch\Client;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Laravel\Scout\Events\ModelsImported;
use LogicException;
use Mockery;
use Tests\TestCase;
use Tests\WithSearch;

use function ceil;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Processor\Processor
 */
class ProcessorTest extends TestCase {
    use WithSearch;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::process
     * @covers ::getOnChangeEvent
     *
     * @dataProvider dataProviderModels
     *
     * @param array<string, mixed>           $settings
     * @param class-string<Model&Searchable> $model
     * @param array<string|int>|null         $keys
     */
    public function testUpdate(
        Closure $expected,
        array $settings,
        string $model,
        array $keys = null,
        bool $rebuild = false,
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

        // Prepare
        $expected    = $expected($this);
        $chunk       = 1;
        $changes     = $expected instanceof Collection
            ? (int) ceil(count($expected) / $chunk)
            : 0;
        $previous    = null;
        $spyOnInit   = Mockery::spy(function (State $state) use ($expected): void {
            $this->assertEquals(count($expected), $state->total);
            $this->assertFalse($this->app->get(Repository::class)->get('scout.queue'));
        });
        $spyOnChange = Mockery::spy(function (State $state) use (&$previous, $chunk): void {
            $this->assertEquals($previous?->processed + $chunk, $state->processed);

            $previous = $state;
        });
        $spyOnFinish = Mockery::spy(function (State $state) use ($expected): void {
            $this->assertEquals(count($expected), $state->processed);
        });

        // Error?
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        // Call
        $this->app->make(Processor::class)
            ->onInit(Closure::fromCallable($spyOnInit))
            ->onChange(Closure::fromCallable($spyOnChange))
            ->onFinish(Closure::fromCallable($spyOnFinish))
            ->setRebuild($rebuild)
            ->setKeys($keys)
            ->setModel($model)
            ->setChunkSize($chunk)
            ->start();

        // Test
        $expected = $expected
            ->filter(static function (Model $model): bool {
                return $model->shouldBeSearchable();
            });

        if (!$this->app->make(Repository::class)->get('scout.soft_delete', false)) {
            $expected = $expected
                ->filter(static function (Model $model): bool {
                    return !$model->trashed();
                });
        }

        $this->assertEquals(
            $expected->map(new GetKey())->sort()->values(),
            GlobalScopes::callWithoutGlobalScope(
                OwnedByOrganizationScope::class,
                static function () use ($model): Collection {
                    return $model::search()
                        ->withTrashed()
                        ->get()
                        ->map(new GetKey())
                        ->sort()
                        ->values()
                        ->toBase();
                },
            ),
        );

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
     * @covers ::getBuilder
     */
    public function testGetBuilderEagerLoading(): void {
        // Mock
        $model    = new class() extends Model {
            public static Builder $builder;

            public static function query(): Builder {
                return self::$builder;
            }
        };
        $instance = Mockery::mock(Model::class);
        $instance->makePartial();
        $instance
            ->shouldReceive('makeAllSearchableUsing')
            ->once()
            ->andReturnUsing(static function () use ($model): Builder {
                return $model::query();
            });

        $model::$builder = Mockery::mock(Builder::class);
        $model::$builder->makePartial();
        $model::$builder
            ->shouldReceive('newModelInstance')
            ->once()
            ->andReturn($instance);
        $model::$builder
            ->shouldReceive('getModel')
            ->once()
            ->andReturn($instance);

        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();

        $processor
            ->setModel($model::class)
            ->getBuilder(new State([
                'model' => $processor->getModel(),
            ]));
    }

    /**
     * @covers ::createIndex
     *
     * @dataProvider dataProviderCreateIndex
     *
     * @param array<mixed>                                       $expected
     * @param class-string<\App\Utils\Eloquent\Model&Searchable> $model
     * @param array<string, string|null>                         $indexes
     */
    public function testCreateIndex(array $expected, string $model, array $indexes): void {
        // Mock
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getClient')
            ->once()
            ->andReturn($this->app->make(Client::class));

        // Prepare
        foreach ($indexes as $index => $alias) {
            $this->createSearchIndex($index, $alias);
        }

        // Run
        $processor->createIndex(new State([
            'model' => $model,
        ]));

        // Test
        $this->assertSearchIndexes($expected);
    }

    /**
     * @covers ::switchIndex
     */
    public function testSwitchIndex(): void {
        // Mock
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
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
        $processor->switchIndex(new State([
            'model' => $model::class,
        ]));

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

    /**
     * @covers ::isIndexActual
     *
     * @dataProvider dataProviderIsIndexActual
     *
     * @param class-string<\App\Utils\Eloquent\Model&Searchable> $model
     * @param array<string, string|null>                         $indexes
     */
    public function testIsIndexActual(bool $expected, string $model, array $indexes): void {
        // Mock
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('getClient')
            ->once()
            ->andReturn($this->app->make(Client::class));

        // Prepare
        foreach ($indexes as $index => $alias) {
            $this->createSearchIndex($index, $alias);
        }

        // Test
        $this->assertEquals($expected, $processor->isIndexActual(new State([
            'model' => $model,
        ])));
    }

    /**
     * @covers ::init
     */
    public function testInit(): void {
        $name      = $this->faker->word;
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('notifyOnInit')
            ->twice()
            ->andReturns();
        $processor
            ->shouldReceive('createIndex')
            ->once()
            ->andReturn($name);

        // Update
        $state = new State(['rebuild' => false]);

        $processor->init($state);

        $this->assertNull($state->name);

        // Rebuild
        $state = new State(['rebuild' => true]);

        $processor->init($state);

        $this->assertEquals($name, $state->name);
    }

    /**
     * @covers ::finish
     */
    public function testFinish(): void {
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('notifyOnFinish')
            ->twice()
            ->andReturns();
        $processor
            ->shouldReceive('switchIndex')
            ->once()
            ->andReturns();

        $processor->finish(new State(['rebuild' => false]));
        $processor->finish(new State(['rebuild' => true]));
    }

    /**
     * @covers ::process
     *
     * @dataProvider dataProviderProcess
     *
     * @param array<string, mixed> $settings
     */
    public function testProcess(
        bool $expected,
        array $settings,
        bool $modelSearchable,
        bool $modelSoftDeletes,
        ?bool $modelTrashed,
    ): void {
        // Settings
        $this->setSettings($settings);

        // Mock
        $as    = $this->faker->word;
        $index = $this->faker->randomElement([null, $this->faker->word]);
        $model = $modelSoftDeletes
            ? Mockery::mock(ProcessorTest__ModelSoftDeletes::class)
            : Mockery::mock(Model::class);
        $model
            ->shouldReceive('shouldBeSearchable')
            ->once()
            ->andReturn($modelSearchable);
        $model
            ->shouldReceive('searchableAs')
            ->once()
            ->andReturn($as);
        $model
            ->shouldReceive('setSearchableAs')
            ->with($index)
            ->once()
            ->andReturnSelf();
        $model
            ->shouldReceive('setSearchableAs')
            ->with($as)
            ->once()
            ->andReturnSelf();

        if ($modelSoftDeletes && $modelTrashed !== null) {
            $model
                ->shouldReceive('trashed')
                ->once()
                ->andReturn($modelTrashed);
        }

        if ($expected) {
            $model
                ->shouldReceive('searchable')
                ->once()
                ->andReturns();
        } else {
            $model
                ->shouldReceive('unsearchable')
                ->once()
                ->andReturns();
        }

        // Test
        $state     = new State(['name' => $index]);
        $config    = $this->app->make(Repository::class);
        $processor = new class($config) extends Processor {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Repository $config,
            ) {
                // empty
            }

            protected function getConfig(): Repository {
                return $this->config;
            }

            public function process(ProcessorState $state, mixed $data, mixed $item): void {
                parent::process($state, $data, $item);
            }
        };

        $processor->process($state, null, $model);
    }

    /**
     * @covers ::defaultState
     */
    public function testDefaultState(): void {
        $keys      = [$this->faker->uuid, $this->faker->uuid];
        $model     = Model::class;
        $processor = Mockery::mock(Processor::class);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();

        $this->assertEquals(
            [
                'model'       => $model,
                'keys'        => $keys,
                'withTrashed' => true,
                'rebuild'     => false,
                'name'        => null,
            ],
            $processor->setModel($model)->setKeys($keys)->setRebuild(false)->defaultState([]),
        );
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderCreateIndex(): array {
        $index = 'testing_test_models@4ba247ffb340f00f8225223275e3aedaf9b531a1';
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
                    'a' => new class('a') extends Property {
                        public function getType(): string {
                            return 'text';
                        }
                    },
                ];
            }
        };

        return [
            'index with same name as alias' => [
                [
                    $index => [
                        'aliases' => [
                            $model->getTable() => [
                                'is_write_index' => true,
                            ],
                        ],
                    ],
                ],
                $model::class,
                [
                    $model->getTable() => null,
                ],
            ],
            'no index + no alias'           => [
                [
                    $index => [
                        'aliases' => [
                            $model->getTable() => [
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
            'index without alias'           => [
                [
                    $index => [
                        'aliases' => [
                            $model->getTable() => [
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
            'another index with alias'      => [
                [
                    'another_index' => [
                        'aliases' => [
                            $model->getTable() => [
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
                    'another_index' => $model->getTable(),
                ],
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderModels(): array {
        return [
            'all'                 => [
                static function (): Collection {
                    // Models
                    $a = Asset::factory()->create([
                        'deleted_at' => Date::now(),
                    ]);
                    $b = Asset::factory()->create();
                    $c = Asset::factory()->create();

                    // Return
                    return new Collection([$a, $b, $c]);
                },
                [
                    // empty
                ],
                Asset::class,
                null,
                true,
            ],
            'all + softDelete'    => [
                static function (): Collection {
                    // Models
                    $a = Asset::factory()->create([
                        'deleted_at' => Date::now(),
                    ]);
                    $b = Asset::factory()->create();
                    $c = Asset::factory()->create();

                    // Return
                    return new Collection([$a, $b, $c]);
                },
                [
                    'scout.soft_delete' => true,
                ],
                Asset::class,
                null,
                true,
            ],
            'keys'                => [
                static function (): Collection {
                    // Models
                    $a = Asset::factory()->create([
                        'id' => '3a25b90c-9022-4eea-a3f5-be5285152794',
                    ]);
                    $b = Asset::factory()->create([
                        'id' => '3ea13c8b-b024-44c7-94ec-3877f5785152',
                    ]);

                    Asset::factory()->create([
                        'id' => '5dc9b072-c1e2-497e-a2cd-32ae62ee5096',
                    ]);

                    // Return
                    return new Collection([$a, $b]);
                },
                [
                    // empty
                ],
                Asset::class,
                [
                    '3a25b90c-9022-4eea-a3f5-be5285152794',
                    '3ea13c8b-b024-44c7-94ec-3877f5785152',
                ],
                false,
            ],
            'rebuild with keys'   => [
                static function (): Exception {
                    return new LogicException('Rebuild is not possible because keys are specified.');
                },
                [
                    // empty
                ],
                Asset::class,
                [
                    'afe6f959-afa4-4b2e-8a77-aa8549ed144e',
                ],
                true,
            ],
            'unsearchable models' => [
                static function (): Collection {
                    // Models
                    $a = ProcessorTest_UnsearchableAsset::factory()->create([
                        'id' => '3a25b90c-9022-4eea-a3f5-be5285152794',
                    ]);

                    // Return
                    return new Collection([$a]);
                },
                [
                    // empty
                ],
                ProcessorTest_UnsearchableAsset::class,
                null,
                true,
            ],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderIsIndexActual(): array {
        $index = 'testing_test_models@4ba247ffb340f00f8225223275e3aedaf9b531a1';
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
                    'a' => new class('a') extends Property {
                        public function getType(): string {
                            return 'text';
                        }
                    },
                ];
            }
        };

        return [
            'no index + no alias'      => [
                false,
                $model::class,
                [
                    // empty
                ],
            ],
            'index without alias'      => [
                false,
                $model::class,
                [
                    $index => null,
                ],
            ],
            'index with alias'         => [
                true,
                $model::class,
                [
                    $index => $model->getTable(),
                ],
            ],
            'another index with alias' => [
                false,
                $model::class,
                [
                    $index          => 'another_alias',
                    'another_index' => $model->getTable(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{bool, array<string,mixed>, bool, bool, ?bool}>
     */
    public function dataProviderProcess(): array {
        return [
            'searchable'                             => [
                true,
                [],
                true,
                false,
                false,
            ],
            'unsearchable'                           => [
                false,
                [],
                false,
                false,
                false,
            ],
            'searchable + SoftDeletes + not trashed' => [
                true,
                [],
                true,
                true,
                false,
            ],
            'searchable + SoftDeletes + trashed'     => [
                false,
                [],
                true,
                true,
                true,
            ],
            'searchable + SoftDeletes + indexed'     => [
                true,
                [
                    'scout.soft_delete' => true,
                ],
                true,
                true,
                null,
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
class ProcessorTest_UnsearchableAsset extends Asset {
    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);

        Relation::morphMap([
            'ProcessorTest_UnsearchableAsset' => $this::class,
        ]);
    }

    public function shouldBeSearchable(): bool {
        return false;
    }

    protected static function newFactory(): Factory {
        return new ProcessorTest_UnsearchableAssetFactory();
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ProcessorTest_UnsearchableAssetFactory extends AssetFactory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = ProcessorTest_UnsearchableAsset::class;
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @see          https://github.com/mockery/mockery/issues/1022
 */
abstract class ProcessorTest__ModelSoftDeletes extends Model {
    use SoftDeletes;
}
// @phpcs:enable
