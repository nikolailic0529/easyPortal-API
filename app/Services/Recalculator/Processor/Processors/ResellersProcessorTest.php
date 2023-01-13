<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Data\Location;
use App\Models\Data\Type;
use App\Models\Document;
use App\Models\Kpi;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Models\ResellerLocation;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Recalculator\Testing\Helper;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\Recalculator\Processor\ChunkData
 * @covers \App\Services\Recalculator\Processor\Processors\ResellersChunkData
 * @covers \App\Services\Recalculator\Processor\Processors\ResellersProcessor
 */
class ResellersProcessorTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Setup
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
        $this->overrideUuidFactory('da788e31-1a09-4ba8-8dd3-016b3dc1db61');
        $this->override(ExceptionHandler::class);

        // Prepare
        $count        = $this->faker->randomNumber(3);
        $type         = Type::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $quoteType    = Type::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $contractType = Type::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $locationA    = Location::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $locationB    = Location::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $locationC    = Location::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $locationD    = Location::factory()->create([
            'id'         => Str::uuid()->toString(),
            'deleted_at' => Date::now(),
        ]);
        $resellerA    = Reseller::factory()
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
        $resellerB    = Reseller::factory()
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
        $resellerC    = Reseller::factory()
            ->create([
                'id'              => Str::uuid()->toString(),
                'customers_count' => $count,
                'locations_count' => $count,
                'assets_count'    => $count,
                'contacts_count'  => $count,
                'statuses_count'  => $count,
            ]);
        $resellerD    = Reseller::factory()
            ->create([
                'id'              => Str::uuid()->toString(),
                'customers_count' => 0,
                'locations_count' => 0,
                'assets_count'    => 0,
                'contacts_count'  => 0,
                'statuses_count'  => 0,
            ]);
        $customerA    = Customer::factory()
            ->hasLocations(1, [
                'id'          => Str::uuid()->toString(),
                'location_id' => $locationA,
            ])
            ->create([
                'id' => Str::uuid()->toString(),
            ]);
        $customerB    = Customer::factory()
            ->hasLocations(1, [
                'id'          => Str::uuid()->toString(),
                'location_id' => $locationC,
            ])
            ->create([
                'id' => Str::uuid()->toString(),
            ]);
        $customerC    = Customer::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $customerD    = Customer::factory()->create([
            'id'         => Str::uuid()->toString(),
            'deleted_at' => Date::now(),
        ]);
        $customerE    = Customer::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $kpiA         = Kpi::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $kpiB         = Kpi::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);

        $this->setSettings([
            'ep.contract_types' => [$contractType->getKey()],
            'ep.quote_types'    => [$quoteType->getKey()],
        ]);

        ResellerCustomer::factory()->create([
            'id'           => Str::uuid()->toString(),
            'reseller_id'  => $resellerA,
            'customer_id'  => $customerA,
            'assets_count' => $count,
            'kpi_id'       => $kpiA,
        ]);
        ResellerCustomer::factory()->create([
            'id'           => Str::uuid()->toString(),
            'reseller_id'  => $resellerA,
            'customer_id'  => $customerE,
            'assets_count' => $count,
            'kpi_id'       => $kpiB,
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
            'type_id'     => $contractType,
            'reseller_id' => $resellerA,
            'customer_id' => $customerC,
        ]);
        Document::factory()->create([
            'id'          => Str::uuid()->toString(),
            'type_id'     => $type,
            'reseller_id' => $resellerA,
            'customer_id' => null,
        ]);
        Document::factory()->create([
            'id'          => Str::uuid()->toString(),
            'type_id'     => $quoteType,
            'reseller_id' => $resellerB,
            'customer_id' => $customerD,
        ]);
        Document::factory()->create([
            'id'          => Str::uuid()->toString(),
            'type_id'     => $quoteType,
            'reseller_id' => $resellerB,
            'customer_id' => $customerB,
        ]);
        Document::factory()->create([
            'id'          => Str::uuid()->toString(),
            'type_id'     => $type,
            'reseller_id' => $resellerB,
            'customer_id' => $customerB,
        ]);

        // Test
        $queries = $this->getQueryLog();
        $events  = Event::fake(ModelsRecalculated::class);

        $this->app->make(ResellersProcessor::class)
            ->setKeys([$resellerA->getKey(), $resellerB->getKey(), $resellerC->getKey(), $resellerD->getKey()])
            ->start();

        self::assertQueryLogEquals('~queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~events.json',
            $events->dispatched(ModelsRecalculated::class),
        );

        $queries->flush();

        unset($events);

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
        $cCustomers = $customers($cReseller);
        $cLocations = $locations($cReseller);
        $attributes = [
            'customer_id',
            'location_id',
            'kpi_id',
        ];

        // A
        self::assertEquals([
            'customers_count' => 4,
            'locations_count' => 1,
            'assets_count'    => 5,
            'contacts_count'  => 0,
            'statuses_count'  => 1,
            'kpi_id'          => null,
        ], $this->getModelCountableProperties($aReseller, $attributes));

        self::assertEquals([
            [
                'assets_count'    => 1,
                'customer_id'     => $customerB->getKey(),
                'kpi_id'          => null,
                'quotes_count'    => 0,
                'contracts_count' => 0,
            ],
            [
                'assets_count'    => 0,
                'customer_id'     => $customerE->getKey(),
                'kpi_id'          => $kpiB->getKey(),
                'quotes_count'    => 0,
                'contracts_count' => 0,
            ],
            [
                'assets_count'    => 1,
                'customer_id'     => $customerA->getKey(),
                'kpi_id'          => $kpiA->getKey(),
                'quotes_count'    => 0,
                'contracts_count' => 0,
            ],
            [
                'assets_count'    => 0,
                'customer_id'     => $customerC->getKey(),
                'kpi_id'          => null,
                'quotes_count'    => 0,
                'contracts_count' => 1,
            ],
        ], $this->getModelCountableProperties($aCustomers, $attributes));

        self::assertEquals([
            [
                'customers_count' => 2,
                'assets_count'    => 2,
                'location_id'     => $locationA->getKey(),
            ],
        ], $this->getModelCountableProperties($aLocations, $attributes));

        // B
        self::assertEquals([
            'customers_count' => 1,
            'locations_count' => 0,
            'assets_count'    => 0,
            'contacts_count'  => 1,
            'statuses_count'  => 0,
            'kpi_id'          => null,
        ], $this->getModelCountableProperties($bReseller, $attributes));

        self::assertEquals([
            [
                'assets_count'    => 0,
                'customer_id'     => $customerB->getKey(),
                'kpi_id'          => null,
                'quotes_count'    => 1,
                'contracts_count' => 0,
            ],
        ], $this->getModelCountableProperties($bCustomers, $attributes));

        self::assertEquals([
            // empty
        ], $this->getModelCountableProperties($bLocations, $attributes));

        // C
        self::assertEquals([
            'customers_count' => 0,
            'locations_count' => 0,
            'assets_count'    => 0,
            'contacts_count'  => 0,
            'statuses_count'  => 0,
            'kpi_id'          => null,
        ], $this->getModelCountableProperties($cReseller, $attributes));

        self::assertEquals([
            // empty
        ], $this->getModelCountableProperties($cCustomers, $attributes));

        self::assertEquals([
            // empty
        ], $this->getModelCountableProperties($cLocations, $attributes));
    }
}
