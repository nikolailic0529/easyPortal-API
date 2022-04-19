<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Map;

use App\Models\Asset;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\User;
use Tests\TestCase;

class MapDataFactory {
    public function __invoke(TestCase $test, ?Organization $org, ?User $user): void {
        // Customers
        $customerA = Customer::factory()->create([
            'id' => 'ad16444a-46a4-3036-b893-7636e2e6209b',
        ]);
        $customerB = Customer::factory()->create([
            'id' => 'bb699764-e10b-4e09-9fea-dd7a62238dd5',
        ]);

        // Resellers
        $resellerA = Reseller::factory()->create([
            'id' => $org->getKey(),
        ]);
        $resellerB = Reseller::factory()->create();

        $resellerA->customers()->attach($customerA);
        $resellerB->customers()->attach($customerB);

        $code    = 0;
        $city    = City::factory()->create([
            'id' => 'c6c90bff-b032-361a-b455-a61e2f3ca288',
        ]);
        $country = Country::factory()->create([
            'id'   => 'c6c90bff-b032-361a-b455-a61e2f3ca289',
            'code' => $code++,
        ]);

        // Inside
        $locationA = Location::factory()->create([
            'id'         => '4d9133ff-482b-4605-870f-9ee88c2062ae',
            'geohash'    => 'u72',
            'latitude'   => 1.00,
            'longitude'  => 1.00,
            'country_id' => Country::factory()->create(['code' => $code++]),
            'city_id'    => $city->getKey(),
        ]);

        $locationA->resellers()->attach($resellerA);
        $locationA->customers()->attach($customerA);

        Asset::factory()->create([
            'location_id' => $locationA,
            'reseller_id' => $resellerA,
            'customer_id' => $customerA,
        ]);

        $locationB = Location::factory()->create([
            'id'         => '6aa4fc05-c3f2-4ad5-a9de-e867772a7335',
            'geohash'    => 'u73',
            'latitude'   => 1.10,
            'longitude'  => 1.10,
            'country_id' => $country->getKey(),
            'city_id'    => City::factory(),
        ]);

        $locationB->customers()->attach($customerA);

        Asset::factory()->create([
            'location_id' => $locationB,
            'customer_id' => $customerA,
        ]);

        $locationC = Location::factory()->create([
            'id'         => '8d8a056f-b224-4d4f-90af-7e0eced13217',
            'geohash'    => 'ue2',
            'latitude'   => 1.25,
            'longitude'  => 1.25,
            'country_id' => Country::factory()->create(['code' => $code++]),
            'city_id'    => City::factory(),
        ]);

        $locationC->customers()->attach($customerB);

        Asset::factory()->create([
            'location_id' => $locationC,
            'customer_id' => $customerB,
        ]);

        $locationD = Location::factory()->create([
            'id'         => '6162c51f-1c24-4e03-a3e7-b26975c7bac7',
            'geohash'    => 'ug2',
            'latitude'   => 1.5,
            'longitude'  => 1.5,
            'country_id' => Country::factory()->create(['code' => $code++]),
            'city_id'    => City::factory(),
        ]);

        $locationD->resellers()->attach($resellerA);
        $locationD->customers()->attach($customerA);
        $locationD->customers()->attach($customerB);

        Asset::factory()->create([
            'location_id' => $locationD,
            'reseller_id' => $resellerB,
            'customer_id' => $customerB,
        ]);

        // No coordinates
        $locationE = Location::factory()->create([
            'latitude'   => null,
            'longitude'  => null,
            'geohash'    => null,
            'country_id' => Country::factory()->create(['code' => $code++]),
            'city_id'    => City::factory(),
        ]);

        $locationE->resellers()->attach($resellerA);
        $locationE->customers()->attach($customerA);

        // Outside
        $locationF = Location::factory()->create([
            'latitude'   => -1.00,
            'longitude'  => 1.00,
            'geohash'    => 'u72',
            'country_id' => Country::factory()->create(['code' => $code++]),
            'city_id'    => City::factory(),
        ]);

        $locationF->resellers()->attach($resellerA);
        $locationF->customers()->attach($customerA);

        // Empty
        Location::factory()->create([
            'latitude'   => 2,
            'longitude'  => 2,
            'geohash'    => 'u72',
            'country_id' => Country::factory()->create(['code' => $code++]),
            'city_id'    => City::factory(),
        ]);
    }
}
