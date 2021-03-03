<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Model;
use Closure;
use Exception;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Provider
 */
class ProviderTest extends TestCase {
    /**
     * @covers ::resolve
     */
    public function testResolve(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $provider   = new class($normalizer) extends Provider {
            public function resolve(mixed $key, Closure $factory = null): ?Model {
                return parent::resolve($key, $factory);
            }
        };

        // Cache is empty, so resolve should return null and store it in cache
        $this->assertNull($provider->resolve(123));

        // The second call must return value from cache
        $this->assertNull($provider->resolve(
            123,
            static function (): ?Model {
                throw new Exception();
            },
        ));

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
}
