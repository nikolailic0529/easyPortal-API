<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org\User;

use App\GraphQL\Mutations\Organization\User\InviteImpossibleKeyCloakUserDisabled;
use App\Models\Enums\UserType;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Notifications\OrganizationUserInvitation;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\User as KeyCloakUser;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Closure;
use Illuminate\Support\Facades\Notification;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;
use Throwable;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Organization\User\Invite
 */
class InviteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $orgFactory,
        Closure $userFactory = null,
        Closure $clientFactory = null,
        Closure $dataFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $data = [
            'input' => [
                'email'   => '',
                'role_id' => '',
                'team_id' => '',
            ],
        ];

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        if ($dataFactory) {
            $data = $dataFactory($this, $org, $user);
        }

        if ($prepare) {
            $prepare($this, $org, $user, $data);
        }

        // Fake
        Notification::fake();

        // Count
        $organizationUsers = GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function (): int {
                return OrganizationUser::query()->count();
            },
        );

        // Test
        $response = $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($input: OrgUserInviteInput!) {
                    org {
                        user {
                            invite(input: $input) {
                                result
                            }
                        }
                    }
                }
                GRAPHQL,
                $data,
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            // Models
            if ($response->json('data.org.user.invite.result')) {
                $this->assertModelsCount([
                    User::class             => 2,
                    Invitation::class       => 1,
                    OrganizationUser::class => 2,
                ]);
            } else {
                $this->assertModelsCount([
                    User::class             => 2,
                    Invitation::class       => 0,
                    OrganizationUser::class => $organizationUsers,
                ]);
            }

            // User
            $user = User::query()->whereKeyNot($user->getKey())->first();

            $this->assertNotNull($user);
            $this->assertEquals($data['input']['email'], $user->email);

            // Notification
            if ($response->json('data.org.user.invite.result')) {
                Notification::assertSentTo($user, OrganizationUserInvitation::class);
            } else {
                Notification::assertNothingSent();
            }

            // Invitation
            if ($response->json('data.org.user.invite.result')) {
                $invitation = Invitation::query()->first();

                $this->assertNotNull($invitation);
                $this->assertEquals($org->getKey(), $invitation->organization_id);
                $this->assertEquals($data['input']['role_id'], $invitation->role_id);
                $this->assertEquals($data['input']['team_id'], $invitation->team_id);
                $this->assertEquals($user->getKey(), $invitation->user_id);
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
        $dataFactory = static function (self $test, Organization $organization): array {
            $role  = Role::factory()->create([
                'organization_id' => $organization,
            ]);
            $team  = Team::factory()->create();
            $email = $test->faker->email;

            return [
                'input' => [
                    'email'   => $email,
                    'role_id' => $role->getKey(),
                    'team_id' => $team->getKey(),
                ],
            ];
        };

        return (new CompositeDataProvider(
            new OrganizationDataProvider('org'),
            new OrganizationUserDataProvider('org', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'no user / ok'                       => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('user.invite', self::class),
                        new JsonFragment('user.invite', [
                            'result' => true,
                        ]),
                    ),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturn(new KeyCloakUser([
                                'id'      => 'e7dbdf00-dcca-4263-8cf4-af9e36038f66',
                                'enabled' => true,
                            ]));
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once()
                            ->andReturn(true);
                    },
                    $dataFactory,
                    static function (): void {
                        // empty
                    },
                ],
                'no user / shared role'              => [
                    new GraphQLValidationError('org'),
                    null,
                    static function (self $test): array {
                        $role  = Role::factory()->create([
                            'organization_id' => null,
                        ]);
                        $team  = Team::factory()->create();
                        $email = $test->faker->email;

                        return [
                            'input' => [
                                'email'   => $email,
                                'role_id' => $role->getKey(),
                                'team_id' => $team->getKey(),
                            ],
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                'no user / keycloak user not exists' => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('user.invite', self::class),
                        new JsonFragment('user.invite', [
                            'result' => true,
                        ]),
                    ),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturn(null);
                        $mock
                            ->shouldReceive('createUser')
                            ->once()
                            ->andReturn(new KeyCloakUser([
                                'id'      => '3b7180cb-bbcf-43bd-bcc2-c00509f1c222',
                                'enabled' => true,
                            ]));
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once()
                            ->andReturn(true);
                    },
                    $dataFactory,
                    static function (): void {
                        // empty
                    },
                ],
                'no user / keycloak user disabled'   => [
                    new GraphQLError(
                        'org',
                        static function (): Throwable {
                            return new InviteImpossibleKeyCloakUserDisabled(new KeyCloakUser([
                                'id' => '3b7180cb-bbcf-43bd-bcc2-c00509f1c222',
                            ]));
                        },
                    ),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserByEmail')
                            ->once()
                            ->andReturn(new KeyCloakUser([
                                'id'      => '3b7180cb-bbcf-43bd-bcc2-c00509f1c222',
                                'enabled' => false,
                            ]));
                    },
                    $dataFactory,
                    static function (): void {
                        // empty
                    },
                ],
                'user / not a member'                => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('user.invite', self::class),
                        new JsonFragment('user.invite', [
                            'result' => true,
                        ]),
                    ),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new KeyCloakUser([
                                'id'      => '3b7180cb-bbcf-43bd-bcc2-c00509f1c222',
                                'enabled' => true,
                            ]));
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once()
                            ->andReturn(true);
                    },
                    $dataFactory,
                    static function (self $test, Organization $org, User $user, array $data): void {
                        User::factory()->create([
                            'id'    => '3b7180cb-bbcf-43bd-bcc2-c00509f1c222',
                            'type'  => UserType::keycloak(),
                            'email' => $data['input']['email'],
                        ]);
                    },
                ],
                'user / a member'                    => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('user.invite', self::class),
                        new JsonFragment('user.invite', [
                            'result' => false,
                        ]),
                    ),
                    static function (MockInterface $mock): void {
                        // empty
                    },
                    $dataFactory,
                    static function (self $test, Organization $org, User $user, array $data): void {
                        $user = User::factory()->create([
                            'id'    => '3b7180cb-bbcf-43bd-bcc2-c00509f1c222',
                            'type'  => UserType::keycloak(),
                            'email' => $data['input']['email'],
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $org->getKey(),
                            'user_id'         => $user,
                            'invited'         => false,
                        ]);
                    },
                ],
                'resend'                             => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('user.invite', self::class),
                        new JsonFragment('user.invite', [
                            'result' => true,
                        ]),
                    ),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new KeyCloakUser([
                                'id'      => '3b7180cb-bbcf-43bd-bcc2-c00509f1c222',
                                'enabled' => true,
                            ]));
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once()
                            ->andReturn(true);
                    },
                    $dataFactory,
                    static function (self $test, Organization $org, User $user, array $data): void {
                        $user = User::factory()->create([
                            'id'    => '3b7180cb-bbcf-43bd-bcc2-c00509f1c222',
                            'type'  => UserType::keycloak(),
                            'email' => $data['input']['email'],
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $org->getKey(),
                            'user_id'         => $user,
                            'invited'         => true,
                        ]);
                    },
                ],
                'local user'                         => [
                    new GraphQLSuccess(
                        'org',
                        new JsonFragmentSchema('user.invite', self::class),
                        new JsonFragment('user.invite', [
                            'result' => false,
                        ]),
                    ),
                    static function (MockInterface $mock): void {
                        // empty
                    },
                    $dataFactory,
                    static function (self $test, Organization $org, User $user, array $data): void {
                        User::factory()->create([
                            'id'    => '3b7180cb-bbcf-43bd-bcc2-c00509f1c222',
                            'type'  => UserType::local(),
                            'email' => $data['input']['email'],
                        ]);
                    },
                ],
                'role not found'                     => [
                    new GraphQLValidationError('org'),
                    null,
                    static function (self $test): array {
                        $team  = Team::factory()->create();
                        $email = $test->faker->email;

                        return [
                            'input' => [
                                'email'   => $email,
                                'role_id' => $test->faker->uuid,
                                'team_id' => $team->getKey(),
                            ],
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                'role from another organization'     => [
                    new GraphQLValidationError('org'),
                    null,
                    static function (self $test): array {
                        $role  = Role::factory()->create([
                            'organization_id' => Organization::factory()->create(),
                        ]);
                        $team  = Team::factory()->create();
                        $email = $test->faker->email;

                        return [
                            'input' => [
                                'email'   => $email,
                                'role_id' => $role->getKey(),
                                'team_id' => $team->getKey(),
                            ],
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                'team not found'                     => [
                    new GraphQLValidationError('org'),
                    null,
                    static function (self $test): array {
                        $organization = Organization::factory()->create();
                        $role         = Role::factory()->create([
                            'organization_id' => $organization,
                        ]);
                        $email        = $test->faker->email;

                        return [
                            'input' => [
                                'email'   => $email,
                                'role_id' => $role->getKey(),
                                'team_id' => $test->faker->uuid,
                            ],
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
