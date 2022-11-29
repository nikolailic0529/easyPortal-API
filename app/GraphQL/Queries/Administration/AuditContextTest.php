<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Audits\Audit;
use App\Services\Audit\Enums\Action;
use App\Services\Audit\Listeners\AuditableListener;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Tests\TestCase;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Administration\AuditContext
 */
class AuditContextTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed>           $expected
     * @param Closure(static): Audit $auditFactory
     */
    public function testInvoke(array $expected, Closure $auditFactory): void {
        $gate     = Mockery::mock(Gate::class);
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

    /**
     * @covers ::getModelHiddenProperties
     */
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

    /**
     * @covers ::getModelHiddenProperties
     */
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
                            'value'    => '********',
                            'previous' => '********',
                        ],
                        'b' => [
                            'value'    => 'value-b',
                            'previous' => 'previous-b',
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
                    ],
                ],
                static function (): Audit {
                    $model = new class() extends Model {
                        /**
                         * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
                         */
                        protected $hidden = ['a', 'c'];
                    };

                    Relation::morphMap(['Test' => $model::class]);

                    $audit              = Audit::factory()->make();
                    $audit->object_type = 'Test';
                    $audit->action      = Action::modelCreated();
                    $audit->context     = [
                        AuditableListener::PROPERTIES => [
                            'a' => [
                                'value'    => 'value-a',
                                'previous' => 'previous-a',
                            ],
                            'b' => [
                                'value'    => 'value-b',
                                'previous' => 'previous-b',
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
                        ],
                    ];

                    return $audit;
                },
            ],
            'not a model' => [
                [
                    'guard' => 'test',
                ],
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
