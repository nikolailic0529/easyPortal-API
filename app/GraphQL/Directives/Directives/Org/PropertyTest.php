<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\GraphQL\Directives\Definitions\OrgPropertyDirective;
use App\Models\Organization;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Organization\Exceptions\UnknownOrganization;
use Closure;
use Exception;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\NameNode;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Eloquent\Exceptions\PropertyIsNotRelation;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\Builders\QueryBuilderDataProvider;
use Tests\TestCase;

use function is_string;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Org\Property
 */
class PropertyTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::handleBuilder
     *
     * @dataProvider dataProviderHandleBuilder
     *
     * @param \Exception|class-string<\Exception>|array{query: string, bindings: array<mixed>} $expected
     */
    public function testHandleBuilder(
        Exception|string|array $expected,
        Closure $builderFactory,
        Closure $organizationFactory = null,
        bool $organizationIsRoot = false,
    ): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        if (is_string($expected)) {
            $this->expectException($expected);
        }

        if ($organizationIsRoot) {
            $this->setOrganization($organizationFactory);
            $this->setRootOrganization($organizationFactory);
        } else {
            $this->setOrganization($organizationFactory);
        }

        $directive      = $this->app->make(OrgPropertyDirective::class)
            ->hydrate(new DirectiveNode([]), new FieldDefinitionNode([
                'name' => new NameNode(['value' => 'property']),
            ]));
        $builderFactory = $builderFactory($this);
        $builderFactory = $directive->handleBuilder($builderFactory, null);
        $builderFactory = $directive->handleBuilder($builderFactory, null);

        $this->assertDatabaseQueryEquals($expected, $builderFactory);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function dataProviderHandleBuilder(): array {
        return (new MergeDataProvider([
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
                        OwnedByOrganizationScope::class,
                    )),
                    static function (): EloquentBuilder {
                        return PropertyTest_ModelWithoutScope::query();
                    },
                ],
                'not a relation'       => [
                    PropertyIsNotRelation::class,
                    static function (): EloquentBuilder {
                        return PropertyTest_ModelWithScopeNotRelation::query();
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
                                1 as `_org_property__property`,
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
                                1 as `_org_property__property`,
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
        ]))->getData();
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
class PropertyTest_ModelWithScopeNotRelation extends Model {
    use OwnedByOrganization;
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class PropertyTest_ModelWithScopeRelationUnsupported extends Model {
    use OwnedByOrganization;

    public function getOrganizationColumn(): string {
        return 'organization.id';
    }

    public function organization(): BelongsTo {
        return $this->belongsTo($this::class);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class PropertyTest_ModelWithScopeRelationSupported extends Model {
    use OwnedByOrganization;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'model_with_relation_supported';

    public function getOrganizationColumn(): string {
        return 'organization.id';
    }

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
