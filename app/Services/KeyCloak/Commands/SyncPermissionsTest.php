<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Permission as PermissionModel;
use App\Models\Role as RoleModel;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Group;
use App\Services\KeyCloak\Client\Types\Role;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Commands\SyncPermissions
 */
class SyncPermissionsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::handle
     */
    public function testHandleOrgAdminGroupNotSet(): void {
        $this->override(Auth::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getPermissions')
                ->once()
                ->andReturns([
                    new Permission('permission-a', orgAdmin: false),
                    new Permission('permission-b', orgAdmin: false),
                ]);
        });
        $this->override(Client::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getRoles')
                ->once()
                ->andReturn([
                    new Role([
                        'id'   => $this->faker->uuid,
                        'name' => 'permission-a',
                    ]),
                    new Role([
                        'id'   => $this->faker->uuid,
                        'name' => 'permission-c',
                    ]),
                    new Role([
                        'id'   => $this->faker->uuid,
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
                        'id'   => $this->faker->uuid,
                        'name' => 'permission-b',
                    ]),
                );
            $mock
                ->shouldReceive('deleteRoleByName')
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

        $this->app->make(SyncPermissions::class)->handle();

        $actual   = $this->getPermissions();
        $expected = [
            'permission-a' => true,
            'permission-b' => true,
            'permission-c' => false,
        ];

        $this->assertEquals($expected, $actual);
        $this->assertEquals(0, RoleModel::query()->count());
    }

    /**
     * @covers ::handle
     */
    public function testHandleOrgAdminGroupSetGroupNotExists(): void {
        $groupId = $this->faker->uuid;

        $this->setSettings([
            'ep.keycloak.org_admin_group' => $groupId,
        ]);
        $this->override(Auth::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getPermissions')
                ->once()
                ->andReturns([
                    new Permission('permission-a'),
                ]);
        });
        $this->override(Client::class, function (MockInterface $mock) use ($groupId): void {
            $mock
                ->shouldReceive('getRoles')
                ->once()
                ->andReturn([
                    new Role([
                        'id'   => $this->faker->uuid,
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

        $this->app->make(SyncPermissions::class)->handle();

        $actual   = $this->getPermissions();
        $expected = [
            'permission-a' => true,
        ];

        $this->assertEquals($expected, $actual);
        $this->assertFalse(RoleModel::query()->whereKey($groupId)->exists());
    }

    /**
     * @covers ::handle
     */
    public function testHandleOrgAdminGroupSetGroupExists(): void {
        $groupId   = $this->faker->uuid;
        $groupName = $this->faker->word;

        $this->setSettings([
            'ep.keycloak.org_admin_group' => $groupId,
        ]);
        $this->override(Auth::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getPermissions')
                ->once()
                ->andReturns([
                    new Permission('permission-a', orgAdmin: true),
                    new Permission('permission-b', orgAdmin: false),
                ]);
        });
        $this->override(Client::class, function (MockInterface $mock) use ($groupId, $groupName): void {
            $group = new Group([
                'id'   => $groupId,
                'name' => $groupName,
            ]);
            $roleA = new Role([
                'id'   => $this->faker->uuid,
                'name' => 'permission-a',
            ]);
            $roleB = new Role([
                'id'   => $this->faker->uuid,
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
                ->shouldReceive('setGroupRoles')
                ->with($group, [$roleA])
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

        $this->app->make(SyncPermissions::class)->handle();

        $role     = RoleModel::query()->whereKey($groupId)->first();
        $actual   = $this->getPermissions();
        $expected = [
            'permission-a' => true,
            'permission-b' => true,
        ];

        $this->assertEquals($expected, $actual);
        $this->assertNotNull($role);
        $this->assertEquals($groupName, $role->name);
        $this->assertNull($role->organization_id);
        $this->assertEquals(
            ['permission-a'],
            $role->permissions
                ->map(static function (PermissionModel $permission): string {
                    return $permission->key;
                })
                ->sort()
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
