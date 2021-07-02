<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use App\Services\KeyCloak\Client\Types\User;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Client
 */
class ClientTest extends TestCase {
    /**
     *
     * @covers ::getUserByEmail
     *
     * @dataProvider dataProviderGetUserByEmail
     */
    public function testGetUserByEmail(string $email, ?User $expected): void {
        $this->override(Factory::class, static function () {
            return Http::fake([
                'users?email=correct@gmail.com' => Http::response(
                    [
                        [
                            'id'        => 'd8ec7dcf-c542-42b5-8d7d-971400c02388',
                            'firstName' => 'correct',
                        ],
                    ],
                    200,
                ),
                'users?email=wrong@gmail.com'   => Http::response([], 200),
            ]);
        });
        $client   = $this->app->make(Client::class);
        $response = $client->getUserByEmail($email);

        $this->assertEquals($expected, $response);
        if ($client->isEnabled()) {
        } else {
            $this->markTestSkipped('Keycloak client is disabled.');
        }
    }

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderGetUserByEmail(): array {
        return [
            [
                'correct@gmail.com',
                new User([
                    'id'        => 'd8ec7dcf-c542-42b5-8d7d-971400c02388',
                    'firstName' => 'correct',
                ]),
            ],
            ['wrong@gmail.com', null],
        ];
    }
    //</editor-fold>
}
