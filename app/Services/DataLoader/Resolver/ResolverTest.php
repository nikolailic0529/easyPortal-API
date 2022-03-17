<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver;

use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Cache\KeyRetriever;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Exceptions\FactorySearchModeException;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Utils\Eloquent\Model;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
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
        $key        = '123';
        $normalizer = $this->app->make(Normalizer::class);
        $collector  = Mockery::mock(Collector::class);
        $collector
            ->shouldReceive('collect')
            ->times(4)
            ->andReturns();

        $provider = new class($normalizer, $collector) extends Resolver {
            public function resolve(mixed $key, Closure $factory = null, bool $find = true): ?Model {
                return parent::resolve($key, $factory, $find);
            }
        };

        // Cache is empty, so resolve should return null and store it in cache
        self::assertNull($provider->resolve($key));

        // The second call with factory must call factory
        self::assertNotNull($provider->resolve(
            $key,
            static function () use ($key): ?Model {
                return new class($key) extends Model {
                    /** @noinspection PhpMissingParentConstructorInspection */
                    public function __construct(string $key) {
                        $this->setAttribute($this->getKeyName(), $key);
                    }
                };
            },
        ));
        self::assertNotNull($provider->resolve($key));

        // If resolver(s) passed it will be used to create model
        $uuid  = $this->faker->uuid;
        $value = new class($uuid) extends Model {
            public function __construct(string $key) {
                parent::__construct();

                $this->setAttribute($this->getKeyName(), $key);
            }
        };

        self::assertSame($value, $provider->resolve(
            $uuid,
            static function () use ($value) {
                return $value;
            },
        ));

        self::assertSame($value, $provider->resolve($uuid, static function (): void {
            throw new Exception();
        }));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveWithoutFind(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $comparator = static function (Key $key) use ($normalizer): bool {
            return (string) $key === (string) (new Key($normalizer, ['abc']));
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
        $resolver  = Mockery::mock(Resolver::class, [$normalizer, $collector]);
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
        $key        = '123';
        $exception  = null;
        $normalizer = $this->app->make(Normalizer::class);
        $collector  = $this->app->make(Collector::class);
        $provider   = new class($normalizer, $collector) extends Resolver {
            public function resolve(mixed $key, Closure $factory = null, bool $find = true): ?Model {
                return parent::resolve($key, $factory, true);
            }

            public function getCache(bool $preload = true): Cache {
                return parent::getCache($preload);
            }
        };

        try {
            $provider->resolve($key, static function (): void {
                throw new FactorySearchModeException();
            });
        } catch (FactorySearchModeException $exception) {
            // empty
        }

        self::assertNotNull($exception);
        self::assertInstanceOf(FactorySearchModeException::class, $exception);
        self::assertTrue($provider->getCache()->has(
            new Key($normalizer, [$key]),
        ));
    }

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $normalizer = $this->app->make(Normalizer::class);
        $keys       = [
            'a' => $this->faker->uuid,
            'b' => $this->faker->uuid,
            'c' => $this->faker->uuid,
        ];
        $cache      = new Cache([
            'key' => new class($normalizer) implements KeyRetriever {
                public function __construct(
                    protected Normalizer $normalizer,
                ) {
                    // empty
                }

                public function getKey(EloquentModel $model): Key {
                    return new Key($this->normalizer, [$model->getKeyName() => $model->getKey()]);
                }
            },
        ]);
        $model      = new class($keys['a']) extends Model {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(string $uuid = null) {
                $this->setAttribute($this->getKeyName(), $uuid);
            }
        };
        $items      = new EloquentCollection([$model]);
        $builder    = Mockery::mock($model->query());
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

        $resolver = Mockery::mock(ResolverTest_Resolver::class, [$normalizer, $collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('getFindQuery')
            ->once()
            ->andReturn($builder);
        $resolver
            ->shouldReceive('getCache')
            ->times(3)
            ->andReturn($cache);
        $resolver
            ->shouldReceive('getCacheKey')
            ->times(3)
            ->andReturnUsing(static function (mixed $key) use ($normalizer): Key {
                return new Key($normalizer, is_array($key) ? $key : [$key]);
            });

        $callback = Mockery::spy(static function (EloquentCollection $collection) use ($items): void {
            self::assertEquals($items, $collection);
        });

        $resolver->prefetch($keys, Closure::fromCallable($callback));

        $callback->shouldHaveBeenCalled()->once();

        $keyA = new Key($normalizer, [$keys['a']]);
        $keyB = new Key($normalizer, [$keys['b']]);

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
        $resolver = Mockery::mock(ResolverTest_Resolver::class);
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
        $items = new Collection();
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
            ->with(false)
            ->once()
            ->andReturn($cache);

        $resolver->reset();
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends Resolver<Model>
 */
class ResolverTest_Resolver extends Resolver {
    // TODO [tests] Remove after https://youtrack.jetbrains.com/issue/WI-25253

    /**
     * @inheritdoc
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
        return parent::prefetch($keys, $callback);
    }
}
