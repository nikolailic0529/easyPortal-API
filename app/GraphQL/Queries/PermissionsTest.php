<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Organization;
use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission as AuthPermission;
use App\Services\Auth\Permissions;
use App\Services\Auth\Permissions as AuthPermissions;
use App\Services\Auth\Permissions\Markers\IsRoot;
use App\Services\Organization\CurrentOrganization;
use App\Services\Organization\RootOrganization;
use Closure;
use Illuminate\Contracts\Auth\Factory;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Mockery;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class PermissionsTest extends TestCase {
    /**
     * @dataProvider dataProviderInvoke
     * @coversNothing
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $translationsFactory = null,
        Closure $permissionsFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setTranslations($translationsFactory);

        if ($permissionsFactory) {
            $permissions = $permissionsFactory($this);

            $this->override(AuthPermissions::class, function () use ($permissions): AuthPermissions {
                return new class($permissions) extends Permissions {
                    /**
                     * @param array<\App\Services\Auth\Permission> $permissions
                     */
                    public function __construct(
                        protected array $permissions,
                    ) {
                        parent::__construct();
                    }

                    /**
                     * @inheritDoc
                     */
                    public function get(): array {
                        return $this->permissions;
                    }
                };
            });
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                permissions {
                    id
                    name
                    key
                    description
                }
            }')
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $a      = new class('permission-a') extends AuthPermission implements IsRoot {
            // empty,
        };
        $b      = new class('permission-b') extends AuthPermission {
            // empty,
        };
        $root   = new ArrayDataProvider([
            'ok' => [
                new GraphQLSuccess('permissions', self::class, [
                    [
                        'id'          => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        'name'        => 'translated-a-name',
                        'key'         => 'permission-a',
                        'description' => 'translated-a-description',
                    ],
                    [
                        'id'          => '42c1ad7c-d371-47cc-8809-59a491f18406',
                        'name'        => 'permission-b',
                        'key'         => 'permission-b',
                        'description' => 'permission-b',
                    ],
                ]),
                static function (TestCase $test, string $locale): array {
                    $id    = '439a0a06-d98a-41f0-b8e5-4e5722518e00';
                    $model = (new Permission())->getMorphClass();

                    return [
                        $locale => [
                            "models.{$model}.{$id}.name"        => 'translated-a-name',
                            "models.{$model}.{$id}.description" => 'translated-a-description',
                        ],
                    ];
                },
                static function () use ($a, $b): array {
                    Permission::factory()->create([
                        'id'  => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        'key' => 'permission-a',
                    ]);
                    Permission::factory()->create([
                        'id'  => '42c1ad7c-d371-47cc-8809-59a491f18406',
                        'key' => 'permission-b',
                    ]);
                    Permission::factory()->create([
                        'id'  => '1e454d44-70b3-447f-97e6-e2bbcdf148e1',
                        'key' => 'permission-c',
                    ]);

                    return [$a, $b];
                },
            ],
        ]);
        $normal = new ArrayDataProvider([
            'ok' => [
                new GraphQLSuccess('permissions', self::class, [
                    [
                        'id'          => '42c1ad7c-d371-47cc-8809-59a491f18406',
                        'name'        => 'permission-b',
                        'key'         => 'permission-b',
                        'description' => 'permission-b',
                    ],
                ]),
                null,
                static function () use ($a, $b): array {
                    Permission::factory()->create([
                        'id'  => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        'key' => 'permission-a',
                    ]);
                    Permission::factory()->create([
                        'id'  => '42c1ad7c-d371-47cc-8809-59a491f18406',
                        'key' => 'permission-b',
                    ]);
                    Permission::factory()->create([
                        'id'  => '1e454d44-70b3-447f-97e6-e2bbcdf148e1',
                        'key' => 'permission-c',
                    ]);

                    return [$a, $b];
                },
            ],
        ]);

        return (new MergeDataProvider([
            'root organization'   => new CompositeDataProvider(
                new ArrayDataProvider([
                    [
                        new UnknownValue(),
                        static function (TestCase $test): ?Organization {
                            return $test->setRootOrganization(
                                Organization::factory()->create(),
                            );
                        },
                    ],
                ]),
                new MergeDataProvider([
                    'administer'     => new CompositeDataProvider(
                        new OrganizationUserDataProvider('permissions', [
                            'administer',
                        ]),
                        $root,
                    ),
                    'org-administer' => new CompositeDataProvider(
                        new OrganizationUserDataProvider('permissions', [
                            'org-administer',
                        ]),
                        $root,
                    ),
                ]),
            ),
            'normal organization' => new CompositeDataProvider(
                new ArrayDataProvider([
                    [
                        new UnknownValue(),
                        static function (TestCase $test): ?Organization {
                            return $test->setOrganization(
                                Organization::factory()->create(),
                            );
                        },
                    ],
                ]),
                new MergeDataProvider([
                    'administer'     => new CompositeDataProvider(
                        new OrganizationUserDataProvider('permissions', [
                            'administer',
                        ]),
                        $normal,
                    ),
                    'org-administer' => new CompositeDataProvider(
                        new OrganizationUserDataProvider('permissions', [
                            'org-administer',
                        ]),
                        $normal,
                    ),
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
