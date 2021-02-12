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
        $spy = Mockery::spy(function () use ($tenant) {
            $current = $this->app->get(CurrentTenant::class);

            $this->assertTrue($current->has());
            $this->assertEquals($tenant, $current->get());
        });

        $middleware->handle($request, function () use ($spy) {
            return $spy();
        });

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

        $middleware->handle($request, function () {
            return null;
        });
    }

    /**
     * @covers ::getTenantFromRequest
     *
     * @dataProvider dataProviderGetTenantFromRequest
     */
    public function testGetTenantFromRequest(bool $expected, ?string $domain, ?string $host, Closure $organizationFactory): void {
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
        $actual       = $middleware->getTenantFromRequest($request);

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
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    public function dataProviderGetTenantFromRequest(): array {
        $tenant  = 'tenant-test';
        $factory = function () use ($tenant) {
            return Organization::factory()->create([
                'subdomain' => $tenant,
            ]);
        };

        return [
            'no domain + no host'                  => [false, null, null, $factory],
            'no domain + example.com'              => [false, null, 'example.com', $factory],
            'no domain + sub.example.com'          => [false, null, 'sub.example.com', $factory],
            'domain + no host'                     => [false, 'example.com', null, $factory],
            'domain + example.com'                 => [false, 'example.com', 'example.com', $factory],
            'domain + sub.example.com'             => [false, 'example.com', 'sub.example.com', $factory],
            'sub domain + no host'                 => [false, 'sub.example.com', null, $factory],
            'sub domain + example.com'             => [false, 'sub.example.com', 'example.com', $factory],
            'sub domain + sub.example.com'         => [false, 'sub.example.com', "{$tenant}.example.com", $factory],
            'tenant domain + no host'              => [true, "{$tenant}.example.com", null, $factory],
            'tenant domain + example.com'          => [true, "{$tenant}.example.com", 'example.com', $factory],
            'tenant domain + sub.example.com'      => [true, "{$tenant}.example.com", "{$tenant}.example.com", $factory],
            'wildcard domain + no host'            => [false, "*.example.com", null, $factory],
            'wildcard domain + example.com'        => [false, "*.example.com", 'example.com', $factory],
            'wildcard domain + sub.example.com'    => [false, "*.example.com", "sub.example.com", $factory],
            'wildcard domain + tenant.example.com' => [true, "*.example.com", "{$tenant}.example.com", $factory],
        ];
    }

    public function dataProviderGetTenantNameFromDomain(): array {
        return [
            'example'             => [null, 'example'],
            'example.com'         => [null, 'example.com'],
            'sub.example.com'     => ['sub', 'sub.example.com'],
            'sub.sub.example.com' => [null, 'sub.sub.example.com'],
        ];
    }

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
    // </editor-fold>
}
