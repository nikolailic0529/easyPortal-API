<?php declare(strict_types = 1);

namespace App\Services;

use App\Services\DataLoader\Importers\Importer as DataLoaderImporter;
use App\Services\DataLoader\Service as DataLoaderService;
use Closure;
use DateInterval;
use Exception;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use InvalidArgumentException;
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
        $class   = $service::class;

        $cache
            ->shouldReceive('get')
            ->with("{$class}:a")
            ->once()
            ->andReturn('123');
        $cache
            ->shouldReceive('get')
            ->with("{$class}:b")
            ->once()
            ->andReturn(null);

        $spy     = Mockery::spy(static function (mixed $value): mixed {
            return $value;
        });
        $factory = Closure::fromCallable($spy);

        $this->assertEquals(123, $service->get('a', $factory));
        $this->assertNull($service->get('b', $factory));

        $spy
            ->shouldNotHaveReceived()
            ->with(123);
    }

    /**
     * @covers ::set
     */
    public function testSet(): void {
        $config  = Mockery::mock(Config::class);
        $cache   = Mockery::mock(Cache::class);
        $service = new class($config, $cache) extends Service implements JsonSerializable {
            /**
             * @return array<mixed>
             */
            public function jsonSerialize(): array {
                return [
                    'json' => 'value',
                ];
            }
        };
        $class   = $service::class;

        $config
            ->shouldReceive('get')
            ->with('ep.cache.service.ttl')
            ->times(2)
            ->andReturn('P1M');

        $cache
            ->shouldReceive('set')
            ->withArgs(static function (mixed $key, mixed $value, mixed $ttl) use ($class): bool {
                return $key === "{$class}:a"
                    && $value === '123'
                    && $ttl instanceof DateInterval
                    && $ttl->format('P%yY%mM%dD%hH%iM%sS') === 'P0Y1M0D0H0M0S';
            })
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('set')
            ->with("{$class}:b", json_encode($service), Mockery::andAnyOtherArgs())
            ->once()
            ->andReturn(true);

        $this->assertEquals(123, $service->set('a', 123));
        $this->assertSame($service, $service->set('b', $service));
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
        $class   = $service::class;

        $cache
            ->shouldReceive('has')
            ->with("{$class}:a")
            ->once()
            ->andReturn(true);

        $this->assertTrue($service->has('a'));
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
        $class   = $service::class;

        $cache
            ->shouldReceive('delete')
            ->with("{$class}:a:b")
            ->once()
            ->andReturn(true);

        $this->assertTrue($service->delete(['a', 'b']));
    }

    /**
     * @covers ::getService
     */
    public function testGetService(): void {
        $this->assertEquals(null, Service::getService(Service::class));
        $this->assertEquals(DataLoaderService::class, Service::getService(DataLoaderService::class));
        $this->assertEquals(DataLoaderService::class, Service::getService(DataLoaderImporter::class));
    }

    /**
     * @covers ::getKey
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

            public function getKey(mixed $key): string {
                return parent::getKey($key);
            }
        };

        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        if (is_string($expected)) {
            $expected = str_replace('${service}', $service::class, $expected);
        }

        $this->assertEquals($expected, $service->getKey($key));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{\Exception|string,array<mixed>}>
     */
    public function dataProviderGetKey(): array {
        return [
            'string' => ['${service}:abc', 'abc'],
            'object' => [
                new InvalidArgumentException('The `$value` cannot be used as a key.'),
                new stdClass(),
            ],
        ];
    }
    // </editor-fold>
}
