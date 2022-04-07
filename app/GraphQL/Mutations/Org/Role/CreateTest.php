<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

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
use Tests\WithOrganization;
use Tests\WithUser;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\Role\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param array<string,mixed> $data
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
        array $data = [
            'name'        => 'wrong',
            'permissions' => [],
        ],
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $this->setOrganization($org));

        $this->setSettings([
            'ep.keycloak.client_id' => 'client_id',
        ]);

        if ($factory) {
            $factory($this, $org, $user);
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
        $clientFactory = static function (MockInterface $mock): void {
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
        $prepare       = static function (TestCase $test): void {
            $test->app->make(Permissions::class)->add([
                new class('permission-a') extends AuthPermission {
                    // empty
                },
            ]);

            Permission::factory()->create([
                'id'  => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                'key' => 'permission-a',
            ]);
        };

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('org', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new OrgUserDataProvider('org', [
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
                                        'key'         => 'permission-a',
                                        'name'        => 'permission-a',
                                        'description' => 'permission-a',
                                    ],
                                ],
                            ],
                        ]),
                    ),
                    $prepare,
                    [
                        'name'        => 'subgroup',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        ],
                    ],
                    $clientFactory,
                ],
                'empty permissions'   => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('role.create', self::class),
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
                    $prepare,
                    [
                        'name'        => 'subgroup',
                        'permissions' => [],
                    ],
                    $clientFactory,
                ],
                'Invalid name'        => [
                    new GraphQLError('org', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
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
                    $prepare,
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
