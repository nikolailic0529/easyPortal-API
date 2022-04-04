<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Audits\Audit;
use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Team;
use App\Models\User;
use App\Models\UserSearch;
use App\Services\Audit\Enums\Action;
use Closure;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrganizationDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;

// FIXME [Test] We should standard User DataProviders here.

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Me
 *
 * @phpstan-import-type OrganizationFactory from \Tests\WithOrganization
 * @phpstan-import-type UserFactory from \Tests\WithUser
 * @phpstan-type        Prepare Closure(static, ?Organization, ?User): void|null
 */
class MeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @covers ::root
     *
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
    ): void {
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        $this
            ->graphQL(/** @lang GraphQL */ '{
                me {
                    id
                    email
                    given_name
                    family_name
                    locale
                    timezone
                    homepage
                    photo
                    title
                    academic_title
                    office_phone
                    mobile_phone
                    contact_email
                    job_title
                    company
                    phone
                    permissions
                    root
                    enabled
                    previous_sign_in
                    team {
                        id
                        name
                    }
                }
            }')
            ->assertThat($expected);
    }

    /**
     * @coversNothing
     *
     * @dataProvider dataProviderSearches
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param Prepare             $prepare
     */
    public function testSearches(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        if ($prepare) {
            $prepare($this, $org, $user);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query {
                    me {
                        searches {
                            id
                            key
                            name
                            conditions
                        }
                    }
            }')
            ->assertThat($expected);
    }

    /**
     * @covers ::team
     *
     * @dataProvider dataProviderTeam
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testTeam(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        // Test
        $this
            ->graphQL(/** @lang GraphQL */
                'query profile {
                    me {
                        team {
                            id
                            name
                        }
                    }
                }',
            )
            ->assertThat($expected);
    }

    /**
     * @covers ::orgs
     *
     * @dataProvider dataProviderOrgs
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param Prepare             $prepare
     */
    public function testOrgs(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        if ($prepare) {
            $prepare($this, $org, $user);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */
                'query profile {
                    me {
                        orgs {
                            id
                        }
                    }
                }',
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{Response, OrganizationFactory, UserFactory}>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new UnknownOrganizationDataProvider(),
            new ArrayDataProvider([
                'guest is allowed'           => [
                    new GraphQLSuccess('me', null),
                    static function (): ?User {
                        return null;
                    },
                ],
                'user is allowed (not root)' => [
                    new GraphQLSuccess('me', Me::class, new JsonFragment('root', false)),
                    static function (): User {
                        return User::factory()->make();
                    },
                ],
                'user is allowed (root)'     => [
                    new GraphQLSuccess('me', Me::class, new JsonFragment('root', true)),
                    static function (): User {
                        return User::factory()->make([
                            'type' => UserType::local(),
                        ]);
                    },
                ],
                'previous_sign_in'           => [
                    new GraphQLSuccess('me', Me::class, new JsonFragment(
                        'previous_sign_in',
                        '"2021-10-18T10:15:00+00:00"',
                    )),
                    static function (): User {
                        $user = User::factory()->make();
                        $date = Date::make('2021-10-19T10:15:00+00:00');

                        Audit::factory()->create([
                            'action'     => Action::authSignedIn(),
                            'user_id'    => $user->getKey(),
                            'created_at' => (clone $date)->subDay(),
                        ]);
                        Audit::factory()->create([
                            'action'     => Action::authSignedIn(),
                            'user_id'    => $user->getKey(),
                            'created_at' => (clone $date),
                        ]);

                        return $user;
                    },
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<string, array{Response, OrganizationFactory, UserFactory, Prepare}>
     */
    public function dataProviderSearches(): array {
        return (new CompositeDataProvider(
            new UnknownOrganizationDataProvider(),
            new ArrayDataProvider([
                'guest is allowed' => [
                    new GraphQLSuccess('me', self::class, 'null'),
                    static function (): ?User {
                        return null;
                    },
                    null,
                ],
                'user is allowed'  => [
                    new GraphQLSuccess('me', self::class, new JsonFragment('searches', [
                        [
                            'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'key'        => 'key',
                            'name'       => 'saved_filter',
                            'conditions' => 'conditions',
                        ],
                    ])),
                    static function (): User {
                        return User::factory()->create();
                    },
                    static function (TestCase $test, ?Organization $organization, ?User $user): void {
                        if ($user) {
                            UserSearch::factory()->create([
                                'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                'key'        => 'key',
                                'name'       => 'saved_filter',
                                'conditions' => 'conditions',
                                'user_id'    => $user->id,
                            ]);
                        }
                    },
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<string, array{Response, OrganizationFactory, UserFactory}>
     */
    public function dataProviderTeam(): array {
        return [
            'no organization'   => [
                new GraphQLSuccess('me', self::class, 'null'),
                static function (): ?Organization {
                    return null;
                },
                static function (): ?User {
                    return null;
                },
            ],
            'no user'           => [
                new GraphQLSuccess('me', self::class, 'null'),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (): ?User {
                    return null;
                },
            ],
            'user without team' => [
                new GraphQLSuccess('me', null, ['team' => null]),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (TestCase $test, ?Organization $organization): User {
                    $user = User::factory()->create();

                    OrganizationUser::factory()->create([
                        'organization_id' => $organization,
                        'user_id'         => $user,
                    ]);

                    return $user;
                },
            ],
            'user with team'    => [
                new GraphQLSuccess('me', null, new JsonFragment('team', [
                    'id'   => 'cff96820-82e2-4c0e-8441-e5cb80107f5b',
                    'name' => 'Team',
                ])),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (TestCase $test, ?Organization $organization): User {
                    $user = User::factory()->create();
                    $team = Team::factory()->create([
                        'id'   => 'cff96820-82e2-4c0e-8441-e5cb80107f5b',
                        'name' => 'Team',
                    ]);

                    OrganizationUser::factory()->create([
                        'organization_id' => $organization,
                        'user_id'         => $user,
                        'team_id'         => $team,
                    ]);

                    return $user;
                },
            ],
            'root without team' => [
                new GraphQLSuccess('me', null, ['team' => null]),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (): User {
                    return User::factory()->create([
                        'type' => UserType::local(),
                    ]);
                },
            ],
        ];
    }

    /**
     * @return array<string, array{Response, OrganizationFactory, UserFactory, Prepare}>
     */
    public function dataProviderOrgs(): array {
        return [
            'no organization'         => [
                new GraphQLSuccess('me', self::class, 'null'),
                static function (): ?Organization {
                    return null;
                },
                static function (): ?User {
                    return null;
                },
                null,
            ],
            'no user'                 => [
                new GraphQLSuccess('me', self::class, 'null'),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (): ?User {
                    return null;
                },
                null,
            ],
            'user with organizations' => [
                new GraphQLSuccess(
                    'me',
                    null,
                    new JsonFragment('orgs', [
                        [
                            'id' => '34092d03-ebfc-4ede-aec1-6b2df58c9f16',
                        ],
                    ]),
                ),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (): User {
                    return User::factory()->create();
                },
                static function (TestCase $test, ?Organization $org, ?User $user): void {
                    if ($user) {
                        OrganizationUser::factory()->create([
                            'enabled'         => true,
                            'user_id'         => $user,
                            'organization_id' => Organization::factory()->create([
                                'id' => '34092d03-ebfc-4ede-aec1-6b2df58c9f16',
                            ]),
                        ]);

                        OrganizationUser::factory()->create([
                            'enabled'         => false,
                            'user_id'         => $user,
                            'organization_id' => Organization::factory()->create([
                                'id' => '3328f60a-1d71-49a9-b024-a733e39f0bd1',
                            ]),
                        ]);
                    }
                },
            ],
        ];
    }
    // </editor-fold>
}
