<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\Events\InvitationAccepted;
use App\GraphQL\Events\InvitationExpired;
use App\GraphQL\Events\InvitationOutdated;
use App\GraphQL\Events\InvitationUsed;
use App\GraphQL\Mutations\Auth\Organization\SignIn;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\User as KeycloakUser;
use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthGuestDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;
use Throwable;

use function is_array;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Auth\SignUpByInvite
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class SignUpByInviteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param Response|array{Response,class-string}                                  $expected
     * @param OrganizationFactory                                                    $orgFactory
     * @param UserFactory                                                            $userFactory
     * @param Closure(Client&MockInterface): void|null                               $clientFactory
     * @param Closure(SignIn&MockInterface): void|null                               $queryFactory
     * @param Closure(static, ?Organization, ?User): array<mixed>|null               $dataFactory
     * @param Closure(static, ?Organization, ?User, array<mixed>): array<mixed>|null $prepare
     */
    public function testInvoke(
        Response|array $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $clientFactory = null,
        Closure $queryFactory = null,
        Closure $dataFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org   = $this->setOrganization($orgFactory);
        $user  = $this->setUser($userFactory, $org);
        $data  = [
            'token' => $this->faker->word(),
            'input' => null,
        ];
        $event = null;

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        if ($queryFactory) {
            $this->override(SignIn::class, $queryFactory);
        }

        if ($dataFactory) {
            $data = $dataFactory($this, $org, $user);
        }

        if ($prepare) {
            $prepare($this, $org, $user, $data);
        }

        if (is_array($expected)) {
            [$expected, $event] = $expected;
        }

        // Fake
        if ($event) {
            Event::fake($event);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($token: String!, $input: AuthSignUpByInviteInput) {
                    auth {
                        signUpByInvite(token: $token, input: $input) {
                            result
                            url
                            org {
                                id
                            }
                        }
                    }
                }
                GRAPHQL,
                $data,
            )
            ->assertThat($expected);

        if ($event) {
            Event::assertDispatched($event);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new UnknownOrgDataProvider(),
            new AuthGuestDataProvider('auth'),
            new ArrayDataProvider([
                'ok'                                                                      => [
                    [
                        new GraphQLSuccess(
                            'auth',
                            new JsonFragment('signUpByInvite', [
                                'result' => true,
                                'url'    => 'https://example.com/',
                                'org'    => [
                                    'id' => 'e0478e7c-53c4-4cbb-927d-636028d1f907',
                                ],
                            ]),
                        ),
                        InvitationAccepted::class,
                    ],
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturns(true);
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturns(new KeycloakUser([
                                'id' => '75f9834e-ae5d-4e15-be98-ca121e7a5404',
                            ]));
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUrl')
                            ->once()
                            ->andReturn('https://example.com/');
                    },
                    static function (self $test): array {
                        $organization = Organization::factory()->create([
                            'id' => 'e0478e7c-53c4-4cbb-927d-636028d1f907',
                        ]);
                        $user         = User::factory()->create([
                            'id'             => '75f9834e-ae5d-4e15-be98-ca121e7a5404',
                            'email_verified' => false,
                        ]);
                        $invitation   = Invitation::factory()->create([
                            'user_id'         => $user,
                            'organization_id' => $organization,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'invited'         => true,
                        ]);

                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'input' => [
                                'given_name'  => 'First',
                                'family_name' => 'Last',
                                'password'    => '123456',
                            ],
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                'filled'                                                                  => [
                    [
                        new GraphQLSuccess(
                            'auth',
                            new JsonFragment('signUpByInvite', [
                                'result' => true,
                                'url'    => 'https://example.com/',
                                'org'    => [
                                    'id' => 'e0478e7c-53c4-4cbb-927d-636028d1f907',
                                ],
                            ]),
                        ),
                        InvitationAccepted::class,
                    ],
                    null,
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUrl')
                            ->once()
                            ->andReturn('https://example.com/');
                    },
                    static function (self $test): array {
                        $organization = Organization::factory()->create([
                            'id' => 'e0478e7c-53c4-4cbb-927d-636028d1f907',
                        ]);
                        $user         = User::factory()->create([
                            'id'             => '75f9834e-ae5d-4e15-be98-ca121e7a5404',
                            'email_verified' => true,
                        ]);
                        $invitation   = Invitation::factory()->create([
                            'user_id'         => $user,
                            'organization_id' => $organization,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'invited'         => true,
                        ]);

                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'input' => null,
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                'args required'                                                           => [
                    new GraphQLSuccess(
                        'auth',
                        new JsonFragment('signUpByInvite', [
                            'result' => false,
                            'url'    => null,
                            'org'    => [
                                'id' => 'e0478e7c-53c4-4cbb-927d-636028d1f907',
                            ],
                        ]),
                    ),
                    null,
                    null,
                    static function (self $test): array {
                        $organization = Organization::factory()->create([
                            'id' => 'e0478e7c-53c4-4cbb-927d-636028d1f907',
                        ]);
                        $user         = User::factory()->create([
                            'id'             => '75f9834e-ae5d-4e15-be98-ca121e7a5404',
                            'email_verified' => false,
                        ]);
                        $invitation   = Invitation::factory()->create([
                            'user_id'         => $user,
                            'organization_id' => $organization,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'invited'         => true,
                        ]);

                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'input' => null,
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                SignUpByInviteInvitationNotFound::class                                   => [
                    new GraphQLError('auth', static function (): Throwable {
                        return new SignUpByInviteInvitationNotFound('');
                    }),
                    null,
                    null,
                    static function (self $test): array {
                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $test->faker->uuid(),
                            ]),
                            'input' => null,
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                SignUpByInviteInvitationUsed::class                                       => [
                    [
                        new GraphQLError('auth', static function (): Throwable {
                            return new SignUpByInviteInvitationUsed(new Invitation());
                        }),
                        InvitationUsed::class,
                    ],
                    null,
                    null,
                    static function (self $test): array {
                        $invitation = Invitation::factory()->create([
                            'used_at' => Date::now(),
                        ]);

                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'input' => null,
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                SignUpByInviteInvitationExpired::class                                    => [
                    [
                        new GraphQLError('auth', static function (): Throwable {
                            return new SignUpByInviteInvitationExpired(new Invitation());
                        }),
                        InvitationExpired::class,
                    ],
                    null,
                    null,
                    static function (self $test): array {
                        $invitation = Invitation::factory()->create([
                            'expired_at' => Date::now()->subDay(),
                        ]);

                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'input' => null,
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                SignUpByInviteInvitationOutdated::class                                   => [
                    [
                        new GraphQLError('auth', static function (): Throwable {
                            return new SignUpByInviteInvitationOutdated(new Invitation());
                        }),
                        InvitationOutdated::class,
                    ],
                    null,
                    null,
                    static function (self $test): array {
                        $invitation = Invitation::factory()->create([
                            'created_at' => Date::now()->subDay(),
                        ]);

                        Invitation::factory()->create([
                            'organization_id' => $invitation->organization_id,
                            'user_id'         => $invitation->user_id,
                            'created_at'      => Date::now(),
                        ]);

                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'input' => null,
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                SignUpByInviteInvitationUserNotFound::class.' / '.User::class             => [
                    new GraphQLError('auth', static function (): Throwable {
                        return new SignUpByInviteInvitationUserNotFound(new Invitation());
                    }),
                    null,
                    null,
                    static function (self $test): array {
                        $organization = Organization::factory()->create();
                        $user         = User::factory()->create([
                            'deleted_at'     => Date::now(),
                            'email_verified' => false,
                        ]);
                        $invitation   = Invitation::factory()->create([
                            'user_id'         => $user,
                            'organization_id' => $organization,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'invited'         => true,
                        ]);

                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'input' => [
                                'given_name'  => 'First',
                                'family_name' => 'Last',
                                'password'    => '123456',
                            ],
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                SignUpByInviteInvitationUserNotFound::class.' / '.OrganizationUser::class => [
                    new GraphQLError('auth', static function (): Throwable {
                        return new SignUpByInviteInvitationUserNotFound(new Invitation());
                    }),
                    null,
                    null,
                    static function (self $test): array {
                        $organization = Organization::factory()->create();
                        $user         = User::factory()->create([
                            'email_verified' => false,
                        ]);
                        $invitation   = Invitation::factory()->create([
                            'user_id'         => $user,
                            'organization_id' => $organization,
                        ]);

                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'input' => [
                                'given_name'  => 'First',
                                'family_name' => 'Last',
                                'password'    => '123456',
                            ],
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                SignUpByInviteInvitationOrganizationNotFound::class                       => [
                    new GraphQLError('auth', static function (): Throwable {
                        return new SignUpByInviteInvitationOrganizationNotFound(new Invitation());
                    }),
                    null,
                    null,
                    static function (self $test): array {
                        $organization = Organization::factory()->create([
                            'deleted_at' => Date::now(),
                        ]);
                        $user         = User::factory()->create([
                            'email_verified' => false,
                        ]);
                        $invitation   = Invitation::factory()->create([
                            'user_id'         => $user,
                            'organization_id' => $organization,
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'invited'         => true,
                        ]);

                        return [
                            'token' => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'input' => [
                                'given_name'  => 'First',
                                'family_name' => 'Last',
                                'password'    => '123456',
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
