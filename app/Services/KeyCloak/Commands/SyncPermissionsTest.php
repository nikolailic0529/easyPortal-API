<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Permission as PermissionModel;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Group;
use App\Services\KeyCloak\Client\Types\Role;
use Closure;
use Illuminate\Support\Facades\Date;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function count;
use function reset;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Commands\SyncPermissions
 */
class SyncPermissionsTest extends TestCase {
    /**
     * @covers ::handle
     *
     * @dataProvider dataProviderTestHandle
     *
     * @param array<string, mixed> $settings
     * @param array<string, bool>  $expected
     */
    public function testHandle(
        array $expected,
        array $settings,
        Closure $authFactory,
        Closure $clientFactory,
        Closure $permissionFactory,
    ): void {
        $this->setSettings($settings);
        $this->override(Auth::class, $authFactory);
        $this->override(Client::class, $clientFactory);

        $permissionFactory($this);

        $command = $this->app->make(SyncPermissions::class);

        $command->handle();

        $actual = PermissionModel::query()
            ->withTrashed()
            ->get()
            ->keyBy(static function (PermissionModel $model): string {
                return $model->key;
            })
            ->map(static function (PermissionModel $model): bool {
                return !$model->trashed();
            })
            ->all();

        $this->assertEquals($expected, $actual);
    }

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderTestHandle(): array {
        return [
            'ep.keycloak.org_admin_group is set but not exists' => [
                [
                    'permission-a' => true,
                ],
                [
                    'ep.keycloak.org_admin_group' => 'c361aa97-150a-4acc-8e81-3964dcf5214e',
                ],
                static function (MockInterface $mock): void {
                    $mock
                        ->shouldReceive('getPermissions')
                        ->once()
                        ->andReturns([
                            new Permission('permission-a'),
                        ]);
                },
                static function (MockInterface $mock, TestCase $test): void {
                    $mock
                        ->shouldReceive('getRoles')
                        ->once()
                        ->andReturn([
                            new Role([
                                'id'   => $test->faker->uuid,
                                'name' => 'permission-a',
                            ]),
                        ]);
                    $mock
                        ->shouldReceive('getGroup')
                        ->with('c361aa97-150a-4acc-8e81-3964dcf5214e')
                        ->once()
                        ->andReturn(null);
                },
                static function (): void {
                    PermissionModel::factory()->create([
                        'key'        => 'permission-a',
                        'deleted_at' => Date::now(),
                    ]);
                },
            ],
            'ep.keycloak.org_admin_group is set and exists'     => [
                [
                    'permission-a' => true,
                    'permission-b' => true,
                ],
                [
                    'ep.keycloak.org_admin_group' => 'c361aa97-150a-4acc-8e81-3964dcf5214e',
                ],
                static function (MockInterface $mock): void {
                    $mock
                        ->shouldReceive('getPermissions')
                        ->once()
                        ->andReturns([
                            new Permission('permission-a', orgAdmin: true),
                            new Permission('permission-b', orgAdmin: false),
                        ]);
                },
                static function (MockInterface $mock, TestCase $test): void {
                    $group = new Group(['id' => 'c361aa97-150a-4acc-8e81-3964dcf5214e']);
                    $roleA = new Role([
                        'id'   => $test->faker->uuid,
                        'name' => 'permission-a',
                    ]);
                    $roleB = new Role([
                        'id'   => $test->faker->uuid,
                        'name' => 'permission-b',
                    ]);

                    $mock
                        ->shouldReceive('getRoles')
                        ->once()
                        ->andReturn([$roleA, $roleB]);
                    $mock
                        ->shouldReceive('getGroup')
                        ->with('c361aa97-150a-4acc-8e81-3964dcf5214e')
                        ->once()
                        ->andReturn($group);
                    $mock
                        ->shouldReceive('setGroupRoles')
                        ->with($group, [$roleA])
                        ->once()
                        ->andReturn(true);
                },
                static function (): void {
                    PermissionModel::factory()->create([
                        'key'        => 'permission-a',
                        'deleted_at' => Date::now(),
                    ]);
                    PermissionModel::factory()->create([
                        'key' => 'permission-b',
                    ]);
                },
            ],
            'ep.keycloak.org_admin_group is not set'            => [
                [
                    'permission-a' => true,
                    'permission-b' => true,
                    'permission-c' => false,
                ],
                [
                    // empty
                ],
                static function (MockInterface $mock): void {
                    $mock
                        ->shouldReceive('getPermissions')
                        ->once()
                        ->andReturns([
                            new Permission('permission-a', orgAdmin: false),
                            new Permission('permission-b', orgAdmin: false),
                        ]);
                },
                static function (MockInterface $mock, TestCase $test): void {
                    $mock
                        ->shouldReceive('getRoles')
                        ->once()
                        ->andReturn([
                            new Role([
                                'id'   => $test->faker->uuid,
                                'name' => 'permission-a',
                            ]),
                            new Role([
                                'id'   => $test->faker->uuid,
                                'name' => 'permission-c',
                            ]),
                            new Role([
                                'id'   => $test->faker->uuid,
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
                                'id'   => $test->faker->uuid,
                                'name' => 'permission-b',
                            ]),
                        );
                    $mock
                        ->shouldReceive('deleteRoleByName')
                        ->with('permission-c')
                        ->once()
                        ->andReturns();
                },
                static function (): void {
                    PermissionModel::factory()->create([
                        'key'        => 'permission-a',
                        'deleted_at' => Date::now(),
                    ]);
                    PermissionModel::factory()->create([
                        'key' => 'permission-c',
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
