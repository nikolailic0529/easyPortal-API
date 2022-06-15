<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Properties\Uuid;
use App\Services\Search\Queue\Tasks\Index;
use App\Services\Search\Queue\Tasks\ModelIndex;
use App\Services\Search\Queue\Tasks\ModelsIndex;
use App\Utils\Eloquent\Model;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

use function is_array;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Indexer
 */
class IndexerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::update
     *
     * @dataProvider dataProviderUpdate
     *
     * @param Exception|array{task: class-string<Index>, keys: array<mixed>}|null $expected
     * @param Closure(static):mixed                                               $factory
     */
    public function testUpdate(Exception|array|null $expected, Closure $factory): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $models  = $factory($this);
        $indexer = $this->app->make(Indexer::class);

        Queue::fake();

        $indexer->update($models); // @phpstan-ignore-line this is the way

        if (is_array($expected)) {
            Queue::assertPushed($expected['task'], static function (Index $task) use ($expected): bool {
                self::assertEquals($expected['keys'], $task->getKeys());

                return true;
            });
        } else {
            Queue::assertNothingPushed();
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{Exception|array{task:class-string<Index>,keys:array<mixed>}|null,Closure(static):mixed}>
     */
    public function dataProviderUpdate(): array {
        $model = new class() extends Model {
            // empty
        };

        return [
            'empty Collection'  => [
                null,
                static function (): mixed {
                    return new Collection();
                },
            ],
            'no key'            => [
                null,
                static function (): mixed {
                    return new class() extends EloquentModel {
                        // empty
                    };
                },
            ],
            'non searchable'    => [
                new InvalidArgumentException(sprintf(
                    'Model `%s` is not Searchable.',
                    $model::class,
                )),
                static function () use ($model): mixed {
                    return (clone $model)->forceFill([
                        $model->getKeyName() => '123',
                    ]);
                },
            ],
            'Collection (many)' => [
                [
                    'task' => ModelsIndex::class,
                    'keys' => [
                        '43e8dc31-8497-4e4e-b5c6-5ff68df929f9',
                        '64719313-a21d-4ec6-a48b-fcbf64d9cee9',
                    ],
                ],
                static function (): mixed {
                    $searchable = new class() extends Model implements Searchable {
                        use SearchableImpl;

                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                         *
                         * @var string
                         */
                        protected $keyType = 'string';

                        /**
                         * @inheritDoc
                         */
                        public static function getSearchProperties(): array {
                            return [
                                'id' => new Uuid('id'),
                            ];
                        }
                    };

                    return new Collection([
                        (clone $searchable)->forceFill([
                            $searchable->getKeyName() => '64719313-a21d-4ec6-a48b-fcbf64d9cee9',
                        ]),
                        (clone $searchable)->forceFill([
                            $searchable->getKeyName() => '64719313-a21d-4ec6-a48b-fcbf64d9cee9',
                        ]),
                        (clone $searchable)->forceFill([
                            $searchable->getKeyName() => '43e8dc31-8497-4e4e-b5c6-5ff68df929f9',
                        ]),
                    ]);
                },
            ],
            'Collection (one)'  => [
                [
                    'task' => ModelIndex::class,
                    'keys' => [
                        '792bdfe0-2fa9-4214-8871-bd28c6b99760',
                    ],
                ],
                static function (): mixed {
                    $searchable = new class() extends Model implements Searchable {
                        use SearchableImpl;

                        /**
                         * @inheritDoc
                         */
                        public static function getSearchProperties(): array {
                            return [
                                'id' => new Uuid('id'),
                            ];
                        }
                    };

                    return new Collection([
                        (clone $searchable)->forceFill([
                            $searchable->getKeyName() => '792bdfe0-2fa9-4214-8871-bd28c6b99760',
                        ]),
                    ]);
                },
            ],
            'Model'             => [
                [
                    'task' => ModelIndex::class,
                    'keys' => [
                        '40fbe33c-c94c-4410-85ca-c554eeba99ca',
                    ],
                ],
                static function (): mixed {
                    $searchable = new class() extends Model implements Searchable {
                        use SearchableImpl;

                        /**
                         * @inheritDoc
                         */
                        public static function getSearchProperties(): array {
                            return [
                                'id' => new Uuid('id'),
                            ];
                        }
                    };

                    return (clone $searchable)->forceFill([
                        $searchable->getKeyName() => '40fbe33c-c94c-4410-85ca-c554eeba99ca',
                    ]);
                },
            ],
            'array (many)'      => [
                [
                    'task' => ModelsIndex::class,
                    'keys' => [
                        '2221ee64-6fae-41d6-86d6-690caadc512d',
                        'b40a1b1c-0146-4557-af7a-a29ae5d82787',
                    ],
                ],
                static function (): mixed {
                    $searchable = new class() extends Model implements Searchable {
                        use SearchableImpl;

                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
                         *
                         * @var string
                         */
                        protected $keyType = 'string';

                        /**
                         * @inheritDoc
                         */
                        public static function getSearchProperties(): array {
                            return [
                                'id' => new Uuid('id'),
                            ];
                        }
                    };

                    return [
                        'model' => $searchable::class,
                        'keys'  => [
                            'b40a1b1c-0146-4557-af7a-a29ae5d82787',
                            '2221ee64-6fae-41d6-86d6-690caadc512d',
                            'b40a1b1c-0146-4557-af7a-a29ae5d82787',
                        ],
                    ];
                },
            ],
            'array (one)'       => [
                [
                    'task' => ModelIndex::class,
                    'keys' => [
                        'fea28245-f311-4ce3-af6d-8dc37673bb5f',
                    ],
                ],
                static function (): mixed {
                    $searchable = new class() extends Model implements Searchable {
                        use SearchableImpl;

                        /**
                         * @inheritDoc
                         */
                        public static function getSearchProperties(): array {
                            return [
                                'id' => new Uuid('id'),
                            ];
                        }
                    };

                    return [
                        'model' => $searchable::class,
                        'keys'  => [
                            'fea28245-f311-4ce3-af6d-8dc37673bb5f',
                        ],
                    ];
                },
            ],
        ];
    }
    // </editor-fold>
}
