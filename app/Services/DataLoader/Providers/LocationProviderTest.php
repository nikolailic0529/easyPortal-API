<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Providers\LocationProvider
 */
class LocationProviderTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $countryA = Country::factory()->create();
        $countryB = Country::factory()->create();
        $cityA    = City::factory()->create();
        $cityB    = City::factory()->create();

        Location::factory()->create([
            'country_id' => $countryA,
            'city_id'    => $cityA,
            'postcode'   => 'postcode a',
            'state'      => 'state a',
            'line_one'   => 'line_one a',
            'line_two'   => 'line_two a',
        ]);
        Location::factory()->create([
            'country_id' => $countryA,
            'city_id'    => $cityA,
            'postcode'   => 'postcode b',
            'state'      => 'state b',
            'line_one'   => 'line_one b',
            'line_two'   => 'line_two b',
        ]);
        Location::factory()->create([
            'country_id' => $countryA,
            'city_id'    => $cityA,
            'postcode'   => 'postcode c',
            'state'      => 'state c',
            'line_one'   => 'line_one c line_two c',
            'line_two'   => '',
        ]);

        // Run
        $provider = $this->app->make(LocationProvider::class);
        $actual   = $provider->get($countryA, $cityA, 'postcode a', 'state a', 'line_one a', 'line_two a');

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertFalse($actual->wasRecentlyCreated);
        $this->assertEquals('postcode a', $actual->postcode);
        $this->assertEquals('state a', $actual->state);
        $this->assertEquals('line_one a', $actual->line_one);
        $this->assertEquals('line_two a', $actual->line_two);
        $this->assertEquals($countryA, $actual->country);
        $this->assertEquals($cityA, $actual->city);

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get(
            $countryA,
            $cityA,
            ' postcode A ',
            'state a',
            'linE_one a',
            'line_two  a',
        ));
        $this->assertSame($actual, $provider->get(
            $countryA,
            $cityA,
            ' poSTCOde A ',
            ' state a',
            'linE_one a',
            ' lIne_two  a',
        ));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get(
            $countryA,
            $cityA,
            ' poSTCOde A ',
            ' state a',
            'linE_one a',
            ' lIne_two  b',
        ));

        $this->flushQueryLog();

        // Should be found in DB
        $foundA = $provider->get($countryA, $cityA, 'postcode c', 'state c', 'line_one c  line_two c');
        $foundB = $provider->get($countryA, $cityA, 'postcode c', 'state any', 'line_one c', 'line_two c');

        $this->assertNotNull($foundA);
        $this->assertEquals($foundA, $foundB);
        $this->assertFalse($foundA->wasRecentlyCreated);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not, the new object should be created
        $created = $provider->get($countryB, $cityB, 'Postcode', 'New', 'line_One a', 'Line_two a');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('Postcode', $created->postcode);
        $this->assertEquals('New', $created->state);
        $this->assertEquals('line_One a', $created->line_one);
        $this->assertEquals('Line_two a', $created->line_two);
        $this->assertEquals($countryB, $created->country);
        $this->assertEquals($cityB, $created->city);
        $this->assertCount(2, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame($created, $provider->get($countryB, $cityB, 'Postcode', 'New', 'line_one  a', 'LINE_two a'));
        $this->assertCount(0, $this->getQueryLog());
    }
}
