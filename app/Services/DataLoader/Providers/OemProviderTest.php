<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Oem;
use App\Services\DataLoader\Normalizer;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
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
        $factory = static function (): Oem {
            return Oem::factory()->make();
        };

        Oem::factory()->create(['abbr' => 'a']);
        Oem::factory()->create(['abbr' => 'b']);
        Oem::factory()->create(['abbr' => 'c']);

        // Run
        $provider = $this->app->make(OemProvider::class);
        $actual   = $provider->get('a', $factory);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertEquals('a', $actual->abbr);

        // Second call should return same instance
        $this->assertSame($actual, $provider->get('a', $factory));
        $this->assertSame($actual, $provider->get(' a ', $factory));
        $this->assertSame($actual, $provider->get('A', $factory));

        // All value should be loaded, so get() should not perform any queries
        $this->assertNotNull($provider->get('b', $factory));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotNull($provider->get('c', $factory));
        $this->assertCount(0, $this->getQueryLog());

        // If value not found the new object should be created
        $spy     = Mockery::spy(static function (): Oem {
            return Oem::factory()->create([
                'abbr' => 'unKnown',
                'name' => 'unKnown',
            ]);
        });
        $created = $provider->get(' unKnown ', Closure::fromCallable($spy));

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals('unKnown', $created->abbr);
        $this->assertEquals('unKnown', $created->name);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get('unknoWn', $factory));
        $this->assertCount(0, $this->getQueryLog());
    }
}
