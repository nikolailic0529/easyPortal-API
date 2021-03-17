<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Model;
use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Cache\ModelKey;
use App\Services\DataLoader\Exceptions\FactoryObjectNotFoundException;
use Closure;
use Exception;
use Illuminate\Support\Collection;
use LogicException;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver
 */
class ResolverTest extends TestCase {
    /**
     * @covers ::resolve
     */
    public function testResolve(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $provider   = new class($normalizer) extends Resolver {
            public function resolve(mixed $key, Closure $factory = null): ?Model {
                return parent::resolve($key, $factory);
            }
        };

        // Cache is empty, so resolve should return null and store it in cache
        $this->assertNull($provider->resolve(123));

        // The second call with factory must call factory
        $this->assertNotNull($provider->resolve(
            123,
            static function (): ?Model {
                return new class(123) extends Model {
                    /** @noinspection PhpMissingParentConstructorInspection */
                    public function __construct(int $key) {
                        $this->{$this->getKeyName()} = $key;
                    }
                };
            },
        ));
        $this->assertNotNull($provider->resolve(123));

        // If resolver(s) passed it will be used to create model
        $uuid  = $this->faker->uuid;
        $value = new class($uuid) extends Model {
            public function __construct(string $key) {
                parent::__construct();

                $this->{$this->getKeyName()} = $key;
            }
        };

        $this->assertSame($value, $provider->resolve(
            $uuid,
            static function () use ($value) {
                return $value;
            },
        ));

        $this->assertSame($value, $provider->resolve($uuid, static function (): void {
            throw new Exception();
        }));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveFactoryObjectNotFoundException(): void {
        $exception  = null;
        $normalizer = $this->app->make(Normalizer::class);
        $provider   = new class($normalizer) extends Resolver {
            public function resolve(mixed $key, Closure $factory = null): ?Model {
                return parent::resolve($key, $factory);
            }

            public function getCache(bool $preload = true): Cache {
                return parent::getCache($preload);
            }
        };

        try {
            $provider->resolve(123, static function (): void {
                throw new FactoryObjectNotFoundException();
            });
        } catch (FactoryObjectNotFoundException $exception) {
            // empty
        }

        $this->assertNotNull($exception);
        $this->assertInstanceOf(FactoryObjectNotFoundException::class, $exception);
        $this->assertTrue($provider->getCache()->has(123));
    }

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $keys    = [
            'a' => $this->faker->uuid,
            'b' => $this->faker->uuid,
            'c' => $this->faker->uuid,
        ];
        $cache   = new Cache(new Collection(), [
            'key' => new ModelKey(),
        ]);
        $model   = new class($keys['a']) extends Model {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(string $uuid = null) {
                $this->{$this->getKeyName()} = $uuid;
            }
        };
        $items   = new Collection([$model]);
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

        $resolver = Mockery::mock(ResolverTest_Resolver::class);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('getFindQuery')
            ->once()
            ->andReturn($builder);
        $resolver
            ->shouldReceive('getCache')
            ->twice()
            ->andReturn($cache);

        $resolver->prefetch($keys, true);

        $this->assertTrue($cache->hasByRetriever('key', $keys['a']));
        $this->assertFalse($cache->hasNull($keys['a']));
        $this->assertFalse($cache->hasByRetriever('key', $keys['b']));
        $this->assertTrue($cache->hasNull($keys['b']));
        $this->assertFalse($cache->hasByRetriever('key', $keys['b']));
        $this->assertTrue($cache->hasNull($keys['b']));
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

        $this->expectExceptionObject(
            new LogicException('Prefetch cannot be used with Resolver without the find query.'),
        );

        $resolver->prefetch([1, 2, 3]);
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ResolverTest_Resolver extends Resolver {
    // TODO [tests] Remove after https://youtrack.jetbrains.com/issue/WI-25253

    /**
     * @inheritdoc
     */
    public function prefetch(array $keys, bool $reset = false): static {
        return parent::prefetch($keys, $reset);
    }
}
