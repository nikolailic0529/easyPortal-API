<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Data\Location;
use App\Models\LocationCustomer;
use App\Models\LocationReseller;
use App\Models\Reseller;
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
 * @covers \App\Services\Recalculator\Processor\Processors\LocationsProcessor
 */
class LocationsProcessorTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Setup
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
        $this->overrideUuidFactory('da788e31-1a09-4ba8-8dd3-016b3dc1db61');
        $this->override(ExceptionHandler::class);

        // Prepare
        $count     = $this->faker->numberBetween(10, 100);
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
        $locationD = Location::factory()->create([
            'id' => Str::uuid()->toString(),
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
        $events  = Event::fake(ModelsRecalculated::class);

        $this->app->make(LocationsProcessor::class)
            ->setKeys([$locationA->getKey(), $locationB->getKey(), $locationC->getKey(), $locationD->getKey()])
            ->start();

        self::assertQueryLogEquals('~queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~events.json',
            $events->dispatched(ModelsRecalculated::class),
        );

        $queries->flush();

        unset($events);

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
        self::assertEquals([
            'customers_count' => 2,
            'assets_count'    => 2,
        ], $this->getModelCountableProperties($aLocation, $attributes));

        self::assertEquals([
            [
                'assets_count' => 2,
                'reseller_id'  => $resellerA->getKey(),
            ],
        ], $this->getModelCountableProperties($aResellers, $attributes));

        self::assertEquals([
            [
                'assets_count' => 1,
                'customer_id'  => $customerB->getKey(),
            ],
            [
                'assets_count' => 1,
                'customer_id'  => $customerA->getKey(),
            ],
        ], $this->getModelCountableProperties($aCustomers, $attributes));

        // B
        self::assertEquals([
            'customers_count' => 0,
            'assets_count'    => 1,
        ], $this->getModelCountableProperties($bLocation, $attributes));

        self::assertEquals([
            // empty
        ], $this->getModelCountableProperties($bResellers, $attributes));

        self::assertEquals([
            // empty
        ], $this->getModelCountableProperties($bCustomers, $attributes));

        // C
        self::assertEquals([
            'customers_count' => 0,
            'assets_count'    => 0,
        ], $this->getModelCountableProperties($cLocation, $attributes));

        self::assertEquals([
            // empty
        ], $this->getModelCountableProperties($cResellers, $attributes));

        self::assertEquals([
            // empty
        ], $this->getModelCountableProperties($cCustomers, $attributes));
    }
}
