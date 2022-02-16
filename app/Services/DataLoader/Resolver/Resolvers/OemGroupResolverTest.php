<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Oem;
use App\Models\OemGroup;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
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
        $factory = static function (): OemGroup {
            return OemGroup::factory()->make();
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

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertEquals('a', $actual->key);
        $this->assertCount(0, $this->getQueryLog());

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($model, 'a', 'A', $factory));
        $this->assertSame($actual, $provider->get($model, ' a ', 'a ', $factory));
        $this->assertSame($actual, $provider->get($model, 'A', ' a', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // Product should be found in DB
        $this->assertNotNull($provider->get($model, 'b', 'b', $factory));
        $this->assertNotNull($provider->get($model, 'b', 'b', $factory));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($model): OemGroup {
            return OemGroup::factory()->make([
                'oem_id' => $model,
                'key'    => 'unKnown',
                'name'   => 'unKnown',
            ]);
        });
        $created = $provider->get($model, ' unKnown ', ' unknown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals('unKnown', $created->key);
        $this->assertEquals('unKnown', $created->name);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($model, 'unknoWn', 'uNknoWn', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // Created object should be found
        $c = OemGroup::factory()->create([
            'oem_id' => $model,
        ]);

        $this->flushQueryLog();
        $this->assertEquals($c->getKey(), $provider->get($model, $c->key, $c->name)?->getKey());
        $this->assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();
    }
}