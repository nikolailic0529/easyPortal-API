<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Oem;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Providers\OemProvider
 */
class OemProviderTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        Oem::factory()->create(['abbr' => 'a']);
        Oem::factory()->create(['abbr' => 'b']);
        Oem::factory()->create(['abbr' => 'c']);

        // Run
        $provider = $this->app->make(OemProvider::class);
        $actual   = $provider->get('a');

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertEquals('a', $actual->abbr);

        // Second call should return same instance
        $this->assertSame($actual, $provider->get('a'));
        $this->assertSame($actual, $provider->get(' a '));
        $this->assertSame($actual, $provider->get('A'));
        $this->assertSame($actual, $provider->get($actual->getKey()));

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get('b'));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotNull($provider->get('c'));
        $this->assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $created = $provider->get(' unKnown ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('unKnown', $created->abbr);
        $this->assertEquals('unKnown', $created->name);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get('unknoWn'));
        $this->assertCount(0, $this->getQueryLog());
    }
}
