<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Oem;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Resolver\Resolvers\OemResolver
 */
class OemResolverTest extends TestCase {
    use WithQueryLog;

    public function testGet(): void {
        // Prepare
        $factory = static function (?Oem $oem): Oem {
            return $oem ?? Oem::factory()->make();
        };

        Oem::factory()->create(['key' => 'a']);
        Oem::factory()->create(['key' => 'b']);
        Oem::factory()->create(['key' => 'c']);

        // Run
        $resolver = $this->app->make(OemResolver::class);
        $actual   = $resolver->get('a', $factory);
        $queries  = $this->getQueryLog();

        $queries->flush();

        // Basic
        self::assertEquals('a', $actual->key);

        // Second call should return same instance
        self::assertSame($actual, $resolver->get('a', $factory));
        self::assertSame($actual, $resolver->get(' a ', $factory));
        self::assertSame($actual, $resolver->get('A', $factory));

        // All value should be loaded, so get() should not perform any queries
        $resolver->get('b', $factory);
        self::assertCount(0, $queries);

        $resolver->get('c', $factory);
        self::assertCount(0, $queries);

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function (): Oem {
            return Oem::factory()->make([
                'key'  => 'unKnown',
                'name' => 'unKnown',
            ]);
        });
        $created = $resolver->get(' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertEquals('unKnown', $created->key);
        self::assertEquals('unKnown', $created->name);
        self::assertCount(0, $queries);

        $queries->flush();

        // The created object should be in cache
        self::assertSame($created, $resolver->get('unknoWn', $factory));
        self::assertCount(0, $queries);

        // Created object should NOT be found
        $c = Oem::factory()->create();

        $queries->flush();

        self::assertNull($resolver->get($c->key));
        self::assertCount(0, $queries);

        $queries->flush();
    }

    public function testModels(): void {
        $resolver = $this->app->make(OemResolver::class);
        $oem      = Oem::factory()->create();

        self::assertEquals($oem, $resolver->getByKey($oem->getKey()));

        $resolver->reset();

        self::assertNull($resolver->getByKey($oem->getKey()));
    }
}
