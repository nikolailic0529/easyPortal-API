<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Mail\InviteOrganizationUser;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Team;
use App\Models\User as UserModel;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\RealmUserAlreadyExists;
use App\Services\KeyCloak\Client\Types\User;
use Closure;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\InviteOrgUser
 */
class InviteOrgUserTest extends TestCase {
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
        Closure $prepare = null,
        Closure $roleFactory = null,
        array $data = [
            'email'   => 'wrong@test.cpm',
            'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
        ],
        Closure $clientFactory = null,
        bool $shouldSendEmail = false,
    ): void {
        // Prepare
        $organization = $organizationFactory($this);
        $user         = $this->setUser($userFactory, $this->setOrganization($organization));

        if ($prepare) {
            $organization = $prepare($this, $organization, $user);
        }

        Mail::fake();

        if ($roleFactory) {
            $roleFactory($this, $organization);
        } else {
            // Lighthouse performs validation BEFORE permission check :(
            //
            // https://github.com/nuwave/lighthouse/issues/1780
            //
            // Following code required to "fix" it
            if (!$organization) {
                $organization = $this->setOrganization(Organization::factory()->create());
            }
            Role::factory()->create([
                'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'name'            => 'role1',
                'organization_id' => $organization->getKey(),
            ]);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation inviteOrgUser($input: InviteOrgUserInput!) {
                inviteOrgUser(input:$input) {
                    result
                }
            }', ['input' => $data])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $shouldSendEmail
                ? Mail::assertSent(InviteOrganizationUser::class)
                : Mail::assertNotSent(InviteOrganizationUser::class);

            $user = UserModel::query()
                ->with(['organizationUser'])
                ->whereKey('f9834bc1-2f2f-4c57-bb8d-7a224ac24987')
                ->first();
            $this->assertNotNull($user);
            $this->assertNotEmpty($user->organizationUser);
            // Organization
            $this->assertContains(
                $organization->getKey(),
                $user->organizationUser->pluck('organization_id'),
            );
            $this->assertEquals('f9834bc1-2f2f-4c57-bb8d-7a224ac24982', $user->role->getKey());
            if (isset($data['team_id'])) {
                $this->assertNotNull($user->team);
                $this->assertEquals($user->team->getKey(), $data['team_id']);
            }
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $roleFactory = static function (TestCase $test, ?Organization $organization): Role {
            $input = [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'name' => 'role1',
            ];
            if ($organization) {
                $input['organization_id'] = $organization->getKey();
            }

            return Role::factory()->create($input);
        };
        $prepare     = static function (TestCase $test, ?Organization $organization, ?UserModel $user): Organization {
            if ($organization && !$organization->keycloak_group_id) {
                $organization->keycloak_group_id = $test->faker->uuid();
                $organization->keycloak_scope    = $test->faker->word();
                $organization->save();
                $organization = $organization->fresh();

                if ($user) {
                    $user->save();
                }

                // Team
                Team::factory()->create([
                    'id' => '745e3dd2-915e-31b2-b02b-cbab069c9d65',
                ]);
            }

            return $organization;
        };

        return (new CompositeDataProvider(
            new OrganizationDataProvider('inviteOrgUser', '745e3dd2-915e-31b2-b02b-cbab069c9d45'),
            new OrganizationUserDataProvider('inviteOrgUser', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                                    => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'team_id' => '745e3dd2-915e-31b2-b02b-cbab069c9d65',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->once()
                            ->andReturns(true);
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturns(new User([
                                'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'email'         => 'test@gmail.com',
                                'emailVerified' => false,
                            ]));
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->never();
                    },
                    true,
                ],
                'ok/empty team'                         => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->once()
                            ->andReturns(true);
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturns(new User([
                                'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'email'         => 'test@gmail.com',
                                'emailVerified' => false,
                            ]));
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->never();
                    },
                    true,
                ],
                'exists'                                => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'team_id' => '745e3dd2-915e-31b2-b02b-cbab069c9d65',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->once()
                            ->andThrow(new RealmUserAlreadyExists('test@gmail.com'));
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturns(new User([
                                'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'email'         => 'test@gmail.com',
                                'emailVerified' => true,
                            ]));
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once();
                    },
                    true,
                ],
                'Resend invitation'                     => [
                    new GraphQLSuccess('inviteOrgUser', InviteOrgUser::class),
                    static function (TestCase $test, ?Organization $organization, ?UserModel $user): Organization {
                        if ($organization && !$organization->keycloak_group_id) {
                            $organization->keycloak_group_id = $test->faker->uuid();
                            $organization->keycloak_scope    = $test->faker->word();
                            $organization->save();
                            $organization = $organization->fresh();

                            if ($user) {
                                $user->save();
                            }
                        }

                        // User in organization
                        UserModel::factory()
                            ->hasOrganizationUser(1, [
                                'organization_id' => $organization->getKey(),
                            ])
                            ->create([
                                'id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'email' => 'test@gmail.com',
                            ]);

                        return $organization;
                    },
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->once()
                            ->andThrow(new RealmUserAlreadyExists('test@gmail.com'));
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturns(new User([
                                'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'email'         => 'test@gmail.com',
                                'emailVerified' => false,
                            ]));
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once();
                    },
                    true,
                ],
                'Invalid role (different organization)' => [
                    new GraphQLError('inviteOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    static function (TestCase $test, ?Organization $organization): Role {
                        return Role::factory()->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'name'            => 'role1',
                            'organization_id' => Organization::factory(),
                        ]);
                    },
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('inviteUser')
                            ->never();
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->never();
                    },
                    false,
                ],
                'Invalid email'                         => [
                    new GraphQLError('inviteOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    ],
                ],
                'Invalid role (no role)'                => [
                    new GraphQLError('inviteOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                    ],
                ],
                'Invalid team'                          => [
                    new GraphQLError('inviteOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $prepare,
                    $roleFactory,
                    [
                        'email'   => 'test@gmail.com',
                        'role_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                        'team_id' => '745e3dd2-915e-31b2-b02b-cbab069c9d66',
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
