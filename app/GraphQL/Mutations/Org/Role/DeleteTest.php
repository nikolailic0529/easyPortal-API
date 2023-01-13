<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\Role;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
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
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;
use Throwable;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Org\Role\Delete
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class DeleteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke

     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $roleFactory = null,
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $role = Role::factory()->make();

        if ($roleFactory) {
            $role = $roleFactory($this, $org, $user);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $response = $this
            ->graphQL(/** @lang GraphQL */ 'mutation delete($id: ID!) {
                org {
                    role(id: $id) {
                        delete {
                            result
                        }
                    }
                }
            }', ['id' => $role->getKey()])
            ->assertThat($expected);

        if ($response instanceof GraphQLSuccess) {
            self::assertTrue(Role::whereKey($role->getKey())->exists());
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
            new AuthOrgDataProvider('org', '439a0a06-d98a-41f0-b8e5-4e5722518e00'),
            new OrgUserDataProvider('org', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'role not exists'                => [
                    new GraphQLError('org', static function (): Throwable {
                        return new ObjectNotFound((new Role())->getMorphClass());
                    }),
                    static function (TestCase $test): Role {
                        return Role::factory()->make();
                    },
                    null,
                ],
                'role without users'             => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragment('role.delete', [
                            'result' => true,
                        ]),
                    ),
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
                    new GraphQLError('org', static function (): Throwable {
                        return new DeleteImpossibleAssignedToUsers(new Role());
                    }),
                    static function (TestCase $test, Organization $organization, User $user): Role {
                        $role    = Role::factory()->create([
                            'id'              => '2ce0a956-b314-40b8-b192-3aaeeb067e37',
                            'name'            => 'name',
                            'organization_id' => $organization,
                        ]);
                        $orgUser = $user->organizations
                            ->first(static function (OrganizationUser $user) use ($organization): bool {
                                return $user->organization_id === $organization->getKey();
                            });

                        $orgUser->role = $role;
                        $orgUser->save();

                        return $role;
                    },
                    static function (MockInterface $mock): void {
                        // empty
                    },
                ],
                'Role from another organization' => [
                    new GraphQLError('org', static function (): Throwable {
                        return new ObjectNotFound((new Role())->getMorphClass());
                    }),
                    static function (): Role {
                        return Role::factory()->create();
                    },
                    null,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
