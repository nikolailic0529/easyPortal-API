<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Customer;
use App\Models\Kpi;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Company;
use App\Services\DataLoader\Schema\Types\CompanyKpis;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

use function array_column;

/**
 * @internal
 * @covers \App\Services\DataLoader\Factory\Factories\CustomerFactory
 */
class CustomerFactoryTest extends TestCase {
    use WithoutGlobalScopes;
    use WithQueryLog;
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $force   = $this->faker->boolean();
        $factory = Mockery::mock(CustomerFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        if ($expected) {
            $factory->shouldReceive($expected)
                ->once()
                ->with($type, $force)
                ->andReturns();
        } else {
            self::expectException(InvalidArgumentException::class);
            self::expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type, $force);
    }

    public function testCreateFromCompany(): void {
        // Mock
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');

        // Prepare
        $factory = $this->app->make(CustomerFactory::class);

        // Models
        $reseller = Reseller::factory()->create([
            'id' => '38722ba3-d1ee-46d0-ab7a-c9678258e493',
        ]);

        // Load
        $json    = $this->getTestData()->json('~customer-full.json');
        $company = new Company($json);

        // Test
        $queries  = $this->getQueryLog()->flush();
        $customer = $factory->create($company);
        $actual   = array_column($queries->get(), 'query');
        $expected = $this->getTestData()->json('~createFromCompany-create-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($customer);
        self::assertTrue($customer->wasRecentlyCreated);
        self::assertEquals($company->id, $customer->getKey());
        self::assertEquals($company->name, $customer->name);
        self::assertEquals($company->updatedAt, $customer->changed_at);
        self::assertCount(2, $customer->statuses);
        self::assertEquals(2, $customer->statuses_count);
        self::assertEquals($this->getStatuses($company), $this->getModelStatuses($customer));
        self::assertCount(2, $customer->locations);
        self::assertEquals(2, $customer->locations_count);
        self::assertCount(1, $customer->resellersPivots);
        self::assertEquals($reseller->getKey(), $customer->resellersPivots->first()->reseller_id);
        self::assertNotNull($customer->resellersPivots->first()->kpi_id);
        self::assertEquals(
            $this->getCompanyLocations($company),
            $this->getCompanyModelLocations($customer),
        );
        self::assertCount(4, $customer->contacts);
        self::assertEquals(4, $customer->contacts_count);
        self::assertEquals(
            $this->getContacts($company),
            $this->getModelContacts($customer),
        );
        self::assertNotNull($customer->kpi);
        self::assertNotNull($company->companyKpis);
        self::assertEquals(
            $company->companyKpis->totalAssets ?? 0,
            $customer->kpi->assets_total,
        );
        self::assertEquals(
            $company->companyKpis->activeAssets ?? 0,
            $customer->kpi->assets_active,
        );
        self::assertEquals(
            $company->companyKpis->activeAssetsPercentage ?? 0,
            $customer->kpi->assets_active_percent,
        );
        self::assertEquals(
            $company->companyKpis->activeCustomers ?? 0,
            $customer->kpi->customers_active,
        );
        self::assertEquals(
            $company->companyKpis->newActiveCustomers ?? 0,
            $customer->kpi->customers_active_new,
        );
        self::assertEquals(
            $company->companyKpis->activeContracts ?? 0,
            $customer->kpi->contracts_active,
        );
        self::assertEquals(
            $company->companyKpis->activeContractTotalAmount ?? 0,
            $customer->kpi->contracts_active_amount,
        );
        self::assertEquals(
            $company->companyKpis->newActiveContracts ?? 0,
            $customer->kpi->contracts_active_new,
        );
        self::assertEquals(
            $company->companyKpis->expiringContracts ?? 0,
            $customer->kpi->contracts_expiring,
        );
        self::assertEquals(
            $company->companyKpis->activeQuotes ?? 0,
            $customer->kpi->quotes_active,
        );
        self::assertEquals(
            $company->companyKpis->activeQuotesTotalAmount ?? 0,
            $customer->kpi->quotes_active_amount,
        );
        self::assertEquals(
            $company->companyKpis->newActiveQuotes ?? 0,
            $customer->kpi->quotes_active_new,
        );
        self::assertEquals(
            $company->companyKpis->expiringQuotes ?? 0,
            $customer->kpi->quotes_expiring,
        );
        self::assertEquals(
            $company->companyKpis->expiredQuotes ?? 0,
            $customer->kpi->quotes_expired,
        );
        self::assertEquals(
            $company->companyKpis->expiredContracts ?? 0,
            $customer->kpi->contracts_expired,
        );
        self::assertEquals(
            $company->companyKpis->orderedQuotes ?? 0,
            $customer->kpi->quotes_ordered,
        );
        self::assertEquals(
            $company->companyKpis->acceptedQuotes ?? 0,
            $customer->kpi->quotes_accepted,
        );
        self::assertEquals(
            $company->companyKpis->requestedQuotes ?? 0,
            $customer->kpi->quotes_requested,
        );
        self::assertEquals(
            $company->companyKpis->receivedQuotes ?? 0,
            $customer->kpi->quotes_received,
        );
        self::assertEquals(
            $company->companyKpis->rejectedQuotes ?? 0,
            $customer->kpi->quotes_rejected,
        );
        self::assertEquals(
            $company->companyKpis->awaitingQuotes ?? 0,
            $customer->kpi->quotes_awaiting,
        );
        self::assertEquals(
            $company->companyKpis->activeAssetsOnContract ?? 0,
            $customer->kpi->assets_active_on_contract,
        );
        self::assertEquals(
            $company->companyKpis->activeAssetsOnWarranty ?? 0,
            $customer->kpi->assets_active_on_warranty,
        );
        self::assertEquals(
            $company->companyKpis->activeExposedAssets ?? 0,
            $customer->kpi->assets_active_exposed,
        );
        self::assertEquals(
            $company->companyKpis->serviceRevenueTotalAmount ?? 0,
            $customer->kpi->service_revenue_total_amount,
        );
        self::assertEquals(
            $company->companyKpis->serviceRevenueTotalAmountChange ?? 0,
            $customer->kpi->service_revenue_total_amount_change,
        );

        // Customer should be updated
        $json     = $this->getTestData()->json('~customer-changed.json');
        $company  = new Company($json);
        $queries  = $this->getQueryLog()->flush();
        $updated  = $factory->create($company);
        $actual   = array_column($queries->get(), 'query');
        $expected = $this->getTestData()->json('~createFromCompany-update-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($updated);
        self::assertSame($customer, $updated);
        self::assertEquals($company->id, $updated->getKey());
        self::assertEquals($company->name, $updated->name);
        self::assertEquals($company->updatedAt, $updated->changed_at);
        self::assertCount(1, $updated->statuses);
        self::assertEquals(1, $updated->statuses_count);
        self::assertEquals($this->getStatuses($company), $this->getModelStatuses($updated));
        self::assertCount(1, $updated->locations);
        self::assertEqualsCanonicalizing(
            $this->getCompanyLocations($company),
            $this->getCompanyModelLocations($updated),
        );
        self::assertCount(1, $updated->contacts);
        self::assertEquals(
            $this->getContacts($company),
            $this->getModelContacts($updated),
        );
        self::assertNull($updated->kpi);
        self::assertCount(1, $updated->resellersPivots);
        self::assertEquals($reseller->getKey(), $updated->resellersPivots->first()->reseller_id);
        self::assertNull($updated->resellersPivots->first()->kpi_id);

        // No changes
        $json    = $this->getTestData()->json('~customer-changed.json');
        $company = new Company($json);
        $queries = $this->getQueryLog()->flush();

        $factory->create($company);

        self::assertCount(0, $queries->get());
    }

    public function testCreateFromCompanyCustomerOnly(): void {
        // Prepare
        $factory = $this->app->make(CustomerFactory::class);

        // Test
        $json     = $this->getTestData()->json('~customer-only.json');
        $company  = new Company($json);
        $customer = $factory->create($company);

        self::assertNotNull($customer);
        self::assertTrue($customer->wasRecentlyCreated);
        self::assertEquals($company->id, $customer->getKey());
        self::assertEquals($company->name, $customer->name);
    }

    public function testCreateFromCompanyTrashed(): void {
        // Mock
        $this->overrideResellerFinder();

        // Prepare
        $factory = $this->app->make(CustomerFactory::class);
        $json    = $this->getTestData()->json('~customer-full.json');
        $company = new Company($json);
        $model   = Customer::factory()->create([
            'id' => $company->id,
        ]);

        self::assertTrue($model->delete());
        self::assertTrue($model->trashed());

        // Test
        $created = $factory->create($company);

        self::assertNotNull($created);
        self::assertFalse($created->trashed());
    }

    public function testResellers(): void {
        $kpiA      = Kpi::factory()->create();
        $kpiB      = Kpi::factory()->create();
        $customer  = Customer::factory()->create();
        $resellerA = ResellerCustomer::factory()
            ->create([
                'kpi_id'      => $kpiA,
                'customer_id' => $customer,
            ])
            ->reseller_id;
        $resellerB = ResellerCustomer::factory()
            ->create([
                'kpi_id'      => $kpiB,
                'customer_id' => $customer,
            ])
            ->reseller_id;
        $resellerC = Reseller::factory()->create()->getKey();
        $kpis      = CompanyKpis::make([
            // Should be ignored
            [
                'resellerId'  => null,
                'totalAssets' => 1,
            ],
            // Should be updated
            [
                'resellerId'  => $resellerB,
                'totalAssets' => 2,
            ],
            // Should be added
            [
                'resellerId'  => $resellerC,
                'totalAssets' => 3,
            ],
        ]);

        $resellers = $this->app->make(ResellerResolver::class);
        $factory   = new class($resellers) extends CustomerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ResellerResolver $resellerResolver,
                protected ?ResellerFinder $resellerFinder = null,
            ) {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function resellers(Customer $customer, array $kpis = null): Collection {
                return parent::resellers($customer, $kpis);
            }
        };

        $actual = $factory->resellers($customer, $kpis);

        self::assertTrue($customer->save());

        $kpiC     = Kpi::query()
            ->whereKeyNot($kpiA->getKey())
            ->whereKeyNot($kpiB->getKey())
            ->firstOrFail();
        $actual   = $actual
            ->map(static function (ResellerCustomer $customer): array {
                return [
                    'customer_id' => $customer->customer_id,
                    'reseller_id' => $customer->reseller_id,
                    'kpi'         => [
                        'id'           => $customer->kpi_id,
                        'total_assets' => $customer->kpi->assets_total ?? null,
                    ],
                ];
            })
            ->all();
        $expected = [
            $resellerA => [
                'customer_id' => $customer->getKey(),
                'reseller_id' => $resellerA,
                'kpi'         => [
                    'id'           => null,
                    'total_assets' => null,
                ],
            ],
            $resellerB => [
                'customer_id' => $customer->getKey(),
                'reseller_id' => $resellerB,
                'kpi'         => [
                    'id'           => $kpiB->getKey(),
                    'total_assets' => 2,
                ],
            ],
            $resellerC => [
                'customer_id' => null,
                'reseller_id' => null,
                'kpi'         => [
                    'id'           => $kpiC->getKey(),
                    'total_assets' => 3,
                ],
            ],
        ];

        self::assertEquals($expected, $actual);
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
