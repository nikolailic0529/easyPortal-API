<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\Contracts\Rootable;
use App\Services\Auth\Permissions\Markers\IsRoot;
use App\Services\Organization\RootOrganization;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

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
        self::assertEquals($expected, $this->app->make(Auth::class)->isRoot($userFactory($this)));
    }

    /**
     * @covers ::getUser
     */
    public function testGetUser(): void {
        $user  = new User();
        $guard = Mockery::mock(Guard::class);

        $guard
            ->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->override(Factory::class, static function (MockInterface $mock) use ($guard): void {
            $mock
                ->shouldReceive('guard')
                ->withNoArgs()
                ->once()
                ->andReturn($guard);
        });

        $auth   = $this->app->make(Auth::class);
        $actual = $auth->getUser();

        self::assertSame($user, $actual);
    }

    /**
     * @covers ::getUser
     */
    public function testGetUserAuthenticatable(): void {
        $user  = Mockery::mock(Authenticatable::class);
        $guard = Mockery::mock(Guard::class);

        $guard
            ->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->override(Factory::class, static function (MockInterface $mock) use ($guard): void {
            $mock
                ->shouldReceive('guard')
                ->withNoArgs()
                ->once()
                ->andReturn($guard);
        });

        $auth   = $this->app->make(Auth::class);
        $actual = $auth->getUser();

        self::assertNull($actual);
    }

    /**
     * @covers ::getAvailablePermissions
     *
     * @dataProvider dataProviderGetAvailablePermissions
     *
     * @param array<Permission> $expected
     * @param array<Permission> $permissions
     */
    public function testGetAvailablePermissions(array $expected, array $permissions, bool $isRootOrganization): void {
        $rootOrganization = Mockery::mock(RootOrganization::class);
        $rootOrganization
            ->shouldReceive('is')
            ->once()
            ->andReturn($isRootOrganization);

        $permissions = $this->app->make(Permissions::class)->set($permissions);

        $service = new class($rootOrganization, $permissions) extends Auth {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected RootOrganization $rootOrganization,
                protected Permissions $permissions,
            ) {
                // empty
            }
        };

        $actual = $service->getAvailablePermissions(new Organization());

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::getOrganizationUserPermissions
     */
    public function testGetOrganizationUserPermissions(): void {
        $org  = Organization::factory()->make();
        $auth = Mockery::mock(Auth::class);
        $auth->makePartial();
        $auth
            ->shouldReceive('getAvailablePermissions')
            ->with($org)
            ->once()
            ->andReturn(['a', 'b', 'c']);
        $user = Mockery::mock(User::class);
        $user
            ->shouldReceive('getOrganizationPermissions')
            ->with($org)
            ->once()
            ->andReturn(['a', 'b', 'd']);

        self::assertEquals(['a', 'b'], $auth->getOrganizationUserPermissions($org, $user));
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
     * @return array<string,mixed>
     */
    public function dataProviderGetAvailablePermissions(): array {
        $a = new class('permission-a') extends Permission implements IsRoot {
            // empty,
        };
        $b = new class('permission-b') extends Permission {
            // empty,
        };

        return [
            'organization'      => [
                [$b->getName()],
                [$a, $b],
                false,
            ],
            'root organization' => [
                [$a->getName(), $b->getName()],
                [$a, $b],
                true,
            ],
        ];
    }
    //</editor-fold>
}
