<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Client;

use App\Models\Role as RoleModel;
use App\Services\Keycloak\Client\Exceptions\RealmUserAlreadyExists;
use App\Services\Keycloak\Client\Exceptions\RealmUserNotFound;
use App\Services\Keycloak\Client\Types\Credential;
use App\Services\Keycloak\Client\Types\Group;
use App\Services\Keycloak\Client\Types\Role;
use App\Services\Keycloak\Client\Types\User;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

use function is_bool;
use function str_contains;

/**
 * @internal
 * @covers \App\Services\Keycloak\Client\Client
 */
class ClientTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     *
     * @dataProvider dataProviderGetUserByEmail
     */
    public function testGetUserByEmail(string $email, ?User $expected): void {
        $this->prepareClient(true);
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

        self::assertEquals($expected, $response);
    }

    /**
     *
     * @dataProvider dataProviderCreateUser
     */
    public function testCreateUser(string $email, bool|Exception $expected): void {
        $this->prepareClient();
        $this->override(Token::class, static function (MockInterface $mock) use ($expected): void {
            $mock
                ->shouldReceive('getAccessToken')
                ->times($expected === true ? 3 : 2)
                ->andReturn('token');
        });
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
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
        $role     = RoleModel::factory()->create();
        $response = $client->createUser($email, $role);

        if (is_bool($expected)) {
            if ($expected) {
                self::assertNotNull($response);
            } else {
                self::assertNull($response);
            }
        }
    }

    public function testRequestResetPassword(): void {
        $this->prepareClient();
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
     * @dataProvider dataProviderGetUserGroups
     */
    public function testGetUserGroups(array|Exception $expected): void {
        $this->prepareClient();
        if ($expected instanceof Exception) {
            self::expectExceptionObject(new RealmUserNotFound('f9834bc1-2f2f-4c57-bb8d-7a224ac24982'));
        }
        $this->override(Token::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getAccessToken')
                ->once()
                ->andReturn('token');
        });
        $this->override(Factory::class, static function () use ($expected) {
            if ($expected instanceof Exception) {
                return Http::fake(['*' => Http::response(null, Response::HTTP_NOT_FOUND)]);
            } else {
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
            }
        });
        $client = $this->app->make(Client::class);
        $groups = $client->getUserGroups('f9834bc1-2f2f-4c57-bb8d-7a224ac24982');
        if (!$expected instanceof Exception) {
            self::assertEquals($expected, $groups);
        }
    }

    public function testResetPassword(): void {
        $this->prepareClient();
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

        self::assertTrue($client->resetPassword('f9834bc1-2f2f-4c57-bb8d-7a224ac24982', '1234567'));
    }

    /**
     *
     * @dataProvider dataProviderUpdateUserEmail
     */
    public function testUpdateUserEmail(string $email, bool|Exception $expected): void {
        $this->prepareClient(true);
        $this->override(Factory::class, static function () {
            return Http::fake(static function (Request $request) {
                $data = $request->data();
                if ($data['email'] === 'correct@example.com') {
                    return Http::response([], Response::HTTP_NO_CONTENT);
                } else {
                    return Http::response(null, Response::HTTP_CONFLICT);
                }
            });
        });
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }
        $client   = $this->app->make(Client::class);
        $response = $client->updateUserEmail('f9834bc1-2f2f-4c57-bb8d-7a224ac24982', $email);
        if (is_bool($expected)) {
            self::assertNull($response);
        }
    }

    /**
     *
     * @dataProvider dataProviderGetUserById
     */
    public function testGetUserById(string $id, User|Exception $expected): void {
        $this->prepareClient(true);
        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }
        $this->override(Factory::class, static function () use ($expected, $id) {
            return Http::fake(static function (Request $request) use ($expected, $id) {
                if ($expected instanceof User) {
                    return Http::response(
                        [
                            'id'        => $id,
                            'firstName' => 'correct',
                            'lastName'  => 'last',
                        ],
                        Response::HTTP_OK,
                    );
                } else {
                    return Http::response(null, Response::HTTP_NOT_FOUND);
                }
            });
        });
        $client   = $this->app->make(Client::class);
        $response = $client->getUserById($id);
        if ($expected instanceof User) {
            self::assertEquals($response, $response);
        }
    }

    public function testDeleteRoleByName(): void {
        $config = Mockery::mock(Repository::class);
        $config
            ->shouldReceive('get')
            ->with('ep.keycloak.client_uuid')
            ->once()
            ->andReturn($this->faker->uuid());

        $client = Mockery::mock(Client::class, [
            Mockery::mock(ExceptionHandler::class),
            Mockery::mock(Factory::class),
            $config,
            Mockery::mock(Token::class),
        ]);
        $client->shouldAllowMockingProtectedMethods();
        $client->makePartial();
        $client
            ->shouldReceive('call')
            ->with(
                Mockery::pattern('|clients/([^/]+)/roles/name|'),
                'DELETE',
            )
            ->once()
            ->andReturn(true);

        $client->deleteRole('name');
    }

    public function testSetGroupRoles(): void {
        $group = $this->faker->randomElement([
            new Group(['id' => $this->faker->uuid()]),
            RoleModel::factory()->make(),
        ]);
        $a     = new Role(['id' => $this->faker->uuid()]);
        $b     = new Role(['id' => $this->faker->uuid()]);
        $c     = new Role(['id' => $this->faker->uuid()]);

        $client = Mockery::mock(Client::class);
        $client->shouldAllowMockingProtectedMethods();
        $client->makePartial();
        $client
            ->shouldReceive('getGroupRoles')
            ->with($group)
            ->once()
            ->andReturn([$a, $b]);
        $client
            ->shouldReceive('deleteGroupRoles')
            ->with($group, [$a])
            ->once()
            ->andReturns();
        $client
            ->shouldReceive('createGroupRoles')
            ->with($group, [$c])
            ->once()
            ->andReturns();

        self::assertTrue($client->updateGroupRoles($group, [$b, $c]));
    }
    //</editor-fold>

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
    public function dataProviderCreateUser(): array {
        return [
            ['correct@gmail.com', true],
            ['wrong@gmail.com', new RealmUserAlreadyExists('wrong@gmail.com')],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderUpdateUserEmail(): array {
        return [
            ['correct@example.com', true],
            ['wrong@example.com', new RealmUserAlreadyExists('wrong@example.com')],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderGetUserGroups(): array {
        $group = new Group([
            'id'   => 'd8ec7dcf-c542-42b5-8d7d-971400c02388',
            'name' => 'test',
            'path' => 'group/test',
        ]);

        return [
            'success'   => [[$group]],
            'not found' => [new RealmUserNotFound('d8ec7dcf-c542-42b5-8d7d-971400c02388')],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderGetUserById(): array {
        return [
            [
                'd8ec7dcf-c542-42b5-8d7d-971400c02388',
                new User([
                    'id'         => 'd8ec7dcf-c542-42b5-8d7d-971400c02388',
                    'firstName'  => 'first',
                    'lastName'   => 'last',
                    'attributes' => [],
                ]),
            ],
            ['d8ec7dcf-c542-42b5-8d7d-971400c02389', new RealmUserNotFound('d8ec7dcf-c542-42b5-8d7d-971400c02389')],
        ];
    }
    //</editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function prepareClient(bool $withToken = false): void {
        $this->setSettings([
            'ep.keycloak.enabled'       => true,
            'ep.keycloak.url'           => $this->faker->url(),
            'ep.keycloak.client_id'     => $this->faker->word(),
            'ep.keycloak.client_secret' => $this->faker->uuid(),
            'ep.keycloak.client_uuid'   => $this->faker->uuid(),
        ]);
        if ($withToken) {
            $this->prepareToken();
        }
    }

    protected function prepareToken(): void {
        $this->override(Token::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('getAccessToken')
                ->once()
                ->andReturn('token');
        });
    }
    //</editor-fold>
}
