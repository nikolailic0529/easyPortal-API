<?php declare(strict_types = 1);

namespace App\Services\Tenant\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Services\Tenant\CurrentTenant;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Tenant\Http\Middleware\SetTenant
 */
class SetTenantTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $tenant     = Organization::factory()->make();
        $request    = new Request();
        $middleware = new class($this->app, $tenant) extends SetTenant {
            protected ?Organization $tenant;

            public function __construct(Application $app, ?Organization $tenant) {
                $this->tenant = $tenant;

                parent::__construct($app);
            }

            protected function getTenantFromRequest(Request $request): ?Organization {
                return $this->tenant;
            }
        };

        // CurrentTenant will be set only within middleware, so it should be
        // undefined at this point.
        $this->assertFalse($this->app->bound(CurrentTenant::class));

        // Within middleware it should be defined
        $spy = Mockery::spy(
            function () use ($tenant): void {
                $current = $this->app->get(CurrentTenant::class);

                $this->assertTrue($current->has());
                $this->assertEquals($tenant, $current->get());
            },
        );

        $middleware->handle(
            $request,
            static function () use ($spy): mixed {
                return $spy();
            },
        );

        $spy->shouldHaveBeenCalled()->once();

        // After request it should be undefined again
        $this->assertFalse($this->app->bound(CurrentTenant::class));
    }

    /**
     * @covers ::handle
     */
    public function testHandleNoTenant(): void {
        $request    = new Request();
        $middleware = new class($this->app) extends SetTenant {
            protected function getTenantFromRequest(Request $request): ?Organization {
                return null;
            }
        };

        // CurrentTenant will be set only within middleware, so it should be
        // undefined at this point.
        $this->assertFalse($this->app->bound(CurrentTenant::class));

        // Within middleware it should not be defined
        $spy = Mockery::spy(
            function (): void {
                $this->assertFalse($this->app->bound(CurrentTenant::class));
            },
        );

        $middleware->handle(
            $request,
            static function () use ($spy): mixed {
                return $spy();
            },
        );

        $spy->shouldHaveBeenCalled()->once();

        // After request it should be still undefined
        $this->assertFalse($this->app->bound(CurrentTenant::class));
    }

    /**
     * @covers ::getTenantFromRequest
     *
     * @dataProvider dataProviderGetTenantFromRequest
     */
    public function testGetTenantFromRequest(
        string|null $expected,
        Closure $userFactory,
    ): void {
        $user    = $userFactory($this);
        $request = Mockery::mock(Request::class);
        $request
            ->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $middleware = new class($this->app) extends SetTenant {
            public function getTenantFromRequest(Request $request): ?Organization {
                return parent::getTenantFromRequest($request);
            }
        };
        $tenant     = $middleware->getTenantFromRequest($request);

        if ($expected) {
            $this->assertNotNull($tenant);
            $this->assertEquals($user->organization, $tenant);
        } else {
            $this->assertNull($tenant);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{string|null, \Closure}>
     */
    public function dataProviderGetTenantFromRequest(): array {
        return [
            'no user'                => [
                null,
                static function (): ?User {
                    return null;
                },
            ],
            'user with organization' => [
                'd15489a5-4508-4394-abb3-213538ceb105',
                static function (): ?User {
                    return User::factory()->make([
                        'organization_id' => static function (): Organization {
                            return Organization::factory()->create([
                                'id' => 'd15489a5-4508-4394-abb3-213538ceb105',
                            ]);
                        },
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
