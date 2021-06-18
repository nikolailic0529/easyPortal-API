<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Roles;

use App\Models\Permission;
use App\Models\Role;
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
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateOrgRole
 */
class UpdateOrgRoleTest extends TestCase {
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
        array $data = [
            'id'          => '',
            'name'        => 'wrong',
            'permissions' => [],
        ],
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        if ($roleFactory) {
            $roleFactory($this);
        }

        $http = Http::fake([
            '*' => Http::response([], 200),
        ]);

        $this->app->instance(Factory::class, $http);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation UpdateOrgRole($input: UpdateOrgRoleInput!) {
                updateOrgRole(input:$input) {
                    updated {
                        id
                        name
                        organization_id
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
            new OrganizationDataProvider('updateOrgRole', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new UserDataProvider('updateOrgRole', [
                'edit-organization',
            ]),
            new ArrayDataProvider([
                'ok'                     => [
                    new GraphQLSuccess('updateOrgRole', UpdateOrgRole::class, [
                        'updated' => [
                            'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'name'            => 'change',
                            'organization_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        ],
                    ]),
                    $factory,
                    [
                        'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                        'name'        => 'change',
                        'permissions' => [
                            'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        ],
                    ],
                ],
                'Invalid name'           => [
                    new GraphQLError('updateOrgRole', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $factory,
                    [
                        'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                        'name'        => '',
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
