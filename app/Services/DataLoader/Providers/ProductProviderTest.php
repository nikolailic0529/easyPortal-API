<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Oem;
use App\Models\Product;
use App\Models\ProductCategory;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Providers\ProductProvider
 */
class ProductProviderTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $oemA = Oem::factory()->create();
        $oemB = Oem::factory()->create();
        $catA = ProductCategory::factory()->create();
        $catB = ProductCategory::factory()->create();

        $a = Product::factory()->create([
            'oem_id'      => $oemA,
            'category_id' => $catA,
            'sku'         => 'a',
        ]);
        Product::factory()->create([
            'oem_id'      => $oemA,
            'category_id' => $catA,
            'sku'         => 'b',
        ]);
        Product::factory()->create([
            'oem_id'      => $oemA,
            'category_id' => $catA,
            'sku'         => 'c',
        ]);

        // Run
        $name     = $this->faker->word;
        $provider = $this->app->make(ProductProvider::class);
        $actual   = $provider->get($oemA, ' a ', $catA, " {$name} ");

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertFalse($actual->wasRecentlyCreated);
        $this->assertEquals('a', $actual->sku);
        $this->assertEquals($a->name, $actual->name);
        $this->assertEquals($oemA, $actual->oem);
        $this->assertEquals($catA, $actual->category);

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get($oemA, 'a', $catA, $name));
        $this->assertSame($actual, $provider->get($oemA, 'a', $catB, $name));
        $this->assertSame($actual, $provider->get($oemA, 'a', $catB, 'any'));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get($oemB, 'a', $catA, $name));
        $this->assertNotSame($actual, $provider->get($oemA, 'b', $catA, $name));

        $this->flushQueryLog();

        // Product should be found in DB
        $found = $provider->get($oemA, 'c', $catA, $name);

        $this->assertNotNull($found);
        $this->assertFalse($found->wasRecentlyCreated);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not, the new object should be created
        $created = $provider->get($oemB, ' unknown ', $catB, ' unknown ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('unknown', $created->sku);
        $this->assertEquals('unknown', $created->name);
        $this->assertEquals($oemB, $created->oem);
        $this->assertEquals($catB, $created->category);
        $this->assertCount(2, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($oemB, ' unknown ', $catB, ' unknown '));
        $this->assertCount(0, $this->getQueryLog());
    }
}
