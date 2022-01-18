<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\GraphQL\Mutations\Auth\Organization\SignIn;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\RealmUserNotFound;
use App\Services\KeyCloak\Client\Types\User as KeyCloakUser;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\GuestDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;
use Throwable;

use function __;
use function app;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Auth\SignUpByInvite
 */
class SignUpByInviteTest extends TestCase {
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
        Closure $queryFactory = null,
        Closure $dataFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $data = [
            'token' => $this->faker->word,
            'input' => null,
        ];

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
                        }
                    }
                }
                GRAPHQL,
                $data,
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            // fixme test properties
        }
    }

    /**
     * @deprecated
     * @covers ::deprecated
     * @dataProvider dataProviderDeprecated
     */
    public function testDeprecated(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $inputFactory = null,
        Closure $clientFactory = null,
    ): void {
        $organization = $this->setOrganization($organizationFactory);
        $this->setUser($userFactory, $organization);

        if (!$organization) {
            // Organization to be redirect;
            $organization = Organization::factory()->create([
                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
            ]);
        }

        $organization->keycloak_scope = 'test_scope';
        $organization->save();

        $data = [
            'token'       => $this->faker->sha256,
            'given_name'  => $this->faker->firstName,
            'family_name' => $this->faker->lastName,
            'password'    => $this->faker->password,
        ];

        if ($inputFactory) {
            $data = $inputFactory($this, $organization);
        }

        $this->override(Client::class, $clientFactory);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation signUpByInvite($input: SignUpByInviteInput!) {
                signUpByInvite(input:$input) {
                    url
                }
            }', ['input' => $data])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $invitation = GlobalScopes::callWithoutGlobalScope(
                OwnedByOrganizationScope::class,
                static function () {
                    return Invitation::whereKey('f9834bc1-2f2f-4c57-bb8d-7a224ac24982')->first();
                },
            );
            $this->assertNotNull($invitation->used_at);
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
            new AnyOrganizationDataProvider('auth'),
            new GuestDataProvider('auth'),
            new ArrayDataProvider([
                'ok'                                                                      => [
                    new GraphQLSuccess(
                        'auth',
                        new JsonFragmentSchema('signUpByInvite', self::class),
                        new JsonFragment('signUpByInvite', [
                            'result' => true,
                            'url'    => 'https://example.com/',
                        ]),
                    ),
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturns(true);
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturns(new KeyCloakUser([
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
                        $organization = Organization::factory()->create();
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
                    new GraphQLSuccess(
                        'auth',
                        new JsonFragmentSchema('signUpByInvite', self::class),
                        new JsonFragment('signUpByInvite', [
                            'result' => true,
                            'url'    => 'https://example.com/',
                        ]),
                    ),
                    null,
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUrl')
                            ->once()
                            ->andReturn('https://example.com/');
                    },
                    static function (self $test): array {
                        $organization = Organization::factory()->create();
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
                        new JsonFragmentSchema('signUpByInvite', self::class),
                        new JsonFragment('signUpByInvite', [
                            'result' => false,
                            'url'    => null,
                        ]),
                    ),
                    null,
                    null,
                    static function (self $test): array {
                        $organization = Organization::factory()->create();
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
                SignUpByInviteTokenInvalid::class                                         => [
                    new GraphQLError('auth', static function (): Throwable {
                        return new SignUpByInviteTokenInvalid('');
                    }),
                    null,
                    null,
                    static function (self $test): array {
                        return [
                            'token' => $test->faker->word,
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
                                'invitation' => $test->faker->uuid,
                            ]),
                            'input' => null,
                        ];
                    },
                    static function (): void {
                        // empty
                    },
                ],
                SignUpByInviteInvitationUsed::class                                       => [
                    new GraphQLError('auth', static function (): Throwable {
                        return new SignUpByInviteInvitationUsed(new Invitation());
                    }),
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
                    new GraphQLError('auth', static function (): Throwable {
                        return new SignUpByInviteInvitationExpired(new Invitation());
                    }),
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
                    new GraphQLError('auth', static function (): Throwable {
                        return new SignUpByInviteInvitationOutdated(new Invitation());
                    }),
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

    /**
     * @deprecated
     * @return array<mixed>
     */
    public function dataProviderDeprecated(): array {
        return (new CompositeDataProvider(
            new AnyOrganizationDataProvider('signUpByInvite', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981'),
            new GuestDataProvider('signUpByInvite'),
            new ArrayDataProvider([
                'ok'                       => [
                    new GraphQLSuccess('signUpByInvite', SignUpByInvite::class),
                    static function (TestCase $test, Organization $organization): array {
                        $user       = User::factory()->create([
                            'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                            'email_verified' => false,
                        ]);
                        $invitation = Invitation::factory()->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'email'           => 'test@gmail.com',
                            'user_id'         => $user->getKey(),
                            'organization_id' => $organization->getKey(),
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'invited'         => true,
                        ]);

                        return [
                            'token'       => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'given_name'  => 'First',
                            'family_name' => 'Last',
                            'password'    => '123456',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturns(true);
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->with('f9834bc1-2f2f-4c57-bb8d-7a224ac24987')
                            ->andReturns(new KeyCloakUser([
                                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                            ]));
                    },
                ],
                'Invalid keycloak user'    => [
                    new GraphQLError('signUpByInvite', new RealmUserNotFound('f9834bc1-2f2f-4c57-bb8d-7a224ac24987')),
                    static function (TestCase $test, Organization $organization): array {
                        $user       = User::factory()->create([
                            'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                            'email_verified' => false,
                        ]);
                        $invitation = Invitation::factory()->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'email'           => 'test@gmail.com',
                            'user_id'         => $user->getKey(),
                            'organization_id' => $organization->getKey(),
                        ]);

                        OrganizationUser::factory()->create([
                            'organization_id' => $organization,
                            'user_id'         => $user,
                            'invited'         => true,
                        ]);

                        return [
                            'token'       => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'given_name'  => 'First',
                            'family_name' => 'Last',
                            'password'    => '123456',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->with('f9834bc1-2f2f-4c57-bb8d-7a224ac24987')
                            ->andThrow(new RealmUserNotFound('f9834bc1-2f2f-4c57-bb8d-7a224ac24987'));
                    },
                ],
                'Invalid token invitation' => [
                    new GraphQLError(
                        'signUpByInvite',
                        new SignUpByInviteInvitationNotFound('f9834bc1-2f2f-4c57-bb8d-7a224ac24982'),
                    ),
                    static function (TestCase $test): array {
                        return [
                            'token'       => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            ]),
                            'given_name'  => 'First',
                            'family_name' => 'Last',
                            'password'    => '123456',
                        ];
                    },
                ],
                'Invalid token encrypt'    => [
                    new GraphQLError('signUpByInvite', new SignUpByInviteTokenInvalid('wrong_encryption')),
                    static function (TestCase $test): array {
                        return [
                            'token'       => 'wrong_encryption',
                            'given_name'  => 'First',
                            'family_name' => 'Last',
                            'password'    => '123456',
                        ];
                    },
                ],
                'Invalid token key'        => [
                    new GraphQLError('signUpByInvite', static function (): Throwable {
                        return new SignUpByInviteTokenInvalid(app()->make(Encrypter::class)->encrypt([
                            'key' => 'value',
                        ]));
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'       => $test->app->make(Encrypter::class)->encrypt([
                                'key' => 'value',
                            ]),
                            'given_name'  => 'First',
                            'family_name' => 'Last',
                            'password'    => '123456',
                        ];
                    },
                ],
                'Invitation used'          => [
                    new GraphQLError('signUpByInvite', static function (): Throwable {
                        return new SignUpByInviteInvitationUsed((new Invitation())->forceFill([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]));
                    }),
                    static function (TestCase $test, Organization $organization): array {
                        $user       = User::factory()->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                        ]);
                        $invitation = Invitation::factory()->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'email'           => 'test@gmail.com',
                            'user_id'         => $user->getKey(),
                            'organization_id' => $organization->getKey(),
                            'used_at'         => Date::now(),
                        ]);

                        return [
                            'token'       => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'given_name'  => 'First',
                            'family_name' => 'Last',
                            'password'    => '123456',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->never();
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                    },
                ],
                'Invitation expired'       => [
                    new GraphQLError('signUpByInvite', static function (): Throwable {
                        return new SignUpByInviteInvitationExpired((new Invitation())->forceFill([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]));
                    }),
                    static function (TestCase $test, Organization $organization): array {
                        $user       = User::factory()->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                        ]);
                        $invitation = Invitation::factory()->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'email'           => 'test@gmail.com',
                            'user_id'         => $user->getKey(),
                            'organization_id' => $organization->getKey(),
                            'used_at'         => null,
                            'expired_at'      => Date::now(),
                        ]);

                        return [
                            'token'       => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'given_name'  => 'First',
                            'family_name' => 'Last',
                            'password'    => '123456',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->never();
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                    },
                ],
                'Invalid token'            => [
                    new GraphQLError('signUpByInvite', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'       => '',
                            'given_name'  => 'First',
                            'family_name' => 'Last',
                            'password'    => '123456',
                        ];
                    },
                ],
                'Invalid first name'       => [
                    new GraphQLError('signUpByInvite', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'       => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'given_name'  => '',
                            'family_name' => 'Last',
                            'password'    => '123456',
                        ];
                    },
                ],
                'Invalid last name'        => [
                    new GraphQLError('signUpByInvite', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'       => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'given_name'  => 'First',
                            'family_name' => '',
                            'password'    => '123456',
                        ];
                    },
                ],
                'Invalid password'         => [
                    new GraphQLError('signUpByInvite', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'       => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'given_name'  => 'First',
                            'family_name' => 'Last',
                            'password'    => '',
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
