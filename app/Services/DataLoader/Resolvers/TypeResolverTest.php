<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Model;
use App\Models\Type;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolvers\TypeResolver
 */
class TypeResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $model   = new class() extends Model {
            public function getMorphClass(): string {
                return 'test';
            }
        };
        $factory = static function (): Type {
            return Type::factory()->make();
        };

        Type::factory()->create([
            'object_type' => $model->getMorphClass(),
            'key'         => 'a',
        ]);
        Type::factory()->create([
            'object_type' => $model->getMorphClass(),
            'key'         => 'b',
        ]);
        Type::factory()->create([
            'object_type' => $model->getMorphClass(),
            'key'         => 'c',
        ]);

        // Run
        $provider = $this->app->make(TypeResolver::class);
        $actual   = $provider->get($model, 'a', $factory);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertEquals('a', $actual->key);

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($model, 'a', $factory));
        $this->assertSame($actual, $provider->get($model, ' a ', $factory));
        $this->assertSame($actual, $provider->get($model, 'A', $factory));

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get($model, 'b', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotNull($provider->get($model, 'c', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($model): Type {
            return Type::factory()->make([
                'object_type' => $model->getMorphClass(),
                'key'         => 'unKnown',
                'name'        => 'unKnown',
            ]);
        });
        $created = $provider->get($model, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals('unKnown', $created->key);
        $this->assertEquals('unKnown', $created->name);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($model, 'unknoWn', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // Created object should be found
        $c = Type::factory()->create([
            'object_type' => $model->getMorphClass(),
        ]);

        $this->flushQueryLog();
        $this->assertEquals($c->getKey(), $provider->get($model, $c->key)?->getKey());
        $this->assertCount(1, $this->getQueryLog());
        $this->flushQueryLog();
    }
}
