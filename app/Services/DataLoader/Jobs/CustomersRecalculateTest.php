<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location;
use App\Services\DataLoader\Testing\Helper;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\CustomersRecalculate
 */
class CustomersRecalculateTest extends TestCase {
    use WithQueryLog;
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
        $job     = $this->app->make(CustomersRecalculate::class)
            ->setModels(new Collection([$customerA, $customerB]));

        $job();

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json();

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);

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
        $this->assertEquals([
            'locations_count' => 1,
            'contacts_count'  => 1,
            'statuses_count'  => 2,
            'assets_count'    => 3,
        ], $this->getModelCountableProperties($aCustomer, $attributes));

        $this->assertEquals([
            [
                'assets_count' => 1,
                'location_id'  => $locationA->getKey(),
            ],
        ], $this->getModelCountableProperties($aLocations, $attributes));

        // B
        $this->assertEquals([
            'locations_count' => 0,
            'contacts_count'  => 0,
            'statuses_count'  => 0,
            'assets_count'    => 1,
        ], $this->getModelCountableProperties($bCustomer, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($bLocations, $attributes));
    }
}
