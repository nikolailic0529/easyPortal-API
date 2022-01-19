<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\Team;
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
 * @coversDefaultClass \App\GraphQL\Queries\Administration\Users
 */
class UsersTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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
                        id
                        given_name
                        family_name
                        email
                        email_verified
                        enabled
                        given_name
                        family_name
                        title
                        academic_title
                        office_phone
                        mobile_phone
                        contact_email
                        job_title
                        photo
                        company
                        phone
                        locale
                        timezone
                        invitations {
                            id
                            organization_id
                            role_id
                            role {
                                id
                                name
                            }
                            team_id
                            team {
                                id
                                name
                            }
                            email
                            used_at
                            expired_at
                        }
                        organizations {
                            organization_id
                            role {
                                id
                                name
                            }
                            team {
                                id
                                name
                            }
                            enabled
                        }
                    }
                    usersAggregated {
                        count
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
                        new GraphQLPaginated(
                            'users',
                            self::class,
                            [
                                [
                                    'id'             => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                    'given_name'     => 'keycloak',
                                    'family_name'    => 'user',
                                    'email'          => 'test1@example.com',
                                    'email_verified' => true,
                                    'enabled'        => true,
                                    'title'          => 'Mr',
                                    'academic_title' => 'Professor',
                                    'phone'          => '+1-202-555-0198',
                                    'office_phone'   => '+1-202-555-0197',
                                    'mobile_phone'   => '+1-202-555-0147',
                                    'contact_email'  => 'test@gmail.com',
                                    'job_title'      => 'Manger',
                                    'photo'          => 'https://example.com/photo.jpg',
                                    'company'        => 'company1',
                                    'locale'         => 'de_AT',
                                    'timezone'       => 'Europe/Guernsey',
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
                                            'team_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                            'team'            => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                                'name' => 'IT',
                                            ],
                                            'used_at'         => null,
                                            'expired_at'      => '2021-01-01T00:00:00+00:00',
                                        ],
                                    ],
                                    'organizations'  => [
                                        [
                                            'organization_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                            'enabled'         => true,
                                            'team'            => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                                'name' => 'IT',
                                            ],
                                            'role'            => [
                                                'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                                'name' => 'role1',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'count' => 1,
                            ],
                        ),
                        static function (TestCase $test, Organization $organization, User $user): void {
                            // Exclude auth user (because we don't know id and cannot use it in `GraphQLPaginated`)
                            $user->delete();

                            // user1
                            $role1 = Role::factory()->create([
                                'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                'name' => 'role1',
                            ]);
                            $team1 = Team::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                'name' => 'IT',
                            ]);
                            $user1 = User::factory()
                                ->hasInvitations(1, [
                                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'email'           => 'test@gmail.com',
                                    'organization_id' => $organization->getKey(),
                                    'user_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                    'role_id'         => $role1,
                                    'team_id'         => $team1,
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
                                    'title'          => 'Mr',
                                    'academic_title' => 'Professor',
                                    'phone'          => '+1-202-555-0198',
                                    'office_phone'   => '+1-202-555-0197',
                                    'mobile_phone'   => '+1-202-555-0147',
                                    'contact_email'  => 'test@gmail.com',
                                    'job_title'      => 'Manger',
                                    'photo'          => 'https://example.com/photo.jpg',
                                    'company'        => 'company1',
                                    'locale'         => 'de_AT',
                                    'timezone'       => 'Europe/Guernsey',
                                ]);

                            OrganizationUser::factory()->create([
                                'organization_id' => $organization,
                                'user_id'         => $user1,
                                'role_id'         => $role1,
                                'team_id'         => $team1,
                                'enabled'         => true,
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
                                'title'          => 'Mr',
                                'academic_title' => 'Professor',
                                'phone'          => '+1-202-555-0198',
                                'office_phone'   => '+1-202-555-0197',
                                'mobile_phone'   => '+1-202-555-0147',
                                'contact_email'  => 'test@gmail.com',
                                'job_title'      => 'Manger',
                                'photo'          => 'https://example.com/photo.jpg',
                                'company'        => 'company1',
                                'locale'         => 'de_AT',
                                'timezone'       => 'Europe/Guernsey',
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
                                        'team_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                        'team'            => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                            'name' => 'IT',
                                        ],
                                        'used_at'         => null,
                                        'expired_at'      => '2021-01-01T00:00:00+00:00',
                                    ],
                                ],
                                'organizations'  => [
                                    [
                                        'organization_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                        'enabled'         => true,
                                        'team'            => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                            'name' => 'IT',
                                        ],
                                        'role'            => [
                                            'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                            'name' => 'role1',
                                        ],
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
                                'title'          => 'Mrs',
                                'academic_title' => 'Associate',
                                'phone'          => '+1-202-555-0199',
                                'office_phone'   => '+1-202-555-0198',
                                'mobile_phone'   => '+1-202-555-0148',
                                'contact_email'  => 'test2@gmail.com',
                                'job_title'      => 'Employee',
                                'photo'          => 'https://example.com/photo1.jpg',
                                'company'        => 'company2',
                                'locale'         => 'de_DE',
                                'timezone'       => 'Europe/Berlin',
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
                                        'team_id'         => null,
                                        'team'            => null,
                                        'used_at'         => null,
                                        'expired_at'      => '2021-01-01T00:00:00+00:00',
                                    ],
                                ],
                                'organizations'  => [
                                    [
                                        'organization_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                        'enabled'         => true,
                                        'team'            => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                            'name' => 'Marketing',
                                        ],
                                        'role'            => [
                                            'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d05',
                                            'name' => 'role2',
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                        static function (TestCase $test, Organization $organization, User $user): void {
                            // Exclude auth user (because we don't know id and cannot use it in `GraphQLPaginated`)
                            $user->type = UserType::local();
                            $user->delete();

                            // User 1
                            $role1 = Role::factory()->create([
                                'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                                'name' => 'role1',
                            ]);
                            $team1 = Team::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                'name' => 'IT',
                            ]);
                            $user1 = User::factory()
                                ->hasInvitations(1, [
                                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'email'           => 'test@gmail.com',
                                    'organization_id' => $organization->getKey(),
                                    'user_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                    'role_id'         => $role1,
                                    'team_id'         => $team1,
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
                                    'title'          => 'Mr',
                                    'academic_title' => 'Professor',
                                    'phone'          => '+1-202-555-0198',
                                    'office_phone'   => '+1-202-555-0197',
                                    'mobile_phone'   => '+1-202-555-0147',
                                    'contact_email'  => 'test@gmail.com',
                                    'job_title'      => 'Manger',
                                    'photo'          => 'https://example.com/photo.jpg',
                                    'company'        => 'company1',
                                    'locale'         => 'de_AT',
                                    'timezone'       => 'Europe/Guernsey',
                                    'created_at'     => Date::now()->subMinutes(1),
                                ]);

                            // Relation
                            OrganizationUser::factory()->create([
                                'organization_id' => $organization,
                                'user_id'         => $user1,
                                'role_id'         => $role1,
                                'team_id'         => $team1,
                                'enabled'         => true,
                            ]);

                            // User2
                            $role2 = Role::factory()->create([
                                'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d05',
                                'name' => 'role2',
                            ]);
                            $user2 = User::factory()
                                ->hasInvitations(1, [
                                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                    'email'           => 'test@gmail.com',
                                    'organization_id' => $organization->getKey(),
                                    'user_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d01',
                                    'role_id'         => 'ae85870f-1593-4eb5-ae08-ee00f0688d05',
                                    'team_id'         => null,
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
                                    'title'          => 'Mrs',
                                    'academic_title' => 'Associate',
                                    'phone'          => '+1-202-555-0199',
                                    'office_phone'   => '+1-202-555-0198',
                                    'mobile_phone'   => '+1-202-555-0148',
                                    'contact_email'  => 'test2@gmail.com',
                                    'job_title'      => 'Employee',
                                    'photo'          => 'https://example.com/photo1.jpg',
                                    'company'        => 'company2',
                                    'locale'         => 'de_DE',
                                    'timezone'       => 'Europe/Berlin',
                                    'created_at'     => Date::now()->subMinutes(2),
                                ]);
                            $team2 = Team::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'Marketing',
                            ]);

                            // Relation
                            OrganizationUser::factory()->create([
                                'organization_id' => $organization,
                                'user_id'         => $user2,
                                'role_id'         => $role2,
                                'team_id'         => $team2,
                                'enabled'         => true,
                            ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
