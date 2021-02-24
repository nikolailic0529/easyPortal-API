<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\ProductCategory;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Providers\ProductCategoryProvider
 */
class ProductCategoryProviderTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        ProductCategory::factory()->create(['name' => 'a']);
        ProductCategory::factory()->create(['name' => 'b']);
        ProductCategory::factory()->create(['name' => 'c']);

        // Run
        $provider = $this->app->make(ProductCategoryProvider::class);
        $actual   = $provider->get('a');

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertEquals('a', $actual->name);

        // Second call should return same instance
        $this->assertSame($actual, $provider->get('a'));
        $this->assertSame($actual, $provider->get($actual->getKey()));

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get('b'));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotNull($provider->get('c'));
        $this->assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $created = $provider->get(' unknown ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('unknown', $created->name);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get('unknown'));
        $this->assertCount(0, $this->getQueryLog());
    }
}
