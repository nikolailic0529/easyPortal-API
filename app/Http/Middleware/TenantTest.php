<?php declare(strict_types = 1);

namespace App\Http\Middleware;

use App\CurrentTenant;
use App\Models\Organization;
use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Middleware\Tenant
 */
class TenantTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $tenant     = Organization::factory()->make();
        $request    = new Request();
        $middleware = new class($this->app, $tenant) extends Tenant {
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
        $this->assertFalse($this->app->get(CurrentTenant::class)->has());

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
        $this->assertFalse($this->app->get(CurrentTenant::class)->has());
    }

    /**
     * @covers ::handle
     */
    public function testHandleNoTenant(): void {
        $request    = new Request();
        $middleware = new class($this->app) extends Tenant {
            protected function getTenantFromRequest(Request $request): ?Organization {
                return null;
            }
        };

        $this->expectExceptionObject(new NotFoundHttpException());

        $middleware->handle(
            $request,
            static function (): mixed {
                return null;
            },
        );
    }

    /**
     * @covers ::getTenantFromRequest
     *
     * @dataProvider dataProviderGetTenantFromRequest
     */
    public function testGetTenantFromRequest(
        bool $expected,
        ?string $domain,
        ?string $host,
        Closure $organizationFactory,
    ): void {
        $organization = $organizationFactory();
        $middleware   = new class($this->app) extends Tenant {
            public function getTenantFromRequest(Request $request): ?Organization {
                return parent::getTenantFromRequest($request);
            }
        };
        $request      = new class($domain, $host) extends Request {
            public function __construct(?string $domain, ?string $host) {
                /** @noinspection PhpNamedArgumentMightBeUnresolvedInspection */
                parent::__construct(server: [
                    'SERVER_NAME' => $domain,
                    'HTTP_HOST'   => $host,
                ]);
            }
        };

        if ($organization->subdomain !== Organization::ROOT) {
            Organization::factory()->root()->create();
        }

        $actual = $middleware->getTenantFromRequest($request);

        if ($expected) {
            $this->assertNotNull($actual);
            $this->assertEquals($organization, $actual);
        } else {
            $this->assertNull($actual);
        }
    }

    /**
     * @covers ::getTenantNameFromDomain
     *
     * @dataProvider dataProviderGetTenantNameFromDomain
     */
    public function testGetTenantNameFromDomain(?string $expected, string $domain): void {
        $middleware = new class($this->app) extends Tenant {
            public function getTenantNameFromDomain(string $domain): ?string {
                return parent::getTenantNameFromDomain($domain);
            }
        };
        $actual     = $middleware->getTenantNameFromDomain($domain);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::isWildcardDomain
     *
     * @dataProvider dataProviderIsWildcardDomain
     */
    public function testIsWildcardDomain(bool $expected, string $env, string $domain): void {
        $this->app['env'] = $env;
        $middleware       = new class($this->app) extends Tenant {
            public function isWildcardDomain(string $domain): bool {
                return parent::isWildcardDomain($domain);
            }
        };
        $actual           = $middleware->isWildcardDomain($domain);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::isRootDomain
     *
     * @dataProvider dataProviderIsRootDomain
     */
    public function testIsRootDomain(bool $expected, string $env, string $domain): void {
        $this->app['env'] = $env;
        $middleware       = new class($this->app) extends Tenant {
            public function isRootDomain(string $domain): bool {
                return parent::isRootDomain($domain);
            }
        };
        $actual           = $middleware->isRootDomain($domain);

        $this->assertEquals($expected, $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{bool, string|null, string|null, \Closure}>
     */
    public function dataProviderGetTenantFromRequest(): array {
        $tenant  = 'tenant-test';
        $root    = static function (): Organization {
            return Organization::factory()->root()->create();
        };
        $factory = static function () use ($tenant): Organization {
            return Organization::factory()->create([
                'subdomain' => $tenant,
            ]);
        };

        return [
            'no domain + no host'                  => [true, null, null, $root],
            'no domain + example.com '             => [true, null, 'example.com', $root],
            'domain + no host'                     => [true, 'example.com', null, $root],
            'domain + example.com'                 => [true, 'example.com', 'example.com', $root],
            'ip + no host'                         => [true, '127.0.0.1', null, $root],
            'ip + example.com'                     => [true, '127.0.0.1', 'example.com', $root],
            'no domain + no host + no root'        => [true, null, null, $root],
            'no domain + example.com + no root'    => [true, null, 'example.com', $root],
            'no domain + sub.example.com'          => [true, null, 'sub.example.com', $root],
            'domain + no host  + no root'          => [true, 'example.com', null, $root],
            'domain + example.com + no root'       => [true, 'example.com', 'example.com', $root],
            'domain + sub.example.com'             => [true, 'example.com', 'sub.example.com', $root],
            'ip + no host  + no root'              => [true, '127.0.0.1', null, $root],
            'ip + example.com + no root'           => [true, '127.0.0.1', 'example.com', $root],
            'sub domain + no host'                 => [false, 'sub.example.com', null, $factory],
            'sub domain + example.com'             => [false, 'sub.example.com', 'example.com', $factory],
            'sub domain + sub.example.com'         => [false, 'sub.example.com', "{$tenant}.example.com", $factory],
            'tenant domain + no host'              => [true, "{$tenant}.example.com", null, $factory],
            'tenant domain + example.com'          => [true, "{$tenant}.example.com", 'example.com', $factory],
            'tenant domain + sub.example.com'      => [
                true,
                "{$tenant}.example.com",
                "{$tenant}.example.com",
                $factory,
            ],
            'wildcard domain + no host'            => [false, '*.example.com', null, $factory],
            'wildcard domain + example.com'        => [false, '*.example.com', 'example.com', $factory],
            'wildcard domain + sub.example.com'    => [false, '*.example.com', 'sub.example.com', $factory],
            'wildcard domain + tenant.example.com' => [true, '*.example.com', "{$tenant}.example.com", $factory],
            'www'                                  => [
                false,
                'www.example.com',
                'www.example.com',
                static function (): Organization {
                    return Organization::factory()->create([
                        'subdomain' => 'www',
                    ]);
                },
            ],
        ];
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public function dataProviderGetTenantNameFromDomain(): array {
        return [
            'example'             => [null, 'example'],
            'example.com'         => [null, 'example.com'],
            'sub.example.com'     => ['sub', 'sub.example.com'],
            'sub.sub.example.com' => [null, 'sub.sub.example.com'],
        ];
    }

    /**
     * @return array<string, array{bool, string, string}>
     */
    public function dataProviderIsWildcardDomain(): array {
        return [
            'nginx default domain _ (local)'                       => [true, 'local', '_'],
            'nginx default domain _ (testing)'                     => [true, 'testing', '_'],
            'nginx default domain _ (production)'                  => [false, 'production', '_'],
            'nginx wildcard domain *.example.com (local)'          => [true, 'local', '*.example.com'],
            'nginx wildcard domain *.example.com (testing)'        => [true, 'testing', '*.example.com'],
            'nginx wildcard domain *.example.com (production)'     => [true, 'production', '*.example.com'],
            'nginx wildcard domain *.sub.example.com (local)'      => [false, 'local', '*.sub.example.com'],
            'nginx wildcard domain *.sub.example.com (testing)'    => [false, 'testing', '*.sub.example.com'],
            'nginx wildcard domain *.sub.example.com (production)' => [false, 'production', '*.sub.example.com'],
        ];
    }

    /**
     * @return array<string, array{bool, string, string}>
     */
    public function dataProviderIsRootDomain(): array {
        return [
            'ip (local)'                                           => [true, 'local', '127.0.0.1'],
            'ip _ (testing)'                                       => [true, 'testing', '127.0.0.1'],
            'ip _ (production)'                                    => [true, 'production', '127.0.0.1'],
            'ip:port (local)'                                      => [true, 'local', '127.0.0.1:80'],
            'ip:port _ (testing)'                                  => [true, 'testing', '127.0.0.1:80'],
            'ip:port _ (production)'                               => [true, 'production', '127.0.0.1:80'],
            'nginx default domain _ (local)'                       => [true, 'local', '_'],
            'nginx default domain _ (testing)'                     => [true, 'testing', '_'],
            'nginx default domain _ (production)'                  => [true, 'production', '_'],
            'localhost _ (local)'                                  => [true, 'local', 'localhost'],
            'localhost _ (testing)'                                => [true, 'testing', 'localhost'],
            'localhost _ (production)'                             => [true, 'production', 'localhost'],
            'localhost:port _ (local)'                             => [true, 'local', 'localhost:80'],
            'localhost:port _ (testing)'                           => [true, 'testing', 'localhost:80'],
            'localhost:port _ (production)'                        => [true, 'production', 'localhost:80'],
            'nginx wildcard domain example.com (local)'            => [true, 'local', 'example.com'],
            'nginx wildcard domain example.com (testing)'          => [true, 'testing', 'example.com'],
            'nginx wildcard domain example.com (production)'       => [true, 'production', 'example.com'],
            'nginx wildcard domain example.com:port (local)'       => [true, 'local', 'example.com:80'],
            'nginx wildcard domain example.com:port (testing)'     => [true, 'testing', 'example.com:80'],
            'nginx wildcard domain example.com:port (production)'  => [true, 'production', 'example.com:80'],
            'nginx wildcard domain *.example.com (local)'          => [false, 'local', '*.example.com'],
            'nginx wildcard domain *.example.com (testing)'        => [false, 'testing', '*.example.com'],
            'nginx wildcard domain *.example.com (production)'     => [false, 'production', '*.example.com'],
            'nginx wildcard domain *.sub.example.com (local)'      => [false, 'local', '*.sub.example.com'],
            'nginx wildcard domain *.sub.example.com (testing)'    => [false, 'testing', '*.sub.example.com'],
            'nginx wildcard domain *.sub.example.com (production)' => [false, 'production', '*.sub.example.com'],
        ];
    }
    // </editor-fold>
}
