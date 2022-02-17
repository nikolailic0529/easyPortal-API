<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use App\Models\LocationCustomer;
use App\Models\LocationReseller;
use App\Models\Reseller;
use App\Services\Recalculator\Testing\Helper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Jobs\LocationsRecalculate
 */
class LocationsRecalculateTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcess(): void {
        // Setup
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
        $this->overrideUuidFactory('da788e31-1a09-4ba8-8dd3-016b3dc1db61');

        // Prepare
        $count     = $this->faker->randomNumber(3);
        $locationA = Location::factory()->create([
            'id'              => Str::uuid()->toString(),
            'assets_count'    => $count,
            'customers_count' => $count,
        ]);
        $locationB = Location::factory()->create([
            'id'              => Str::uuid()->toString(),
            'assets_count'    => $count,
            'customers_count' => $count,
        ]);
        $locationC = Location::factory()->create([
            'id'              => Str::uuid()->toString(),
            'assets_count'    => $count,
            'customers_count' => $count,
        ]);
        $resellerA = Reseller::factory()
            ->hasLocations(1, [
                'id'          => Str::uuid()->toString(),
                'location_id' => $locationA,
            ])
            ->create([
                'id' => Str::uuid()->toString(),
            ]);
        $resellerB = Reseller::factory()
            ->create([
                'id'         => Str::uuid()->toString(),
                'deleted_at' => Date::now(),
            ]);
        $customerA = Customer::factory()
            ->hasLocations(1, [
                'id'          => Str::uuid()->toString(),
                'location_id' => $locationA,
            ])
            ->create([
                'id' => Str::uuid()->toString(),
            ]);
        $customerB = Customer::factory()
            ->create([
                'id' => Str::uuid()->toString(),
            ]);
        $customerC = Customer::factory()
            ->create([
                'id'         => Str::uuid()->toString(),
                'deleted_at' => Date::now(),
            ]);

        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => $resellerA,
            'customer_id' => $customerA,
            'location_id' => $locationA,
        ]);
        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => $resellerA,
            'customer_id' => $customerB,
            'location_id' => $locationA,
        ]);
        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => $resellerB,
            'customer_id' => $customerC,
            'location_id' => $locationB,
        ]);

        // Test
        $queries = $this->getQueryLog();
        $job     = $this->app->make(LocationsRecalculate::class)
            ->init(new Collection([$locationA, $locationB, $locationC]));

        $job();

        $this->assertQueryLogEquals('.json', $queries);

        // Properties
        $resellers  = static function (Location $location): Collection {
            return LocationReseller::query()
                ->where('location_id', '=', $location->getKey())
                ->orderBy('id')
                ->get();
        };
        $customers  = static function (Location $location): Collection {
            return LocationCustomer::query()
                ->where('location_id', '=', $location->getKey())
                ->orderBy('id')
                ->get();
        };
        $aLocation  = $locationA->refresh();
        $aResellers = $resellers($aLocation);
        $aCustomers = $customers($aLocation);
        $bLocation  = $locationB->refresh();
        $bResellers = $resellers($bLocation);
        $bCustomers = $customers($bLocation);
        $cLocation  = $locationC->refresh();
        $cResellers = $resellers($cLocation);
        $cCustomers = $customers($cLocation);
        $attributes = [
            'reseller_id',
            'customer_id',
        ];

        // A
        $this->assertEquals([
            'customers_count' => 2,
            'assets_count'    => 2,
        ], $this->getModelCountableProperties($aLocation, $attributes));

        $this->assertEquals([
            [
                'assets_count' => 2,
                'reseller_id'  => $resellerA->getKey(),
            ],
        ], $this->getModelCountableProperties($aResellers, $attributes));

        $this->assertEquals([
            [
                'assets_count' => 1,
                'customer_id'  => $customerA->getKey(),
            ],
            [
                'assets_count' => 1,
                'customer_id'  => $customerB->getKey(),
            ],
        ], $this->getModelCountableProperties($aCustomers, $attributes));

        // B
        $this->assertEquals([
            'customers_count' => 0,
            'assets_count'    => 1,
        ], $this->getModelCountableProperties($bLocation, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($bResellers, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($bCustomers, $attributes));

        // C
        $this->assertEquals([
            'customers_count' => 0,
            'assets_count'    => 0,
        ], $this->getModelCountableProperties($cLocation, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($cResellers, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($cCustomers, $attributes));
    }
}
