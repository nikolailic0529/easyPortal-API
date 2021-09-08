<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User as UserModel;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\UserDoesntExists;
use App\Services\KeyCloak\Client\Types\User;
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

use function __;

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
            'token'      => $this->faker->sha256,
            'first_name' => $this->faker->firstName,
            'last_name'  => $this->faker->lastName,
            'password'   => $this->faker->password,
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
                'ok'                    => [
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
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
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
                'Invalid keycloak user' => [
                    new GraphQLError('signUpByInvite', new UserDoesntExists()),
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
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->with('f9834bc1-2f2f-4c57-bb8d-7a224ac24987')
                            ->andThrow(new UserDoesntExists());
                    },
                ],
                'Invalid token data'    => [
                    new GraphQLError('signUpByInvite', new SignUpByInviteInvalidToken()),
                    static function (TestCase $test): array {
                        return [
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'key' => 'value',
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
                        ];
                    },
                ],
                'Invitation used'       => [
                    new GraphQLError('signUpByInvite', new SignUpByInviteAlreadyUsed()),
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
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'invitation' => $invitation->getKey(),
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
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
                'Invalid token'         => [
                    new GraphQLError('signUpByInvite', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'      => '',
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
                        ];
                    },
                ],
                'Invalid first name'    => [
                    new GraphQLError('signUpByInvite', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name' => '',
                            'last_name'  => 'Last',
                            'password'   => '123456',
                        ];
                    },
                ],
                'Invalid last name'     => [
                    new GraphQLError('signUpByInvite', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name' => 'First',
                            'last_name'  => '',
                            'password'   => '123456',
                        ];
                    },
                ],
                'Invalid password'      => [
                    new GraphQLError('signUpByInvite', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '',
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
