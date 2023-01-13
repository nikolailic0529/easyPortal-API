<?php declare(strict_types = 1);

namespace App\Services\Queue\Utils;

use App\Services\Queue\Concerns\WithModelKeys;
use App\Services\Queue\Job;
use Closure;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

use function is_array;

/**
 * @internal
 * @covers \App\Services\Queue\Utils\Dispatcher
 */
class DispatcherTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderDispatch
     *
     * @param Exception|array{task: class-string<Job>, keys: array<mixed>}|null $expected
     * @param Closure(static):mixed                                             $factory
     */
    public function testDispatch(Exception|array|null $expected, Closure $factory, bool $dispatchable): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $models     = $factory($this);
        $dispatcher = new class($dispatchable) extends Dispatcher {
            public function __construct(
                protected bool $dispatchable,
            ) {
                parent::__construct();
            }

            protected function isDispatchable(string $model): bool {
                return $this->dispatchable;
            }

            protected function dispatchModel(string $model, int|string $key): void {
                Container::getInstance()->make(DispatcherTest_ModelTask::class)
                    ->init($model, [$key])
                    ->dispatch();
            }

            /**
             * @inheritDoc
             */
            protected function dispatchModels(string $model, array $keys): void {
                Container::getInstance()->make(DispatcherTest_ModelsTask::class)
                    ->init($model, $keys)
                    ->dispatch();
            }
        };

        Queue::fake();

        $dispatcher->dispatch($models); // @phpstan-ignore-line this is the way

        if (is_array($expected)) {
            Queue::assertPushed($expected['task'], static function (object $task) use ($expected): bool {
                self::assertInstanceOf(DispatcherTest_Task::class, $task);
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
     * @return array<string,array{Exception|array{task:class-string<DispatcherTest_Task>,keys:array<mixed>}|null,Closure(static):mixed}>
     */
    public function dataProviderDispatch(): array {
        $model = new class() extends Model {
            /**
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
             *
             * @var string
             */
            protected $keyType = 'string';
        };

        return [
            'empty Collection'  => [
                null,
                static function (): mixed {
                    return new Collection();
                },
                true,
            ],
            'no key'            => [
                null,
                static function (): mixed {
                    return new class() extends Model {
                        // empty
                    };
                },
                true,
            ],
            'non dispatchable'  => [
                null,
                static function () use ($model): mixed {
                    return new Collection([
                        (clone $model)->forceFill([
                            $model->getKeyName() => '792bdfe0-2fa9-4214-8871-bd28c6b99760',
                        ]),
                    ]);
                },
                false,
            ],
            'Collection (many)' => [
                [
                    'task' => DispatcherTest_ModelsTask::class,
                    'keys' => [
                        '43e8dc31-8497-4e4e-b5c6-5ff68df929f9',
                        '64719313-a21d-4ec6-a48b-fcbf64d9cee9',
                    ],
                ],
                static function () use ($model): mixed {
                    return new Collection([
                        (clone $model)->forceFill([
                            $model->getKeyName() => '64719313-a21d-4ec6-a48b-fcbf64d9cee9',
                        ]),
                        (clone $model)->forceFill([
                            $model->getKeyName() => '64719313-a21d-4ec6-a48b-fcbf64d9cee9',
                        ]),
                        (clone $model)->forceFill([
                            $model->getKeyName() => '43e8dc31-8497-4e4e-b5c6-5ff68df929f9',
                        ]),
                    ]);
                },
                true,
            ],
            'Collection (one)'  => [
                [
                    'task' => DispatcherTest_ModelTask::class,
                    'keys' => [
                        '792bdfe0-2fa9-4214-8871-bd28c6b99760',
                    ],
                ],
                static function () use ($model): mixed {
                    return new Collection([
                        (clone $model)->forceFill([
                            $model->getKeyName() => '792bdfe0-2fa9-4214-8871-bd28c6b99760',
                        ]),
                    ]);
                },
                true,
            ],
            'Model'             => [
                [
                    'task' => DispatcherTest_ModelTask::class,
                    'keys' => [
                        '40fbe33c-c94c-4410-85ca-c554eeba99ca',
                    ],
                ],
                static function () use ($model): mixed {
                    return (clone $model)->forceFill([
                        $model->getKeyName() => '40fbe33c-c94c-4410-85ca-c554eeba99ca',
                    ]);
                },
                true,
            ],
            'array (many)'      => [
                [
                    'task' => DispatcherTest_ModelsTask::class,
                    'keys' => [
                        '2221ee64-6fae-41d6-86d6-690caadc512d',
                        'b40a1b1c-0146-4557-af7a-a29ae5d82787',
                    ],
                ],
                static function () use ($model): mixed {
                    return [
                        'model' => $model::class,
                        'keys'  => [
                            'b40a1b1c-0146-4557-af7a-a29ae5d82787',
                            '2221ee64-6fae-41d6-86d6-690caadc512d',
                            'b40a1b1c-0146-4557-af7a-a29ae5d82787',
                        ],
                    ];
                },
                true,
            ],
            'array (one)'       => [
                [
                    'task' => DispatcherTest_ModelTask::class,
                    'keys' => [
                        'fea28245-f311-4ce3-af6d-8dc37673bb5f',
                    ],
                ],
                static function () use ($model): mixed {
                    return [
                        'model' => $model::class,
                        'keys'  => [
                            'fea28245-f311-4ce3-af6d-8dc37673bb5f',
                        ],
                    ];
                },
                true,
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
interface DispatcherTest_Task {
    /**
     * @return array<string|int>
     */
    public function getKeys(): array;
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class DispatcherTest_ModelTask extends Job implements DispatcherTest_Task {
    /**
     * @use WithModelKeys<Model>
     */
    use WithModelKeys;

    public function displayName(): string {
        return 'model-job';
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class DispatcherTest_ModelsTask extends Job implements DispatcherTest_Task {
    /**
     * @use WithModelKeys<Model>
     */
    use WithModelKeys;

    public function displayName(): string {
        return 'models-job';
    }
}
