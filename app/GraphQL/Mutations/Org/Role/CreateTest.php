<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Group;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\Role\Create
 */
class CreateTest extends TestCase {
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
        Closure $factory = null,
        array $data = [
            'name'        => 'wrong',
            'permissions' => [],
        ],
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $this->setOrganization($organization));

        if ($organization && !$organization->keycloak_group_id) {
            $organization->keycloak_group_id = $this->faker->uuid();
            $organization->save();
            $organization = $organization->fresh();
        }

        $this->setSettings([
            'ep.keycloak.client_id' => 'client_id',
        ]);

        if ($factory) {
            $factory($this, $organization, $user);
        }

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

        if ($expected instanceof GraphQLSuccess) {
            $role = Role::with('permissions')->whereKey('fd421bad-069f-491c-ad5f-5841aa9a9dff')->first();
            $this->assertNotNull($role);
            $this->assertEquals($data['name'], $role->name);
            $this->assertEquals(
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
        $clientFactory     = static function (MockInterface $mock): void {
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
        $permissionFactory = static function (TestCase $test): void {
            Permission::factory()->create([
                'id'  => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                'key' => 'permission1',
            ]);
        };

        return (new CompositeDataProvider(
            new OrganizationDataProvider('org', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new OrganizationUserDataProvider('org', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                  => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('role.create', self::class),
                        new JsonFragment('role.create', [
                            'result' => true,
                            'role'   => [
                                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                                'name'        => 'subgroup',
                                'permissions' => [
                                    [
                                        'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                                        'key'         => 'permission1',
                                        'name'        => 'permission1',
                                        'description' => 'permission1',
                                    ],
                                ],
                            ],
                        ]),
                    ),
                    $permissionFactory,
                    [
                        'name'        => 'subgroup',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        ],
                    ],
                    $clientFactory,
                ],
                'Invalid name'        => [
                    new GraphQLError('org', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $permissionFactory,
                    [
                        'name'        => '',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        ],
                    ],
                    null,
                ],
                'Invalid permissions' => [
                    new GraphQLError('org', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $permissionFactory,
                    [
                        'name'        => 'subgroup',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfd',
                        ],
                    ],
                    null,
                ],
                'Role exists'         => [
                    new GraphQLError('org', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test, Organization $organization): void {
                        Role::factory()->create([
                            'name'            => 'new role',
                            'organization_id' => $organization,
                        ]);
                    },
                    [
                        'name'        => 'new role',
                        'permissions' => [],
                    ],
                    null,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}