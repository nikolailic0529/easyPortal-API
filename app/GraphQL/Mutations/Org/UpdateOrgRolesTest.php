<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Permission;
use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
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
        Closure $requestFactory = null,
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

        $role = null;
        if ($roleFactory) {
            $role = $roleFactory($this);
        }


        $requests = [
            '*' => Http::response([], 200),
        ];

        if ($requestFactory && $role) {
            $requests = $requestFactory($this, $role);
        }

        $http = Http::fake($requests);

        $this->app->instance(Factory::class, $http);

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
        $factory = static function (TestCase $test): Role {
            $role = Role::factory()->create([
                'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                'name'            => 'name',
                'organization_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
            ]);

            Permission::factory()->create([
                'id'  => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                'key' => 'permission1',
            ]);

            return $role;
        };

        $requestFactory = static function (TestCase $test, Role $role): array {
            $client  = $test->app->make(Client::class);
            $baseUrl = $client->getBaseUrl();
            return [
                "{$baseUrl}/groups/{$role->getKey()}" => Http::sequence()
                    ->push([], 200)                        // update
                    ->push([
                        'id'          => $role->getKey(), // Get First
                        'name'        => 'name',
                        'clientRoles' => [
                            'portal-web-app' => [
                                'permission2',
                            ],
                        ],
                    ], 200)
                    ->push([                              // Get after update
                        'id'          => $role->getKey(),
                        'name'        => 'change',
                        'clientRoles' => [
                            'portal-web-app' => [
                                'permission1',
                            ],
                        ],
                    ], 200),
                '*'                                   => Http::response([], 200),
            ];
        };
        return (new CompositeDataProvider(
            new OrganizationDataProvider('updateOrgRoles', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new UserDataProvider('updateOrgRoles', [
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
                    $requestFactory,
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
                    $requestFactory,
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
