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
     *
     * @param array<string> $deleted
     */
    public function testHandle(
        array $expected,
        Closure $authFactory,
        Closure $clientFactory,
        Closure $permissionFactory = null,
        array $deleted = null,
    ): void {
        // prepare
        if ($permissionFactory) {
            $permissionFactory();
        }
        $this->override(Auth::class, $authFactory);
        $this->override(Client::class, $clientFactory);

        $command = $this->app->make(SyncPermissions::class);

        $command->handle();

        if ($deleted) {
            $this->assertFalse(PermissionModel::where('key', '=', $deleted)->exists());
        }
        $this->assertEqualsCanonicalizing(PermissionModel::pluck('key')->all(), $expected);
    }

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderTestHandle(): array {
        return [
            'empty permissions'                  => [
                ['permission1'],
                static function (MockInterface $mock): void {
                    $mock
                        ->shouldReceive('getPermissions')
                        ->once()
                        ->andReturns([
                            new Permission('permission1', orgAdmin: false),
                        ]);
                },
                static function (MockInterface $mock, TestCase $test): void {
                    $mock
                        ->shouldReceive('getRoles')
                        ->once()
                        ->andReturn([
                            new Role([
                                'id'   => $test->faker->uuid,
                                'name' => 'role',
                            ]),
                        ]);
                        $mock
                            ->shouldReceive('updateRoleByName')
                            ->once();
                        $mock
                            ->shouldReceive('createRole')
                            ->andReturn(
                                new Role([
                                    'id'   => $test->faker->uuid,
                                    'name' => 'permission1',
                                ]),
                            );
                },
            ],
            'existing permissions/different key' => [
                ['permission1'],
                static function (MockInterface $mock): void {
                    $mock
                        ->shouldReceive('getPermissions')
                        ->once()
                        ->andReturns([
                            new Permission('permission1', orgAdmin: false),
                        ]);
                },
                static function (MockInterface $mock, TestCase $test): void {
                    $mock
                        ->shouldReceive('getRoles')
                        ->once()
                        ->andReturn([
                            new Role([
                                'id'   => $test->faker->uuid,
                                'name' => 'role',
                            ]),
                        ]);
                        $mock
                            ->shouldReceive('updateRoleByName')
                            ->once();
                        $mock
                            ->shouldReceive('createRole')
                            ->andReturn(
                                new Role([
                                    'id'   => $test->faker->uuid,
                                    'name' => 'permission1',
                                ]),
                            );
                },
                static function (): void {
                    PermissionModel::factory()->create([
                        'key'        => 'permission1',
                        'created_at' => Date::now()->subHour(),
                        'deleted_at' => Date::now(),
                    ]);

                    PermissionModel::factory()->create([
                        'key' => 'permission1',
                    ]);
                },
                ['old-permissions'],
            ],
            'soft deleted permissions'           => [
                ['permission1'],
                static function (MockInterface $mock): void {
                    $mock
                        ->shouldReceive('getPermissions')
                        ->once()
                        ->andReturns([
                            new Permission('permission1', orgAdmin: false),
                        ]);
                },
                static function (MockInterface $mock, TestCase $test): void {
                    $mock
                        ->shouldReceive('getRoles')
                        ->once()
                        ->andReturn([
                            new Role([
                                'id'   => $test->faker->uuid,
                                'name' => 'permission1',
                            ]),
                        ]);
                        $mock
                            ->shouldReceive('updateRoleByName')
                            ->never();
                        $mock
                            ->shouldReceive('createRole')
                            ->andReturn(
                                new Role([
                                    'id'   => $test->faker->uuid,
                                    'name' => 'permission1',
                                ]),
                            );
                },
                static function (): void {
                    $permission = PermissionModel::factory()->create([
                        'id'  => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                        'key' => 'permission1',
                    ]);

                    $permission->delete();
                },
                ['old-permissions'],
            ],
        ];
    }
    // </editor-fold>
}
