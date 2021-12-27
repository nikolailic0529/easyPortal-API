<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\GraphQL\Mutations\Org\Role\DeleteImpossibleAssignedToUsers;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
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
use Throwable;

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
     * @param array<string,mixed> $input
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $roleFactory = null,
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $role         = Role::factory()->make();

        if ($roleFactory) {
            $role = $roleFactory($this, $organization, $user);
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
            }', ['input' => ['id' => $role->getKey()]])
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
            new OrganizationUserDataProvider('deleteOrgRole', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'role not exists'                => [
                    new GraphQLSuccess('deleteOrgRole', DeleteOrgRole::class, [
                        'deleted' => false,
                    ]),
                    static function (TestCase $test): Role {
                        return Role::factory()->make();
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('deleteGroup')
                            ->never();
                    },
                ],
                'role without users'             => [
                    new GraphQLSuccess('deleteOrgRole', DeleteOrgRole::class, [
                        'deleted' => true,
                    ]),
                    static function (TestCase $test, Organization $organization, User $user): Role {
                        return Role::factory()->create([
                            'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'name'            => 'name',
                            'organization_id' => $organization,
                        ]);
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('deleteGroup')
                            ->once()
                            ->andReturn(true);
                    },
                ],
                'role with users'                => [
                    new GraphQLError('deleteOrgRole', static function (): Throwable {
                        return new DeleteImpossibleAssignedToUsers(new Role());
                    }),
                    static function (TestCase $test, Organization $organization, User $user): Role {
                        $role = Role::factory()->create([
                            'id'              => '2ce0a956-b314-40b8-b192-3aaeeb067e37',
                            'name'            => 'name',
                            'organization_id' => $organization,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'role_id'         => $role,
                        ]);

                        return $role;
                    },
                    static function (MockInterface $mock): void {
                        // empty
                    },
                ],
                'role from another organization' => [
                    new GraphQLSuccess('deleteOrgRole', DeleteOrgRole::class, [
                        'deleted' => false,
                    ]),
                    static function (TestCase $test): Role {
                        return Role::factory()->create([
                            'id'              => 'c92d38b1-401a-4501-8f1e-c0c03244596d',
                            'organization_id' => Organization::factory()->create(),
                        ]);
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('deleteGroup')
                            ->never();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
