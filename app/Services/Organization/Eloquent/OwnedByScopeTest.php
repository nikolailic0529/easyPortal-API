<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Models\Enums\OrganizationType;
use App\Models\Organization;
use App\Services\Search\Builders\Builder as SearchBuilder;
use App\Services\Search\Configuration;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @covers \App\Services\Organization\Eloquent\OwnedByScope
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class OwnedByScopeTest extends TestCase {
    use WithoutGlobalScopes;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderHandle
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     * @param OrganizationFactory                          $orgFactory
     * @param Closure(static): Model                       $modelFactory
     */
    public function testHandle(
        array $expected,
        mixed $orgFactory,
        Closure $modelFactory,
    ): void {
        $this->setOrganization($orgFactory);

        $model   = $modelFactory($this);
        $scope   = $this->app->make(OwnedByScopeTest_Scope::class);
        $builder = $model::query();

        $scope->handle($builder, $model);

        self::assertDatabaseQueryEquals($expected, $builder);
    }

    /**
     * @dataProvider dataProviderHandleForSearch
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     * @param OrganizationFactory                          $orgFactory
     * @param Closure(static): Model                       $modelFactory
     */
    public function testHandleForSearch(
        array $expected,
        mixed $orgFactory,
        Closure $modelFactory,
    ): void {
        $this->setOrganization($orgFactory);

        $model   = $modelFactory($this);
        $scope   = $this->app->make(OwnedByScopeTest_Scope::class);
        $builder = $this->app->make(SearchBuilder::class, [
            'query' => '123',
            'model' => $model,
        ]);

        $scope->handleForSearch($builder, $model);

        self::assertEquals($expected, $builder->wheres);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, mixed>
     */
    public function dataProviderHandle(): array {
        $id       = '8251199a-7e8c-439b-a29d-f6ba55c0b58e';
        $root     = static function (self $test) use ($id): ?Organization {
            return $test->setRootOrganization(
                Organization::factory()->make([
                    'id' => $id,
                ]),
            );
        };
        $reseller = static function () use ($id): Organization {
            return Organization::factory()->make([
                'id'   => $id,
                'type' => OrganizationType::reseller(),
            ]);
        };

        return (new MergeDataProvider([
            'other'    => new ArrayDataProvider([
                'root organization'  => [
                    [
                        'query'    => <<<'SQL'
                            select * from `owned_by_organization`
                        SQL
                        ,
                        'bindings' => [],
                    ],
                    $root,
                    static function (): Model {
                        return new OwnedByScopeTest_ModelOwnedByOrganization();
                    },
                ],
                'no owner'           => [
                    [
                        'query'    => <<<'SQL'
                            select * from `model`
                        SQL
                        ,
                        'bindings' => [],
                    ],
                    $reseller,
                    static function (): Model {
                        return new OwnedByScopeTest_Model();
                    },
                ],
                OwnedByShared::class => [
                    [
                        'query'    => <<<'SQL'
                            select *
                            from `owned_by_shared`
                            where (
                                `owned_by_shared`.`organization_id` = ?
                                or
                                `owned_by_shared`.`organization_id` is null
                            )
                        SQL
                        ,
                        'bindings' => [
                            $id,
                        ],
                    ],
                    $reseller,
                    static function (): Model {
                        return new OwnedByScopeTest_ModelOwnedByShared();
                    },
                ],
            ]),
            'reseller' => new ArrayDataProvider([
                OwnedByOrganization::class => [
                    [
                        'query'    => <<<'SQL'
                            select *
                            from `owned_by_organization`
                            where
                                (`owned_by_organization`.`organization_id` = ?)
                        SQL
                        ,
                        'bindings' => [
                            $id,
                        ],
                    ],
                    $reseller,
                    static function (): Model {
                        return new OwnedByScopeTest_ModelOwnedByOrganization();
                    },
                ],
                OwnedByReseller::class     => [
                    [
                        'query'    => <<<'SQL'
                            select *
                            from `owned_by_reseller`
                            where
                                (`owned_by_reseller`.`reseller_id` = ?)
                        SQL
                        ,
                        'bindings' => [
                            $id,
                        ],
                    ],
                    $reseller,
                    static function (): Model {
                        return new OwnedByScopeTest_ModelOwnedByReseller();
                    },
                ],
            ]),
        ]))->getData();
    }

    /**
     * @return array<string, mixed>
     */
    public function dataProviderHandleForSearch(): array {
        $id       = '8251199a-7e8c-439b-a29d-f6ba55c0b58e';
        $root     = static function (self $test) use ($id): ?Organization {
            return $test->setRootOrganization(
                Organization::factory()->make([
                    'id' => $id,
                ]),
            );
        };
        $reseller = static function () use ($id): Organization {
            return Organization::factory()->make([
                'id'   => $id,
                'type' => OrganizationType::reseller(),
            ]);
        };

        return (new MergeDataProvider([
            'other'    => new ArrayDataProvider([
                'root organization'  => [
                    [
                        // empty
                    ],
                    $root,
                    static function (): Model {
                        return new OwnedByScopeTest_ModelOwnedByOrganization();
                    },
                ],
                'no owner'           => [
                    [
                        // empty
                    ],
                    $reseller,
                    static function (): Model {
                        return new OwnedByScopeTest_Model();
                    },
                ],
                OwnedByShared::class => [
                    [
                        Configuration::getMetadataName('owners.organization') => $id,
                    ],
                    $reseller,
                    static function (): Model {
                        return new OwnedByScopeTest_ModelOwnedByShared();
                    },
                ],
            ]),
            'reseller' => new ArrayDataProvider([
                OwnedByOrganization::class => [
                    [
                        Configuration::getMetadataName('owners.organization') => $id,
                    ],
                    $reseller,
                    static function (): Model {
                        return new OwnedByScopeTest_ModelOwnedByOrganization();
                    },
                ],
                OwnedByReseller::class     => [
                    [
                        Configuration::getMetadataName('owners.reseller') => $id,
                    ],
                    $reseller,
                    static function (): Model {
                        return new OwnedByScopeTest_ModelOwnedByReseller();
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
class OwnedByScopeTest_Scope extends OwnedByScope {
    public function handle(EloquentBuilder $builder, Model $model): void {
        parent::handle($builder, $model);
    }

    public function handleForSearch(SearchBuilder $builder, Model $model): void {
        parent::handleForSearch($builder, $model);
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class OwnedByScopeTest_Model extends Model {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'model';
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class OwnedByScopeTest_ModelOwnedByOrganization extends Model implements OwnedByOrganization {
    use OwnedByOrganizationImpl;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'owned_by_organization';
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class OwnedByScopeTest_ModelOwnedByReseller extends Model implements OwnedByReseller {
    use OwnedByResellerImpl;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'owned_by_reseller';
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class OwnedByScopeTest_ModelOwnedByShared extends Model implements
    OwnedByOrganization,
    OwnedByShared {
    use OwnedByOrganizationImpl;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'owned_by_shared';
}
