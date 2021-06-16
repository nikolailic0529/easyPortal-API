<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Permission;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass App\Services\KeyCloak\Commands\SyncPermissions
 */
class SyncPermissionsTest extends TestCase {
    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        // prepare
        $requests = $this->requests();
        $client   = Http::fake($requests);
        $this->app->instance(Factory::class, $client);

        $command = $this->app->make(SyncPermissions::class);

        $permission = Permission::factory()->create();

        $command->handle();

        $this->assertFalse(Permission::whereKey($permission->getKey())->exists());
        $this->assertTrue(Permission::whereKey('70a74596-08f2-48d1-b3f9-0e4f339bc1b3')->exists());
    }

    /**
     * @return array<string,\GuzzleHttp\Promise\PromiseInterface>
     */
    public function requests(): array {
        return [
            '*' => Http::response(
                [
                    [
                        'id'          => '70a74596-08f2-48d1-b3f9-0e4f339bc1b3',
                        'name'        => 'edit-organization',
                        'description' => 'Can edit own organization (branding, etc).',
                        'composite'   => false,
                        'clientRole'  => true,
                        'containerId' => 'd0e2e1fd-9825-44e8-878f-2d45e9f81f72',
                    ],
                ],
                200,
            ),
        ];
    }
}
