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
        $resellerB = Reseller::factory()
            ->hasContacts(1)
            ->create([
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
            'customer_id' => $customerB,
            'location_id' => $locationA,
        ]);
        Document::factory()->create([
            'reseller_id' => $resellerA,
            'customer_id' => $customerC,
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
        $resellerA  = $resellerA->refresh();
        $customersA = $customers($resellerA);
        $locationsA = $locations($resellerA);
        $resellerB  = $resellerB->refresh();
        $customersB = $customers($resellerB);
        $locationsB = $locations($resellerB);
        $attributes = [
            'customer_id',
        ];

        $this->assertEquals([
            'customers_count' => 2,
            'locations_count' => 0,
            'assets_count'    => 2,
            'contacts_count'  => 0,
            'statuses_count'  => 1,
        ], $this->getModelCountableProperties($resellerA, $attributes));

        $this->assertEquals([
            [
                'assets_count'    => 0,
                'locations_count' => 0,
                'customer_id'     => $customerC->getKey(),
            ],
            [
                'assets_count'    => 1,
                'locations_count' => 1,
                'customer_id'     => $customerB->getKey(),
            ],
            [
                'assets_count'    => 1,
                'locations_count' => 1,
                'customer_id'     => $customerA->getKey(),
            ],
        ], $this->getModelCountableProperties($customersA, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($locationsA, $attributes));

        $this->assertEquals([
            'customers_count' => 0,
            'locations_count' => 0,
            'assets_count'    => 0,
            'contacts_count'  => 1,
            'statuses_count'  => 0,
        ], $this->getModelCountableProperties($resellerB, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($customersB, $attributes));

        $this->assertEquals([
            // empty
        ], $this->getModelCountableProperties($locationsB, $attributes));
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
