<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\Permission as AuthPermission;
use App\Services\Auth\Permissions;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\Group;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Org\Role\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                               $orgFactory
     * @param UserFactory                                       $userFactory
     * @param Closure(static, ?Organization, ?User): ?Role|null $roleFactory
     * @param Closure(): void|null                              $clientFactory
     * @param array<string,mixed>                               $data
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $roleFactory = null,
        Closure $clientFactory = null,
        array $data = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings([
            'ep.keycloak.client_id' => 'client_id',
        ]);

        $role   = $roleFactory
            ? $roleFactory($this, $org, $user)
            : null;
        $data ??= [
            'name'        => 'wrong',
            'permissions' => [],
        ];

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation create($input: OrgRoleCreateInput!) {
                org {
                    role {
                        create (input: $input) {
                            result
                            role {
                                id
                                name
                                permissions {
                                    id
                                    name
                                    key
                                    description
                                }
                            }
                        }
                    }
                }
            }', ['input' => $data])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess && $role) {
            $role = $role->fresh();

            self::assertNotNull($role);
            self::assertEquals($data['name'], $role->name);
            self::assertEquals(
                $role->permissions->pluck((new Permission())->getKeyName())->all(),
                $data['permissions'],
            );
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $client  = static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('createGroup')
                ->once()
                ->andReturns(new Group([
                    'id'   => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    'name' => 'subgroup',
                ]));
            $mock
                ->shouldReceive('updateGroup')
                ->once()
                ->andReturn(true);
            $mock
                ->shouldReceive('updateGroupRoles')
                ->once()
                ->andReturn(true);
        };
        $factory = static function (TestCase $test): ?Role {
            $test->app->make(Permissions::class)->add([
                new class('permission-a') extends AuthPermission {
                    // empty
                },
            ]);

            Permission::factory()->create([
                'id'  => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                'key' => 'permission-a',
            ]);

            return null;
        };

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('org', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new OrgUserDataProvider('org', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragment('role.create', [
                            'result' => true,
                            'role'   => [
                                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                                'name'        => 'subgroup',
                                'permissions' => [
                                    [
                                        'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                                        'key'         => 'permission-a',
                                        'name'        => 'permission-a',
                                        'description' => 'permission-a',
                                    ],
                                ],
                            ],
                        ]),
                    ),
                    $factory,
                    $client,
                    [
                        'name'        => 'subgroup',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        ],
                    ],
                ],
                'empty permissions' => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragment('role.create', [
                            'result' => true,
                            'role'   => [
                                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                                'name'        => 'subgroup',
                                'permissions' => [
                                    // empty
                                ],
                            ],
                        ]),
                    ),
                    $factory,
                    $client,
                    [
                        'name'        => 'subgroup',
                        'permissions' => [],
                    ],
                ],
                'Invalid input'     => [
                    new GraphQLValidationError('org', static function (): array {
                        return [
                            'input.name'          => [
                                trans('validation.required'),
                            ],
                            'input.permissions.0' => [
                                trans('validation.org_permission_id'),
                            ],
                        ];
                    }),
                    $factory,
                    null,
                    [
                        'name'        => '',
                        'permissions' => [
                            'f6739b66-5582-476d-ab46-b7634d758b6f',
                        ],
                    ],
                ],
                'Role exists'       => [
                    new GraphQLValidationError('org', static function (): array {
                        return [
                            'input.name' => [
                                trans('validation.org_role_name'),
                            ],
                        ];
                    }),
                    static function (TestCase $test, Organization $org): Role {
                        return Role::factory()->create([
                            'name'            => 'new role',
                            'organization_id' => $org,
                        ]);
                    },
                    null,
                    [
                        'name'        => 'new role',
                        'permissions' => [],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
