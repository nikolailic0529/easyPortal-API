<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Mockery;
use Tests\TestCase;

use function is_null;

/**
 * @internal
 * @coversDefaultClass \App\Services\Auth\Auth
 */
class AuthTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::isRoot
     *
     * @dataProvider dataProviderIsRoot
     *
     * @param array<string,mixed> $settings
     */
    public function testIsRoot(bool $expected, array $settings, Closure $userFactory): void {
        $this->setSettings($settings);

        $this->assertEquals($expected, $this->app->make(Auth::class)->isRoot($userFactory($this)));
    }

    /**
     * @covers ::hasPermission
     *
     * @dataProvider dataProviderHasPermission
     *
     * @param array<string> $permissions
     */
    public function testHasPermission(bool $expected, array|null $permissions, string $permission): void {
        $user = null;
        $auth = new class($this->app->make(Repository::class)) extends Auth {
            public function hasPermission(?Authenticatable $user, string $permission): bool {
                return parent::hasPermission($user, $permission);
            }
        };

        if (!is_null($permissions)) {
            $user = Mockery::mock(Authenticatable::class, HasPermissions::class);
            $user
                ->shouldReceive('getPermissions')
                ->once()
                ->andReturn($permissions);
        }

        $this->assertEquals($expected, $auth->hasPermission($user, $permission));
    }

    /**
     * @covers ::gateBefore
     * @covers ::gateAfter
     *
     * @dataProvider dataProviderGate
     *
     * @param array<string> $permissions
     */
    public function testGate(
        bool $expected,
        array|null $permissions,
        bool $isRoot,
        string $permission,
        bool|null $can,
    ): void {
        $gate = $this->app->make(Gate::class);
        $user = null;

        if (!is_null($permissions)) {
            $id   = $this->faker->uuid;
            $user = Mockery::mock(Authenticatable::class, HasPermissions::class);
            $user
                ->shouldReceive('getAuthIdentifier')
                ->once()
                ->andReturn($id);

            if ($isRoot) {
                $this->setSettings([
                    'ep.root_users' => [$id],
                ]);
            } else {
                $user
                    ->shouldReceive('getPermissions')
                    ->once()
                    ->andReturn($permissions);
            }
        }

        if (!is_null($can)) {
            $gate->define($permission, static function () use ($can) {
                return $can;
            });
        }

        $this->assertEquals($expected, $gate->forUser($user)->allows($permission));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderIsRoot(): array {
        return [
            'no settings - no user' => [
                false,
                [],
                static function () {
                    return null;
                },
            ],
            'user is not root'      => [
                false,
                [
                    'ep.root_users' => [
                        '96948814-7626-4aab-a5a8-f0b7b4be8e6d',
                        'f470ecc9-1394-4f95-bfa2-435307f9c4f3',
                    ],
                ],
                static function () {
                    return User::factory()->make([
                        'id' => 'da83c04b-5273-418f-ad78-134324cc1c01',
                    ]);
                },
            ],
            'user is root'          => [
                true,
                [
                    'ep.root_users' => [
                        '96948814-7626-4aab-a5a8-f0b7b4be8e6d',
                        'f470ecc9-1394-4f95-bfa2-435307f9c4f3',
                    ],
                ],
                static function () {
                    return User::factory()->make([
                        'id' => 'f470ecc9-1394-4f95-bfa2-435307f9c4f3',
                    ]);
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderHasPermission(): array {
        return [
            'guest and no permissions'     => [
                false,
                null,
                '',
            ],
            'user with valid permission'   => [
                true,
                ['a', 'b'],
                'a',
            ],
            'user with invalid permission' => [
                false,
                ['a', 'b'],
                'c',
            ],
            'user with empty permission'   => [
                false,
                [],
                '',
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderGate(): array {
        return [
            'gate defined - guest with can'           => [
                false,
                null,
                false,
                'a',
                true,
            ],
            'gate defined - guest with can'           => [
                false,
                null,
                false,
                'a',
                null,
            ],
            'user - without can - without permission' => [
                false,
                ['a'],
                false,
                'b',
                null,
            ],
            'user - without can - with permission'    => [
                true,
                ['a'],
                false,
                'a',
                null,
            ],
            'user - can - without permission'         => [
                false,
                ['a'],
                false,
                'b',
                true,
            ],
            'user - cannot - without permission'      => [
                false,
                ['a'],
                false,
                'b',
                false,
            ],
            'user - can - with permission'            => [
                true,
                ['a'],
                false,
                'a',
                true,
            ],
            'user - cannot - with permission'         => [
                false,
                ['a'],
                false,
                'a',
                false,
            ],
            'root - without can - without permission' => [
                true,
                ['a'],
                true,
                'b',
                null,
            ],
            'root - can - without permission'         => [
                true,
                ['a'],
                true,
                'b',
                true,
            ],
            'root - cannot - without permission'      => [
                true,
                ['a'],
                true,
                'b',
                false,
            ],
            'root - cannot - with permission'         => [
                true,
                ['a'],
                true,
                'a',
                false,
            ],
        ];
    }
    //</editor-fold>
}
