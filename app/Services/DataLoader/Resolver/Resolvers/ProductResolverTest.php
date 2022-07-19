<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Oem;
use App\Models\Product;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\ProductResolver
 */
class ProductResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $oemA    = Oem::factory()->create();
        $oemB    = Oem::factory()->create();
        $factory = static function (): Product {
            return Product::factory()->make();
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

        $this->flushQueryLog();

        // Basic
        self::assertNotEmpty($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('a', $actual->sku);
        self::assertEquals($a->name, $actual->name);
        self::assertEquals($oemA, $actual->oem);

        $this->flushQueryLog();

        // Second call should return same instance
        self::assertSame($actual, $provider->get($oemA, 'a', $factory));
        self::assertSame($actual, $provider->get($oemA, ' a ', $factory));
        self::assertSame($actual, $provider->get($oemA, 'A', $factory));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotSame($actual, $provider->get($oemB, 'a', static function (): Product {
            return Product::factory()->make();
        }));

        $this->flushQueryLog();

        // Product should be found in DB
        self::assertNotEmpty($provider->get($oemA, 'c', $factory));
        self::assertCount(0, $this->getQueryLog());

        $this->flushQueryLog();

        // If not, the new object should be created
        $spy     = Mockery::spy(static function () use ($oemB): Product {
            return Product::factory()->create([
                'oem_id' => $oemB,
                'sku'    => 'unKnown',
            ]);
        });
        $created = $provider->get($oemB, ' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        self::assertNotEmpty($created);
        self::assertEquals('unKnown', $created->sku);
        self::assertEquals($oemB->getKey(), $created->oem_id);
        self::assertCount(2, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame($created, $provider->get($oemB, ' unknown ', $factory));
        self::assertCount(0, $this->getQueryLog());
    }
}
