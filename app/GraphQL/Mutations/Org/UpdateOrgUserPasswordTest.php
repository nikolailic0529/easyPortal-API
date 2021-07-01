<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Services\KeyCloak\Client\Client;
use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
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
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateOrgUserPassword
 */
class UpdateOrgUserPasswordTest extends TestCase {
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
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        $data = [
            'token'                 => $this->app->make(Encrypter::class)->encrypt([
                'email'        => 'test@gmail.com',
                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
            ]),
            'first_name'            => 'First',
            'last_name'             => 'Last',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ];
        if ($prepare) {
            $data = $prepare($this);
        }

        $requests = ['*' => Http::response(true, 201)];


        if ($requestFactory) {
            $requests = $requestFactory($this);
        }
        $client = Http::fake($requests);

        $this->app->instance(Factory::class, $client);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation updateOrgUserPassword($input: UpdateOrgUserPasswordInput!) {
                updateOrgUserPassword(input:$input) {
                    result
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
        $requestFactory = static function (TestCase $test): array {
            $client  = $test->app->make(Client::class);
            $baseUrl = $client->getBaseUrl();
            return [
                "{$baseUrl}/users?email=test@gmail.com"      => Http::response([
                    [
                        'id'         => $test->faker->uuid(),
                        'attributes' => [
                            'invited'                       => [true],
                            'added_password_through_invite' => [false],
                        ],
                    ],
                ], 200),
                "{$baseUrl}/users?email=uninvited@gmail.com" => Http::response([
                    [
                        'id'         => $test->faker->uuid(),
                        'attributes' => [
                            'invited'                       => [false],
                            'added_password_through_invite' => [false],
                        ],
                    ],
                ], 200),
                "{$baseUrl}/users?email=added@gmail.com"     => Http::response([
                    [
                        'id'         => $test->faker->uuid(),
                        'attributes' => [
                            'invited'                       => [true],
                            'added_password_through_invite' => [true],
                        ],
                    ],
                ], 200),
                "{$baseUrl}/users?email=wrong@gmail.com"     => Http::response([], 200),
                "{$baseUrl}/users"                           => Http::response([], 200),
                '*'                                          => Http::response(true, 200),
            ];
        };

        return (new CompositeDataProvider(
            new AnyOrganizationDataProvider('updateOrgUserPassword'),
            new AnyUserDataProvider('updateOrgUserPassword'),
            new ArrayDataProvider([
                'ok'                            => [
                    new GraphQLSuccess('updateOrgUserPassword', UpdateOrgUserPassword::class),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name'            => 'First',
                            'last_name'             => 'Last',
                            'password'              => '123456',
                            'password_confirmation' => '123456',
                        ];
                    },
                    $requestFactory,
                ],
                'Invalid user'                  => [
                    new GraphQLError('updateOrgUserPassword', new UpdateOrgUserPasswordInvalidUser()),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'wrong@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name'            => 'First',
                            'last_name'             => 'Last',
                            'password'              => '123456',
                            'password_confirmation' => '123456',
                        ];
                    },
                    $requestFactory,
                ],
                'Invalid token data'            => [
                    new GraphQLError('updateOrgUserPassword', new UpdateOrgUserPasswordInvalidToken()),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => $test->app->make(Encrypter::class)->encrypt([
                                'email' => 'test@gmail.com',
                            ]),
                            'first_name'            => 'First',
                            'last_name'             => 'Last',
                            'password'              => '123456',
                            'password_confirmation' => '123456',
                        ];
                    },
                    $requestFactory,
                ],
                'Invalid not invited user'      => [
                    new GraphQLError('updateOrgUserPassword', new UpdateOrgUserPasswordUnInvitedUser()),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'uninvited@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name'            => 'First',
                            'last_name'             => 'Last',
                            'password'              => '123456',
                            'password_confirmation' => '123456',
                        ];
                    },
                    $requestFactory,
                ],
                'Invalid invited already added' => [
                    new GraphQLError('updateOrgUserPassword', new UpdateOrgUserPasswordAlreadyAdded()),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'added@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name'            => 'First',
                            'last_name'             => 'Last',
                            'password'              => '123456',
                            'password_confirmation' => '123456',
                        ];
                    },
                    $requestFactory,
                ],
                'Invalid token'                 => [
                    new GraphQLError('updateOrgUserPassword', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => '',
                            'first_name'            => 'First',
                            'last_name'             => 'Last',
                            'password'              => '123456',
                            'password_confirmation' => '123456',
                        ];
                    },
                    $requestFactory,
                ],
                'Invalid first name'            => [
                    new GraphQLError('updateOrgUserPassword', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name'            => '',
                            'last_name'             => 'Last',
                            'password'              => '123456',
                            'password_confirmation' => '123456',
                        ];
                    },
                    $requestFactory,
                ],
                'Invalid last name'             => [
                    new GraphQLError('updateOrgUserPassword', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name'            => 'First',
                            'last_name'             => '',
                            'password'              => '123456',
                            'password_confirmation' => '123456',
                        ];
                    },
                    $requestFactory,
                ],
                'Invalid password'              => [
                    new GraphQLError('updateOrgUserPassword', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name'            => 'First',
                            'last_name'             => 'Last',
                            'password'              => '',
                            'password_confirmation' => '',
                        ];
                    },
                    $requestFactory,
                ],
                'Invalid confirmed password'    => [
                    new GraphQLError('updateOrgUserPassword', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'token'                 => $test->app->make(Encrypter::class)->encrypt([
                                'email'        => 'test@gmail.com',
                                'organization' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            ]),
                            'first_name'            => 'First',
                            'last_name'             => 'Last',
                            'password'              => '123456',
                            'password_confirmation' => '12345',
                        ];
                    },
                    $requestFactory,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
