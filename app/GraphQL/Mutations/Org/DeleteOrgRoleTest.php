<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Role;
use App\Services\KeyCloak\Client\Client;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\DeleteOrgRole
 */
class DeleteOrgRoleTest extends TestCase {
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
            'id' => '',
        ],
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        $role = null;
        if ($roleFactory) {
            $role = $roleFactory($this);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $response = $this
            ->graphQL(/** @lang GraphQL */ 'mutation DeleteOrgRole($input: DeleteOrgRoleInput!) {
                deleteOrgRole(input:$input) {
                    deleted
                }
            }', ['input' => $data])
            ->assertThat($expected);

        if ($response instanceof GraphQLSuccess) {
            $this->assertTrue(Role::whereKey($role->getKey())->exists());
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new OrganizationDataProvider('deleteOrgRole', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new UserDataProvider('deleteOrgRole', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('deleteOrgRole', DeleteOrgRole::class, [
                        'deleted' => true,
                    ]),
                    static function (TestCase $test): Role {
                        return Role::factory()->create([
                            'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'name'            => 'name',
                            'organization_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        ]);
                    },
                    [
                        'id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('deleteGroup')
                            ->once();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
