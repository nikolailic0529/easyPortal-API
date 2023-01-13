<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Importer;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\User as KeycloakUser;
use App\Services\Keycloak\Exceptions\FailedToImportUserConflictType;
use App\Services\Keycloak\Utils\Map;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Processor\State;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function reset;

/**
 * @internal
 * @covers \App\Services\Keycloak\Importer\UsersImporter
 */
class UsersImporterTest extends TestCase {
    public function testImport(): void {
        // Prepare
        $organization = $this->setOrganization(Organization::factory()->create([
            'keycloak_group_id' => 'c0200a6c-1b8a-4365-9f1b-32d753194336',
        ]));
        $role         = Role::factory()->create([
            'id'              => 'c0200a6c-1b8a-4365-9f1b-32d753194337',
            'organization_id' => $organization->getKey(),
        ]);
        $keycloakUser = new KeycloakUser([
            'id'            => 'c0200a6c-1b8a-4365-9f1b-32d753194335',
            'email'         => 'test@example.com',
            'firstName'     => 'first',
            'lastName'      => 'last',
            'emailVerified' => false,
            'enabled'       => false,
            'groups'        => [
                'c0200a6c-1b8a-4365-9f1b-32d753194336',
                'c0200a6c-1b8a-4365-9f1b-32d753194337',
            ],
            'attributes'    => [
                'contact_email'  => [
                    'test@gmail.com',
                ],
                'academic_title' => [
                    'academic_title',
                ],
                'title'          => [
                    'Mr',
                ],
                'office_phone'   => [
                    '01000230232',
                ],
                'mobile_phone'   => [
                    '0100023023232',
                ],
                'job_title'      => [
                    'manger',
                ],
                'company'        => [
                    'EP',
                ],
                'phone'          => [
                    '0100023023235',
                ],
                'photo'          => [
                    'http://example.com/photo.jpg',
                ],
                'locale'         => [
                    'en',
                ],
                'homepage'       => [
                    'https://example.com/',
                ],
                'timezone'       => [
                    'Europe/Berlin',
                ],
            ],
        ]);

        $this->override(Client::class, static function (MockInterface $mock) use ($keycloakUser): void {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->makePartial();
            $mock
                ->shouldReceive('call')
                ->never();
            $mock
                ->shouldReceive('getUsers')
                ->once()
                ->andReturns([
                    $keycloakUser,
                ]);
            $mock
                ->shouldReceive('usersCount')
                ->once()
                ->andReturns(1);
        });

        // call
        $this->app->make(UsersImporter::class)
            ->setChunkSize(1)
            ->setLimit(1)
            ->start();

        $user = GlobalScopes::callWithoutAll(static function () use ($keycloakUser): ?User {
            return User::query()
                ->with(['organizations'])
                ->whereKey($keycloakUser->id)
                ->first();
        });

        self::assertNotNull($user);
        self::assertFalse($user->email_verified);
        self::assertFalse($user->enabled);
        self::assertEquals($user->given_name, $keycloakUser->firstName);
        self::assertEquals($user->family_name, $keycloakUser->lastName);
        self::assertEquals($user->email, $keycloakUser->email);
        self::assertEmpty($user->permissions);

        // profile
        self::assertEquals($user->office_phone, $keycloakUser->attributes['office_phone'][0]);
        self::assertEquals($user->contact_email, $keycloakUser->attributes['contact_email'][0]);
        self::assertEquals($user->title, $keycloakUser->attributes['title'][0]);
        self::assertEquals($user->mobile_phone, $keycloakUser->attributes['mobile_phone'][0]);
        self::assertEquals($user->job_title, $keycloakUser->attributes['job_title'][0]);
        self::assertEquals($user->phone, $keycloakUser->attributes['phone'][0]);
        self::assertEquals($user->company, $keycloakUser->attributes['company'][0]);
        self::assertEquals($user->photo, $keycloakUser->attributes['photo'][0]);
        self::assertEquals($user->locale, Map::getAppLocale($keycloakUser->attributes['locale'][0]));
        self::assertEquals($user->homepage, $keycloakUser->attributes['homepage'][0]);
        self::assertEquals($user->timezone, $keycloakUser->attributes['timezone'][0]);

        // Test
        $expected = [
            [
                'organization_id' => $organization->getKey(),
                'role_id'         => $role->getKey(),
                'enabled'         => false,
            ],
        ];
        $actual   = $user->organizations
            ->map(static function (OrganizationUser $user): array {
                return [
                    'organization_id' => $user->organization_id,
                    'role_id'         => $user->role_id,
                    'enabled'         => $user->enabled,
                ];
            })
            ->all();

        self::assertEquals($expected, $actual);
    }

    public function testImportExistingUserWithRoles(): void {
        // Prepare
        $orgA  = Organization::factory()->create([
            'keycloak_group_id' => 'c0200a6c-1b8a-4365-9f1b-32d753194336',
        ]);
        $orgB  = Organization::factory()->create([
            'keycloak_group_id' => 'a2ff9b08-0404-4bde-a400-288d6ce4a1c8',
        ]);
        $roleA = Role::factory()->create([
            'id'              => 'c0200a6c-1b8a-4365-9f1b-32d753194337',
            'organization_id' => $orgA->getKey(),
        ]);
        $roleB = Role::factory()->create([
            'id'              => '4b3d3c8f-4a55-45f9-ac8b-1b3f3547d7b0',
            'organization_id' => $orgA->getKey(),
        ]);
        $user  = User::factory()->create([
            'id' => 'c0200a6c-1b8a-4365-9f1b-32d753194335',
        ]);

        GlobalScopes::callWithoutAll(static function () use ($user, $orgA, $orgB, $roleB): void {
            OrganizationUser::factory()->create([
                'organization_id' => $orgA,
                'user_id'         => $user,
                'role_id'         => null,
                'enabled'         => true,
            ]);

            OrganizationUser::factory()->create([
                'organization_id' => $orgB,
                'user_id'         => $user,
                'role_id'         => $roleB,
                'enabled'         => false,
            ]);
        });

        $keycloakUser = new KeycloakUser([
            'id'            => $user->getKey(),
            'email'         => 'test@example.com',
            'firstName'     => 'first',
            'lastName'      => 'last',
            'emailVerified' => false,
            'enabled'       => true,
            'groups'        => [
                $roleA->getKey(),
                $roleB->getKey(),
            ],
        ]);

        $this->override(Client::class, static function (MockInterface $mock) use ($keycloakUser): void {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->makePartial();
            $mock
                ->shouldReceive('call')
                ->never();
            $mock
                ->shouldReceive('getUsers')
                ->once()
                ->andReturns([
                    $keycloakUser,
                ]);
            $mock
                ->shouldReceive('usersCount')
                ->once()
                ->andReturns(1);
        });

        // call
        $this->app->make(UsersImporter::class)
            ->setChunkSize(1)
            ->setLimit(1)
            ->start();

        $user = GlobalScopes::callWithoutAll(static function () use ($keycloakUser): ?User {
            return User::query()
                ->with(['organizations'])
                ->whereKey($keycloakUser->id)
                ->first();
        });

        self::assertNotNull($user);
        self::assertFalse($user->email_verified);
        self::assertTrue($user->enabled);
        self::assertEquals($user->given_name, $keycloakUser->firstName);
        self::assertEquals($user->family_name, $keycloakUser->lastName);
        self::assertEquals($user->email, $keycloakUser->email);

        // Organization
        $order    = 'organization_id';
        $expected = (new Collection([
            [
                'organization_id' => $orgA->getKey(),
                'role_id'         => $roleA->getKey(),
                'enabled'         => true,
            ],
            [
                'organization_id' => $orgB->getKey(),
                'role_id'         => null,
                'enabled'         => false,
            ],
        ]))
            ->sortBy($order)
            ->values()
            ->all();
        $actual   = $user->organizations
            ->map(static function (OrganizationUser $user): array {
                return [
                    'organization_id' => $user->organization_id,
                    'role_id'         => $user->role_id,
                    'enabled'         => $user->enabled,
                ];
            })
            ->sortBy($order)
            ->values()
            ->all();

        self::assertEquals($expected, $actual);
    }

    /**
     * @see https://github.com/fakharanwar/easyPortal-API/issues/1008
     */
    public function testImportExistingUserWithPermissions(): void {
        // Prepare
        $org  = Organization::factory()->create([
            'keycloak_group_id' => 'a0943090-5d61-4d4e-ba25-22eb962ab8e9',
        ]);
        $role = Role::factory()->create([
            'id'              => 'fcc8bcf4-6c21-4219-a2f4-0dacfa67a030',
            'organization_id' => $org->getKey(),
        ]);
        $user = User::factory()->create([
            'id'          => '6b5501e1-c754-4f24-882e-aa35c8432775',
            'permissions' => ['permission-a', 'permission-b'],
        ]);

        GlobalScopes::callWithoutAll(static function () use ($user, $org): void {
            OrganizationUser::factory()->create([
                'organization_id' => $org,
                'user_id'         => $user,
                'role_id'         => null,
                'enabled'         => true,
            ]);
        });

        $keycloakUser = new KeycloakUser([
            'id'            => $user->getKey(),
            'email'         => 'test@example.com',
            'firstName'     => 'first',
            'lastName'      => 'last',
            'emailVerified' => false,
            'enabled'       => true,
            'groups'        => [
                $role->getKey(),
            ],
        ]);

        $this->override(Client::class, static function (MockInterface $mock) use ($keycloakUser): void {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->makePartial();
            $mock
                ->shouldReceive('call')
                ->never();
            $mock
                ->shouldReceive('getUsers')
                ->once()
                ->andReturns([
                    $keycloakUser,
                ]);
            $mock
                ->shouldReceive('usersCount')
                ->once()
                ->andReturns(1);
        });

        // call
        $this->app->make(UsersImporter::class)
            ->setChunkSize(1)
            ->setLimit(1)
            ->start();

        $user = $user->refresh();

        self::assertEquals(['permission-a', 'permission-b'], $user->permissions);
    }

    public function testGetUserNormal(): void {
        $user = User::factory()->make();
        $item = new KeycloakUser(['id' => $user->getKey()]);
        $data = Mockery::mock(UsersImporterChunkData::class);
        $data
            ->shouldReceive('getUserById')
            ->once()
            ->with($item->id)
            ->andReturn(clone $user);

        $importer = new class() extends UsersImporter {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getUser(UsersImporterChunkData $data, KeycloakUser $item): User {
                return parent::getUser($data, $item);
            }
        };

        $actual = $importer->getUser($data, $item);

        self::assertEquals($user, $actual);
    }

    public function testGetUserNoUser(): void {
        $item = new KeycloakUser(['id' => $this->faker->uuid()]);
        $data = Mockery::mock(UsersImporterChunkData::class);
        $data
            ->shouldReceive('getUserById')
            ->once()
            ->with($item->id)
            ->andReturn(null);

        $importer = new class() extends UsersImporter {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getUser(UsersImporterChunkData $data, KeycloakUser $item): User {
                return parent::getUser($data, $item);
            }
        };

        $actual = $importer->getUser($data, $item);

        self::assertFalse($actual->exists);
        self::assertEquals($item->id, $actual->getKey());
        self::assertEquals(UserType::keycloak(), $actual->type);
    }

    public function testGetUserTrashed(): void {
        $user = User::factory()->make(['deleted_at' => Date::now()]);
        $item = new KeycloakUser(['id' => $user->getKey()]);
        $data = Mockery::mock(UsersImporterChunkData::class);
        $data
            ->shouldReceive('getUserById')
            ->once()
            ->with($item->id)
            ->andReturn(clone $user);

        $importer = new class() extends UsersImporter {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getUser(UsersImporterChunkData $data, KeycloakUser $item): User {
                return parent::getUser($data, $item);
            }
        };

        $actual = $importer->getUser($data, $item);

        self::assertTrue($user->trashed());
        self::assertFalse($actual->trashed());
    }

    public function testGetUserInvalidType(): void {
        $user = User::factory()->make(['type' => UserType::local()]);
        $item = new KeycloakUser(['id' => $user->getKey()]);
        $data = Mockery::mock(UsersImporterChunkData::class);
        $data
            ->shouldReceive('getUserById')
            ->once()
            ->with($item->id)
            ->andReturn(clone $user);

        $importer = new class() extends UsersImporter {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getUser(UsersImporterChunkData $data, KeycloakUser $item): User {
                return parent::getUser($data, $item);
            }
        };

        self::expectExceptionObject(
            new FailedToImportUserConflictType($importer, $item, $user),
        );

        $importer->getUser($data, $item);
    }

    public function testProcess(): void {
        $state = new UsersImporterState();
        $user  = User::factory()->make();
        $item  = new KeycloakUser([
            'id'            => $user->getKey(),
            'email'         => $this->faker->email(),
            'firstName'     => $this->faker->firstName(),
            'lastName'      => $this->faker->lastName(),
            'emailVerified' => $this->faker->boolean(),
            'enabled'       => $this->faker->boolean(),
            'groups'        => [],
            'attributes'    => [
                'phone'          => [$this->faker->e164PhoneNumber()],
                'office_phone'   => [$this->faker->e164PhoneNumber()],
                'mobile_phone'   => [$this->faker->e164PhoneNumber()],
                'contact_email'  => [$this->faker->email()],
                'title'          => [$this->faker->title()],
                'job_title'      => [$this->faker->title()],
                'academic_title' => [$this->faker->title()],
                'company'        => [$this->faker->company()],
                'photo'          => [$this->faker->url()],
            ],
        ]);
        $data  = Mockery::mock(UsersImporterChunkData::class);
        $data
            ->shouldReceive('getUserById')
            ->with($item->id)
            ->once()
            ->andReturn(clone $user);
        $data
            ->shouldReceive('getUserByEmail')
            ->with($item->email)
            ->once()
            ->andReturn(null);

        $importer = new class() extends UsersImporter {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function process(State $state, mixed $data, mixed $item): void {
                parent::process($state, $data, $item);
            }
        };

        $importer->process($state, $data, $item);

        $actual = User::query()->whereKey($item->id)->first();

        self::assertInstanceOf(User::class, $actual);
        self::assertNotNull($actual->synced_at);
        self::assertEquals($item->id, $actual->getKey());
        self::assertEquals($item->email, $actual->email);
        self::assertEquals($item->emailVerified, $actual->email_verified);
        self::assertEquals($item->enabled, $actual->enabled);
        self::assertEquals($item->firstName, $actual->given_name);
        self::assertEquals($item->lastName, $actual->family_name);
        self::assertEquals(reset($item->attributes['office_phone']), $actual->office_phone);
        self::assertEquals(reset($item->attributes['contact_email']), $actual->contact_email);
        self::assertEquals(reset($item->attributes['title']), $actual->title);
        self::assertEquals(reset($item->attributes['academic_title']), $actual->academic_title);
        self::assertEquals(reset($item->attributes['job_title']), $actual->job_title);
        self::assertEquals(reset($item->attributes['phone']), $actual->phone);
        self::assertEquals(reset($item->attributes['mobile_phone']), $actual->mobile_phone);
        self::assertEquals(reset($item->attributes['company']), $actual->company);
        self::assertEquals(reset($item->attributes['photo']), $actual->photo);
        self::assertEquals([], $actual->permissions);
    }

    public function testProcessEmailConflict(): void {
        $state   = new UsersImporterState();
        $another = User::factory()->make();
        $user    = User::factory()->make();
        $item    = new KeycloakUser([
            'id'            => $user->getKey(),
            'email'         => $another->email,
            'firstName'     => $this->faker->firstName(),
            'lastName'      => $this->faker->lastName(),
            'emailVerified' => $this->faker->boolean(),
            'enabled'       => $this->faker->boolean(),
            'groups'        => [],
        ]);
        $data    = Mockery::mock(UsersImporterChunkData::class);
        $data
            ->shouldReceive('getUserById')
            ->with($item->id)
            ->once()
            ->andReturn(clone $user);
        $data
            ->shouldReceive('getUserByEmail')
            ->with($item->email)
            ->once()
            ->andReturn($another);

        $importer = new class() extends UsersImporter {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function process(State $state, mixed $data, mixed $item): void {
                parent::process($state, $data, $item);
            }
        };

        $importer->process($state, $data, $item);

        $actual  = User::query()->whereKey($item->id)->first();
        $another = $another->refresh();

        self::assertEquals("(conflict) {$item->email}", $another->email);
        self::assertEquals($item->email, $actual->email);
    }

    public function testFinish(): void {
        // Users
        $a = User::factory()->create([
            'type'       => UserType::keycloak(),
            'given_name' => 'Should be deleted (`synced_at` is `null`)',
            'synced_at'  => null,
        ]);
        $b = User::factory()->create([
            'type'       => UserType::keycloak(),
            'given_name' => 'Should be deleted (`synced_at` is old)',
            'synced_at'  => Date::now()->subDay(),
        ]);
        $c = User::factory()->create([
            'type'       => UserType::local(),
            'given_name' => 'Should not be deleted (local user)',
            'synced_at'  => Date::now()->addDay(),
        ]);
        $d = User::factory()->create([
            'type'       => UserType::keycloak(),
            'given_name' => 'Should not be deleted',
            'synced_at'  => Date::now()->addDay(),
        ]);

        // Mocks
        $state    = new UsersImporterState([
            'started' => Date::now(),
            'overall' => true,
            'failed'  => 0,
        ]);
        $importer = new class() extends UsersImporter {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function finish(State $state): void {
                parent::finish($state);
            }

            protected function notifyOnFinish(State $state): void {
                // empty
            }
        };

        // Run
        $importer->finish($state);

        // Test
        self::assertFalse(User::query()->whereKey($a->getKey())->exists());
        self::assertFalse(User::query()->whereKey($b->getKey())->exists());
        self::assertTrue(User::query()->whereKey($c->getKey())->exists());
        self::assertTrue(User::query()->whereKey($d->getKey())->exists());
    }

    public function testFinishDeleteNotPossible(): void {
        // Mock
        $importer = Mockery::mock(UsersImporter::class);
        $importer->shouldAllowMockingProtectedMethods();
        $importer->makePartial();
        $importer
            ->shouldReceive('notifyOnFinish')
            ->twice()
            ->andReturns();
        $importer
            ->shouldReceive('deleteUser')
            ->never();

        // Iteration by part of Users
        $importer->finish(new UsersImporterState([
            'started' => Date::now(),
            'overall' => false,
        ]));

        // Failed items
        $importer->finish(new UsersImporterState([
            'started' => Date::now(),
            'overall' => true,
            'failed'  => 1,
        ]));
    }
}
