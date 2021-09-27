<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

use function array_column;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\CustomerFactory
 */
class CustomerFactoryTest extends TestCase {
    use WithoutOrganizationScope;
    use WithQueryLog;
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory = $this->app->make(CustomerFactory::class);
        $json    = $this->getTestData()->json('~customer-full.json');
        $company = new Company($json);

        $this->flushQueryLog();

        $factory->find($company);

        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(CustomerFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        if ($expected) {
            $factory->shouldReceive($expected)
                ->once()
                ->with($type)
                ->andReturns();
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompany(): void {
        // Prepare
        $factory    = $this->app->make(CustomerFactory::class);
        $normalizer = $this->app->make(Normalizer::class);

        // Load
        $json    = $this->getTestData()->json('~customer-full.json');
        $company = new Company($json);

        $this->flushQueryLog();

        // Test
        $customer = $factory->create($company);

        $this->assertEquals(
            $this->getTestData()->json('~createFromCompany-create-expected.json'),
            array_column($this->getQueryLog(), 'query'),
        );
        $this->assertNotNull($customer);
        $this->assertTrue($customer->wasRecentlyCreated);
        $this->assertEquals($company->id, $customer->getKey());
        $this->assertEquals($company->name, $customer->name);
        $this->assertEquals($company->updatedAt, $this->getDatetime($customer->changed_at));
        $this->assertEquals($this->getCompanyType($company), $customer->type->key);
        $this->assertCount(2, $customer->statuses);
        $this->assertEquals($this->getCompanyStatuses($company), $this->getModelStatuses($customer));
        $this->assertCount(2, $customer->locations);
        $this->assertEquals(2, $customer->locations_count);
        $this->assertEquals(
            $this->getCompanyLocations($company),
            $this->getCompanyModelLocations($customer),
        );
        $this->assertCount(4, $customer->contacts);
        $this->assertEquals(4, $customer->contacts_count);
        $this->assertEquals(
            $this->getContacts($company),
            $this->getModelContacts($customer),
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->totalAssets),
            $customer->kpi->assets_total,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeAssets),
            $customer->kpi->assets_active,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->activeAssetsPercentage),
            $customer->kpi->assets_active_percent,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeCustomers),
            $customer->kpi->customers_active,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->newActiveCustomers),
            $customer->kpi->customers_active_new,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeContracts),
            $customer->kpi->contracts_active,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->activeContractTotalAmount),
            $customer->kpi->contracts_active_amount,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->newActiveContracts),
            $customer->kpi->contracts_active_new,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->expiringContracts),
            $customer->kpi->contracts_expiring,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeQuotes),
            $customer->kpi->quotes_active,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->activeQuotesTotalAmount),
            $customer->kpi->quotes_active_amount,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->newActiveQuotes),
            $customer->kpi->quotes_active_new,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->expiringQuotes),
            $customer->kpi->quotes_expiring,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->expiredQuotes),
            $customer->kpi->quotes_expired,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->expiredContracts),
            $customer->kpi->contracts_expired,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->orderedQuotes),
            $customer->kpi->quotes_ordered,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->acceptedQuotes),
            $customer->kpi->quotes_accepted,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->requestedQuotes),
            $customer->kpi->quotes_requested,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->receivedQuotes),
            $customer->kpi->quotes_received,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->rejectedQuotes),
            $customer->kpi->quotes_rejected,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->awaitingQuotes),
            $customer->kpi->quotes_awaiting,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeAssetsOnContract),
            $customer->kpi->assets_active_on_contract,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeAssetsOnWarranty),
            $customer->kpi->assets_active_on_warranty,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeExposedAssets),
            $customer->kpi->assets_active_exposed,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->serviceRevenueTotalAmount),
            $customer->kpi->service_revenue_total_amount,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->serviceRevenueTotalAmountChange),
            $customer->kpi->service_revenue_total_amount_change,
        );

        $this->flushQueryLog();

        // Customer should be updated
        $json    = $this->getTestData()->json('~customer-changed.json');
        $company = new Company($json);
        $updated = $factory->create($company);

        $this->assertEquals(
            $this->getTestData()->json('~createFromCompany-update-expected.json'),
            array_column($this->getQueryLog(), 'query'),
        );
        $this->assertNotNull($updated);
        $this->assertSame($customer, $updated);
        $this->assertEquals($company->id, $updated->getKey());
        $this->assertEquals($company->name, $updated->name);
        $this->assertEquals($company->updatedAt, $this->getDatetime($updated->changed_at));
        $this->assertEquals($this->getCompanyType($company), $updated->type->key);
        $this->assertCount(1, $updated->statuses);
        $this->assertEquals($this->getCompanyStatuses($company), $this->getModelStatuses($updated));
        $this->assertCount(1, $updated->locations);
        $this->assertEqualsCanonicalizing(
            $this->getCompanyLocations($company),
            $this->getCompanyModelLocations($updated),
        );
        $this->assertCount(1, $updated->contacts);
        $this->assertEquals(
            $this->getContacts($company),
            $this->getModelContacts($updated),
        );
        $this->assertNull($updated->kpi);

        $this->flushQueryLog();

        // No changes
        $json    = $this->getTestData()->json('~customer-changed.json');
        $company = new Company($json);

        $factory->create($company);

        $this->assertCount(0, $this->getQueryLog());
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyCustomerOnly(): void {
        // Prepare
        $factory = $this->app->make(CustomerFactory::class);

        // Test
        $json     = $this->getTestData()->json('~customer-only.json');
        $company  = new Company($json);
        $customer = $factory->create($company);

        $this->assertNotNull($customer);
        $this->assertTrue($customer->wasRecentlyCreated);
        $this->assertEquals($company->id, $customer->getKey());
        $this->assertEquals($company->name, $customer->name);
    }

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $a          = new Company([
            'id' => $this->faker->uuid,
        ]);
        $b          = new Company([
            'id' => $this->faker->uuid,
        ]);
        $asset      = new ViewAsset([
            'customerId'    => $b->id,
            'assetDocument' => [
                [
                    'customer' => [
                        'id' => $this->faker->uuid,
                    ],
                    'document' => [
                        'customerId' => $this->faker->uuid,
                    ],
                ],
            ],
        ]);
        $resolver   = $this->app->make(CustomerResolver::class);
        $normalizer = $this->app->make(Normalizer::class);

        $factory = new class($normalizer, $resolver) extends CustomerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected CustomerResolver $customerResolver,
            ) {
                // empty
            }
        };

        $callback = Mockery::spy(function (EloquentCollection $collection): void {
            $this->assertCount(0, $collection);
        });

        $factory->prefetch(
            [$a, $asset, new ViewAsset(['customerId' => $b->id])],
            false,
            Closure::fromCallable($callback),
        );

        $callback->shouldHaveBeenCalled()->once();

        $this->flushQueryLog();

        $factory->find($a);
        $factory->find($b);
        $resolver->get($asset->assetDocument[0]->customer->id);
        $resolver->get($asset->assetDocument[0]->document->customerId);

        $this->assertCount(0, $this->getQueryLog());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            Company::class => ['createFromCompany', new Company()],
            'Unknown'      => [
                null,
                new class() extends Type {
                    // empty
                },
            ],
        ];
    }
    // </editor-fold>
}
