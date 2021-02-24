<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Country;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Providers\CountryProvider
 */
class CountryProviderTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        Country::factory()->create(['code' => 'a']);
        Country::factory()->create(['code' => 'b']);
        Country::factory()->create(['code' => 'c']);

        // Run
        $provider = $this->app->make(CountryProvider::class);
        $actual   = $provider->get('a', $this->faker->word);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertEquals('a', $actual->code);

        // Second call should return same instance
        $this->assertSame($actual, $provider->get('a', $this->faker->word));
        $this->assertCount(0, $this->getQueryLog());

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get('b', 'b name'));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotNull($provider->get('c', 'c name'));
        $this->assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $created = $provider->get(' unk ', ' unknown  name ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('unk', $created->code);
        $this->assertEquals('unknown name', $created->name);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get('unk', 'any'));
        $this->assertCount(0, $this->getQueryLog());
    }
}
