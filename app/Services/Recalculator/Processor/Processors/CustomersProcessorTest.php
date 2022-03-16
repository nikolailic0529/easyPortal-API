<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Recalculator\Testing\Helper;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Processor\Processors\CustomersProcessor
 */
class CustomersProcessorTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    /**
     * @covers ::run
     * @covers ::process
     */
    public function testRun(): void {
        // Setup
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
        $this->overrideUuidFactory('da788e31-1a09-4ba8-8dd3-016b3dc1db61');
        $this->override(ExceptionHandler::class);

        // Prepare
        $count     = $this->faker->randomNumber(3);
        $locationA = Location::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $locationB = Location::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $locationC = Location::factory()->create([
            'id'         => Str::uuid()->toString(),
            'deleted_at' => Date::now(),
        ]);
        $customerA = Customer::factory()
            ->hasLocations(1, [
                'id'           => Str::uuid()->toString(),
                'location_id'  => $locationA,
                'assets_count' => $count,
            ])
            ->hasContacts(1)
            ->hasStatuses(2)
            ->create([
                'id' => Str::uuid()->toString(),
            ]);
        $customerB = Customer::factory()
            ->create([
                'id' => Str::uuid()->toString(),
            ]);

        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => null,
            'customer_id' => $customerA,
            'location_id' => $locationA,
        ]);
        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => null,
            'customer_id' => $customerA,
            'location_id' => $locationB,
        ]);
        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => null,
            'customer_id' => $customerA,
            'location_id' => $locationC,
        ]);
        Asset::factory()->create([
            'id'          => Str::uuid()->toString(),
            'reseller_id' => null,
            'customer_id' => $customerB,
            'location_id' => $locationC,
        ]);

        // Test
        $queries = $this->getQueryLog();
        $events  = Event::fake(ModelsRecalculated::class);

        $this->app->make(CustomersProcessor::class)
            ->setKeys([$customerA->getKey(), $customerB->getKey()])
            ->start();

        self::assertQueryLogEquals('~queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~events.json',
            $events->dispatched(ModelsRecalculated::class),
        );

        $queries->flush();

        unset($events);

        // Properties
        $locations  = static function (Customer $customer): Collection {
            return GlobalScopes::callWithoutGlobalScope(
                OwnedByOrganizationScope::class,
                static function () use ($customer): Collection {
                    return CustomerLocation::query()
                        ->where('customer_id', '=', $customer->getKey())
                        ->orderBy('id')
                        ->get();
                },
            );
        };
        $aCustomer  = $customerA->refresh();
        $aLocations = $locations($aCustomer);
        $bCustomer  = $customerB->refresh();
        $bLocations = $locations($bCustomer);
        $attributes = [
            'location_id',
        ];

        // A
        self::assertEquals([
            'locations_count' => 1,
            'contacts_count'  => 1,
            'statuses_count'  => 2,
            'assets_count'    => 3,
        ], $this->getModelCountableProperties($aCustomer, $attributes));

        self::assertEquals([
            [
                'assets_count' => 1,
                'location_id'  => $locationA->getKey(),
            ],
        ], $this->getModelCountableProperties($aLocations, $attributes));

        // B
        self::assertEquals([
            'locations_count' => 0,
            'contacts_count'  => 0,
            'statuses_count'  => 0,
            'assets_count'    => 1,
        ], $this->getModelCountableProperties($bCustomer, $attributes));

        self::assertEquals([
            // empty
        ], $this->getModelCountableProperties($bLocations, $attributes));
    }
}
