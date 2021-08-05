<?php declare(strict_types = 1);

namespace App\Services;

use Closure;
use DateInterval;
use Illuminate\Contracts\Cache\Repository;
use JsonSerializable;
use Mockery;
use Tests\TestCase;

use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\Service
 */
class ServiceTest extends TestCase {
    /**
     * @covers ::get
     */
    public function testGet(): void {
        $cache   = Mockery::mock(Repository::class);
        $service = new class($cache) extends Service {
            // empty
        };
        $class   = $service::class;

        $cache
            ->shouldReceive('has')
            ->with("{$class}:a")
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('get')
            ->with("{$class}:a", null)
            ->once()
            ->andReturn(123);
        $cache
            ->shouldReceive('set')
            ->withArgs(static function (mixed $key, mixed $value, mixed $ttl) use ($class): bool {
                return $key === "{$class}:a"
                    && $value === 123
                    && $ttl instanceof DateInterval
                    && $ttl->format('P%yY%mM%dD%hH%iM%sS') === 'P0Y1M0D0H0M0S';
            })
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('has')
            ->with("{$class}:b")
            ->once()
            ->andReturn(false);

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
        $cache   = Mockery::mock(Repository::class);
        $service = new class($cache) extends Service implements JsonSerializable {
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

        $cache
            ->shouldReceive('set')
            ->withArgs(static function (mixed $key, mixed $value, mixed $ttl) use ($class): bool {
                return $key === "{$class}:a"
                    && $value === 123
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
        $cache   = Mockery::mock(Repository::class);
        $service = new class($cache) extends Service {
            // empty
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
        $cache   = Mockery::mock(Repository::class);
        $service = new class($cache) extends Service {
            // empty
        };
        $class   = $service::class;

        $cache
            ->shouldReceive('delete')
            ->with("{$class}:a")
            ->once()
            ->andReturn(true);

        $this->assertTrue($service->delete('a'));
    }
}
