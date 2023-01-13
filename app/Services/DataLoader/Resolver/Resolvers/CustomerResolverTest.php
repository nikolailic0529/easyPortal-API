<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Customer;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @covers \App\Services\DataLoader\Resolver\Resolvers\CustomerResolver
 */
class CustomerResolverTest extends TestCase {
    use WithoutGlobalScopes;
    use WithQueryLog;

    public function testGet(): void {
        // Prepare
        $factory = static function (?Customer $customer): Customer {
            return $customer ?? Customer::factory()->make();
        };

        $a = Customer::factory()->create();
        $b = Customer::factory()->create();

        self::assertTrue($b->delete());
        self::assertTrue($b->trashed());

        // Run
        $provider = $this->app->make(CustomerResolver::class);
        $actual   = $provider->get($a->getKey(), $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertEquals($a->getKey(), $actual->getKey());

        // Second call should return same instance
        $queries = $this->getQueryLog()->flush();

        self::assertSame($actual, $provider->get($a->getKey(), $factory));
        self::assertSame($actual, $provider->get(" {$a->getKey()} ", $factory));

        self::assertCount(0, $queries);

        self::assertNotSame($actual, $provider->get($b->getKey(), $factory));
        self::assertCount(1, $queries);

        // If value not found the new object should be created
        $uuid    = $this->faker->uuid();
        $spy     = Mockery::spy(static function () use ($uuid): Customer {
            return Customer::factory()->make([
                'id'        => $uuid,
                'type_id'   => $uuid,
                'status_id' => $uuid,
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get($uuid, Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals($uuid, $created->getKey());
        self::assertCount(1, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get($uuid, $factory));
        self::assertCount(0, $queries);
    }
}
