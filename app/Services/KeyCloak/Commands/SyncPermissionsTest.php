<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Permission as PermissionModel;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Role;
use Closure;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use Tests\TestCase;

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
     * @param array<string> $expected
     */
    public function testHandle(
        array $expected,
        Closure $authFactory,
        Closure $clientFactory,
        Closure $permissionFactory,
    ): void {
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
            'permissions' => [
                [
                    'permission-a' => true,
                    'permission-b' => true,
                    'permission-c' => false,
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
