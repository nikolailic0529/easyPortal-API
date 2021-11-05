<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Reseller;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Organization\Exceptions\UnknownOrganization;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Eloquent\Exceptions\PropertyIsNotRelation;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use Tests\TestCase;

use function is_string;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Org\Loader
 */
class LoaderTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::load
     * @covers ::extract
     */
    public function testLoad(): void {
        $organization = $this->setOrganization(Organization::factory()->create());
        $reseller     = Reseller::factory()->create(['id' => $organization->getKey()]);
        $current      = $this->app->make(CurrentOrganization::class);
        $loader       = new Loader($current, 'assets_count');
        $countA       = $this->faker->randomNumber();
        $countB       = $this->faker->randomNumber();
        $customerA    = Customer::factory()->create();
        $customerB    = Customer::factory()->create();
        $customerC    = Customer::factory()->create();
        $parents      = new Collection([$customerB, $customerA, $customerC]);

        $customerA->resellers()->attach($reseller, [
            'assets_count' => $countA,
        ]);
        $customerB->resellers()->attach($reseller, [
            'assets_count' => $countB,
        ]);

        $loader->load($parents);

        $this->assertNotEquals($countA, $countB);
        $this->assertEquals($countA, $loader->extract($customerA));
        $this->assertEquals($countB, $loader->extract($customerB));
        $this->assertEquals(0, $loader->extract($customerC));
    }

    /**
     * @covers ::extract
     */
    public function testExtractFromModel(): void {
        $current                       = $this->app->make(CurrentOrganization::class);
        $loader                        = new class($current, 'property') extends Loader {
            public function getMarker(): string {
                return parent::getMarker();
            }

            public function getProperty(): string {
                return parent::getProperty();
            }
        };
        $model                         = new LoaderTest_ModelWithoutScope();
        $model[$loader->getMarker()]   = '1';
        $model[$loader->getProperty()] = 123;

        $this->assertEquals(123, $loader->extract($model));
        $this->assertEquals(0, $loader->extract(new LoaderTest_ModelWithoutScope()));
    }

    /**
     * @covers ::getQuery
     *
     * @dataProvider dataProviderHandleBuilder
     *
     * @param \Exception|class-string<\Exception>|array{query: string, bindings: array<mixed>}|null $expectedQuery
     * @param array<string>|null                                                                    $parents
     */
    public function testGetQuery(
        Exception|string|array|null $expectedQuery,
        array|null $expectedBuilder,
        Closure $builderFactory,
        Closure $organizationFactory = null,
        array $parents = null,
    ): void {
        if ($expectedQuery instanceof Exception) {
            $this->expectExceptionObject($expectedQuery);
        }

        if (is_string($expectedQuery)) {
            $this->expectException($expectedQuery);
        }

        $this->setOrganization($organizationFactory);

        if ($parents) {
            $parents = (new Collection($parents))->map(static function (string $key): Model {
                return (new LoaderTest_ModelWithoutScope())->forceFill(['id' => $key]);
            });
        }

        $organization    = $this->app->make(CurrentOrganization::class);
        $loader          = new Loader($organization, 'property');
        $actualBuilder   = $builderFactory($this);
        $expectedBuilder = $expectedBuilder ?: clone $actualBuilder;
        $actualQuery     = $loader->getQuery($actualBuilder, $parents);

        $loader->getQuery($actualBuilder, $parents);

        if ($expectedQuery) {
            $this->assertDatabaseQueryEquals($expectedQuery, $actualQuery);
        } else {
            $this->assertNull($actualQuery);
        }

        $this->assertDatabaseQueryEquals($expectedBuilder, $actualBuilder);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function dataProviderHandleBuilder(): array {
        return (new ArrayDataProvider([
            'no scope'                       => [
                new InvalidArgumentException(sprintf(
                    'Model `%s` doesn\'t use the `%s` scope.',
                    LoaderTest_ModelWithoutScope::class,
                    OwnedByOrganizationScope::class,
                )),
                null,
                static function (): EloquentBuilder {
                    return LoaderTest_ModelWithoutScope::query();
                },
            ],
            'not a relation'                 => [
                PropertyIsNotRelation::class,
                null,
                static function (): EloquentBuilder {
                    return LoaderTest_ModelWithScopeNotRelation::query();
                },
            ],
            'unsupported relation'           => [
                new InvalidArgumentException(sprintf(
                    'Relation `%s` is not supported.',
                    BelongsTo::class,
                )),
                null,
                static function (): EloquentBuilder {
                    return LoaderTest_ModelWithScopeRelationUnsupported::query();
                },
            ],
            'no organization'                => [
                UnknownOrganization::class,
                null,
                static function (): EloquentBuilder {
                    return LoaderTest_ModelWithScopeRelationSupported::query();
                },
            ],
            'root organization'              => [
                null,
                null,
                static function (): EloquentBuilder {
                    return LoaderTest_ModelWithScopeRelationSupported::query();
                },
                static function (self $test): Organization {
                    return $test->setRootOrganization(Organization::factory()->make([
                        'id' => '1cc137a2-61e5-4069-a407-f0e1f32dc634',
                    ]));
                },
            ],
            'without select without parents' => [
                [
                    'query'    => <<<'SQL'
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
                        SQL
                    ,
                    'bindings' => [
                        '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                    ],
                ],
                [
                    'query'    => <<<'SQL'
                        select
                            `model_with_relation_supported`.*,
                            1 as `org_property__property`
                        from
                            `model_with_relation_supported`
                        where
                            `model_with_relation_supported`.`parentKey` in (
                                select
                                    distinct `pivot`.`foreignPivotKey`
                                from
                                    `model_without_scopes`
                                inner join `pivot` on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                where
                                    `model_without_scopes`.`id` = ?
                            )
                        SQL
                    ,
                    'bindings' => [
                        '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                    ],
                ],
                static function (): EloquentBuilder {
                    return LoaderTest_ModelWithScopeRelationSupported::query();
                },
                static function (): Organization {
                    return Organization::factory()->make([
                        'id' => '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                    ]);
                },
            ],
            'with select without parents'    => [
                [
                    'query'    => <<<'SQL'
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
                        SQL
                    ,
                    'bindings' => [
                        '21a50911-912b-4543-b721-51c7398e8384',
                    ],
                ],
                [
                    'query'    => <<<'SQL'
                        select
                            `id`,
                            1 as `org_property__property`
                        from
                            `model_with_relation_supported`
                        where
                            `model_with_relation_supported`.`parentKey` in (
                                select
                                    distinct `pivot`.`foreignPivotKey`
                                from
                                    `model_without_scopes`
                                inner join `pivot` on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                where
                                    `model_without_scopes`.`id` = ?
                            )
                        SQL
                    ,
                    'bindings' => [
                        '21a50911-912b-4543-b721-51c7398e8384',
                    ],
                ],
                static function (): EloquentBuilder {
                    return LoaderTest_ModelWithScopeRelationSupported::query()->select('id');
                },
                static function (): Organization {
                    return Organization::factory()->make([
                        'id' => '21a50911-912b-4543-b721-51c7398e8384',
                    ]);
                },
            ],
            'without select with parents'    => [
                [
                    'query'    => <<<'SQL'
                        select
                            `pivot`.`property`,
                            `pivot`.`foreignPivotKey` as `id`
                        from
                            `model_without_scopes`
                        inner join `pivot`
                            on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                        where
                            `model_without_scopes`.`id` = ?
                            and
                            `pivot`.`foreignPivotKey` in (?, ?)
                        SQL
                    ,
                    'bindings' => [
                        '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                        '5985f4ce-f4a2-4cf2-afb7-2959fc126785',
                        'd840dfdb-7c9a-4324-8470-12ec91199834',
                    ],
                ],
                [
                    'query'    => <<<'SQL'
                        select
                            `model_with_relation_supported`.*,
                            1 as `org_property__property`
                        from
                            `model_with_relation_supported`
                        where
                            `model_with_relation_supported`.`parentKey` in (
                                select
                                    distinct `pivot`.`foreignPivotKey`
                                from
                                    `model_without_scopes`
                                inner join `pivot` on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                where
                                    `model_without_scopes`.`id` = ?
                            )
                        SQL
                    ,
                    'bindings' => [
                        '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                    ],
                ],
                static function (): EloquentBuilder {
                    return LoaderTest_ModelWithScopeRelationSupported::query();
                },
                static function (): Organization {
                    return Organization::factory()->make([
                        'id' => '6aa25c7f-55f7-4ff5-acfe-783b2dd1da47',
                    ]);
                },
                [
                    '5985f4ce-f4a2-4cf2-afb7-2959fc126785',
                    'd840dfdb-7c9a-4324-8470-12ec91199834',
                ],
            ],
            'with select with parents'       => [
                [
                    'query'    => <<<'SQL'
                        select
                            `pivot`.`property`,
                            `pivot`.`foreignPivotKey` as `id`
                        from
                            `model_without_scopes`
                        inner join `pivot`
                            on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                        where
                            `model_without_scopes`.`id` = ?
                            and
                            `pivot`.`foreignPivotKey` in (?, ?)
                        SQL
                    ,
                    'bindings' => [
                        '21a50911-912b-4543-b721-51c7398e8384',
                        '981edfa2-2139-42f6-bc7a-f7ff66df52ad',
                        '79b91f78-c244-4e95-a99d-bf8b15255591',
                    ],
                ],
                [
                    'query'    => <<<'SQL'
                        select
                            `id`,
                            1 as `org_property__property`
                        from
                            `model_with_relation_supported`
                        where
                            `model_with_relation_supported`.`parentKey` in (
                                select
                                    distinct `pivot`.`foreignPivotKey`
                                from
                                    `model_without_scopes`
                                inner join `pivot` on `model_without_scopes`.`relatedKey` = `pivot`.`relatedPivotKey`
                                where
                                    `model_without_scopes`.`id` = ?
                            )
                        SQL
                    ,
                    'bindings' => [
                        '21a50911-912b-4543-b721-51c7398e8384',
                    ],
                ],
                static function (): EloquentBuilder {
                    return LoaderTest_ModelWithScopeRelationSupported::query()->select('id');
                },
                static function (): Organization {
                    return Organization::factory()->make([
                        'id' => '21a50911-912b-4543-b721-51c7398e8384',
                    ]);
                },
                [
                    '981edfa2-2139-42f6-bc7a-f7ff66df52ad',
                    '79b91f78-c244-4e95-a99d-bf8b15255591',
                ],
            ],
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
class LoaderTest_ModelWithoutScope extends Model {
    /**
     * Primary Key always UUID.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    public $incrementing = false;

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
class LoaderTest_ModelWithScopeNotRelation extends Model {
    use OwnedByOrganization;
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class LoaderTest_ModelWithScopeRelationUnsupported extends Model {
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
class LoaderTest_ModelWithScopeRelationSupported extends Model {
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
            LoaderTest_ModelWithoutScope::class,
            'pivot',
            'foreignPivotKey',
            'relatedPivotKey',
            'parentKey',
            'relatedKey',
        );
    }
}
