<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeyCloakUser;
use App\Services\KeyCloak\Exceptions\FailedToImportUserConflictType;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function reset;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Importer\UsersImporter
 */
class UsersImporterTest extends TestCase {
    /**
     * @covers ::import
     */
    public function testImport(): void {
        // Prepare
        $organization = $this->setOrganization(Organization::factory()->create([
            'keycloak_group_id' => 'c0200a6c-1b8a-4365-9f1b-32d753194336',
        ]));
        $role         = Role::factory()->create([
            'id'              => 'c0200a6c-1b8a-4365-9f1b-32d753194337',
            'organization_id' => $organization->getKey(),
        ]);
        $keycloakUser = new KeyCloakUser([
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

        $user = GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($keycloakUser) {
                return User::query()
                    ->with(['organizations'])
                    ->whereKey($keycloakUser->id)
                    ->first();
            },
        );
        $this->assertNotNull($user);
        $this->assertFalse($user->email_verified);
        $this->assertFalse($user->enabled);
        $this->assertEquals($user->given_name, $keycloakUser->firstName);
        $this->assertEquals($user->family_name, $keycloakUser->lastName);
        $this->assertEquals($user->email, $keycloakUser->email);

        // profile
        $this->assertEquals($user->office_phone, $keycloakUser->attributes['office_phone'][0]);
        $this->assertEquals($user->contact_email, $keycloakUser->attributes['contact_email'][0]);
        $this->assertEquals($user->title, $keycloakUser->attributes['title'][0]);
        $this->assertEquals($user->mobile_phone, $keycloakUser->attributes['mobile_phone'][0]);
        $this->assertEquals($user->job_title, $keycloakUser->attributes['job_title'][0]);
        $this->assertEquals($user->phone, $keycloakUser->attributes['phone'][0]);
        $this->assertEquals($user->company, $keycloakUser->attributes['company'][0]);
        $this->assertEquals($user->photo, $keycloakUser->attributes['photo'][0]);

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

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::import
     */
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

        GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($user, $orgA, $orgB, $roleB): void {
                OrganizationUser::factory()->create([
                    'organization_id' => $orgA,
                    'user_id'         => $user,
                    'role_id'         => null,
                ]);

                OrganizationUser::factory()->create([
                    'organization_id' => $orgB,
                    'user_id'         => $user,
                    'role_id'         => $roleB,
                    'enabled'         => false,
                ]);
            },
        );

        $keycloakUser = new KeyCloakUser([
            'id'            => $user->getKey(),
            'email'         => 'test@example.com',
            'firstName'     => 'first',
            'lastName'      => 'last',
            'emailVerified' => false,
            'enabled'       => true,
            'groups'        => [
                $orgA->keycloak_group_id,
                $roleA->getKey(),
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

        $user = GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($keycloakUser) {
                return User::query()
                    ->with(['organizations'])
                    ->whereKey($keycloakUser->id)
                    ->first();
            },
        );
        $this->assertNotNull($user);
        $this->assertFalse($user->email_verified);
        $this->assertTrue($user->enabled);
        $this->assertEquals($user->given_name, $keycloakUser->firstName);
        $this->assertEquals($user->family_name, $keycloakUser->lastName);
        $this->assertEquals($user->email, $keycloakUser->email);

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

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserNormal(): void {
        $user = User::factory()->make();
        $item = new KeyCloakUser(['id' => $user->getKey()]);
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

            public function getUser(UsersImporterChunkData $data, KeyCloakUser $item): User {
                return parent::getUser($data, $item);
            }
        };

        $actual = $importer->getUser($data, $item);

        $this->assertEquals($user, $actual);
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserNoUser(): void {
        $item = new KeyCloakUser(['id' => $this->faker->uuid]);
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

            public function getUser(UsersImporterChunkData $data, KeyCloakUser $item): User {
                return parent::getUser($data, $item);
            }
        };

        $actual = $importer->getUser($data, $item);

        $this->assertFalse($actual->exists);
        $this->assertEquals($item->id, $actual->getKey());
        $this->assertEquals(UserType::keycloak(), $actual->type);
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserTrashed(): void {
        $user = User::factory()->make(['deleted_at' => Date::now()]);
        $item = new KeyCloakUser(['id' => $user->getKey()]);
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

            public function getUser(UsersImporterChunkData $data, KeyCloakUser $item): User {
                return parent::getUser($data, $item);
            }
        };

        $actual = $importer->getUser($data, $item);

        $this->assertTrue($user->trashed());
        $this->assertFalse($actual->trashed());
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserInvalidType(): void {
        $user = User::factory()->make(['type' => UserType::local()]);
        $item = new KeyCloakUser(['id' => $user->getKey()]);
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

            public function getUser(UsersImporterChunkData $data, KeyCloakUser $item): User {
                return parent::getUser($data, $item);
            }
        };

        $this->expectExceptionObject(
            new FailedToImportUserConflictType($importer, $item, $user),
        );

        $importer->getUser($data, $item);
    }

    /**
     * @covers ::process
     */
    public function testProcess(): void {
        $user = User::factory()->make();
        $item = new KeyCloakUser([
            'id'            => $user->getKey(),
            'email'         => $this->faker->email,
            'firstName'     => $this->faker->firstName,
            'lastName'      => $this->faker->lastName,
            'emailVerified' => $this->faker->boolean,
            'enabled'       => $this->faker->boolean,
            'groups'        => [],
            'attributes'    => [
                'phone'          => [$this->faker->e164PhoneNumber],
                'office_phone'   => [$this->faker->e164PhoneNumber],
                'mobile_phone'   => [$this->faker->e164PhoneNumber],
                'contact_email'  => [$this->faker->email],
                'title'          => [$this->faker->title],
                'job_title'      => [$this->faker->title],
                'academic_title' => [$this->faker->title],
                'company'        => [$this->faker->company],
                'photo'          => [$this->faker->url],
            ],
        ]);
        $data = Mockery::mock(UsersImporterChunkData::class);
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

            public function process(mixed $data, mixed $item): void {
                parent::process($data, $item);
            }

            protected function getUserOrganizations(User $user, KeyCloakUser $item): Collection {
                return new Collection();
            }
        };

        $importer->process($data, $item);

        $actual = User::query()->whereKey($item->id)->first();

        $this->assertInstanceOf(User::class, $actual);
        $this->assertEquals($item->id, $actual->getKey());
        $this->assertEquals($item->email, $actual->email);
        $this->assertEquals($item->emailVerified, $actual->email_verified);
        $this->assertEquals($item->enabled, $actual->enabled);
        $this->assertEquals($item->firstName, $actual->given_name);
        $this->assertEquals($item->lastName, $actual->family_name);
        $this->assertEquals(reset($item->attributes['office_phone']), $actual->office_phone);
        $this->assertEquals(reset($item->attributes['contact_email']), $actual->contact_email);
        $this->assertEquals(reset($item->attributes['title']), $actual->title);
        $this->assertEquals(reset($item->attributes['academic_title']), $actual->academic_title);
        $this->assertEquals(reset($item->attributes['job_title']), $actual->job_title);
        $this->assertEquals(reset($item->attributes['phone']), $actual->phone);
        $this->assertEquals(reset($item->attributes['mobile_phone']), $actual->mobile_phone);
        $this->assertEquals(reset($item->attributes['company']), $actual->company);
        $this->assertEquals(reset($item->attributes['photo']), $actual->photo);
        $this->assertEquals([], $actual->permissions);
    }

    /**
     * @covers ::process
     */
    public function testProcessEmailConflict(): void {
        $another = User::factory()->make();
        $user    = User::factory()->make();
        $item    = new KeyCloakUser([
            'id'            => $user->getKey(),
            'email'         => $another->email,
            'firstName'     => $this->faker->firstName,
            'lastName'      => $this->faker->lastName,
            'emailVerified' => $this->faker->boolean,
            'enabled'       => $this->faker->boolean,
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

            public function process(mixed $data, mixed $item): void {
                parent::process($data, $item);
            }

            protected function getUserOrganizations(User $user, KeyCloakUser $item): Collection {
                return new Collection();
            }
        };

        $importer->process($data, $item);

        $actual  = User::query()->whereKey($item->id)->first();
        $another = $another->refresh();

        $this->assertEquals("(conflict) {$item->email}", $another->email);
        $this->assertEquals($item->email, $actual->email);
    }
}
