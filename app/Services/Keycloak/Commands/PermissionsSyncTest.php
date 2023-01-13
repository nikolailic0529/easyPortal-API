<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Commands;

use App\Models\Permission as PermissionModel;
use App\Models\Role as RoleModel;
use App\Services\Auth\Auth;
use App\Services\Auth\Contracts\Permissions\IsRoot;
use App\Services\Auth\Permission;
use App\Services\Auth\Permissions;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\Group;
use App\Services\Keycloak\Client\Types\Role;
use App\Services\Organization\Eloquent\OwnedByScope;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @covers \App\Services\Keycloak\Commands\PermissionsSync
 */
class PermissionsSyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:keycloak-permissions-sync');
    }

    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:keycloak-permissions-sync', $this->app->make(Kernel::class)->all());
    }

    public function testHandleOrgAdminGroupNotSet(): void {
        $this->setSettings([
            'ep.keycloak.org_admin_group' => null,
        ]);

        $this->override(Auth::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getPermissions')
                ->once()
                ->andReturns([
                    new class('permission-a') extends Permission {
                        // empty
                    },
                    new class('permission-b') extends Permission {
                        // empty
                    },
                ]);
        });
        $this->override(Client::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getRoles')
                ->once()
                ->andReturn([
                    new Role([
                        'id'   => $this->faker->uuid(),
                        'name' => 'permission-a',
                    ]),
                    new Role([
                        'id'   => $this->faker->uuid(),
                        'name' => 'permission-c',
                    ]),
                    new Role([
                        'id'   => $this->faker->uuid(),
                        'name' => 'unknown',
                    ]),
                ]);
            $mock
                ->shouldReceive('createRole')
                ->withArgs(static function (Role $role): bool {
                    return $role->name === 'permission-b'
                        && $role->description === 'permission-b';
                })
                ->andReturn(
                    new Role([
                        'id'   => $this->faker->uuid(),
                        'name' => 'permission-b',
                    ]),
                );
            $mock
                ->shouldReceive('deleteRole')
                ->with('permission-c')
                ->once()
                ->andReturns();
        });

        PermissionModel::factory()->create([
            'key'        => 'permission-a',
            'deleted_at' => Date::now(),
        ]);
        PermissionModel::factory()->create([
            'key' => 'permission-c',
        ]);

        $this->artisan(PermissionsSync::class);

        $actual   = $this->getPermissions();
        $expected = [
            'permission-a' => true,
            'permission-b' => true,
            'permission-c' => false,
        ];

        self::assertEquals($expected, $actual);
        self::assertEquals(0, RoleModel::query()->withoutGlobalScope(OwnedByScope::class)->count());
    }

    public function testHandleOrgAdminGroupSetGroupNotExists(): void {
        $groupId = $this->faker->uuid();

        $this->setSettings([
            'ep.keycloak.org_admin_group' => $groupId,
        ]);
        $this->override(Auth::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getPermissions')
                ->once()
                ->andReturns([
                    new class('permission-a') extends Permission {
                        // empty
                    },
                ]);
        });
        $this->override(Client::class, function (MockInterface $mock) use ($groupId): void {
            $mock
                ->shouldReceive('getRoles')
                ->once()
                ->andReturn([
                    new Role([
                        'id'   => $this->faker->uuid(),
                        'name' => 'permission-a',
                    ]),
                ]);
            $mock
                ->shouldReceive('getGroup')
                ->with($groupId)
                ->once()
                ->andReturn(null);
        });

        PermissionModel::factory()->create([
            'key'        => 'permission-a',
            'deleted_at' => Date::now(),
        ]);

        $this->artisan(PermissionsSync::class);

        $actual   = $this->getPermissions();
        $expected = [
            'permission-a' => true,
        ];

        self::assertEquals($expected, $actual);
        self::assertFalse(
            RoleModel::query()
                ->withoutGlobalScope(OwnedByScope::class)
                ->whereKey($groupId)
                ->exists(),
        );
    }

    public function testHandleOrgAdminGroupSetGroupExists(): void {
        $groupId   = $this->faker->uuid();
        $groupName = $this->faker->word();

        $this->app->make(Permissions::class)->set([
            new class('permission-a') extends Permission {
                // empty
            },
            new class('permission-b') extends Permission implements IsRoot {
                // empty
            },
        ]);

        $this->setSettings([
            'ep.keycloak.org_admin_group' => $groupId,
        ]);
        $this->override(Client::class, function (MockInterface $mock) use ($groupId, $groupName): void {
            $group = new Group([
                'id'   => $groupId,
                'name' => $groupName,
            ]);
            $roleA = new Role([
                'id'   => $this->faker->uuid(),
                'name' => 'permission-a',
            ]);
            $roleB = new Role([
                'id'   => $this->faker->uuid(),
                'name' => 'permission-b',
            ]);

            $mock
                ->shouldReceive('getRoles')
                ->once()
                ->andReturn([$roleA, $roleB]);
            $mock
                ->shouldReceive('getGroup')
                ->with($groupId)
                ->once()
                ->andReturn($group);
            $mock
                ->shouldReceive('updateGroupRoles')
                ->with($group, [$roleA, $roleB])
                ->once()
                ->andReturn(true);
        });

        PermissionModel::factory()->create([
            'key'        => 'permission-a',
            'deleted_at' => Date::now(),
        ]);
        PermissionModel::factory()->create([
            'key' => 'permission-b',
        ]);

        $this->artisan(PermissionsSync::class);

        $role     = RoleModel::query()
            ->withoutGlobalScope(OwnedByScope::class)
            ->whereKey($groupId)
            ->first();
        $actual   = $this->getPermissions();
        $expected = [
            'permission-a' => true,
            'permission-b' => true,
        ];

        self::assertEquals($expected, $actual);
        self::assertNotNull($role);
        self::assertEquals($groupName, $role->name);
        self::assertNull($role->organization_id);
        self::assertEqualsCanonicalizing(
            array_keys($expected),
            $role->permissions
                ->map(static function (PermissionModel $permission): string {
                    return $permission->key;
                })
                ->all(),
        );
    }
    //</editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    /**
     * @return array<string, bool>
     */
    protected function getPermissions(): array {
        return PermissionModel::query()
            ->withTrashed()
            ->get()
            ->keyBy(static function (PermissionModel $model): string {
                return $model->key;
            })
            ->map(static function (PermissionModel $model): bool {
                return !$model->trashed();
            })
            ->all();
    }
    // </editor-fold>
}
