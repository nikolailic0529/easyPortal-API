<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Field;
use App\Utils\Eloquent\Model;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\FieldResolver
 */
class FieldResolverTest extends TestCase {
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
        $factory = static function (): Field {
            return Field::factory()->make();
        };

        Field::factory()->create([
            'object_type' => $model->getMorphClass(),
            'key'         => 'a',
        ]);
        Field::factory()->create([
            'object_type' => $model->getMorphClass(),
            'key'         => 'b',
        ]);
        Field::factory()->create([
            'object_type' => $model->getMorphClass(),
            'key'         => 'c',
        ]);

        // Run
        $provider = $this->app->make(FieldResolver::class);
        $actual   = $provider->get($model, 'a', $factory);
        $queries  = $this->getQueryLog()->flush();

        // Basic
        self::assertNotEmpty($actual);
        self::assertEquals('a', $actual->key);

        // Second call should return same instance
        self::assertSame($actual, $provider->get($model, 'a', $factory));
        self::assertSame($actual, $provider->get($model, ' a ', $factory));
        self::assertSame($actual, $provider->get($model, 'A', $factory));

        // All value should be loaded, so get() should not perform any queries
        self::assertNotEmpty($provider->get($model, 'b', $factory));
        self::assertCount(0, $queries);

        self::assertNotEmpty($provider->get($model, 'c', $factory));
        self::assertCount(0, $queries);

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function () use ($model): Field {
            return Field::factory()->make([
                'object_type' => $model->getMorphClass(),
                'key'         => 'unKnown',
                'name'        => 'unKnown',
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get($model, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('unKnown', $created->key);
        self::assertEquals('unKnown', $created->name);
        self::assertCount(1, $queries);

        $queries->flush();

        // The created object should be in cache
        self::assertSame($created, $provider->get($model, 'unknoWn', $factory));
        self::assertCount(0, $queries);

        // Created object should be found
        $c = Field::factory()->create([
            'object_type' => $model->getMorphClass(),
        ]);

        $queries->flush();

        self::assertEquals($c->getKey(), $provider->get($model, $c->key)?->getKey());
        self::assertCount(1, $queries);
    }
}
