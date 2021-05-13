<?php declare(strict_types = 1);

namespace App\Services\KeyCloak;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\KeyCloak\Exceptions\AnotherUserExists;
use Closure;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Mockery;
use Tests\TestCase;

use function is_null;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\UserProvider
 */
class UserProviderTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::retrieveById
     */
    public function testRetrieveById(): void {
        $provider = $this->app->make(UserProvider::class);
        $user     = User::factory()->create();

        $this->assertEquals($user, $provider->retrieveById($user->getKey()));
        $this->assertNull($provider->retrieveById($this->faker->uuid));
    }

    /**
     * @covers ::retrieveByCredentials
     *
     * @dataProvider dataProviderRetrieveByCredentialsEmail
     */
    public function testRetrieveByCredentialsEmail(
        string|null $expected,
        Closure $prepare,
        Closure $credentialsFactory,
    ): void {
        $prepare($this);

        $organization = $this->setRootOrganization(Organization::factory()->create());
        $credentials  = $credentialsFactory($this);
        $provider     = $this->app->make(UserProvider::class);
        $actual       = $provider->retrieveByCredentials($credentials);

        if ($expected) {
            $this->assertNotNull($actual);
            $this->assertEquals($expected, $actual->getKey());
            $this->assertEquals($organization, $actual->getOrganization());
        } else {
            $this->assertNull($actual);
        }
    }

    /**
     * @covers ::retrieveByCredentials
     *
     * @dataProvider dataProviderRetrieveByCredentialsToken
     */
    public function testRetrieveByCredentialsToken(
        Exception|bool|null $expected,
        Closure $prepare,
        Closure $credentialsFactory,
    ): void {
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $prepare($this);

        $credentials = $credentialsFactory($this);
        $provider    = Mockery::mock(UserProvider::class);
        $provider->shouldAllowMockingProtectedMethods();
        $provider->makePartial();

        if ($credentials) {
            $provider
                ->shouldReceive('getToken')
                ->once()
                ->andReturnUsing(static function (array $credentials) {
                    return $credentials[UserProvider::CREDENTIAL_ACCESS_TOKEN];
                });

            if (!($expected instanceof Exception)) {
                $provider
                    ->shouldReceive('updateTokenUser')
                    ->once()
                    ->andReturnUsing(static function (User $user) {
                        return $user;
                    });
            }
        }

        $actual = $provider->retrieveByCredentials($credentials);

        if (!is_null($expected)) {
            $this->assertNotNull($actual);
            $this->assertEquals($expected, !$actual->exists);
        } else {
            $this->assertNull($actual);
        }
    }

    /**
     * @covers ::validateCredentials
     *
     * @dataProvider dataProviderValidateCredentials
     */
    public function testValidateCredentials(
        bool $expected,
        Closure $authenticatableFactory,
        Closure $credentialsFactory,
    ): void {
        $authenticatable = $authenticatableFactory($this);
        $credentials     = $credentialsFactory($this);
        $hasher          = $this->app->make(Hasher::class);
        $provider        = Mockery::mock(UserProvider::class);
        $provider->shouldAllowMockingProtectedMethods();
        $provider->makePartial();
        $provider
            ->shouldReceive('getHasher')
            ->andReturn($hasher);

        if ($credentials && $authenticatable instanceof User) {
            $provider
                ->shouldReceive('getToken')
                ->once()
                ->andReturnUsing(static function (array $credentials) {
                    return $credentials[UserProvider::CREDENTIAL_ACCESS_TOKEN] ?? null;
                });
        }

        $this->assertEquals($expected, $provider->validateCredentials($authenticatable, $credentials));
    }

    /**
     * @covers ::getProperties
     */
    public function testGetProperties(): void {
        $clientId     = $this->faker->word;
        $organization = Organization::factory()->create([
            'keycloak_scope' => $this->faker->word,
        ]);

        $keycloak = Mockery::mock(KeyCloak::class);
        $keycloak
            ->shouldReceive('getClientId')
            ->once()
            ->andReturn($clientId);

        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldAllowMockingProtectedMethods();
        $provider->makePartial();
        $provider
            ->shouldReceive('getKeyCloak')
            ->atLeast()
            ->once()
            ->andReturn($keycloak);

        $token = $this->getToken([
            'typ'                => 'Bearer',
            'resource_access'    => [
                $clientId => [
                    'roles' => [
                        'test_role_1',
                        'test_role_2',
                    ],
                ],
            ],
            'scope'              => 'openid profile email',
            'email_verified'     => true,
            'name'               => 'Tesg Test',
            'groups'             => [
                '/access-requested',
                '/resellers/reseller2',
                'offline_access',
                'uma_authorization',
            ],
            'phone_number'       => '12345678',
            'preferred_username' => 'dun00101@eoopy.com',
            'given_name'         => 'Tesg',
            'family_name'        => 'Test',
            'email'              => 'dun00101@eoopy.com',
            'reseller_access'    => [
                $organization->keycloak_scope => true,
            ],
        ]);

        $this->assertEquals([
            'email'          => 'dun00101@eoopy.com',
            'email_verified' => true,
            'given_name'     => 'Tesg',
            'family_name'    => 'Test',
            'phone'          => '12345678',
            'phone_verified' => false,
            'locale'         => null,
            'permissions'    => [
                'test_role_1',
                'test_role_2',
            ],
            'organization'   => $organization,
        ], $provider->getProperties($token));
    }

    /**
     * @covers ::updateTokenUser
     */
    public function testUpdateTokenUser(): void {
        $token        = Mockery::mock(UnencryptedToken::class);
        $organization = Organization::factory()->create([
            'keycloak_scope' => $this->faker->word,
        ]);

        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldAllowMockingProtectedMethods();
        $provider->makePartial();
        $provider
            ->shouldReceive('getProperties')
            ->once()
            ->andReturn([
                'given_name'   => '123',
                'family_name'  => '456',
                'permissions'  => [
                    'test_role_1',
                    'test_role_2',
                ],
                'organization' => $organization,
            ]);

        // Test
        $user = User::factory()->make();

        $provider->updateTokenUser($user, $token);

        $this->assertEquals('123', $user->given_name);
        $this->assertEquals('456', $user->family_name);
        $this->assertEquals(['test_role_1', 'test_role_2'], $user->getPermissions());
        $this->assertEquals($organization, $user->organization);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @param array<mixed> $claims
     */
    protected function getToken(array $claims = [], Closure $callback = null): UnencryptedToken {
        $config  = Configuration::forUnsecuredSigner();
        $builder = $config->builder();

        foreach ($claims as $claim => $value) {
            $builder = $builder->withClaim($claim, $value);
        }

        if ($callback) {
            $builder = $callback($builder) ?? $builder;
        }

        return $builder->getToken($config->signer(), $config->signingKey());
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderRetrieveByCredentialsEmail(): array {
        return [
            'empty'                              => [
                null,
                static function (): void {
                    User::factory()->create();
                },
                static function (): void {
                    return [];
                },
            ],
            'local user by email'                => [
                'de238c70-3253-411b-a176-f15192a9c1e1',
                static function (): void {
                    User::factory()->create([
                        'id'    => 'de238c70-3253-411b-a176-f15192a9c1e1',
                        'type'  => UserType::local(),
                        'email' => 'email@example.com',
                    ]);
                },
                static function () {
                    return [
                        UserProvider::CREDENTIAL_EMAIL => 'email@example.com',
                    ];
                },
            ],
            'local user by email (soft deleted)' => [
                null,
                static function (TestCase $test): void {
                    User::factory()->create([
                        'type'       => UserType::local(),
                        'email'      => 'email@example.com',
                        'deleted_at' => $test->faker()->dateTime,
                    ]);
                },
                static function () {
                    return [
                        UserProvider::CREDENTIAL_EMAIL => 'email@example.com',
                    ];
                },
            ],
            'keycloak user by email'             => [
                null,
                static function (): void {
                    User::factory()->create([
                        'type'  => UserType::keycloak(),
                        'email' => 'email@example.com',
                    ]);
                },
                static function () {
                    return [
                        UserProvider::CREDENTIAL_EMAIL => 'email@example.com',
                    ];
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderRetrieveByCredentialsToken(): array {
        return [
            'empty'                                  => [
                null,
                static function (): void {
                    User::factory()->create();
                },
                static function () {
                    return [];
                },
            ],
            'keycloak user exists + valid token'     => [
                false,
                static function (): void {
                    User::factory()->create([
                        'id'   => 'c1aa09cc-0bd8-490e-8c7b-25c18df23e18',
                        'type' => UserType::keycloak(),
                    ]);
                },
                static function (UserProviderTest $test) {
                    return [
                        UserProvider::CREDENTIAL_ACCESS_TOKEN => $test->getToken(
                            [],
                            static function (Builder $builder): void {
                                $builder->relatedTo('c1aa09cc-0bd8-490e-8c7b-25c18df23e18');
                            },
                        ),
                    ];
                },
            ],
            'keycloak user not exists + valid token' => [
                true,
                static function (): void {
                    // empty
                },
                static function (UserProviderTest $test) {
                    return [
                        UserProvider::CREDENTIAL_ACCESS_TOKEN => $test->getToken(
                            [],
                            static function (Builder $builder): void {
                                $builder->relatedTo('c1aa09cc-0bd8-490e-8c7b-25c18df23e18');
                            },
                        ),
                    ];
                },
            ],
            'another user exists + valid token'      => [
                new AnotherUserExists((new User())->forceFill([
                    'id' => 'c1aa09cc-0bd8-490e-8c7b-25c18df23e18',
                ])),
                static function (): void {
                    User::factory()->create([
                        'id'   => 'c1aa09cc-0bd8-490e-8c7b-25c18df23e18',
                        'type' => UserType::local(),
                    ]);
                },
                static function (UserProviderTest $test) {
                    return [
                        UserProvider::CREDENTIAL_ACCESS_TOKEN => $test->getToken(
                            [],
                            static function (Builder $builder): void {
                                $builder->relatedTo('c1aa09cc-0bd8-490e-8c7b-25c18df23e18');
                            },
                        ),
                    ];
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderValidateCredentials(): array {
        return [
            'empty'                  => [
                false,
                static function () {
                    return Mockery::mock(Authenticatable::class);
                },
                static function () {
                    return [];
                },
            ],
            'not instance of User'   => [
                false,
                static function () {
                    return Mockery::mock(Authenticatable::class);
                },
                static function (UserProviderTest $test) {
                    return [
                        UserProvider::CREDENTIAL_PASSWORD => '12345',
                    ];
                },
            ],
            'valid token for user'   => [
                true,
                static function () {
                    return User::factory()->make([
                        'id' => '6b9dcdcd-a8e9-44c7-b750-f80a974b2a35',
                    ]);
                },
                static function (UserProviderTest $test) {
                    return [
                        UserProvider::CREDENTIAL_ACCESS_TOKEN => $test->getToken(
                            [],
                            static function (Builder $builder): void {
                                $builder->relatedTo('6b9dcdcd-a8e9-44c7-b750-f80a974b2a35');
                            },
                        ),
                    ];
                },
            ],
            'invalid token for user' => [
                false,
                static function () {
                    return User::factory()->make();
                },
                static function (UserProviderTest $test) {
                    return [
                        UserProvider::CREDENTIAL_ACCESS_TOKEN => $test->getToken(
                            [],
                            static function (Builder $builder): void {
                                $builder->relatedTo('6b9dcdcd-a8e9-44c7-b750-f80a974b2a35');
                            },
                        ),
                    ];
                },
            ],
            'valid password'         => [
                true,
                static function (TestCase $test) {
                    return User::factory()->make([
                        'password' => $test->app()->make(Hasher::class)->make('12345'),
                    ]);
                },
                static function (TestCase $test) {
                    return [
                        UserProvider::CREDENTIAL_PASSWORD => '12345',
                    ];
                },
            ],
            'invalid password'       => [
                false,
                static function (TestCase $test) {
                    return User::factory()->make([
                        'password' => $test->app()->make(Hasher::class)->make('12345'),
                    ]);
                },
                static function (TestCase $test) {
                    return [
                        UserProvider::CREDENTIAL_PASSWORD => 'invalid',
                    ];
                },
            ],
        ];
    }
    //</editor-fold>
}
