<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\Organization;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User;
use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AnyUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\SignUpByInvite
 */
class SignUpByInviteTest extends TestCase {
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
        Closure $requestFactory = null,
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
            'token'      => $this->app->make(Encrypter::class)->encrypt([
                'email'        => 'test@gmail.com',
                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
            ]),
            'first_name' => 'First',
            'last_name'  => 'Last',
            'password'   => '123456',
        ];

        if ($prepare) {
            $data = $prepare($this);
        }

        $this->override(Client::class, static function ($mock) {
            $mock
                ->shouldReceive('updateUser')
                ->andReturns();

            $mock
                ->shouldReceive('getUserByEmail')
                ->with('test@gmail.com')
                ->andReturns(new User([
                    'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                    'attributes' => [
                        'ep_invite' => [
                            '{"id":null,"organization_id":"f9834bc1-2f2f-4c57-bb8d-7a224ac24981",
                                "sent_at":1625232777,"used_at":null}',
                        ],
                    ],
                ]));

            $mock
                ->shouldReceive('getUserByEmail')
                ->with('uninvited@gmail.com')
                ->andReturns(new User([
                    'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                    'attributes' => [],
                ]));

            $mock
                ->shouldReceive('getUserByEmail')
                ->with('added@gmail.com')
                ->andReturns(new User([
                    'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                    'attributes' => [
                        'ep_invite' => [
                            '{"id":"f9834bc1-2f2f-4c57-bb8d-7a224ac24982",
                                "organization_id":"f9834bc1-2f2f-4c57-bb8d-7a224ac24981",
                                "sent_at":1625232777,"used_at":1625232788}',
                        ],
                    ],
                ]));

            $mock
                ->shouldReceive('getUserByEmail')
                ->with('wrong@gmail.com')
                ->andReturns(null);
            return $mock;
        });

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
            new AnyUserDataProvider('signUpByInvite'),
            new ArrayDataProvider([
                'ok'                            => [
                    new GraphQLSuccess('signUpByInvite', SignUpByInvite::class),
                    static function (TestCase $test): array {
                        return [
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
                        ];
                    },
                ],
                'Invalid user'                  => [
                    new GraphQLError('signUpByInvite', new SignUpByInviteInvalidUser()),
                    static function (TestCase $test): array {
                        return [
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'wrong@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
                        ];
                    },
                ],
                'Invalid token data'            => [
                    new GraphQLError('signUpByInvite', new SignUpByInviteInvalidToken()),
                    static function (TestCase $test): array {
                        return [
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'email' => 'test@gmail.com',
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
                        ];
                    },
                ],
                'Invalid not invited user'      => [
                    new GraphQLError('signUpByInvite', new SignUpByInviteUnInvitedUser()),
                    static function (TestCase $test): array {
                        return [
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'uninvited@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
                        ];
                    },
                ],
                'Invalid invited already added' => [
                    new GraphQLError('signUpByInvite', new SignUpByInviteAlreadyUsed()),
                    static function (TestCase $test): array {
                        return [
                            'token'      => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'added@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name' => 'First',
                            'last_name'  => 'Last',
                            'password'   => '123456',
                        ];
                    },
                ],
                'Invalid token'                 => [
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
                'Invalid first name'            => [
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
                'Invalid last name'             => [
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
                'Invalid password'              => [
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
