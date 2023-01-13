<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\GraphQL\Queries\Administration\User as UserQuery;
use App\Models\Data\Status;
use App\Models\Data\Team;
use App\Models\Enums\UserType;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Closure;
use Illuminate\Support\Facades\Date;
use JsonSerializable;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Administration\User
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class UserTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $id   = $this->faker->uuid();

        if ($prepare) {
            $id = $prepare($this, $org, $user)->getKey();
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
                        job_title
                        photo
                        company
                        phone
                        locale
                        timezone
                        previous_sign_in
                        invitations_count
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
                        organizations_count
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
                            status {
                                id
                                key
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

    /**
     * @dataProvider dataProviderStatus
     *
     * @param Closure(self): OrganizationUser $organizationUserFactory
     */
    public function testStatus(Status $expected, Closure $organizationUserFactory): void {
        $user   = $organizationUserFactory($this);
        $query  = $this->app->make(UserQuery::class);
        $actual = GlobalScopes::callWithoutAll(static function () use ($user, $query): Status {
            return $query->status($user);
        });

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new AuthOrgRootDataProvider('user', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981'),
            new OrgUserDataProvider('user', [
                'administer',
            ]),
            new ArrayDataProvider([
                'keycloak users'                => [
                    new GraphQLSuccess('user', [
                        'id'                  => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                        'given_name'          => 'keycloak',
                        'family_name'         => 'user',
                        'email'               => 'test1@example.com',
                        'email_verified'      => true,
                        'enabled'             => true,
                        'title'               => 'Mr',
                        'academic_title'      => 'Professor',
                        'phone'               => '+1-202-555-0198',
                        'office_phone'        => '+1-202-555-0197',
                        'mobile_phone'        => '+1-202-555-0147',
                        'contact_email'       => 'test@gmail.com',
                        'job_title'           => 'Manger',
                        'photo'               => 'https://example.com/photo.jpg',
                        'company'             => 'company1',
                        'locale'              => 'de_AT',
                        'timezone'            => 'Europe/Guernsey',
                        'previous_sign_in'    => '2022-10-03T00:00:00+00:00',
                        'invitations_count'   => 1,
                        'invitations'         => [
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
                        'organizations_count' => 1,
                        'organizations'       => [
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
                                'status'          => [
                                    'id'   => 'f482da3b-f3e9-4af3-b2ab-8e4153fa8eb1',
                                    'key'  => 'active',
                                    'name' => 'Active',
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
                                'id'               => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                'given_name'       => 'keycloak',
                                'family_name'      => 'user',
                                'email'            => 'test1@example.com',
                                'email_verified'   => true,
                                'enabled'          => true,
                                'type'             => UserType::keycloak(),
                                'title'            => 'Mr',
                                'academic_title'   => 'Professor',
                                'phone'            => '+1-202-555-0198',
                                'office_phone'     => '+1-202-555-0197',
                                'mobile_phone'     => '+1-202-555-0147',
                                'contact_email'    => 'test@gmail.com',
                                'job_title'        => 'Manger',
                                'photo'            => 'https://example.com/photo.jpg',
                                'company'          => 'company1',
                                'locale'           => 'de_AT',
                                'timezone'         => 'Europe/Guernsey',
                                'previous_sign_in' => '2022-10-03T00:00:00+00:00',
                                'created_at'       => Date::now()->subMinutes(1),
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
                    new GraphQLSuccess('user', new class() implements JsonSerializable {
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
                                'id'               => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                'given_name'       => 'keycloak',
                                'family_name'      => 'user',
                                'email'            => 'test1@example.com',
                                'email_verified'   => true,
                                'enabled'          => true,
                                'type'             => UserType::local(),
                                'title'            => 'Mr',
                                'academic_title'   => 'Professor',
                                'phone'            => '+1-202-555-0198',
                                'office_phone'     => '+1-202-555-0197',
                                'mobile_phone'     => '+1-202-555-0147',
                                'contact_email'    => 'test@gmail.com',
                                'job_title'        => 'Manger',
                                'photo'            => 'https://example.com/photo.jpg',
                                'company'          => 'company1',
                                'locale'           => 'de_AT',
                                'timezone'         => 'Europe/Guernsey',
                                'previous_sign_in' => '2022-10-03T00:00:00+00:00',
                                'created_at'       => Date::now()->subMinutes(1),
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
                    new GraphQLSuccess('user', [
                        'id'                  => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                        'given_name'          => 'keycloak',
                        'family_name'         => 'user',
                        'email'               => 'test1@example.com',
                        'email_verified'      => true,
                        'enabled'             => true,
                        'title'               => 'Mr',
                        'academic_title'      => 'Professor',
                        'phone'               => '+1-202-555-0198',
                        'office_phone'        => '+1-202-555-0197',
                        'mobile_phone'        => '+1-202-555-0147',
                        'contact_email'       => 'test@gmail.com',
                        'job_title'           => 'Manger',
                        'photo'               => 'https://example.com/photo.jpg',
                        'company'             => 'company1',
                        'locale'              => 'de_AT',
                        'timezone'            => 'Europe/Guernsey',
                        'previous_sign_in'    => '2022-10-03T00:00:00+00:00',
                        'invitations_count'   => 1,
                        'invitations'         => [
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
                        'organizations_count' => 1,
                        'organizations'       => [
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
                                'status'          => [
                                    'id'   => 'f482da3b-f3e9-4af3-b2ab-8e4153fa8eb1',
                                    'key'  => 'active',
                                    'name' => 'Active',
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
                                'id'               => 'ae85870f-1593-4eb5-ae08-ee00f0688d00',
                                'given_name'       => 'keycloak',
                                'family_name'      => 'user',
                                'email'            => 'test1@example.com',
                                'email_verified'   => true,
                                'enabled'          => true,
                                'type'             => UserType::local(),
                                'title'            => 'Mr',
                                'academic_title'   => 'Professor',
                                'phone'            => '+1-202-555-0198',
                                'office_phone'     => '+1-202-555-0197',
                                'mobile_phone'     => '+1-202-555-0147',
                                'contact_email'    => 'test@gmail.com',
                                'job_title'        => 'Manger',
                                'photo'            => 'https://example.com/photo.jpg',
                                'company'          => 'company1',
                                'locale'           => 'de_AT',
                                'timezone'         => 'Europe/Guernsey',
                                'previous_sign_in' => '2022-10-03T00:00:00+00:00',
                                'created_at'       => Date::now()->subMinutes(1),
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

    /**
     * @return array<string, mixed>
     */
    public function dataProviderStatus(): array {
        return [
            'disabled'                               => [
                (new Status())->forceFill([
                    'id'          => '347e5072-9cd8-42a7-a1be-47f329a9e3eb',
                    'key'         => 'inactive',
                    'name'        => 'Inactive',
                    'object_type' => 'User',
                ]),
                static function (): OrganizationUser {
                    return OrganizationUser::factory()->make([
                        'enabled' => false,
                    ]);
                },
            ],
            'enabled + not invited'                  => [
                (new Status())->forceFill([
                    'id'          => 'f482da3b-f3e9-4af3-b2ab-8e4153fa8eb1',
                    'key'         => 'active',
                    'name'        => 'Active',
                    'object_type' => 'User',
                ]),
                static function (): OrganizationUser {
                    return OrganizationUser::factory()->make([
                        'enabled' => true,
                    ]);
                },
            ],
            'enabled + invited + no invitation'      => [
                (new Status())->forceFill([
                    'id'          => 'c4136a8c-7cc4-4e30-8712-e47565a5e167',
                    'key'         => 'expired',
                    'name'        => 'Expired',
                    'object_type' => 'User',
                ]),
                static function (): OrganizationUser {
                    return OrganizationUser::factory()->make([
                        'enabled' => true,
                        'invited' => true,
                    ]);
                },
            ],
            'enabled + invited + invitation'         => [
                (new Status())->forceFill([
                    'id'          => '849deaf1-1ff4-4cd4-9c03-a1c4d9ba0402',
                    'key'         => 'invited',
                    'name'        => 'Invited',
                    'object_type' => 'User',
                ]),
                static function (): OrganizationUser {
                    $invitation = Invitation::factory()->create([
                        'expired_at' => Date::now()->addDay(),
                    ]);

                    return OrganizationUser::factory()->make([
                        'enabled'         => true,
                        'invited'         => true,
                        'user_id'         => $invitation->user_id,
                        'organization_id' => $invitation->organization_id,
                    ]);
                },
            ],
            'enabled + invited + expired invitation' => [
                (new Status())->forceFill([
                    'id'          => 'c4136a8c-7cc4-4e30-8712-e47565a5e167',
                    'key'         => 'expired',
                    'name'        => 'Expired',
                    'object_type' => 'User',
                ]),
                static function (): OrganizationUser {
                    $invitation = Invitation::factory()->create([
                        'expired_at' => Date::now()->subDay(),
                    ]);

                    return OrganizationUser::factory()->make([
                        'enabled'         => true,
                        'invited'         => true,
                        'user_id'         => $invitation->user_id,
                        'organization_id' => $invitation->organization_id,
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
