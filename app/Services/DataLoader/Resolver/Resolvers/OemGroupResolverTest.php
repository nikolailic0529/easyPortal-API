<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Oem;
use App\Models\OemGroup;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\OemGroupResolver
 */
class OemGroupResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $model   = Oem::factory()->create();
        $factory = static function (?OemGroup $group): OemGroup {
            return $group ?? OemGroup::factory()->make();
        };

        OemGroup::factory()->create([
            'oem_id' => $model,
            'key'    => 'a',
            'name'   => 'a',
        ]);
        OemGroup::factory()->create([
            'oem_id' => $model,
            'key'    => 'b',
            'name'   => 'b',
        ]);
        OemGroup::factory()->create([
            'oem_id' => $model,
            'key'    => 'c',
            'name'   => 'c',
        ]);

        // Run
        $provider = $this->app->make(OemGroupResolver::class);
        $actual   = $provider->get($model, 'a', 'a', $factory);

        // Basic
        $queries = $this->getQueryLog()->flush();

        self::assertNotEmpty($actual);
        self::assertEquals('a', $actual->key);
        self::assertCount(0, $queries);

        // Second call should return same instance
        $queries = $this->getQueryLog()->flush();

        self::assertSame($actual, $provider->get($model, 'a', 'A', $factory));
        self::assertSame($actual, $provider->get($model, ' a ', 'a ', $factory));
        self::assertSame($actual, $provider->get($model, 'A', ' a', $factory));
        self::assertCount(0, $queries);

        // Product should be found in DB
        $queries = $this->getQueryLog()->flush();

        self::assertNotEmpty($provider->get($model, 'b', 'b', $factory));
        self::assertNotEmpty($provider->get($model, 'b', 'b', $factory));
        self::assertCount(1, $queries);

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($model): OemGroup {
            return OemGroup::factory()->make([
                'oem_id' => $model,
                'key'    => 'unKnown',
                'name'   => 'unKnown',
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get($model, ' unKnown ', ' unknown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('unKnown', $created->key);
        self::assertEquals('unKnown', $created->name);
        self::assertCount(1, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get($model, 'unknoWn', 'uNknoWn', $factory));
        self::assertCount(0, $queries);

        // Created object should be found
        $c       = OemGroup::factory()->create([
            'oem_id' => $model,
        ]);
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($c->getKey(), $provider->get($model, $c->key, $c->name)?->getKey());
        self::assertCount(1, $queries);
    }
}
