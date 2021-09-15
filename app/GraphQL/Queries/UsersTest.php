<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;

/**
 * @internal
 */
class UsersTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($prepare) {
            $prepare($this, $organization, $user);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query {
                  users {
                    data {
                      id
                      given_name
                      family_name
                      email
                      email_verified
                      enabled
                      roles {
                          id
                          name
                      }
                      invitations {
                          id
                          organization_id
                          role_id
                          role {
                              id
                              name
                          }
                          email
                          used_at
                          expired_at
                      }
                    }
                    paginatorInfo {
                      count
                      currentPage
                      firstItem
                      hasMorePages
                      lastItem
                      lastPage
                      perPage
                      total
                    }
                  }
                }
            ')->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new MergeDataProvider([
            'keycloak' => new CompositeDataProvider(
                new RootOrganizationDataProvider('users', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981'),
                new OrganizationUserDataProvider('users', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('users', self::class, [
                            [
                                'id'             => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                'given_name'     => 'keycloak',
                                'family_name'    => 'user',
                                'email'          => 'test1@example.com',
                                'email_verified' => true,
                                'enabled'        => true,
                                'roles'          => [
                                    [
                                        'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                        'name' => 'role1',
                                    ],
                                ],
                                'invitations'    => [
                                    [
                                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'email'           => 'test@gmail.com',
                                        'organization_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                        'role_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                        'role'            => [
                                            'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                            'name' => 'role1',
                                        ],
                                        'used_at'         => null,
                                        'expired_at'      => '2021-01-01T00:00:00+00:00',
                                    ],
                                ],
                            ],
                        ]),
                        static function (TestCase $test, Organization $organization, User $user): void {
                            if ($user) {
                                $user->type = UserType::keycloak();
                            }

                            User::factory()
                                ->hasRoles(1, [
                                    'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                    'name' => 'role1',
                                ])
                                ->hasInvitations(1, [
                                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'email'           => 'test@gmail.com',
                                    'organization_id' => $organization->getKey(),
                                    'user_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                    'role_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                    'used_at'         => null,
                                    'sender_id'       => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                    'expired_at'      => '2021-01-01T00:00:00+00:00',
                                ])
                                ->create([
                                    'id'             => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                    'given_name'     => 'keycloak',
                                    'family_name'    => 'user',
                                    'email'          => 'test1@example.com',
                                    'email_verified' => true,
                                    'enabled'        => true,
                                    'type'           => UserType::keycloak(),
                                ]);

                            User::factory()->create([
                                'id'             => 'ae85870f-1593-4eb5-ae08-ee00f0688d01',
                                'given_name'     => 'local',
                                'family_name'    => 'user',
                                'email'          => 'test2@example.com',
                                'email_verified' => true,
                                'enabled'        => true,
                                'type'           => UserType::local(),
                            ]);
                        },
                    ],
                ]),
            ),
            'root'     => new CompositeDataProvider(
                new RootOrganizationDataProvider('users', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981'),
                new OrganizationUserDataProvider('users', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('users', self::class, [
                            [
                                'id'             => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                'given_name'     => 'keycloak',
                                'family_name'    => 'user',
                                'email'          => 'test1@example.com',
                                'email_verified' => true,
                                'enabled'        => true,
                                'roles'          => [
                                    [
                                        'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                        'name' => 'role1',
                                    ],
                                ],
                                'invitations'    => [
                                    [
                                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'email'           => 'test@gmail.com',
                                        'organization_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                        'role_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                        'role'            => [
                                            'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                            'name' => 'role1',
                                        ],
                                        'used_at'         => null,
                                        'expired_at'      => '2021-01-01T00:00:00+00:00',
                                    ],
                                ],
                            ],
                            [
                                'id'             => 'ae85870f-1593-4eb5-ae08-ee00f0688d01',
                                'given_name'     => 'local',
                                'family_name'    => 'user',
                                'email'          => 'test2@example.com',
                                'email_verified' => true,
                                'enabled'        => true,
                                'roles'          => [
                                    [
                                        'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d05',
                                        'name' => 'role2',
                                    ],
                                ],
                                'invitations'    => [
                                    [
                                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                        'email'           => 'test@gmail.com',
                                        'organization_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                        'role_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d05',
                                        'role'            => [
                                            'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d05',
                                            'name' => 'role2',
                                        ],
                                        'used_at'         => null,
                                        'expired_at'      => '2021-01-01T00:00:00+00:00',
                                    ],
                                ],
                            ],
                        ]),
                        static function (TestCase $test, Organization $organization, User $user): void {
                            if ($user) {
                                $user->type = UserType::local();
                            }
                            User::factory()
                                ->hasRoles(1, [
                                    'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                    'name' => 'role1',
                                ])
                                ->hasInvitations(1, [
                                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'email'           => 'test@gmail.com',
                                    'organization_id' => $organization->getKey(),
                                    'user_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                    'role_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                    'sender_id'       => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                    'used_at'         => null,
                                    'expired_at'      => '2021-01-01T00:00:00+00:00',
                                ])
                                ->create([
                                    'id'             => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                    'given_name'     => 'keycloak',
                                    'family_name'    => 'user',
                                    'email'          => 'test1@example.com',
                                    'email_verified' => true,
                                    'enabled'        => true,
                                    'type'           => UserType::keycloak(),
                                    'created_at'     => Date::now()->subMinutes(1),
                                ]);

                            User::factory()
                                ->hasRoles(1, [
                                    'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d05',
                                    'name' => 'role2',
                                ])
                                ->hasInvitations(1, [
                                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                    'email'           => 'test@gmail.com',
                                    'organization_id' => $organization->getKey(),
                                    'user_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d01',
                                    'role_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d05',
                                    'sender_id'       => 'ae85870f-1593-4eb5-ae08-ee00f0688d01',
                                    'used_at'         => null,
                                    'expired_at'      => '2021-01-01T00:00:00+00:00',
                                ])
                                ->create([
                                    'id'             => 'ae85870f-1593-4eb5-ae08-ee00f0688d01',
                                    'given_name'     => 'local',
                                    'family_name'    => 'user',
                                    'email'          => 'test2@example.com',
                                    'email_verified' => true,
                                    'enabled'        => true,
                                    'type'           => UserType::local(),
                                    'created_at'     => Date::now()->subMinutes(2),
                                ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
