<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Reseller;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @covers \App\Services\DataLoader\Resolver\Resolvers\ResellerResolver
 */
class ResellerResolverTest extends TestCase {
    use WithoutGlobalScopes;
    use WithQueryLog;

    public function testGet(): void {
        // Prepare
        $factory = static function (?Reseller $reseller): Reseller {
            return $reseller ?? Reseller::factory()->make();
        };

        $a = Reseller::factory()->create();
        $b = Reseller::factory()->create();

        self::assertTrue($b->delete());
        self::assertTrue($b->trashed());

        // Run
        $provider = $this->app->make(ResellerResolver::class);
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
        $spy     = Mockery::spy(static function () use ($uuid): Reseller {
            return Reseller::factory()->make([
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
