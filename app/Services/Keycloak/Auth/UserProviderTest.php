<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Auth;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\Auth\Auth;
use App\Services\Keycloak\Exceptions\Auth\AnotherUserExists;
use App\Services\Keycloak\Exceptions\Auth\UserDisabled;
use App\Services\Keycloak\Exceptions\Auth\UserInsufficientData;
use Closure;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use InvalidArgumentException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Mockery;
use stdClass;
use Tests\TestCase;
use Tests\WithOrganization;

use function is_null;
use function sprintf;

/**
 * @internal
 * @covers \App\Services\Keycloak\Auth\UserProvider
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class UserProviderTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testRetrieveById(): void {
        $provider = $this->app->make(UserProvider::class);
        $user     = User::factory()->create();

        self::assertEquals($user, $provider->retrieveById($user->getKey()));
        self::assertNull($provider->retrieveById($this->faker->uuid()));
    }

    /**
     * @dataProvider dataProviderRetrieveByCredentialsEmail
     */
    public function testRetrieveByCredentialsEmail(
        Exception|string|null $expected,
        Closure $prepare,
        Closure $credentialsFactory,
    ): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        $prepare($this);

        $organization = $this->setRootOrganization(Organization::factory()->create());
        $credentials  = $credentialsFactory($this);
        $provider     = $this->app->make(UserProvider::class);
        $actual       = $provider->retrieveByCredentials($credentials);

        if ($expected) {
            self::assertNotNull($actual);
            self::assertEquals($expected, $actual->getKey());
            self::assertEquals($organization, $actual->getOrganization());
        } else {
            self::assertNull($actual);
        }
    }

    /**
     * @dataProvider dataProviderRetrieveByCredentialsToken
     */
    public function testRetrieveByCredentialsToken(
        Exception|bool|null $expected,
        Closure $prepare,
        Closure $credentialsFactory,
        bool $shouldBeUpdated,
    ): void {
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
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

            if ($shouldBeUpdated) {
                $provider
                    ->shouldReceive('updateTokenUser')
                    ->once()
                    ->andReturnUsing(static function (User $user, UnencryptedToken $token) {
                        $user->enabled = $token->claims()->get('enabled');

                        return $user;
                    });
            } else {
                $provider
                    ->shouldReceive('updateTokenUser')
                    ->never();
            }
        }

        $actual = $provider->retrieveByCredentials($credentials);

        if (!is_null($expected)) {
            self::assertNotNull($actual);
            self::assertEquals($expected, !$actual->exists);
        } else {
            self::assertNull($actual);
        }
    }

    /**
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

        self::assertEquals($expected, $provider->validateCredentials($authenticatable, $credentials));
    }

    /**
     * @dataProvider dataProviderGetProperties
     */
    public function testGetProperties(Closure $expected, Closure $claims): void {
        // Prepare
        $clientId     = $this->faker->word();
        $organization = Organization::factory()->create([
            'keycloak_name' => $this->faker->word(),
        ]);
        $claims       = $claims($clientId, $organization);
        $token        = $this->getToken($claims);
        $user         = new User();
        $org          = Organization::factory()->make();

        if ($expected instanceof Closure) {
            $expected = $expected($clientId, $organization, $user);
        }

        // Error?
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        // Mock
        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldAllowMockingProtectedMethods();
        $provider->makePartial();
        $provider
            ->shouldReceive('getOrganization')
            ->with($user, $token, $org)
            ->once()
            ->andReturn($organization);
        $provider
            ->shouldReceive('getPermissions')
            ->with($user, $token, $organization)
            ->once()
            ->andReturn([]);

        // Test
        $actual = $provider->getProperties($user, $token, $org);

        self::assertEquals($expected, $actual);
    }

    public function testUpdateTokenUser(): void {
        $token        = Mockery::mock(UnencryptedToken::class);
        $organization = Organization::factory()->create([
            'keycloak_name' => $this->faker->word(),
        ]);

        $provider = Mockery::mock(UserProvider::class);
        $provider->shouldAllowMockingProtectedMethods();
        $provider->makePartial();
        $provider
            ->shouldReceive('getProperties')
            ->once()
            ->andReturn([
                'phone'          => null,
                'phone_verified' => null,
                'given_name'     => '123',
                'family_name'    => '456',
                'permissions'    => [
                    'test_role_1',
                    'test_role_2',
                ],
                'organization'   => $organization,
                'photo'          => 'https://example.com/photo.jpg',
                'title'          => 'Mr',
                'academic_title' => 'Professor',
                'office_phone'   => '+1-202-555-0197',
                'mobile_phone'   => '+1-202-555-0147',
                'contact_email'  => 'test@gmail.com',
                'job_title'      => 'Manger',
            ]);

        // Test
        $user = User::factory()->make();

        $provider->updateTokenUser($user, $token, null);

        self::assertEquals('123', $user->given_name);
        self::assertEquals('456', $user->family_name);
        self::assertEquals(['test_role_1', 'test_role_2'], $user->getPermissions());
        self::assertEquals($organization, $user->organization);
        self::assertEquals('https://example.com/photo.jpg', $user->photo);
        self::assertEquals('Mr', $user->title);
        self::assertEquals('Professor', $user->academic_title);
        self::assertEquals('+1-202-555-0197', $user->office_phone);
        self::assertEquals('+1-202-555-0147', $user->mobile_phone);
        self::assertEquals('test@gmail.com', $user->contact_email);
        self::assertEquals('Manger', $user->job_title);
    }

    /**
     * @dataProvider dataProviderGetOrganization
     *
     * @param Closure(static, string, Organization, User): array<mixed> $claimsFactory
     * @param Closure(static, Organization): ?Organization              $orgFactory
     */
    public function testGetOrganization(bool $expected, Closure $claimsFactory, Closure $orgFactory): void {
        $org      = Organization::factory()->create([
            'keycloak_name' => $this->faker->word(),
        ]);
        $user     = User::factory()->create();
        $clientId = $this->faker->word();
        $claims   = $claimsFactory($this, $clientId, $org, $user);
        $token    = $this->getToken($claims);
        $provider = new class() extends UserProvider {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getOrganization(
                User $user,
                UnencryptedToken $token,
                ?Organization $organization,
            ): ?Organization {
                return parent::getOrganization($user, $token, $organization);
            }
        };

        $organization = $orgFactory($this, $org);
        $actual       = $provider->getOrganization($user, $token, $organization);

        if ($expected) {
            self::assertNotNull($actual);
            self::assertEquals($org->getKey(), $actual->getKey());
        } else {
            self::assertNull($actual);
        }
    }

    public function testGetPermissionsNoOrganization(): void {
        $user     = User::factory()->create();
        $token    = $this->getToken();
        $provider = new class() extends UserProvider {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getPermissions(User $user, UnencryptedToken $token, ?Organization $organization): array {
                return parent::getPermissions($user, $token, $organization);
            }
        };

        self::assertEquals([], $provider->getPermissions($user, $token, null));
    }

    public function testGetPermissionsNotMember(): void {
        $org      = Organization::factory()->make();
        $user     = User::factory()->create();
        $token    = $this->getToken();
        $provider = new class($this->app->make(Auth::class)) extends UserProvider {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Auth $auth,
            ) {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getPermissions(User $user, UnencryptedToken $token, ?Organization $organization): array {
                return parent::getPermissions($user, $token, $organization);
            }
        };

        self::assertEquals([], $provider->getPermissions($user, $token, $org));
    }

    public function testGetPermissions(): void {
        $org         = Organization::factory()->create();
        $user        = User::factory()->create();
        $role        = Role::factory()->create();
        $permissionA = Permission::factory()->create([
            'key' => 'permission-a',
        ]);
        $permissionB = Permission::factory()->create([
            'key' => 'permission-b',
        ]);
        $permissionC = Permission::factory()->create([
            'key' => 'permission-c',
        ]);

        RolePermission::factory()->create([
            'role_id'       => $role,
            'permission_id' => $permissionA,
        ]);
        RolePermission::factory()->create([
            'role_id'       => $role,
            'permission_id' => $permissionB,
        ]);
        RolePermission::factory()->create([
            'role_id'       => $role,
            'permission_id' => $permissionC,
        ]);

        OrganizationUser::factory()->create([
            'organization_id' => $org,
            'user_id'         => $user,
            'role_id'         => $role,
            'enabled'         => true,
        ]);

        $auth = Mockery::mock(Auth::class);
        $auth
            ->shouldReceive('getOrganizationUserPermissions')
            ->once()
            ->andReturn([
                $permissionA->key,
                $permissionB->key,
            ]);
        $token    = $this->getToken();
        $provider = new class($auth) extends UserProvider {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Auth $auth,
            ) {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getPermissions(User $user, UnencryptedToken $token, ?Organization $organization): array {
                return parent::getPermissions($user, $token, $organization);
            }
        };

        self::assertEqualsCanonicalizing(
            [$permissionA->key, $permissionB->key],
            $provider->getPermissions($user, $token, $org),
        );
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
                static function () {
                    return [];
                },
            ],
            'local user by email'                => [
                'de238c70-3253-411b-a176-f15192a9c1e1',
                static function (): void {
                    User::factory()->create([
                        'id'      => 'de238c70-3253-411b-a176-f15192a9c1e1',
                        'type'    => UserType::local(),
                        'email'   => 'email@example.com',
                        'enabled' => true,
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
                        'deleted_at' => $test->faker()->dateTime(),
                        'enabled'    => true,
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
            'local disabled user by email'       => [
                new UserDisabled((new User())->forceFill([
                    'id' => 'de238c70-3253-411b-a176-f15192a9c1e1',
                ])),
                static function (): void {
                    User::factory()->create([
                        'id'      => 'de238c70-3253-411b-a176-f15192a9c1e1',
                        'type'    => UserType::local(),
                        'email'   => 'email@example.com',
                        'enabled' => false,
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
                false,
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
                                $builder
                                    ->relatedTo('c1aa09cc-0bd8-490e-8c7b-25c18df23e18')
                                    ->withClaim('enabled', true);
                            },
                        ),
                    ];
                },
                true,
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
                                $builder
                                    ->relatedTo('c1aa09cc-0bd8-490e-8c7b-25c18df23e18')
                                    ->withClaim('enabled', true);
                            },
                        ),
                    ];
                },
                true,
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
                false,
            ],
            'keycloak user disabled + valid token'   => [
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
                                $builder
                                    ->relatedTo('c1aa09cc-0bd8-490e-8c7b-25c18df23e18')
                                    ->withClaim('enabled', false);
                            },
                        ),
                    ];
                },
                true,
            ],
            'not Organization'                       => [
                new InvalidArgumentException(sprintf(
                    'The `%s` must be `null` or instance of `%s`.',
                    UserProvider::CREDENTIAL_ORGANIZATION,
                    Organization::class,
                )),
                static function (): void {
                    // empty
                },
                static function (UserProviderTest $test) {
                    return [
                        UserProvider::CREDENTIAL_ORGANIZATION => new stdClass(),
                        UserProvider::CREDENTIAL_ACCESS_TOKEN => $test->getToken([]),
                    ];
                },
                false,
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

    /**
     * @return array<mixed>
     */
    public function dataProviderGetProperties(): array {
        return [
            'all'                                        => [
                static function (string $client, Organization $organization): array {
                    return [
                        'email'          => 'dun00101@eoopy.com',
                        'email_verified' => true,
                        'given_name'     => 'Tesg',
                        'family_name'    => 'Test',
                        'phone'          => '12345678',
                        'phone_verified' => true,
                        'permissions'    => [],
                        'organization'   => $organization,
                        'photo'          => 'https://example.com/photo.jpg',
                        'enabled'        => true,
                        'title'          => 'Mr',
                        'academic_title' => 'Professor',
                        'office_phone'   => '+1-202-555-0197',
                        'mobile_phone'   => '+1-202-555-0147',
                        'contact_email'  => 'test@gmail.com',
                        'job_title'      => 'Manger',
                        'locale'         => 'it_IT',
                        'homepage'       => 'https://example.com/',
                        'timezone'       => 'Europe/Berlin',
                        'company'        => 'Abc',
                    ];
                },
                static function (string $client, Organization $organization): array {
                    return [
                        'typ'                   => 'Bearer',
                        'scope'                 => 'openid profile email',
                        'email_verified'        => true,
                        'name'                  => 'Tesg Test',
                        'groups'                => [
                            '/access-requested',
                            '/resellers/reseller2',
                            'offline_access',
                            'uma_authorization',
                        ],
                        'phone_number'          => '12345678',
                        'phone_number_verified' => true,
                        'preferred_username'    => 'dun00101@eoopy.com',
                        'given_name'            => 'Tesg',
                        'family_name'           => 'Test',
                        'email'                 => 'dun00101@eoopy.com',
                        'reseller_access'       => [
                            $organization->keycloak_name => true,
                        ],
                        'photo'                 => 'https://example.com/photo.jpg',
                        'enabled'               => true,
                        'title'                 => 'Mr',
                        'academic_title'        => 'Professor',
                        'office_phone'          => '+1-202-555-0197',
                        'mobile_phone'          => '+1-202-555-0147',
                        'contact_email'         => 'test@gmail.com',
                        'job_title'             => 'Manger',
                        'locale'                => 'it',
                        'homepage'              => 'https://example.com/',
                        'timezone'              => 'Europe/Berlin',
                        'company'               => 'Abc',
                    ];
                },
            ],
            'phone_number without phone_number_verified' => [
                static function (string $client, Organization $organization): array {
                    return [
                        'email'          => 'dun00101@eoopy.com',
                        'email_verified' => true,
                        'given_name'     => 'Tesg',
                        'family_name'    => 'Test',
                        'phone'          => '12345678',
                        'phone_verified' => null,
                        'organization'   => $organization,
                        'permissions'    => [],
                        'photo'          => 'https://example.com/photo.jpg',
                        'enabled'        => true,
                        'title'          => 'Mr',
                        'academic_title' => 'Professor',
                        'office_phone'   => '+1-202-555-0197',
                        'mobile_phone'   => '+1-202-555-0147',
                        'contact_email'  => 'test@gmail.com',
                        'job_title'      => 'Manger',
                        'locale'         => 'de_DE',
                        'homepage'       => null,
                        'timezone'       => null,
                        'company'        => null,
                    ];
                },
                static function (string $client, Organization $organization): array {
                    return [
                        'typ'                => 'Bearer',
                        'scope'              => 'openid profile email',
                        'email_verified'     => true,
                        'name'               => 'Tesg Test',
                        'phone_number'       => '12345678',
                        'preferred_username' => 'dun00101@eoopy.com',
                        'given_name'         => 'Tesg',
                        'family_name'        => 'Test',
                        'email'              => 'dun00101@eoopy.com',
                        'reseller_access'    => [
                            $organization->keycloak_name => true,
                        ],
                        'photo'              => 'https://example.com/photo.jpg',
                        'enabled'            => true,
                        'title'              => 'Mr',
                        'academic_title'     => 'Professor',
                        'office_phone'       => '+1-202-555-0197',
                        'mobile_phone'       => '+1-202-555-0147',
                        'contact_email'      => 'test@gmail.com',
                        'job_title'          => 'Manger',
                        'locale'             => 'de',
                    ];
                },
            ],
            'no phone_number but phone_number_verified'  => [
                static function (string $client, Organization $organization): array {
                    return [
                        'email'          => 'dun00101@eoopy.com',
                        'email_verified' => true,
                        'given_name'     => 'Tesg',
                        'family_name'    => 'Test',
                        'phone'          => null,
                        'phone_verified' => null,
                        'organization'   => $organization,
                        'permissions'    => [],
                        'photo'          => 'https://example.com/photo.jpg',
                        'enabled'        => true,
                        'title'          => 'Mr',
                        'academic_title' => 'Professor',
                        'office_phone'   => '+1-202-555-0197',
                        'mobile_phone'   => '+1-202-555-0147',
                        'contact_email'  => 'test@gmail.com',
                        'job_title'      => 'Manger',
                        'locale'         => null,
                        'homepage'       => null,
                        'timezone'       => null,
                        'company'        => null,
                    ];
                },
                static function (string $client, Organization $organization): array {
                    return [
                        'typ'                   => 'Bearer',
                        'scope'                 => 'openid profile email',
                        'email_verified'        => true,
                        'name'                  => 'Tesg Test',
                        'phone_number_verified' => true,
                        'preferred_username'    => 'dun00101@eoopy.com',
                        'given_name'            => 'Tesg',
                        'family_name'           => 'Test',
                        'email'                 => 'dun00101@eoopy.com',
                        'reseller_access'       => [
                            $organization->keycloak_name => true,
                        ],
                        'photo'                 => 'https://example.com/photo.jpg',
                        'enabled'               => true,
                        'title'                 => 'Mr',
                        'academic_title'        => 'Professor',
                        'office_phone'          => '+1-202-555-0197',
                        'mobile_phone'          => '+1-202-555-0147',
                        'contact_email'         => 'test@gmail.com',
                        'job_title'             => 'Manger',
                        'locale'                => 'unknown',
                    ];
                },
            ],
            'required claim missed'                      => [
                static function (string $client, Organization $organization, User $user): Exception {
                    return new UserInsufficientData($user, ['email']);
                },
                static function (string $client, Organization $organization): array {
                    return [
                        'typ'                   => 'Bearer',
                        'scope'                 => 'openid profile email',
                        'name'                  => 'Tesg Test',
                        'phone_number_verified' => true,
                        'preferred_username'    => 'dun00101@eoopy.com',
                        'given_name'            => 'Tesg',
                        'family_name'           => 'Test',
                        'reseller_access'       => [
                            $organization->keycloak_name => true,
                        ],
                    ];
                },
            ],
            'invalid timezone'                           => [
                static function (string $client, Organization $organization): array {
                    return [
                        'email'          => 'dun00101@eoopy.com',
                        'email_verified' => true,
                        'given_name'     => 'Tesg',
                        'family_name'    => 'Test',
                        'phone'          => null,
                        'phone_verified' => null,
                        'organization'   => $organization,
                        'permissions'    => [],
                        'photo'          => null,
                        'enabled'        => true,
                        'title'          => null,
                        'academic_title' => null,
                        'office_phone'   => null,
                        'mobile_phone'   => null,
                        'contact_email'  => null,
                        'job_title'      => null,
                        'locale'         => null,
                        'homepage'       => null,
                        'timezone'       => null,
                        'company'        => null,
                    ];
                },
                static function (string $client, Organization $organization): array {
                    return [
                        'typ'                => 'Bearer',
                        'scope'              => 'openid profile email',
                        'email_verified'     => true,
                        'name'               => 'Tesg Test',
                        'preferred_username' => 'dun00101@eoopy.com',
                        'given_name'         => 'Tesg',
                        'family_name'        => 'Test',
                        'email'              => 'dun00101@eoopy.com',
                        'reseller_access'    => [
                            $organization->keycloak_name => true,
                        ],
                        'enabled'            => true,
                        'timezone'           => 'invalid',
                    ];
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderGetOrganization(): array {
        $success = static function (self $test, string $client, Organization $organization, User $user): array {
            OrganizationUser::factory()->create([
                'organization_id' => $organization,
                'user_id'         => $user,
            ]);

            return [
                'typ'             => 'Bearer',
                'scope'           => 'openid profile email',
                'reseller_access' => [
                    $organization->keycloak_name => true,
                ],
            ];
        };

        return [
            'organization not found'                                                                  => [
                false,
                static function (self $test, string $client, Organization $organization): array {
                    return [
                        'typ'             => 'Bearer',
                        'scope'           => 'openid profile email',
                        'reseller_access' => [],
                    ];
                },
                static function (): ?Organization {
                    return null;
                },
            ],
            'organization found but the user is not a member of it'                                   => [
                false,
                static function (self $test, string $client, Organization $organization): array {
                    return [
                        'typ'             => 'Bearer',
                        'scope'           => 'openid profile email',
                        'reseller_access' => [
                            $organization->keycloak_name => true,
                        ],
                    ];
                },
                static function (): ?Organization {
                    return null;
                },
            ],
            'organization found and the user is a member of it'                                       => [
                true,
                $success,
                static function (): ?Organization {
                    return null;
                },
            ],
            'organization found and the user is a member of it but current organization is match'     => [
                true,
                $success,
                static function (self $test, Organization $organization): ?Organization {
                    return $organization;
                },
            ],
            'organization found and the user is a member of it but current organization is not match' => [
                false,
                $success,
                static function (): ?Organization {
                    return Organization::factory()->make();
                },
            ],
            'organization found and the user is a member of it but no access'                         => [
                false,
                static function (self $test, string $client, Organization $organization, User $user): array {
                    OrganizationUser::factory()->create([
                        'organization_id' => $organization,
                        'user_id'         => $user,
                    ]);

                    return [
                        'typ'             => 'Bearer',
                        'scope'           => 'openid profile email',
                        'reseller_access' => [
                            $organization->keycloak_name => false,
                        ],
                    ];
                },
                static function (): ?Organization {
                    return null;
                },
            ],
        ];
    }
    //</editor-fold>
}
