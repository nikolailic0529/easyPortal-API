<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\KeyCloak\Client\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
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
        $requests = $this->requests($this);
        $client   = Http::fake($requests);
        $this->app->instance(Factory::class, $client);
        $auth    = $this->app->make(Auth::class);
        $command = $this->app->make(SyncPermissions::class);

        $permission = Permission::factory()->create();

        $command->handle();

        $this->assertFalse(Permission::whereKey($permission->getKey())->exists());
        $this->assertCount(Permission::count(), $auth->getPermissions());
    }

    /**
     * @return array<string,\GuzzleHttp\Promise\PromiseInterface>
     */
    public function requests(TestCase $test): array {
        $client   = $test->app->make(Client::class);
        $baseUrl  = $client->getBaseUrl();
        $clientId = (string) $test->app->make(Repository::class)->get('ep.keycloak.client_uuid');
        return [
            "{$baseUrl}/clients/{$clientId}/roles" => static function (Request $request) use ($test) {
                if ($request->method() === 'POST') {
                    $data = $request->data();
                    return Http::response([
                        'id'   => $test->faker->uuid(),
                        'name' => $data['name'],
                    ], 201);
                } elseif ($request->method() === 'GET') {
                    return Http::response(
                        [
                            [
                                'id'   => '70a74596-08f2-48d1-b3f9-0e4f339bc1b2',
                                'name' => 'role',
                            ],
                        ],
                        200,
                    );
                } else {
                    // empty
                }
            },
            '*'                                    => Http::response([], 200),
        ];
    }
}
