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
use JsonSerializable;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 */
class UserTest extends TestCase {
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
        $id           = $this->faker->uuid;

        if ($prepare) {
            $id = $prepare($this, $organization, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query getUser($id: ID!) {
                    user(id: $id) {
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
                        department
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
                        team {
                            id
                            name
                        }
                        role {
                            id
                            name
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
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('user', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981'),
            new OrganizationUserDataProvider('user', [
                'administer',
            ]),
            new ArrayDataProvider([
                'keycloak users'                => [
                    new GraphQLSuccess('user', self::class, [
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
                        'department'     => 'HR',
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
                        'team'           => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                            'name' => 'IT',
                        ],
                        'role'           => [
                            'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                            'name' => 'role1',
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
                    ]),
                    static function (TestCase $test, Organization $organization, User $user): User {
                        // Update user
                        $user->type = UserType::keycloak();
                        $user->save();

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
                                'department'     => 'HR',
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

                        // Return
                        return $user1;
                    },
                ],
                'keycloak user cannot see root' => [
                    new GraphQLSuccess('user', null, new class() implements JsonSerializable {
                        public function jsonSerialize(): mixed {
                            return null;
                        }
                    }),
                    static function (TestCase $test, Organization $organization, User $user): User {
                        // Update user
                        $user->type = UserType::keycloak();
                        $user->save();

                        // User 1
                        $role1 = Role::factory()->create([
                            'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                            'name' => 'role1',
                        ]);
                        $user1 = User::factory()
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
                                'type'           => UserType::local(),
                                'title'          => 'Mr',
                                'academic_title' => 'Professor',
                                'phone'          => '+1-202-555-0198',
                                'office_phone'   => '+1-202-555-0197',
                                'mobile_phone'   => '+1-202-555-0147',
                                'contact_email'  => 'test@gmail.com',
                                'department'     => 'HR',
                                'job_title'      => 'Manger',
                                'photo'          => 'https://example.com/photo.jpg',
                                'company'        => 'company1',
                                'locale'         => 'de_AT',
                                'timezone'       => 'Europe/Guernsey',
                                'created_at'     => Date::now()->subMinutes(1),
                            ]);

                        $team1 = Team::factory()->create([
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                            'name' => 'IT',
                        ]);

                        // Relation
                        $organizationUser                  = new OrganizationUser();
                        $organizationUser->organization_id = $organization->getKey();
                        $organizationUser->user_id         = $user1->getKey();
                        $organizationUser->team_id         = $team1->getKey();
                        $organizationUser->role_id         = $role1->getKey();
                        $organizationUser->enabled         = true;
                        $organizationUser->save();

                        // Return
                        return $user1;
                    },
                ],
                'root cannot see root'          => [
                    new GraphQLSuccess('user', self::class, [
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
                        'department'     => 'HR',
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
                                'team_id'         => null,
                                'team'            => null,
                                'used_at'         => null,
                                'expired_at'      => '2021-01-01T00:00:00+00:00',
                            ],
                        ],
                        'team'           => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                            'name' => 'IT',
                        ],
                        'role'           => [
                            'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                            'name' => 'role1',
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
                    ]),
                    static function (TestCase $test, Organization $organization, User $user): User {
                        // Update user
                        $user->type = UserType::local();
                        $user->save();

                        // User 1
                        $role1 = Role::factory()->create([
                            'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                            'name' => 'role1',
                        ]);
                        $user1 = User::factory()
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
                                'type'           => UserType::local(),
                                'title'          => 'Mr',
                                'academic_title' => 'Professor',
                                'phone'          => '+1-202-555-0198',
                                'office_phone'   => '+1-202-555-0197',
                                'mobile_phone'   => '+1-202-555-0147',
                                'contact_email'  => 'test@gmail.com',
                                'department'     => 'HR',
                                'job_title'      => 'Manger',
                                'photo'          => 'https://example.com/photo.jpg',
                                'company'        => 'company1',
                                'locale'         => 'de_AT',
                                'timezone'       => 'Europe/Guernsey',
                                'created_at'     => Date::now()->subMinutes(1),
                            ]);

                        $team1 = Team::factory()->create([
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                            'name' => 'IT',
                        ]);

                        // Relation
                        $organizationUser                  = new OrganizationUser();
                        $organizationUser->organization_id = $organization->getKey();
                        $organizationUser->user_id         = $user1->getKey();
                        $organizationUser->team_id         = $team1->getKey();
                        $organizationUser->role_id         = $role1->getKey();
                        $organizationUser->enabled         = true;
                        $organizationUser->save();

                        // Return
                        return $user1;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
