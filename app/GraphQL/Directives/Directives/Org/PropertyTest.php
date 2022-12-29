<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\GraphQL\Directives\Definitions\OrgPropertyDirective;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Reseller;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationImpl;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Services\Organization\Exceptions\UnknownOrganization;
use Closure;
use Exception;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\NameNode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Eloquent\Exceptions\PropertyIsNotRelation;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Handler;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property as BuilderProperty;
use LastDragon_ru\LaraASP\GraphQL\SearchBy\Definitions\SearchByDirective;
use LastDragon_ru\LaraASP\GraphQL\SortBy\Definitions\SortByDirective;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use PHPUnit\Framework\Constraint\Constraint;
use Tests\DataProviders\Builders\QueryBuilderDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithOrganization;

use function is_string;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Org\Property
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class PropertyTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::extend
     *
     * @dataProvider dataProviderExtend
     *
     * @param Exception|class-string<Exception>|array{query: string, bindings: array<mixed>} $expected
     * @param Closure(static): Handler                                                       $handlerFactory
     * @param Closure(static): object                                                        $builderFactory
     * @param OrganizationFactory                                                            $orgFactory
     */
    public function testExtend(
        Exception|string|array $expected,
        Closure $handlerFactory,
        Closure $builderFactory,
        mixed $orgFactory = null,
        bool $organizationIsRoot = false,
    ): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        if (is_string($expected)) {
            self::expectException($expected);
        }

        if ($organizationIsRoot) {
            $this->setOrganization($orgFactory);
            $this->setRootOrganization($orgFactory);
        } else {
            $this->setOrganization($orgFactory);
        }

        $property  = new BuilderProperty('parent', 'property');
        $directive = $this->app->make(OrgPropertyDirective::class);

        $directive->hydrate(new DirectiveNode([]), new FieldDefinitionNode([
            'name' => new NameNode(['value' => $property->getName()]),
        ]));

        $handler = $handlerFactory($this);
        $builder = $builderFactory($this);
        $builder = $directive->extend($handler, $builder, $property, static function (): void {
            // empty
        });

        if ($handler instanceof SortByDirective) {
            // Only one sorting clause should be added
            $builder = $directive->extend($handler, $builder, $property, static function (): void {
                // empty
            });
        }

        self::assertInstanceOf(Builder::class, $builder);
        self::assertDatabaseQueryEquals($expected, $builder);
    }

    /**
     * @covers ::resolveField
     *
     * @dataProvider dataProviderResolveField
     *
     * @param OrganizationFactory $orgFactory
     */
    public function testResolveField(
        Constraint $expected,
        mixed $orgFactory,
        Closure $factory,
    ): void {
        $org = $this->setOrganization($orgFactory);

        $factory($this, $org);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                type Query {
                    customers: [Customer!]! @all
                }

                type Customer {
                    id: ID!
                    assets_count: Int! @orgProperty
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    customers {
                        id
                        assets_count
                    }
                }
                GRAPHQL,
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function dataProviderExtend(): array {
        return (new MergeDataProvider([
            SearchByDirective::class => new CompositeDataProvider(
                new ArrayDataProvider([
                    'directive' => [
                        new UnknownValue(),
                        static function (TestCase $test): Handler {
                            return $test->app()->make(SearchByDirective::class);
                        },
                    ],
                ]),
                new MergeDataProvider([
                    QueryBuilder::class    => new CompositeDataProvider(
                        new QueryBuilderDataProvider(),
                        new ArrayDataProvider([
                            'unsupported' => [
                                new InvalidArgumentException(sprintf(
                                    'Builder must be instance of `%s`, `%s` given.',
                                    EloquentBuilder::class,
                                    QueryBuilder::class,
                                )),
                            ],
                        ]),
                    ),
                    EloquentBuilder::class => new ArrayDataProvider([
                        'no scope'             => [
                            new InvalidArgumentException(sprintf(
                                'Model `%s` doesn\'t use the `%s` scope.',
                                PropertyTest_ModelWithoutScope::class,
                                OwnedByScope::class,
                            )),
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithoutScope::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make();
                            },
                            false,
                        ],
                        'not a relation'       => [
                            PropertyIsNotRelation::class,
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeNotRelation::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make();
                            },
                        ],
                        'unsupported relation' => [
                            new InvalidArgumentException(sprintf(
                                'Relation `%s` is not supported.',
                                BelongsTo::class,
                            )),
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationUnsupported::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make();
                            },
                            false,
                        ],
                        'no organization'      => [
                            UnknownOrganization::class,
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationSupported::query();
                            },
                        ],
                        'root organization'    => [
                            [
                                'query'    => <<<'SQL'
                                select
                                    *
                                from
                                    `model_with_relation_supported`
                                SQL
                                ,
                                'bindings' => [
                                    // empty
                                ],
                            ],
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationSupported::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make([
                                    'id' => '1cc137a2-61e5-4069-a407-f0e1f32dc634',
                                ]);
                            },
                            true,
                        ],
                        'without select'       => [
                            [
                                'query'    => <<<'SQL'
                                select
                                    *
                                from
                                    `model_with_relation_supported`
                                where
                                    exists (
                                        select
                                            *
                                        from
                                            `model_without_scopes`
                                            inner join `pivot`
                                                on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                        where
                                            `model_with_relation_supported`.`parentKey` = `pivot`.`foreignPivotKey`
                                            and `model_without_scopes`.`id` = ?
                                    )
                                    and `model_with_relation_supported`.`parentKey` in (
                                        select
                                            distinct `pivot`.`foreignPivotKey`
                                        from
                                            `model_without_scopes`
                                            inner join `pivot`
                                                on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                        where
                                            `model_without_scopes`.`id` = ?
                                    )
                                SQL
                                ,
                                'bindings' => [
                                    '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                                    '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                                ],
                            ],
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationSupported::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make([
                                    'id' => '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                                ]);
                            },
                        ],
                        'with select'          => [
                            [
                                'query'    => <<<'SQL'
                                select
                                    `id`
                                from
                                    `model_with_relation_supported`
                                where
                                    exists (
                                        select
                                            *
                                        from
                                            `model_without_scopes`
                                            inner join `pivot`
                                                on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                        where
                                            `model_with_relation_supported`.`parentKey` = `pivot`.`foreignPivotKey`
                                            and `model_without_scopes`.`id` = ?
                                    )
                                    and `model_with_relation_supported`.`parentKey` in (
                                        select
                                            distinct `pivot`.`foreignPivotKey`
                                        from
                                            `model_without_scopes`
                                            inner join `pivot`
                                                on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                        where
                                            `model_without_scopes`.`id` = ?
                                    )
                                SQL
                                ,
                                'bindings' => [
                                    '21a50911-912b-4543-b721-51c7398e8384',
                                    '21a50911-912b-4543-b721-51c7398e8384',
                                ],
                            ],
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationSupported::query()->select('id');
                            },
                            static function (): Organization {
                                return Organization::factory()->make([
                                    'id' => '21a50911-912b-4543-b721-51c7398e8384',
                                ]);
                            },
                        ],
                    ]),
                ]),
            ),
            SortByDirective::class   => new CompositeDataProvider(
                new ArrayDataProvider([
                    'directive' => [
                        new UnknownValue(),
                        static function (TestCase $test): Handler {
                            return $test->app()->make(SortByDirective::class);
                        },
                    ],
                ]),
                new MergeDataProvider([
                    QueryBuilder::class    => new CompositeDataProvider(
                        new QueryBuilderDataProvider(),
                        new ArrayDataProvider([
                            'unsupported' => [
                                new InvalidArgumentException(sprintf(
                                    'Builder must be instance of `%s`, `%s` given.',
                                    EloquentBuilder::class,
                                    QueryBuilder::class,
                                )),
                            ],
                        ]),
                    ),
                    EloquentBuilder::class => new ArrayDataProvider([
                        'no scope'             => [
                            new InvalidArgumentException(sprintf(
                                'Model `%s` doesn\'t use the `%s` scope.',
                                PropertyTest_ModelWithoutScope::class,
                                OwnedByScope::class,
                            )),
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithoutScope::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make();
                            },
                            false,
                        ],
                        'not a relation'       => [
                            PropertyIsNotRelation::class,
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeNotRelation::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make();
                            },
                        ],
                        'unsupported relation' => [
                            new InvalidArgumentException(sprintf(
                                'Relation `%s` is not supported.',
                                BelongsTo::class,
                            )),
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationUnsupported::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make();
                            },
                            false,
                        ],
                        'no organization'      => [
                            UnknownOrganization::class,
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationSupported::query();
                            },
                        ],
                        'root organization'    => [
                            [
                                'query'    => <<<'SQL'
                                select
                                    *
                                from
                                    `model_with_relation_supported`
                                SQL
                                ,
                                'bindings' => [
                                    // empty
                                ],
                            ],
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationSupported::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make([
                                    'id' => '1cc137a2-61e5-4069-a407-f0e1f32dc634',
                                ]);
                            },
                            true,
                        ],
                        'without select'       => [
                            [
                                'query'    => <<<'SQL'
                                select
                                    `model_with_relation_supported`.*,
                                    1 as `org_property__property`,
                                    (
                                        select
                                            `pivot`.`property`
                                        from
                                            `model_without_scopes`
                                        inner join `pivot`
                                            on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                        where
                                            `model_without_scopes`.`id` = ?
                                            and
                                            `pivot`.`foreignPivotKey` = `model_with_relation_supported`.`parentKey`
                                        limit
                                            1
                                    ) as `property`
                                from
                                    `model_with_relation_supported`
                                where
                                    `model_with_relation_supported`.`parentKey` in (
                                        select
                                            distinct `pivot`.`foreignPivotKey`
                                        from
                                            `model_without_scopes`
                                        inner join `pivot`
                                            on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                        where
                                            `model_without_scopes`.`id` = ?
                                    )
                                SQL
                                ,
                                'bindings' => [
                                    '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                                    '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                                ],
                            ],
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationSupported::query();
                            },
                            static function (): Organization {
                                return Organization::factory()->make([
                                    'id' => '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                                ]);
                            },
                        ],
                        'with select'          => [
                            [
                                'query'    => <<<'SQL'
                                select
                                    `id`,
                                    1 as `org_property__property`,
                                    (
                                        select
                                            `pivot`.`property`
                                        from
                                            `model_without_scopes`
                                        inner join `pivot`
                                            on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                        where
                                            `model_without_scopes`.`id` = ?
                                            and
                                            `pivot`.`foreignPivotKey` = `model_with_relation_supported`.`parentKey`
                                        limit
                                            1
                                    ) as `property`
                                from
                                    `model_with_relation_supported`
                                where
                                    `model_with_relation_supported`.`parentKey` in (
                                        select
                                            distinct `pivot`.`foreignPivotKey`
                                        from
                                            `model_without_scopes`
                                        inner join `pivot`
                                            on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                        where
                                            `model_without_scopes`.`id` = ?
                                    )
                                SQL
                                ,
                                'bindings' => [
                                    '21a50911-912b-4543-b721-51c7398e8384',
                                    '21a50911-912b-4543-b721-51c7398e8384',
                                ],
                            ],
                            static function (): EloquentBuilder {
                                return PropertyTest_ModelWithScopeRelationSupported::query()->select('id');
                            },
                            static function (): Organization {
                                return Organization::factory()->make([
                                    'id' => '21a50911-912b-4543-b721-51c7398e8384',
                                ]);
                            },
                        ],
                    ]),
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<string,mixed>
     */
    public function dataProviderResolveField(): array {
        $customerId = '4e15b024-40f8-4340-a68b-c3ba8c993e66';
        $rootValue  = 321;
        $orgValue   = 123;
        $factory    = static function (
            self $test,
            Organization $organization,
        ) use (
            $customerId,
            $rootValue,
            $orgValue,
        ): void {
            $reseller = Reseller::factory()->create(['id' => $organization]);
            $customer = Customer::factory()->create([
                'id'           => $customerId,
                'assets_count' => $rootValue,
            ]);

            $customer->resellers()->attach($reseller, [
                'assets_count' => $orgValue,
            ]);
        };

        return [
            'root organization' => [
                new GraphQLSuccess('customers', [
                    [
                        'id'           => $customerId,
                        'assets_count' => $rootValue,
                    ],
                ]),
                static function (self $test): Organization {
                    return $test->setRootOrganization(Organization::factory()->create());
                },
                $factory,
            ],
            'organization'      => [
                new GraphQLSuccess('customers', [
                    [
                        'id'           => $customerId,
                        'assets_count' => $orgValue,
                    ],
                ]),
                static function (self $test): Organization {
                    return Organization::factory()->create();
                },
                $factory,
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
class PropertyTest_ModelWithoutScope extends Model {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'model_without_scopes';
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class PropertyTest_ModelWithScopeNotRelation extends Model implements OwnedByOrganization {
    use OwnedByOrganizationImpl;
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class PropertyTest_ModelWithScopeRelationUnsupported extends Model implements OwnedByOrganization {
    use OwnedByOrganizationImpl;

    public static function getOwnedByOrganizationColumn(): string {
        return 'organization.id';
    }

    /**
     * @return BelongsTo<self, self>
     */
    public function organization(): BelongsTo {
        return $this->belongsTo($this::class);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class PropertyTest_ModelWithScopeRelationSupported extends Model implements OwnedByOrganization {
    use OwnedByOrganizationImpl;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'model_with_relation_supported';

    public static function getOwnedByOrganizationColumn(): string {
        return 'organization.id';
    }

    /**
     * @return BelongsToMany<PropertyTest_ModelWithoutScope>
     */
    public function organization(): BelongsToMany {
        return $this->belongsToMany(
            PropertyTest_ModelWithoutScope::class,
            'pivot',
            'foreignPivotKey',
            'relatedPivotKey',
            'parentKey',
            'relatedKey',
        );
    }
}
