<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
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
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;
use Throwable;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\Role\Update
 */
class UpdateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $data
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $roleFactory = null,
        Closure $clientFactory = null,
        array $data = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $this->setSettings([
            'ep.keycloak.client_id' => 'client_id',
        ]);

        $role   = $roleFactory
            ? $roleFactory($this, $organization, $user)
            : Role::factory()->make();
        $data ??= [
            'name'        => 'wrong',
            'permissions' => [],
        ];

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation update($id: ID!, $input: OrgRoleUpdateInput!) {
                    org {
                        role(id: $id) {
                            update(input: $input) {
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
                }
                GRAPHQL,
                [
                    'id'    => $role->getKey(),
                    'input' => $data,
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $updated = $role->fresh();

            if (isset($data['name'])) {
                self::assertEquals($data['name'], $updated->name);
            }

            if (isset($data['permissions'])) {
                self::assertEquals(
                    $updated->permissions->pluck((new Permission())->getKeyName())->all(),
                    $data['permissions'],
                );
            }
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $factory = static function (TestCase $test, Organization $organization): Role {
            $role = Role::factory()->create([
                'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                'name'            => 'name',
                'organization_id' => $organization,
            ]);

            Permission::factory()->create([
                'id'  => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                'key' => 'permission-a',
            ]);

            $test->app->make(Permissions::class)->add([
                new class('permission-a') extends AuthPermission {
                    // empty
                },
            ]);

            return $role;
        };
        $client  = static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('createGroup')
                ->once()
                ->andReturn(new Group([
                    'id'   => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    'name' => 'name',
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

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('org'),
            new OrgUserDataProvider('org', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                                 => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('role.update', self::class),
                        new JsonFragment('role.update', [
                            'result' => true,
                            'role'   => [
                                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                                'name'        => 'change',
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
                        'name'        => 'change',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        ],
                    ],
                ],
                'Role not found'                     => [
                    new GraphQLError('org', static function (): Throwable {
                        return new ObjectNotFound((new Role())->getMorphClass());
                    }),
                    null,
                    null,
                    null,
                ],
                'Empty name'                         => [
                    new GraphQLError('org', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $factory,
                    null,
                    [
                        'name'        => '',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        ],
                    ],
                ],
                'Invalid permissionsIds'             => [
                    new GraphQLError('org', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $factory,
                    null,
                    [
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfz',
                        ],
                    ],
                ],
                'Role from another organization'     => [
                    new GraphQLError('org', static function (): Throwable {
                        return new ObjectNotFound((new Role())->getMorphClass());
                    }),
                    static function (): Role {
                        return Role::factory()->create();
                    },
                    null,
                    null,
                ],
                'Role exists'                        => [
                    new GraphQLError('org', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test, Organization $organization): Role {
                        Role::factory()->create([
                            'name'            => 'new role',
                            'organization_id' => $organization,
                        ]);

                        return Role::factory()->create([
                            'organization_id' => $organization,
                        ]);
                    },
                    null,
                    [
                        'name' => 'new role',
                    ],
                ],
                'Role exists (another organization)' => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('role.update', self::class),
                        new JsonFragment('role.update.result', true),
                    ),
                    static function (TestCase $test, Organization $organization): Role {
                        Role::factory()->create([
                            'name' => 'new role',
                        ]);

                        return Role::factory()->create([
                            'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'organization_id' => $organization,
                        ]);
                    },
                    $client,
                    [
                        'name' => 'new role',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
