<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Oem;
use App\Models\Product;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolvers\ProductResolver
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
        $this->assertNotNull($actual);
        $this->assertFalse($actual->wasRecentlyCreated);
        $this->assertEquals('a', $actual->sku);
        $this->assertEquals($a->name, $actual->name);
        $this->assertEquals($oemA, $actual->oem);

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($oemA, 'a', $factory));
        $this->assertSame($actual, $provider->get($oemA, ' a ', $factory));
        $this->assertSame($actual, $provider->get($oemA, 'A', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get($oemB, 'a', static function (): Product {
            return Product::factory()->make();
        }));

        $this->flushQueryLog();

        // Product should be found in DB
        $found = $provider->get($oemA, 'c', $factory);

        $this->assertNotNull($found);
        $this->assertFalse($found->wasRecentlyCreated);
        $this->assertCount(1, $this->getQueryLog());

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

        $this->assertNotNull($created);
        $this->assertEquals('unKnown', $created->sku);
        $this->assertEquals($oemB->getKey(), $created->oem_id);
        $this->assertCount(2, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($oemB, ' unknown ', $factory));
        $this->assertCount(0, $this->getQueryLog());
    }
}
