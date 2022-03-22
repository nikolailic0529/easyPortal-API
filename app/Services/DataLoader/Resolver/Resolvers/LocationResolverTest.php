<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use Closure;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Resolver\Resolvers\LocationResolver
 */
class LocationResolverTest extends TestCase {
    use WithoutOrganizationScope;
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
        $factory  = static function (): Location {
            return Location::factory()->make();
        };

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
        $provider = $this->app->make(LocationResolver::class);
        $actual   = $provider->get($countryA, $cityA, 'postcode a', 'line_one a', 'line_two a', $factory);

        $this->flushQueryLog();

        // Basic
        self::assertNotNull($actual);
        self::assertFalse($actual->wasRecentlyCreated);
        self::assertEquals('postcode a', $actual->postcode);
        self::assertEquals('state a', $actual->state);
        self::assertEquals('line_one a', $actual->line_one);
        self::assertEquals('line_two a', $actual->line_two);
        self::assertEquals($countryA, $actual->country);
        self::assertEquals($cityA, $actual->city);

        $this->flushQueryLog();

        // Second call should return same instance
        self::assertSame($actual, $provider->get(
            $countryA,
            $cityA,
            ' postcode A ',
            'linE_one a',
            'line_two  a',
            $factory,
        ));
        self::assertSame($actual, $provider->get(
            $countryA,
            $cityA,
            ' poSTCOde A ',
            'linE_one a',
            ' lIne_two  a',
            $factory,
        ));
        self::assertCount(0, $this->getQueryLog());

        self::assertNotSame($actual, $provider->get(
            $countryA,
            $cityA,
            ' poSTCOde A ',
            'linE_one a',
            ' lIne_two  b',
            $factory,
        ));

        $this->flushQueryLog();

        // Should be found in DB
        $foundA = $provider->get($countryA, $cityA, 'postcode c', 'line_one c  line_two c', '', $factory);
        $foundB = $provider->get($countryA, $cityA, 'postcode c', 'line_one c', 'line_two c', $factory);

        self::assertNotNull($foundA);
        self::assertEquals($foundA, $foundB);
        self::assertFalse($foundA->wasRecentlyCreated);
        self::assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not, the new object should be created
        $spy     = Mockery::spy(static function () use ($countryB, $cityB): Location {
            return Location::factory()->create([
                'country_id' => $countryB,
                'city_id'    => $cityB,
                'postcode'   => 'Postcode',
                'state'      => 'New',
                'line_one'   => 'line_One a',
                'line_two'   => 'Line_two a',
            ]);
        });
        $created = $provider->get(
            $countryB,
            $cityB,
            'Postcode',
            'line_One a',
            'Line_two a',
            Closure::fromCallable($spy),
        );

        $spy->shouldHaveBeenCalled();

        self::assertNotNull($created);
        self::assertEquals('Postcode', $created->postcode);
        self::assertEquals('New', $created->state);
        self::assertEquals('line_One a', $created->line_one);
        self::assertEquals('Line_two a', $created->line_two);
        self::assertEquals($countryB->getKey(), $created->country_id);
        self::assertEquals($cityB->getKey(), $created->city_id);
        self::assertCount(2, $this->getQueryLog());

        $this->flushQueryLog();

        // The created object should be in cache
        self::assertSame(
            $created,
            $provider->get($countryB, $cityB, 'Postcode', 'line_one  a', 'LINE_two a', $factory),
        );
        self::assertCount(0, $this->getQueryLog());
    }
}
