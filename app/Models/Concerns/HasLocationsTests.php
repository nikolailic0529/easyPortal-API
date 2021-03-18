<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Model;

/**
 * @internal
 * @mixin \Tests\TestCase
 */
trait HasLocationsTests {
    /**
     * @return \App\Models\Model&\App\Models\Concerns\HasLocations
     */
    abstract protected function getModel(): Model;

    /**
     * @covers ::setLocationsAttribute
     */
    public function testSetLocationsAttribute(): void {
        /** @var \App\Models\Model&\App\Models\Concerns\HasLocations $model */
        $model    = $this->getModel()->factory()->create([
            'locations_count' => 2,
        ]);
        $morph    = $model->getMorphClass();
        $location = Location::factory()->create([
            'object_id'   => $model->getKey(),
            'object_type' => $morph,
        ]);
        $used     = Location::factory()->create([
            'object_id'   => $model->getKey(),
            'object_type' => $morph,
        ]);
        $asset    = Asset::factory()->create([
            'location_id' => $used,
        ]);

        // Base
        $this->assertEquals(2, Location::query()->count());
        $this->assertEqualsCanonicalizing([$location, $used], $model->locations->all());
        $this->assertEquals(2, $model->locations_count);

        // Used shouldn't be deleted
        $created          = Location::factory()->create([
            'object_id'   => $model->getKey(),
            'object_type' => $morph,
        ]);
        $model->locations = [$created];
        $used             = $used->refresh();

        $this->assertEquals([$created], $model->locations->all());
        $this->assertEquals(2, Location::query()->count());
        $this->assertNull($used->object_id);
        $this->assertEquals($morph, $used->object_type);
        $this->assertTrue(Asset::query()->whereKey($asset->getKey())->exists());
        $this->assertFalse(Location::query()->whereKey($location->getKey())->exists());
    }
}
