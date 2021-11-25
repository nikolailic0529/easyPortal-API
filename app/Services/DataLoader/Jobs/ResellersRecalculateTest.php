<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Location;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Models\ResellerLocation;
use App\Services\DataLoader\Testing\Helper;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\TestCase;

use function count;
use function in_array;
use function str_ends_with;

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
        $locationA = Location::factory()->create();
        $locationB = Location::factory()->create();
        $locationC = Location::factory()->create();
        $resellerA = Reseller::factory()
            ->hasCustomers(1)
            ->hasLocations(1, [
                'location_id' => $locationA,
            ])
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
        $resellerB = Reseller::factory()
            ->hasContacts(1)
            ->create([
                'customers_count' => $count,
                'locations_count' => $count,
                'assets_count'    => $count,
                'contacts_count'  => $count,
                'statuses_count'  => $count,
            ]);
        $customerA = Customer::factory()
            ->hasLocations(1, [
                'location_id' => $locationA,
            ])
            ->create();
        $customerB = Customer::factory()
            ->hasLocations(1, [
                'location_id' => $locationC,
            ])
            ->create();
        $customerC = Customer::factory()->create();

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
            'customer_id' => null,
            'location_id' => null,
        ]);
        Asset::factory()->create([
            'reseller_id' => $resellerA,
            'customer_id' => $customerB,
            'location_id' => $locationA,
        ]);
        Document::factory()->create([
            'reseller_id' => $resellerA,
            'customer_id' => $customerC,
        ]);
        Document::factory()->create([
            'reseller_id' => $resellerA,
            'customer_id' => null,
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
        $attributes = [
            'customer_id',
            'location_id',
        ];

        $this->assertEquals([
            'customers_count' => 3,
            'locations_count' => 1,
            'assets_count'    => 3,
            'contacts_count'  => 0,
            'statuses_count'  => 1,
        ], $this->getModelCountableProperties($aReseller, $attributes));

        $this->assertEquals([
            [
                'assets_count'    => 1,
                'locations_count' => 1,
                'customer_id'     => $customerA->getKey(),
            ],
            [
                'assets_count'    => 0,
                'locations_count' => 0,
                'customer_id'     => $customerC->getKey(),
            ],
            [
                'assets_count'    => 1,
                'locations_count' => 0,
                'customer_id'     => $customerB->getKey(),
            ],
        ], $this->getModelCountableProperties($aCustomers, $attributes));

        $this->assertEquals([
            [
                'customers_count' => 2,
                'assets_count'    => 2,
                'location_id'     => $locationA->getKey(),
            ],
        ], $this->getModelCountableProperties($aLocations, $attributes));

        $this->assertEquals([
            'customers_count' => 0,
            'locations_count' => 0,
            'assets_count'    => 0,
            'contacts_count'  => 1,
            'statuses_count'  => 0,
        ], $this->getModelCountableProperties($bReseller, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($bCustomers, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($bLocations, $attributes));
    }

    /**
     * @param \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model>
     *     |\Illuminate\Database\Eloquent\Model $model
     * @param array<string> $attributes
     *
     * @return array<string, mixed>
     */
    protected function getModelCountableProperties(Collection|Model $model, array $attributes = []): array {
        $properties = [];

        if ($model instanceof Collection) {
            $properties = $model
                ->map(function (Model $model) use ($attributes): array {
                    return $this->getModelCountableProperties($model, $attributes);
                })
                ->all();
        } else {
            foreach ($model->getAttributes() as $attribute => $value) {
                if (str_ends_with($attribute, '_count') || in_array($attribute, $attributes, true)) {
                    $properties[$attribute] = $value;
                }
            }
        }

        return $properties;
    }
}
