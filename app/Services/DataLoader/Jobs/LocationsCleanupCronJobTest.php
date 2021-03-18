<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\LocationsCleanupCronJobTest
 */
class LocationsCleanupCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(LocationsCleanupCronJob::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $now = Date::now();
        $a   = Location::factory()->create([
            'object_id'   => $this->faker->uuid,
            'object_type' => (new Customer())->getMorphClass(),
            'created_at'  => $now->subWeek(),
        ]);
        $b   = Location::factory()->create([
            'object_id'   => null,
            'object_type' => (new Customer())->getMorphClass(),
            'created_at'  => $now->subWeek(),
        ]);
        $c   = Location::factory()->create([
            'object_id'   => null,
            'object_type' => (new Customer())->getMorphClass(),
            'created_at'  => $now->subWeek(),
        ]);
        $d   = Location::factory()->create([
            'object_id'   => null,
            'object_type' => (new Customer())->getMorphClass(),
            'created_at'  => $now,
        ]);

        Asset::factory()->create([
            'location_id' => $c,
        ]);

        // Run
        $this->app->call([$this->app->make(LocationsCleanupCronJob::class), 'handle']);

        // Test
        $this->assertTrue(Location::query()->whereKey($a->getKey())->exists()); // has object_id
        $this->assertFalse(Location::query()->whereKey($b->getKey())->exists());// unused
        $this->assertTrue(Location::query()->whereKey($c->getKey())->exists()); // used
        $this->assertTrue(Location::query()->whereKey($d->getKey())->exists()); // not yet expired
    }
}
