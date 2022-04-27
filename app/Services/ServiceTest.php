<?php declare(strict_types = 1);

namespace App\Services;

use App\GraphQL\Directives\Directives\Auth\AuthDirective as GraphQLAuthDirective;
use App\GraphQL\Service as GraphQLService;
use App\Services\DataLoader\Importer\Importer as DataLoaderImporter;
use App\Services\DataLoader\Service as DataLoaderService;
use App\Utils\Cache\CacheKeyInvalidValue;
use Closure;
use DateInterval;
use Exception;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use JsonSerializable;
use Mockery;
use stdClass;
use Tests\TestCase;

use function is_string;
use function json_encode;
use function str_replace;

/**
 * @internal
 * @coversDefaultClass \App\Services\Service
 */
class ServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::get
     */
    public function testGet(): void {
        $cache   = Mockery::mock(Cache::class);
        $service = new class($cache) extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Cache $cache,
            ) {
                // empty
            }
        };
        $name    = Service::getServiceName($service);

        $cache
            ->shouldReceive('get')
            ->with("app:{$name}:a")
            ->once()
            ->andReturn('123');
        $cache
            ->shouldReceive('get')
            ->with("app:{$name}:b")
            ->once()
            ->andReturn(null);

        $spy     = Mockery::spy(static function (mixed $value): mixed {
            return $value;
        });
        $factory = Closure::fromCallable($spy);

        self::assertEquals(123, $service->get('a', $factory));
        self::assertNull($service->get('b', $factory));

        $spy
            ->shouldNotHaveReceived(null)
            ->with(123);
    }

    /**
     * @covers ::set
     */
    public function testSet(): void {
        $store  = $this->faker->word();
        $config = Mockery::mock(Config::class);
        $config
            ->shouldReceive('get')
            ->with('ep.cache.service.store')
            ->once()
            ->andReturn($store);
        $config
            ->shouldReceive('get')
            ->with('ep.cache.service.ttl')
            ->times(2)
            ->andReturn('P1M');

        $cache = Mockery::mock(Cache::class);

        $factory = Mockery::mock(CacheFactory::class);
        $factory
            ->shouldReceive('store')
            ->with($store)
            ->once()
            ->andReturn($cache);

        $service = new class($config, $factory) extends Service implements JsonSerializable {
            /**
             * @return array<mixed>
             */
            public function jsonSerialize(): array {
                return [
                    'json' => 'value',
                ];
            }
        };
        $name    = Service::getServiceName($service);

        $cache
            ->shouldReceive('set')
            ->withArgs(static function (mixed $key, mixed $value, mixed $ttl) use ($name): bool {
                return $key === "app:{$name}:a"
                    && $value === '123'
                    && $ttl instanceof DateInterval
                    && $ttl->format('P%yY%mM%dD%hH%iM%sS') === 'P0Y1M0D0H0M0S';
            })
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('set')
            ->with("app:{$name}:b", json_encode($service), Mockery::andAnyOtherArgs())
            ->once()
            ->andReturn(true);

        self::assertEquals(123, $service->set('a', 123));
        self::assertSame($service, $service->set('b', $service));
    }

    /**
     * @covers ::has
     */
    public function testHas(): void {
        $cache   = Mockery::mock(Cache::class);
        $service = new class($cache) extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Cache $cache,
            ) {
                // empty
            }
        };
        $name    = Service::getServiceName($service);

        $cache
            ->shouldReceive('has')
            ->with("app:{$name}:a")
            ->once()
            ->andReturn(true);

        self::assertTrue($service->has('a'));
    }

    /**
     * @covers ::delete
     */
    public function testDelete(): void {
        $cache   = Mockery::mock(Cache::class);
        $service = new class($cache) extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Cache $cache,
            ) {
                // empty
            }
        };
        $name    = Service::getServiceName($service);

        $cache
            ->shouldReceive('delete')
            ->with("app:{$name}:a:b")
            ->once()
            ->andReturn(true);

        self::assertTrue($service->delete(['a', 'b']));
    }

    /**
     * @covers ::getService
     */
    public function testGetService(): void {
        self::assertEquals(null, Service::getService(Service::class));
        self::assertEquals(GraphQLService::class, Service::getService(GraphQLService::class));
        self::assertEquals(GraphQLService::class, Service::getService(GraphQLAuthDirective::class));
        self::assertEquals(DataLoaderService::class, Service::getService(DataLoaderService::class));
        self::assertEquals(DataLoaderService::class, Service::getService(DataLoaderImporter::class));
    }

    /**
     * @covers ::getServiceName
     */
    public function testGetServiceName(): void {
        self::assertEquals(null, Service::getServiceName(Service::class));
        self::assertEquals('GraphQL', Service::getServiceName(GraphQLService::class));
        self::assertEquals('GraphQL', Service::getServiceName(GraphQLAuthDirective::class));
        self::assertEquals('DataLoader', Service::getServiceName(DataLoaderService::class));
        self::assertEquals('DataLoader', Service::getServiceName(DataLoaderImporter::class));
        self::assertEquals(null, Service::getServiceName(new class() extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }
        }));
    }

    /**
     * @covers ::getCacheKey
     *
     * @dataProvider dataProviderGetKey
     *
     * @param array<object|string>|object|string $key
     */
    public function testGetKey(Exception|string $expected, mixed $key): void {
        $service = new class() extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty;
            }
        };

        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        if (is_string($expected)) {
            $name     = (string) Service::getServiceName($service);
            $expected = str_replace('${service}', $name, $expected);
        }

        self::assertEquals($expected, $service->getCacheKey($key));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{Exception|string,array<mixed>}>
     */
    public function dataProviderGetKey(): array {
        return [
            'string' => ['app:${service}:abc', 'abc'],
            'object' => [
                new CacheKeyInvalidValue(new stdClass()),
                new stdClass(),
            ],
        ];
    }
    // </editor-fold>
}
