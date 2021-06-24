<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Roles;

use App\Models\Organization;
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
 * @coversDefaultClass \App\GraphQL\Mutations\CreateOrgRole
 */
class CreateOrgRoleTest extends TestCase {
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
        array $data = [
            'name' => 'wrong',
        ],
        Closure $requests = null,
    ): void {
        // Prepare
        $organization = null;

        if ($organizationFactory) {
            $organization = $organizationFactory($this);
        }

        if ($organization && !$organization->keycloak_group_id) {
            $organization->keycloak_group_id = $this->faker->uuid();
            $organization->save();
            $organization = $organization->fresh();
        }

        $this->setUser($userFactory, $this->setOrganization($organization));

        if ($requests) {
            $this->app->instance(Factory::class, Http::fake(
                $requests($this, $organization),
            ));
        }


        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation CreateOrgRole($input: CreateOrgRoleInput!) {
                createOrgRole(input:$input) {
                    created {
                        id
                        name
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
        $requests = static function (TestCase $test, Organization $organization): array {
            $client = $test->app->make(Client::class);
            $url    = $organization && $organization->keycloak_group_id
                ? "{$client->getBaseUrl()}/groups/{$organization->keycloak_group_id}/children"
                : '*';
            return [
                $url => Http::response(
                    [
                        'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                        'name'        => 'subgroup',
                        'path'        => '/test/subgroup',
                        'attributes'  => [],
                        'realmRoles'  => [],
                        'clientRoles' => [],
                        'subGroups'   => [],
                    ],
                    201,
                ),
            ];
        };
        return (new CompositeDataProvider(
            new OrganizationDataProvider('createOrgRole', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new UserDataProvider('createOrgRole', [
                'edit-organization',
            ]),
            new ArrayDataProvider([
                'ok'           => [
                    new GraphQLSuccess('createOrgRole', CreateOrgRole::class, [
                        'created' => [
                            'id'   => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'name' => 'subgroup',
                        ],
                    ]),
                    [
                        'name' => 'subgroup',
                    ],
                    $requests,
                ],
                'Invalid name' => [
                    new GraphQLError('createOrgRole', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [
                        'name' => '',
                    ],
                    $requests,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
