<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Data\Oem;
use App\Models\Data\Product;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Resolver\Resolvers\ProductResolver
 */
class ProductResolverTest extends TestCase {
    use WithQueryLog;

    public function testGet(): void {
        // Prepare
        $oemA    = Oem::factory()->create();
        $oemB    = Oem::factory()->create();
        $factory = static function (?Product $product): Product {
            return $product ?? Product::factory()->make();
        };

        $a = Product::factory()->create([
            'oem_id' => $oemA,
            'sku'    => 'a',
        ]);
        Product::factory()->create([
            'oem_id' => $oemA,
            'sku'    => 'b',
        ]);
        Product::factory()->create([
            'oem_id' => $oemA,
            'sku'    => 'c',
        ]);

        // Run
        $provider = $this->app->make(ProductResolver::class);
        $actual   = $provider->get($oemA, ' a ', $factory);

        // Basic
        self::assertNotEmpty($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->sku);
        self::assertEquals($a->name, $actual->name);
        self::assertEquals($oemA, $actual->oem);

        // Second call should return same instance
        $queries = $this->getQueryLog()->flush();

        self::assertSame($actual, $provider->get($oemA, 'a', $factory));
        self::assertSame($actual, $provider->get($oemA, ' a ', $factory));
        self::assertSame($actual, $provider->get($oemA, 'A', $factory));
        self::assertCount(0, $queries);

        self::assertNotSame($actual, $provider->get($oemB, 'a', static function (): Product {
            return Product::factory()->make();
        }));

        // Product should be found in DB
        $queries = $this->getQueryLog()->flush();

        self::assertNotEmpty($provider->get($oemA, 'c', $factory));
        self::assertCount(0, $queries);

        // If not, the new object should be created
        $spy     = Mockery::spy(static function () use ($oemB): Product {
            return Product::factory()->create([
                'oem_id' => $oemB,
                'sku'    => 'unKnown',
            ]);
        });
        $queries = $this->getQueryLog()->flush();
        $created = $provider->get($oemB, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('unKnown', $created->sku);
        self::assertEquals($oemB->getKey(), $created->oem_id);
        self::assertCount(2, $queries);

        // The created object should be in cache
        $queries = $this->getQueryLog()->flush();

        self::assertSame($created, $provider->get($oemB, ' unknown ', $factory));
        self::assertCount(0, $queries);
    }
}
