<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Location;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolvers\LocationResolver
 */
class LocationResolverTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::get
     */
    public function testGet(): void {
        // Prepare
        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();
        $countryA  = Country::factory()->create();
        $countryB  = Country::factory()->create();
        $cityA     = City::factory()->create();
        $cityB     = City::factory()->create();
        $factory   = static function (): Location {
            return Location::factory()->make();
        };

        Location::factory()->create([
            'object_type' => $customerA->getMorphClass(),
            'object_id'   => $customerA,
            'country_id'  => $countryA,
            'city_id'     => $cityA,
            'postcode'    => 'postcode a',
            'state'       => 'state a',
            'line_one'    => 'line_one a',
            'line_two'    => 'line_two a',
        ]);
        Location::factory()->create([
            'object_type' => $customerA->getMorphClass(),
            'object_id'   => $customerA,
            'country_id'  => $countryA,
            'city_id'     => $cityA,
            'postcode'    => 'postcode b',
            'state'       => 'state b',
            'line_one'    => 'line_one b',
            'line_two'    => 'line_two b',
        ]);
        Location::factory()->create([
            'object_type' => $customerA->getMorphClass(),
            'object_id'   => $customerA,
            'country_id'  => $countryA,
            'city_id'     => $cityA,
            'postcode'    => 'postcode c',
            'state'       => 'state c',
            'line_one'    => 'line_one c line_two c',
            'line_two'    => '',
        ]);

        // Run
        $provider = $this->app->make(LocationResolver::class);
        $actual   = $provider->get($customerA, $countryA, $cityA, 'postcode a', 'line_one a', 'line_two a', $factory);

        $this->flushQueryLog();

        // Basic
        $this->assertNotNull($actual);
        $this->assertFalse($actual->wasRecentlyCreated);
        $this->assertEquals($customerA->getMorphClass(), $actual->object_type);
        $this->assertEquals($customerA->getKey(), $actual->object_id);
        $this->assertEquals('postcode a', $actual->postcode);
        $this->assertEquals('state a', $actual->state);
        $this->assertEquals('line_one a', $actual->line_one);
        $this->assertEquals('line_two a', $actual->line_two);
        $this->assertEquals($countryA, $actual->country);
        $this->assertEquals($cityA, $actual->city);

        $this->flushQueryLog();

        // Second call should return same instance
        $this->assertSame($actual, $provider->get(
            $customerA,
            $countryA,
            $cityA,
            ' postcode A ',
            'linE_one a',
            'line_two  a',
            $factory,
        ));
        $this->assertSame($actual, $provider->get(
            $customerA,
            $countryA,
            $cityA,
            ' poSTCOde A ',
            'linE_one a',
            ' lIne_two  a',
            $factory,
        ));
        $this->assertCount(0, $this->getQueryLog());

        $this->assertNotSame($actual, $provider->get(
            $customerA,
            $countryA,
            $cityA,
            ' poSTCOde A ',
            'linE_one a',
            ' lIne_two  b',
            $factory,
        ));

        $this->flushQueryLog();

        // Should be found in DB
        $foundA = $provider->get($customerA, $countryA, $cityA, 'postcode c', 'line_one c  line_two c', '', $factory);
        $foundB = $provider->get($customerA, $countryA, $cityA, 'postcode c', 'line_one c', 'line_two c', $factory);

        $this->assertNotNull($foundA);
        $this->assertEquals($foundA, $foundB);
        $this->assertFalse($foundA->wasRecentlyCreated);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not, the new object should be created
        $spy     = Mockery::spy(static function () use ($customerB, $countryB, $cityB): Location {
            return Location::factory()->create([
                'object_type' => $customerB->getMorphClass(),
                'object_id'   => $customerB->getKey(),
                'country_id'  => $countryB,
                'city_id'     => $cityB,
                'postcode'    => 'Postcode',
                'state'       => 'New',
                'line_one'    => 'line_One a',
                'line_two'    => 'Line_two a',
            ]);
        });
        $created = $provider->get(
            $customerB,
            $countryB,
            $cityB,
            'Postcode',
            'line_One a',
            'Line_two a',
            Closure::fromCallable($spy),
        );

        $spy->shouldHaveBeenCalled();

        $this->assertNotNull($created);
        $this->assertEquals($customerB->getMorphClass(), $created->object_type);
        $this->assertEquals($customerB->getKey(), $created->object_id);
        $this->assertEquals('Postcode', $created->postcode);
        $this->assertEquals('New', $created->state);
        $this->assertEquals('line_One a', $created->line_one);
        $this->assertEquals('Line_two a', $created->line_two);
        $this->assertEquals($countryB->getKey(), $created->country_id);
        $this->assertEquals($cityB->getKey(), $created->city_id);
        $this->assertCount(2, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        $this->assertSame(
            $created,
            $provider->get($customerB, $countryB, $cityB, 'Postcode', 'line_one  a', 'LINE_two a', $factory),
        );
        $this->assertCount(0, $this->getQueryLog());
    }
}
