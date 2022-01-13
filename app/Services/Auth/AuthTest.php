<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\Auth\Contracts\Enableable;
use App\Services\Auth\Contracts\HasPermissions;
use App\Services\Auth\Contracts\Rootable;
use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
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
     */
    public function testIsRoot(bool $expected, Closure $userFactory): void {
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
        $auth = new class() extends Auth {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

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
        bool $isEnabled,
        bool $isRoot,
        string $permission,
        bool|null $can,
    ): void {
        $gate = $this->app->make(Gate::class);
        $user = null;

        if (!is_null($permissions)) {
            $user = Mockery::mock(Authenticatable::class, Enableable::class, Rootable::class, HasPermissions::class);
            $user
                ->shouldReceive('isEnabled')
                ->once()
                ->andReturn($isEnabled);

            if ($isEnabled) {
                $user
                    ->shouldReceive('isRoot')
                    ->once()
                    ->andReturn($isRoot);
            }

            if ($isEnabled && !$isRoot) {
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
                static function () {
                    return null;
                },
            ],
            'user is not Root'      => [
                false,
                static function () {
                    $user = Mockery::mock(Authenticatable::class, Rootable::class);
                    $user
                        ->shouldReceive('isRoot')
                        ->once()
                        ->andReturn(false);

                    return $user;
                },
            ],
            'keycloak user is root' => [
                false,
                static function () {
                    return User::factory()->make([
                        'type' => UserType::keycloak(),
                    ]);
                },
            ],
            'local user is root'    => [
                true,
                static function () {
                    return User::factory()->make([
                        'type' => UserType::local(),
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
                true,
                false,
                'a',
                true,
            ],
            'gate defined - guest without can'        => [
                false,
                null,
                true,
                false,
                'a',
                null,
            ],
            'user - without can - without permission' => [
                false,
                ['a'],
                true,
                false,
                'b',
                null,
            ],
            'user - without can - with permission'    => [
                true,
                ['a'],
                true,
                false,
                'a',
                null,
            ],
            'user - can - without permission'         => [
                false,
                ['a'],
                true,
                false,
                'b',
                true,
            ],
            'user - cannot - without permission'      => [
                false,
                ['a'],
                true,
                false,
                'b',
                false,
            ],
            'user - can - with permission'            => [
                true,
                ['a'],
                true,
                false,
                'a',
                true,
            ],
            'user - cannot - with permission'         => [
                false,
                ['a'],
                true,
                false,
                'a',
                false,
            ],
            'root - without can - without permission' => [
                true,
                ['a'],
                true,
                true,
                'b',
                null,
            ],
            'root - can - without permission'         => [
                true,
                ['a'],
                true,
                true,
                'b',
                true,
            ],
            'root - cannot - without permission'      => [
                true,
                ['a'],
                true,
                true,
                'b',
                false,
            ],
            'root - cannot - with permission'         => [
                true,
                ['a'],
                true,
                true,
                'a',
                false,
            ],
            'disabled user - can - with permission'   => [
                false,
                ['a'],
                false,
                false,
                'a',
                true,
            ],
            'disabled root'                           => [
                false,
                ['a'],
                false,
                true,
                'a',
                true,
            ],
        ];
    }
    //</editor-fold>
}
