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
 * @coversDefaultClass \App\GraphQL\Mutations\Org\UpdateOrgRoles
 */
class UpdateOrgRolesTest extends TestCase {
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
            [
                'id'          => '',
                'name'        => 'wrong',
                'permissions' => [],
            ],
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
            ->graphQL(/** @lang GraphQL */ 'mutation UpdateOrgRoles($input: [UpdateOrgRolesInput!]!) {
                updateOrgRoles(input:$input) {
                    updated {
                        id
                        name
                        permissions
                    }
                }
            }', ['input' => $data])
            ->assertThat($expected);
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
            new OrganizationDataProvider('updateOrgRoles', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new OrganizationUserDataProvider('updateOrgRoles', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                     => [
                    new GraphQLSuccess('updateOrgRoles', UpdateOrgRoles::class, [
                        'updated' => [
                            [
                                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                                'name'        => 'change',
                                'permissions' => [
                                    'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                                ],
                            ],
                        ],
                    ]),
                    $factory,
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('editSubGroup')
                            ->once();
                        $mock
                            ->shouldReceive('addRolesToGroup')
                            ->once();
                        $mock
                            ->shouldReceive('getGroup')
                            ->twice()
                            ->andReturns(
                                new Group([
                                    'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                                    'name'        => 'name',
                                    'clientRoles' => [
                                        'client_id' => [
                                            'permission2',
                                        ],
                                    ],
                                ]),
                                new Group([
                                    'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                                    'name'        => 'change',
                                    'clientRoles' => [
                                        'client_id' => [
                                            'permission1',
                                        ],
                                    ],
                                ]),
                            );
                    },
                    [
                        [
                            'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'name'        => 'change',
                            'permissions' => [
                                'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            ],
                        ],
                    ],
                ],
                'Invalid permissionsIds' => [
                    new GraphQLError('updateOrgRoles', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $factory,
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('editSubGroup')
                            ->never();
                        $mock
                            ->shouldReceive('addRolesToGroup')
                            ->never();
                        $mock
                            ->shouldReceive('getGroup')
                            ->never();
                    },
                    [
                        [
                            'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'name'        => 'change',
                            'permissions' => [
                                'fd421bad-069f-491c-ad5f-5841aa9a9dfz',
                            ],
                        ],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
