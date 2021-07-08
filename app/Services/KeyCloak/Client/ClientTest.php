<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Client;

use App\Models\Role;
use App\Services\KeyCloak\Client\Exceptions\UserAlreadyExists;
use App\Services\KeyCloak\Client\Types\Credential;
use App\Services\KeyCloak\Client\Types\Group;
use App\Services\KeyCloak\Client\Types\User;
use Exception;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

use function is_bool;
use function str_contains;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Client\Client
 */
class ClientTest extends TestCase {
    /**
     *
     * @covers ::getUserByEmail
     *
     * @dataProvider dataProviderGetUserByEmail
     */
    public function testGetUserByEmail(string $email, ?User $expected): void {
        $this->prepareClient();
        $this->override(Token::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getAccessToken')
                ->once()
                ->andReturn('token');
        });
        $this->override(Factory::class, static function () {
            return Http::fake([
                'users?email=correct@gmail.com' => Http::response(
                    [
                        [
                            'id'        => 'd8ec7dcf-c542-42b5-8d7d-971400c02388',
                            'firstName' => 'correct',
                        ],
                    ],
                    Response::HTTP_OK,
                ),
                'users?email=wrong@gmail.com'   => Http::response([], Response::HTTP_OK),
            ]);
        });
        $client   = $this->app->make(Client::class);
        $response = $client->getUserByEmail($email);

        $this->assertEquals($expected, $response);
    }

    /**
     *
     * @covers ::inviteUser
     *
     * @dataProvider dataProviderInviteUser
     */
    public function testInviteUser(string $email, bool|Exception $expected): void {
        $this->prepareClient();
        $this->override(Token::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getAccessToken')
                ->twice()
                ->andReturn('token');
        });
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }
        $this->override(Factory::class, static function () {
            return Http::fake(static function (Request $request) {
                if (str_contains($request->url(), 'groups')) {
                    return Http::response(['path' => 'test'], Response::HTTP_OK);
                } elseif (str_contains($request->url(), 'users')) {
                    $data = $request->data();
                    if ($data['email'] === 'correct@gmail.com') {
                        return Http::response(
                            [
                                [
                                    'id'        => 'd8ec7dcf-c542-42b5-8d7d-971400c02388',
                                    'firstName' => 'correct',
                                ],
                            ],
                            Response::HTTP_OK,
                        );
                    } else {
                        return Http::response(null, Response::HTTP_CONFLICT);
                    }
                }
            });
        });
        $client   = $this->app->make(Client::class);
        $role     = Role::factory()->create();
        $response = $client->inviteUser($role, $email);
        if (is_bool($expected)) {
            $this->assertEquals($expected, $response);
        }
    }
    /*
     * @covers ::requestResetPassword
     */
    public function testRequestResetPassword(): void {
        $this->setSettings([
            'ep.keycloak.url'           => $this->faker->url,
            'ep.keycloak.client_id'     => $this->faker->uuid,
            'ep.keycloak.client_secret' => $this->faker->uuid,
        ]);
        $this->override(Client::class, static function (MockInterface $mock): void {
            $mock->makePartial();
            $mock->shouldAllowMockingProtectedMethods();
            $mock
                ->shouldReceive('call')
                ->with(
                    'users/f9834bc1-2f2f-4c57-bb8d-7a224ac24982/execute-actions-email',
                    'PUT',
                    ['json' => ['UPDATE_PASSWORD']],
                )
                ->once()
                ->andReturns();
        });
        $client = $this->app->make(Client::class);
        $client->requestResetPassword('f9834bc1-2f2f-4c57-bb8d-7a224ac24982');
    }

    /**
     * @covers ::getUserGroups
     */
    public function testGetUserGroups(): void {
        $this->prepareClient();
        $this->override(Token::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getAccessToken')
                ->once()
                ->andReturn('token');
        });
        $this->override(Factory::class, static function () {
            return Http::fake([
                '*' => Http::response(
                    [
                        [
                            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02388',
                            'name' => 'test',
                            'path' => 'group/test',
                        ],
                    ],
                    Response::HTTP_OK,
                ),
            ]);
        });
        $client   = $this->app->make(Client::class);
        $group    = new Group([
            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02388',
            'name' => 'test',
            'path' => 'group/test',
        ]);
        $response = $client->getUserGroups($this->faker->uuid);
        $this->assertEquals($response, [$group]);
        $this->setSettings([
            'ep.keycloak.url'           => $this->faker->url,
            'ep.keycloak.client_id'     => $this->faker->uuid,
            'ep.keycloak.client_secret' => $this->faker->uuid,
        ]);
        $this->override(Client::class, static function (MockInterface $mock): void {
            $mock->makePartial();
            $mock->shouldAllowMockingProtectedMethods();
            $mock
                ->shouldReceive('call')
                ->with(
                    'users/f9834bc1-2f2f-4c57-bb8d-7a224ac24982/groups',
                    'GET',
                )
                ->once()
                ->andReturns([]);
        });
        $client = $this->app->make(Client::class);
        $client->getUserGroups('f9834bc1-2f2f-4c57-bb8d-7a224ac24982');
    }

    /*
     * @covers ::resetPassword
     */
    public function testResetPassword(): void {
        $this->setSettings([
            'ep.keycloak.url'           => $this->faker->url,
            'ep.keycloak.client_id'     => $this->faker->uuid,
            'ep.keycloak.client_secret' => $this->faker->uuid,
        ]);
        $this->override(Client::class, static function (MockInterface $mock): void {
            $credentials = new Credential([
                'type'      => 'password',
                'temporary' => false,
                'value'     => '1234567',
            ]);
            $mock->makePartial();
            $mock->shouldAllowMockingProtectedMethods();
            $mock
                ->shouldReceive('call')
                ->with(
                    'users/f9834bc1-2f2f-4c57-bb8d-7a224ac24982/reset-password',
                    'PUT',
                    ['json' => $credentials->toArray()],
                )
                ->once()
                ->andReturn(true);
        });
        $client = $this->app->make(Client::class);

        $this->assertTrue($client->resetPassword('f9834bc1-2f2f-4c57-bb8d-7a224ac24982', '1234567'));
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

    /**
     * @return array<mixed>
     */
    public function dataProviderInviteUser(): array {
        return [
            ['correct@gmail.com', true],
            ['wrong@gmail.com', new UserAlreadyExists('wrong@gmail.com')],
        ];
    }
    //</editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function prepareClient(): void {
        $this->setSettings([
            'ep.keycloak.url'           => $this->faker->url,
            'ep.keycloak.client_id'     => $this->faker->word,
            'ep.keycloak.client_secret' => $this->faker->uuid,
            'ep.keycloak.client_uuid'   => $this->faker->uuid,
        ]);
    }
    //</editor-fold>
}
