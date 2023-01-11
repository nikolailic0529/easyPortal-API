<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Audits\Audit;
use App\Services\Audit\Contracts\Auditable;
use App\Services\Audit\Enums\Action;
use App\Services\Audit\Listeners\AuditableListener;
use Closure;
use Exception;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Tests\TestCase;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Administration\AuditContext
 */
class AuditContextTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed>           $expected
     * @param Closure(static): Audit $auditFactory
     */
    public function testInvoke(array $expected, ?bool $administer, Closure $auditFactory): void {
        $gate = Mockery::mock(Gate::class);

        if ($administer !== null) {
            $gate
                ->shouldReceive('check')
                ->with('administer')
                ->once()
                ->andReturn($administer);
        }

        $audit    = $auditFactory($this);
        $context  = new class($gate) extends AuditContext {
            /**
             * @inheritdoc
             */
            public function getModelHiddenProperties(?Model $model, array $context): array {
                return parent::getModelHiddenProperties($model, $context);
            }
        };
        $actual   = $context($audit, [], Mockery::mock(GraphQLContext::class), Mockery::mock(ResolveInfo::class));
        $expected = json_encode($expected, JSON_THROW_ON_ERROR);

        self::assertEquals($expected, $actual);
    }

    public function testGetModelHiddenProperties(): void {
        $gate    = Mockery::mock(Gate::class);
        $model   = new class() extends Model {
            /**
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
             */
            protected $hidden = ['a', 'c'];
        };
        $context = new class($gate) extends AuditContext {
            /**
             * @inheritdoc
             */
            public function getModelHiddenProperties(?Model $model, array $context): array {
                return parent::getModelHiddenProperties($model, $context);
            }
        };

        self::assertEquals(
            [
                'a',
                'c',
            ],
            $context->getModelHiddenProperties($model, [
                AuditableListener::PROPERTIES => [
                    'a' => 123,
                    'b' => 'b',
                ],
                AuditableListener::RELATIONS  => [
                    'c' => [1, 2, 3],
                ],
            ]),
        );
    }

    public function testGetModelHiddenPropertiesNoModel(): void {
        $gate    = Mockery::mock(Gate::class);
        $context = new class($gate) extends AuditContext {
            /**
             * @inheritdoc
             */
            public function getModelHiddenProperties(?Model $model, array $context): array {
                return parent::getModelHiddenProperties($model, $context);
            }
        };

        self::assertEquals(
            [
                'a',
                'b',
                'c',
            ],
            $context->getModelHiddenProperties(null, [
                AuditableListener::PROPERTIES => [
                    'a' => 123,
                    'b' => 'b',
                ],
                AuditableListener::RELATIONS  => [
                    'c' => [1, 2, 3],
                ],
            ]),
        );
    }

    public function testGetModelInternalProperties(): void {
        $gate = Mockery::mock(Gate::class);
        $gate
            ->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $model   = new class() extends Model implements Auditable {
            /**
             * @inheritdoc
             */
            public function getDirtyRelations(): array {
                throw new Exception('should not be called');
            }

            /**
             * @inheritdoc
             */
            public function getInternalAttributes(): array {
                return ['b'];
            }
        };
        $context = new class($gate) extends AuditContext {
            /**
             * @inheritdoc
             */
            public function getModelInternalProperties(?Model $model, array $context): array {
                return parent::getModelInternalProperties($model, $context);
            }
        };

        self::assertEquals(
            [
                'b',
            ],
            $context->getModelInternalProperties($model, [
                AuditableListener::PROPERTIES => [
                    'a' => 123,
                    'b' => 'b',
                ],
                AuditableListener::RELATIONS  => [
                    'c' => [1, 2, 3],
                ],
            ]),
        );
    }

    public function testGetModelInternalPropertiesAdminister(): void {
        $gate = Mockery::mock(Gate::class);
        $gate
            ->shouldReceive('check')
            ->with('administer')
            ->once()
            ->andReturn(true);

        $context = new class($gate) extends AuditContext {
            /**
             * @inheritdoc
             */
            public function getModelInternalProperties(?Model $model, array $context): array {
                return parent::getModelInternalProperties($model, $context);
            }
        };

        self::assertEquals(
            [
                // empty
            ],
            $context->getModelInternalProperties(null, [
                AuditableListener::PROPERTIES => [
                    'a' => 123,
                    'b' => 'b',
                ],
                AuditableListener::RELATIONS  => [
                    'c' => [1, 2, 3],
                ],
            ]),
        );
    }

    public function testGetModelInternalPropertiesNotModel(): void {
        $gate = Mockery::mock(Gate::class);
        $gate
            ->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $context = new class($gate) extends AuditContext {
            /**
             * @inheritdoc
             */
            public function getModelInternalProperties(?Model $model, array $context): array {
                return parent::getModelInternalProperties($model, $context);
            }
        };

        self::assertEquals(
            [
                'a',
                'b',
                'c',
            ],
            $context->getModelInternalProperties(null, [
                AuditableListener::PROPERTIES => [
                    'a' => 123,
                    'b' => 'b',
                ],
                AuditableListener::RELATIONS  => [
                    'c' => [1, 2, 3],
                ],
            ]),
        );
    }

    public function testGetModelInternalPropertiesNotAuditable(): void {
        $gate = Mockery::mock(Gate::class);
        $gate
            ->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $model   = new class() extends Model {
            // empty
        };
        $context = new class($gate) extends AuditContext {
            /**
             * @inheritdoc
             */
            public function getModelInternalProperties(?Model $model, array $context): array {
                return parent::getModelInternalProperties($model, $context);
            }
        };

        self::assertEquals(
            [
                'a',
                'b',
                'c',
            ],
            $context->getModelInternalProperties($model, [
                AuditableListener::PROPERTIES => [
                    'a' => 123,
                    'b' => 'b',
                ],
                AuditableListener::RELATIONS  => [
                    'c' => [1, 2, 3],
                ],
            ]),
        );
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderInvoke(): array {
        return [
            'model'       => [
                [
                    AuditableListener::PROPERTIES => [
                        'a' => [
                            'value'    => null,
                            'previous' => '********',
                        ],
                        'b' => [
                            'value'    => 'value-b',
                            'previous' => null,
                        ],
                    ],
                    AuditableListener::RELATIONS  => [
                        'c' => [
                            'type'    => '********',
                            'added'   => ['********'],
                            'deleted' => ['********'],
                        ],
                        'd' => [
                            'type'    => 'ModelD',
                            'added'   => [123],
                            'deleted' => [345],
                        ],
                        'e' => [
                            'type'    => '********',
                            'added'   => ['********'],
                            'deleted' => ['********'],
                        ],
                    ],
                ],
                false,
                static function (): Audit {
                    $model = new class() extends Model implements Auditable {
                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
                         */
                        protected $hidden = ['a', 'c'];

                        /**
                         * @inheritdoc
                         */
                        public function getDirtyRelations(): array {
                            throw new Exception('should not be called');
                        }

                        /**
                         * @inheritdoc
                         */
                        public function getInternalAttributes(): array {
                            return ['e'];
                        }
                    };

                    Relation::morphMap(['Test' => $model::class]);

                    $audit              = Audit::factory()->make();
                    $audit->object_type = 'Test';
                    $audit->action      = Action::modelCreated();
                    $audit->context     = [
                        AuditableListener::PROPERTIES => [
                            'a' => [
                                'value'    => null,
                                'previous' => 'previous-a',
                            ],
                            'b' => [
                                'value'    => 'value-b',
                                'previous' => null,
                            ],
                        ],
                        AuditableListener::RELATIONS  => [
                            'c' => [
                                'type'    => 'ModelC',
                                'added'   => [123],
                                'deleted' => [],
                            ],
                            'd' => [
                                'type'    => 'ModelD',
                                'added'   => [123],
                                'deleted' => [345],
                            ],
                            'e' => [
                                'type'    => 'ModelD',
                                'added'   => [],
                                'deleted' => [345],
                            ],
                        ],
                    ];

                    return $audit;
                },
            ],
            'not a model' => [
                [
                    'guard' => 'test',
                ],
                null,
                static function (): Audit {
                    $audit              = Audit::factory()->make();
                    $audit->object_type = 'Test';
                    $audit->action      = Action::authFailed();
                    $audit->context     = [
                        'guard' => 'test',
                    ];

                    return $audit;
                },
            ],
        ];
    }
    // </editor-fold>
}
