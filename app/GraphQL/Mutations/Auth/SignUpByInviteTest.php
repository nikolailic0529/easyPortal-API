<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User as UserModel;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\RealmUserNotFound;
use App\Services\KeyCloak\Client\Types\User;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
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
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new AnyOrganizationDataProvider('signUpByInvite', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981'),
            new GuestDataProvider('signUpByInvite'),
            new ArrayDataProvider([
                'ok'                       => [
                    new GraphQLSuccess('signUpByInvite', SignUpByInvite::class),
                    static function (TestCase $test, Organization $organization): array {
                        $user       = UserModel::factory()->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                        ]);
                        $invitation = Invitation::factory()->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'email'           => 'test@gmail.com',
                            'user_id'         => $user->getKey(),
                            'organization_id' => $organization->getKey(),
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
                            ->andReturns();
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->with('f9834bc1-2f2f-4c57-bb8d-7a224ac24987')
                            ->andReturns(new User([
                                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                            ]));
                    },
                ],
                'Invalid keycloak user'    => [
                    new GraphQLError('signUpByInvite', new RealmUserNotFound('f9834bc1-2f2f-4c57-bb8d-7a224ac24987')),
                    static function (TestCase $test, Organization $organization): array {
                        $user       = UserModel::factory()->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                        ]);
                        $invitation = Invitation::factory()->create([
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'email'           => 'test@gmail.com',
                            'user_id'         => $user->getKey(),
                            'organization_id' => $organization->getKey(),
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
                        new SignUpByInviteNotFound('f9834bc1-2f2f-4c57-bb8d-7a224ac24982'),
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
                    new GraphQLError('signUpByInvite', new SignUpByInviteInvalidToken('wrong_encryption')),
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
                        return new SignUpByInviteInvalidToken(app()->make(Encrypter::class)->encrypt([
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
                        return new SignUpByInviteAlreadyUsed((new Invitation())->forceFill([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]));
                    }),
                    static function (TestCase $test, Organization $organization): array {
                        $user       = UserModel::factory()->create([
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
                        return new SignUpByInviteExpired((new Invitation())->forceFill([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]));
                    }),
                    static function (TestCase $test, Organization $organization): array {
                        $user       = UserModel::factory()->create([
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
