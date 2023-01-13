<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver;

use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Cache\KeyRetriever;
use App\Services\DataLoader\Collector\Collector;
use App\Utils\Eloquent\Model;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use LogicException;
use Mockery;
use Tests\TestCase;

use function is_array;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolver
 */
class ResolverTest extends TestCase {
    /**
     * @covers ::resolve
     */
    public function testResolve(): void {
        // Prepare
        $key       = '123';
        $collector = Mockery::mock(Collector::class);
        $collector
            ->shouldReceive('collect')
            ->times(4)
            ->andReturns();

        $provider = new class($collector) extends Resolver {
            public function resolve(mixed $key, Closure $factory = null, bool $find = true): ?EloquentModel {
                return parent::resolve($key, $factory, $find);
            }
        };

        // Cache is empty, so resolve should return null and store it in cache
        self::assertNull($provider->resolve($key));

        // The second call with factory must call factory
        self::assertNotNull($provider->resolve(
            $key,
            static function () use ($key): EloquentModel {
                return (new class() extends Model {
                    // empty
                })->setKey($key);
            },
        ));
        self::assertNotNull($provider->resolve($key));

        // If resolver(s) passed it will be used to create model
        $uuid  = $this->faker->uuid();
        $value = (new class() extends Model {
            // empty
        })->setKey($uuid);

        self::assertSame($value, $provider->resolve(
            $uuid,
            static function () use ($value): EloquentModel {
                return $value;
            },
        ));

        self::assertSame($value, $provider->resolve($uuid, static function (?EloquentModel $value): EloquentModel {
            self::assertNotNull($value);

            return $value;
        }));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithoutFind(): void {
        // Prepare
        $comparator = static function (Key $key): bool {
            return (string) $key === (string) (new Key(['abc']));
        };
        $cache      = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('has')
            ->withArgs($comparator)
            ->twice()
            ->andReturn(false);
        $cache
            ->shouldReceive('putNull')
            ->twice()
            ->andReturnSelf();

        $collector = Mockery::mock(Collector::class);
        $resolver  = Mockery::mock(Resolver::class, [$collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('getCache')
            ->times(4)
            ->andReturn($cache);
        $resolver
            ->shouldReceive('find')
            ->withArgs($comparator)
            ->once()
            ->andReturn(null);

        self::assertNull($resolver->resolve('abc', null, false));
        self::assertNull($resolver->resolve('abc', null, true));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveFactoryObjectNotFoundException(): void {
        $key       = '123';
        $exception = null;
        $collector = $this->app->make(Collector::class);
        $provider  = new class($collector) extends Resolver {
            public function resolve(mixed $key, Closure $factory = null, bool $find = true): ?EloquentModel {
                return parent::resolve($key, $factory, $find);
            }

            public function getCache(): Cache {
                return parent::getCache();
            }
        };

        try {
            $provider->resolve($key, static function (): void {
                throw new Exception(__METHOD__);
            });
        } catch (Exception $exception) {
            // empty
        }

        self::assertNotNull($exception);
        self::assertTrue($provider->getCache()->has(
            new Key([$key]),
        ));
    }

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $keys    = [
            'a' => $this->faker->uuid(),
            'b' => $this->faker->uuid(),
            'c' => $this->faker->uuid(),
        ];
        $cache   = new Cache([
            'key' => new class() implements KeyRetriever {
                public function getKey(EloquentModel $model): Key {
                    return new Key([$model->getKeyName() => $model->getKey()]);
                }
            },
        ]);
        $model   = (new class() extends Model {
            // empty
        })->setKey($keys['a']);
        $items   = new EloquentCollection([$model]);
        $builder = Mockery::mock($model->query());
        $builder->makePartial();
        $builder
            ->shouldReceive('get')
            ->once()
            ->andReturn($items);
        $builder
            ->shouldReceive('where')
            ->once()
            ->andReturn($builder);

        $collector = Mockery::mock(Collector::class);
        $collector
            ->shouldReceive('collect')
            ->once()
            ->andReturns();

        $resolver = Mockery::mock(Resolver::class, [$collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('getFindQuery')
            ->once()
            ->andReturn($builder);
        $resolver
            ->shouldReceive('getCache')
            ->times(4)
            ->andReturn($cache);
        $resolver
            ->shouldReceive('getCacheKey')
            ->times(3)
            ->andReturnUsing(static function (mixed $key): Key {
                return new Key(is_array($key) ? $key : [$key]);
            });

        $callback = Mockery::spy(static function (EloquentCollection $collection) use ($items): void {
            self::assertEquals($items, $collection);
        });

        $resolver->prefetch($keys, Closure::fromCallable($callback));

        $callback->shouldHaveBeenCalled()->once();

        $keyA = new Key([$keys['a']]);
        $keyB = new Key([$keys['b']]);

        self::assertTrue($cache->hasByRetriever('key', $keyA));
        self::assertFalse($cache->hasNull($keyA));
        self::assertFalse($cache->hasByRetriever('key', $keyB));
        self::assertTrue($cache->hasNull($keyB));
        self::assertFalse($cache->hasByRetriever('key', $keyB));
        self::assertTrue($cache->hasNull($keyB));
    }

    /**
     * @covers ::prefetch
     */
    public function testPrefetchNoFindQuery(): void {
        $resolver = Mockery::mock(Resolver::class);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('getFindQuery')
            ->once()
            ->andReturnNull();

        self::expectExceptionObject(
            new LogicException('Prefetch cannot be used with Resolver without the find query.'),
        );

        $resolver->prefetch([1, 2, 3]);
    }

    /**
     * @covers ::getResolved
     */
    public function testGetResolved(): void {
        $items = new EloquentCollection();
        $cache = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($items);

        $resolver = Mockery::mock(Resolver::class);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('getCache')
            ->withNoArgs()
            ->once()
            ->andReturn($cache);

        self::assertSame($items, $resolver->getResolved());
    }

    /**
     * @covers ::reset
     */
    public function testReset(): void {
        $cache = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('reset')
            ->once()
            ->andReturnSelf();

        $resolver = Mockery::mock(Resolver::class);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('getCache')
            ->once()
            ->andReturn($cache);

        $resolver->reset();
    }
}
