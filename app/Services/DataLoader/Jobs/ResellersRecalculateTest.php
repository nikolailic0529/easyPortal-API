<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Reseller;
use App\Services\DataLoader\Testing\Helper;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\ResellersRecalculate
 */
class ResellersRecalculateTest extends TestCase {
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
        $resellerA = Reseller::factory()
            ->hasCustomers(1)
            ->hasStatuses([
                'object_type' => (new Reseller())->getMorphClass(),
            ])
            ->create([
                'customers_count' => $count,
                'locations_count' => $count,
                'assets_count'    => $count,
                'contacts_count'  => $count,
                'statuses_count'  => $count,
            ]);
        $resellerB = Reseller::factory()->create([
            'customers_count' => $count,
            'locations_count' => $count,
            'assets_count'    => $count,
            'contacts_count'  => $count,
            'statuses_count'  => $count,
        ]);
        $locationA = Location::factory()->create();
        $locationB = Location::factory()->create();
        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();

        GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            static function () use ($customerA, $customerB, $locationA, $locationB): void {
                $customerA->locations = [$locationA, $locationB];
                $customerB->locations = [$locationA, $locationB];
            },
        );

        Asset::factory()->create([
            'reseller_id' => $resellerA,
            'customer_id' => $customerA,
            'location_id' => $locationA,
        ]);
        Asset::factory()->create([
            'reseller_id' => $resellerA,
            'customer_id' => $customerB,
            'location_id' => $locationA,
        ]);

        // Test
        $queries = $this->getQueryLog();
        $job     = $this->app->make(ResellersRecalculate::class)
            ->setModels(new Collection([$resellerA, $resellerB]));

        $job();

        $actual   = $this->cleanupQueryLog($queries->get());
        $expected = $this->getTestData()->json();

        $this->assertCount(count($expected), $actual);
        $this->assertEquals($expected, $actual);

        // Properties
        $resellerA  = $resellerA->refresh();
        $resellerB  = $resellerB->refresh();
        $attributes = [
            'customers_count',
            'locations_count',
            'assets_count',
            'contacts_count',
            'statuses_count',
        ];

        $this->assertEquals([
            'customers_count' => 2,
            'locations_count' => 0,
            'assets_count'    => 2,
            'contacts_count'  => 0,
            'statuses_count'  => 1,
        ], $resellerA->only($attributes));

        $this->assertEquals([
            'customers_count' => 0,
            'locations_count' => 0,
            'assets_count'    => 0,
            'contacts_count'  => 0,
            'statuses_count'  => 0,
        ], $resellerB->only($attributes));
    }
}
