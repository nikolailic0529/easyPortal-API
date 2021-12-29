<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

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
use Tests\TestCase;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\UpdateOrgRole
 */
class UpdateOrgRoleTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $data
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $roleFactory = null,
        Closure $clientFactory = null,
        array $data = [
            'id'          => '',
            'name'        => 'wrong',
            'permissions' => [],
        ],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setSettings([
            'ep.keycloak.client_id' => 'client_id',
        ]);
        if ($roleFactory) {
            $roleFactory($this);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation UpdateOrgRole($input: UpdateOrgRoleInput!) {
                updateOrgRole(input:$input) {
                    updated {
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
            }', ['input' => $data])
            ->assertThat($expected);
        if ($expected instanceof GraphQLSuccess) {
            $role = Role::with('permissions')->whereKey($data['id'])->first();
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
        $factory = static function (TestCase $test): void {
            Role::factory()->create([
                'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                'name'            => 'name',
                'organization_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
            ]);

            Permission::factory()->create([
                'id'  => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                'key' => 'permission1',
            ]);
        };

        return (new CompositeDataProvider(
            new OrganizationDataProvider('updateOrgRole', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new OrganizationUserDataProvider('updateOrgRole', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                     => [
                    new GraphQLSuccess('updateOrgRole', UpdateOrgRole::class, [
                        'updated' => [
                            'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'name'        => 'change',
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
                    $factory,
                    static function (MockInterface $mock): void {
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
                    },
                    [
                        'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                        'name'        => 'change',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        ],
                    ],
                ],
                'Invalid permissionsIds' => [
                    new GraphQLError('updateOrgRole', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $factory,
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateGroup')
                            ->never();
                        $mock
                            ->shouldReceive('createGroupRoles')
                            ->never();
                        $mock
                            ->shouldReceive('getGroup')
                            ->never();
                    },
                    [
                        'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                        'name'        => '',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfz',
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
