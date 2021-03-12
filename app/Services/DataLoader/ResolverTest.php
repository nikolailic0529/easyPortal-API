<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Model;
use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Exceptions\FactoryObjectNotFoundException;
use Closure;
use Exception;
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

            public function getCache(): Cache {
                return parent::getCache();
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
}
