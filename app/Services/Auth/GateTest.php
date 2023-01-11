<?php declare(strict_types = 1);

namespace App\Services\Auth;

use App\Models\Organization;
use App\Services\Auth\Contracts\Enableable;
use App\Services\Auth\Contracts\HasPermissions;
use App\Services\Auth\Contracts\Permissions\Composite;
use App\Services\Auth\Contracts\Rootable;
use App\Services\Auth\Permissions\AssetsView;
use App\Services\Auth\Permissions\CustomersView;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Mockery;
use Tests\TestCase;

use function is_null;

/**
 * @internal
 * @covers \App\Services\Auth\Gate
 */
class GateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderHasPermission
     *
     * @param array<Permission>|null $permissions
     * @param array<string>|null     $orgPermissions
     * @param array<string>|null     $userPermissions
     */
    public function testHasPermission(
        bool $expected,
        array|null $permissions,
        array|null $orgPermissions,
        array|null $userPermissions,
        string $permission,
    ): void {
        $org  = null;
        $user = null;
        $auth = Mockery::mock(Auth::class)->makePartial();
        $gate = new class($auth, Mockery::mock(CurrentOrganization::class)) extends Gate {
            public function hasPermission(?Organization $org, ?Authenticatable $user, string $permission): bool {
                return parent::hasPermission($org, $user, $permission);
            }
        };

        if ($permissions !== null) {
            $auth
                ->shouldReceive('getPermissions')
                ->times($orgPermissions ? 1 : 2)
                ->andReturn($permissions);
        }

        if ($orgPermissions !== null) {
            $org = Mockery::mock(Organization::class);
            $auth
                ->shouldReceive('getAvailablePermissions')
                ->with($org)
                ->once()
                ->andReturn($orgPermissions);
        }

        if ($userPermissions !== null) {
            $user = Mockery::mock(Authenticatable::class, HasPermissions::class);
            $user
                ->shouldReceive('getPermissions')
                ->once()
                ->andReturn($userPermissions);
        }

        self::assertEquals($expected, $gate->hasPermission($org, $user, $permission));
    }

    /**
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
        $gate = $this->app->make(GateContract::class);
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

        self::assertEquals($expected, $gate->forUser($user)->allows($permission));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================/**
    /**
     * @return array<mixed>
     */
    public function dataProviderHasPermission(): array {
        $a = new class('a') extends Permission {
            // empty,
        };
        $b = new class('b') extends Permission {
            // empty,
        };
        $c = new class('c') extends Permission implements Composite {
            /**
             * @inheritDoc
             */
            public function getPermissions(): array {
                return [
                    new class('a') extends Permission {
                        // empty,
                    },
                ];
            }
        };

        return [
            'guest and no permissions'                                 => [
                false,
                null,
                null,
                null,
                '',
            ],
            'user with valid permission'                               => [
                true,
                [$a, $b],
                null,
                ['a', 'b'],
                'a',
            ],
            'user with invalid permission'                             => [
                false,
                [$a, $b],
                null,
                ['a', 'b'],
                'c',
            ],
            'user with empty permission'                               => [
                false,
                [$a, $b],
                null,
                [],
                '',
            ],
            'user with valid permission + not related to organization' => [
                false,
                [$a, $b],
                ['b'],
                ['a', 'b'],
                'a',
            ],
            'user with valid permission + related to organization'     => [
                true,
                [$a, $b],
                ['a'],
                ['a', 'b'],
                'a',
            ],
            'user with valid permission + expand'                      => [
                true,
                [$a, $b, $c],
                ['a', 'b', 'c'],
                ['c'],
                'a',
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderGate(): array {
        $a = (new AssetsView())->getName();
        $b = (new CustomersView())->getName();

        return [
            'gate defined - guest with can'           => [
                false,
                null,
                true,
                false,
                $a,
                true,
            ],
            'gate defined - guest without can'        => [
                false,
                null,
                true,
                false,
                $a,
                null,
            ],
            'user - without can - without permission' => [
                false,
                [$a],
                true,
                false,
                $b,
                null,
            ],
            'user - without can - with permission'    => [
                true,
                [$a],
                true,
                false,
                $a,
                null,
            ],
            'user - can - without permission'         => [
                false,
                [$a],
                true,
                false,
                $b,
                true,
            ],
            'user - cannot - without permission'      => [
                false,
                [$a],
                true,
                false,
                $b,
                false,
            ],
            'user - can - with permission'            => [
                true,
                [$a],
                true,
                false,
                $a,
                true,
            ],
            'user - cannot - with permission'         => [
                false,
                [$a],
                true,
                false,
                $a,
                false,
            ],
            'root - without can - without permission' => [
                true,
                [$a],
                true,
                true,
                $b,
                null,
            ],
            'root - can - without permission'         => [
                true,
                [$a],
                true,
                true,
                $b,
                true,
            ],
            'root - cannot - without permission'      => [
                true,
                [$a],
                true,
                true,
                $b,
                false,
            ],
            'root - cannot - with permission'         => [
                true,
                [$a],
                true,
                true,
                $a,
                false,
            ],
            'disabled user - can - with permission'   => [
                false,
                [$a],
                false,
                false,
                $a,
                true,
            ],
            'disabled root'                           => [
                false,
                [$a],
                false,
                true,
                'a',
                true,
            ],
        ];
    }
    //</editor-fold>
}
