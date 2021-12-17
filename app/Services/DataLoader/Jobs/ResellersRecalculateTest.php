<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Kpi;
use App\Models\Location;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Models\ResellerLocation;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\ResellersRecalculate
 */
class ResellersRecalculateTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::process
     */
    public function testProcess(): void {
        // Setup
        $this->overrideDateFactory();
        $this->overrideUuidFactory();

        // Prepare
        $count     = $this->faker->randomNumber(3);
        $locationA = Location::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $locationB = Location::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $locationC = Location::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $locationD = Location::factory()->create([
            'id'         => Str::uuid()->toString(),
            'deleted_at' => Date::now(),
        ]);
        $resellerA = Reseller::factory()
            ->hasCustomers(1, [
                'id' => Str::uuid()->toString(),
            ])
            ->hasLocations(1, [
                'id'          => Str::uuid()->toString(),
                'location_id' => $locationA,
            ])
            ->hasStatuses([
                'id'          => Str::uuid()->toString(),
                'object_type' => (new Reseller())->getMorphClass(),
            ])
            ->create([
                'id'              => Str::uuid()->toString(),
                'customers_count' => $count,
                'locations_count' => $count,
                'assets_count'    => $count,
                'contacts_count'  => $count,
                'statuses_count'  => $count,
            ]);
        $resellerB = Reseller::factory()
            ->hasContacts(1, [
                'id' => Str::uuid()->toString(),
            ])
            ->create([
                'id'              => Str::uuid()->toString(),
                'customers_count' => $count,
                'locations_count' => $count,
                'assets_count'    => $count,
                'contacts_count'  => $count,
                'statuses_count'  => $count,
            ]);
        $resellerC = Reseller::factory()
            ->create([
                'id'              => Str::uuid()->toString(),
                'customers_count' => $count,
                'locations_count' => $count,
                'assets_count'    => $count,
                'contacts_count'  => $count,
                'statuses_count'  => $count,
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
            ->hasLocations(1, [
                'id'          => Str::uuid()->toString(),
                'location_id' => $locationC,
            ])
            ->create([
                'id' => Str::uuid()->toString(),
            ]);
        $customerC = Customer::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $customerD = Customer::factory()->create([
            'id'         => Str::uuid()->toString(),
            'deleted_at' => Date::now(),
        ]);
        $customerE = Customer::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);

        $kpiA = Kpi::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $kpiB = Kpi::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);

        ResellerCustomer::factory()->create([
            'id'              => Str::uuid()->toString(),
            'reseller_id'     => $resellerA,
            'customer_id'     => $customerA,
            'assets_count'    => $count,
            'locations_count' => $count,
            'kpi_id'          => $kpiA,
        ]);
        ResellerCustomer::factory()->create([
            'id'              => Str::uuid()->toString(),
            'reseller_id'     => $resellerA,
            'customer_id'     => $customerE,
            'assets_count'    => $count,
            'locations_count' => $count,
            'kpi_id'          => $kpiB,
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
            'customer_id' => null,
            'location_id' => null,
        ]);
        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => $resellerA,
            'customer_id' => $customerB,
            'location_id' => $locationA,
        ]);
        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => $resellerA,
            'customer_id' => $customerD,
            'location_id' => $locationB,
        ]);
        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => $resellerA,
            'customer_id' => $customerD,
            'location_id' => $locationD,
        ]);
        Document::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => $resellerA,
            'customer_id' => $customerC,
        ]);
        Document::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => $resellerA,
            'customer_id' => null,
        ]);
        Document::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => $resellerB,
            'customer_id' => $customerD,
        ]);

        // Test
        $queries = $this->getQueryLog();
        $job     = $this->app->make(ResellersRecalculate::class)
            ->setModels(new Collection([$resellerA, $resellerB, $resellerC]));

        $job();

        $this->assertQueryLogEquals('.json', $queries);

        // Properties
        $customers  = static function (Reseller $reseller): Collection {
            return ResellerCustomer::query()
                ->where('reseller_id', '=', $reseller->getKey())
                ->orderBy('id')
                ->get();
        };
        $locations  = static function (Reseller $reseller): Collection {
            return ResellerLocation::query()
                ->where('reseller_id', '=', $reseller->getKey())
                ->orderBy('id')
                ->get();
        };
        $aReseller  = $resellerA->refresh();
        $aCustomers = $customers($aReseller);
        $aLocations = $locations($aReseller);
        $bReseller  = $resellerB->refresh();
        $bCustomers = $customers($bReseller);
        $bLocations = $locations($bReseller);
        $cReseller  = $resellerC->refresh();
        $cCustomers = $customers($bReseller);
        $cLocations = $locations($bReseller);
        $attributes = [
            'customer_id',
            'location_id',
            'kpi_id',
        ];

        // A
        $this->assertEquals([
            'customers_count' => 4,
            'locations_count' => 1,
            'assets_count'    => 5,
            'contacts_count'  => 0,
            'statuses_count'  => 1,
            'kpi_id'          => null,
        ], $this->getModelCountableProperties($aReseller, $attributes));

        $this->assertEquals([
            [
                'assets_count'    => 1,
                'locations_count' => 1,
                'customer_id'     => $customerA->getKey(),
                'kpi_id'          => $kpiA->getKey(),
            ],
            [
                'assets_count'    => 0,
                'locations_count' => 0,
                'customer_id'     => $customerE->getKey(),
                'kpi_id'          => $kpiB->getKey(),
            ],
            [
                'assets_count'    => 0,
                'locations_count' => 0,
                'customer_id'     => $customerC->getKey(),
                'kpi_id'          => null,
            ],
            [
                'assets_count'    => 1,
                'locations_count' => 0,
                'customer_id'     => $customerB->getKey(),
                'kpi_id'          => null,
            ],
        ], $this->getModelCountableProperties($aCustomers, $attributes));

        $this->assertEquals([
            [
                'customers_count' => 2,
                'assets_count'    => 2,
                'location_id'     => $locationA->getKey(),
            ],
        ], $this->getModelCountableProperties($aLocations, $attributes));

        // B
        $this->assertEquals([
            'customers_count' => 0,
            'locations_count' => 0,
            'assets_count'    => 0,
            'contacts_count'  => 1,
            'statuses_count'  => 0,
            'kpi_id'          => null,
        ], $this->getModelCountableProperties($bReseller, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($bCustomers, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($bLocations, $attributes));

        // C
        $this->assertEquals([
            'customers_count' => 0,
            'locations_count' => 0,
            'assets_count'    => 0,
            'contacts_count'  => 0,
            'statuses_count'  => 0,
            'kpi_id'          => null,
        ], $this->getModelCountableProperties($cReseller, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($cCustomers, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($cLocations, $attributes));
    }
}
