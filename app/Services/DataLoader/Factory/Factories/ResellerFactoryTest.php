<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

use function array_column;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Factories\ResellerFactory
 */
class ResellerFactoryTest extends TestCase {
    use WithoutGlobalScopes;
    use WithQueryLog;
    use Helper;

    // <editor-fold desc='Tests'>
    // =========================================================================
    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(ResellerFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        if ($expected) {
            $factory->shouldReceive($expected)
                ->once()
                ->with($type)
                ->andReturns();
        } else {
            self::expectException(InvalidArgumentException::class);
            self::expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompany(): void {
        // Fake
        Event::fake();

        // Mock
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');

        // Prepare
        $factory    = $this->app->make(ResellerFactory::class);
        $normalizer = $this->app->make(Normalizer::class);

        // Load
        $json    = $this->getTestData()->json('~reseller-full.json');
        $company = new Company($json);
        $queries = $this->getQueryLog()->flush();

        // Test
        $reseller = $factory->create($company);
        $actual   = array_column($queries->get(), 'query');
        $expected = $this->getTestData()->json('~createFromCompany-create-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($reseller);
        self::assertTrue($reseller->wasRecentlyCreated);
        self::assertEquals($company->id, $reseller->getKey());
        self::assertEquals($company->name, $reseller->name);
        self::assertEquals($company->updatedAt, $reseller->changed_at);
        self::assertCount(2, $reseller->statuses);
        self::assertEquals(2, $reseller->statuses_count);
        self::assertEquals($this->getStatuses($company), $this->getModelStatuses($reseller));
        self::assertCount(2, $reseller->locations);
        self::assertEquals(2, $reseller->locations_count);
        self::assertEqualsCanonicalizing(
            $this->getCompanyLocations($company),
            $this->getCompanyModelLocations($reseller),
        );
        self::assertCount(4, $reseller->contacts);
        self::assertEquals(4, $reseller->contacts_count);
        self::assertEquals(
            $this->getContacts($company),
            $this->getModelContacts($reseller),
        );

        self::assertNotNull($company->companyKpis);
        self::assertNotNull($reseller->kpi);
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->totalAssets)),
            $reseller->kpi->assets_total,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->activeAssets)),
            $reseller->kpi->assets_active,
        );
        self::assertEquals(
            (float) $normalizer->unsigned($normalizer->float($company->companyKpis->activeAssetsPercentage)),
            $reseller->kpi->assets_active_percent,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->activeCustomers)),
            $reseller->kpi->customers_active,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->newActiveCustomers)),
            $reseller->kpi->customers_active_new,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->activeContracts)),
            $reseller->kpi->contracts_active,
        );
        self::assertEquals(
            (float) $normalizer->unsigned($normalizer->float($company->companyKpis->activeContractTotalAmount)),
            $reseller->kpi->contracts_active_amount,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->newActiveContracts)),
            $reseller->kpi->contracts_active_new,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->expiringContracts)),
            $reseller->kpi->contracts_expiring,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->activeQuotes)),
            $reseller->kpi->quotes_active,
        );
        self::assertEquals(
            (float) $normalizer->unsigned($normalizer->float($company->companyKpis->activeQuotesTotalAmount)),
            $reseller->kpi->quotes_active_amount,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->newActiveQuotes)),
            $reseller->kpi->quotes_active_new,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->expiringQuotes)),
            $reseller->kpi->quotes_expiring,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->expiredQuotes)),
            $reseller->kpi->quotes_expired,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->expiredContracts)),
            $reseller->kpi->contracts_expired,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->orderedQuotes)),
            $reseller->kpi->quotes_ordered,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->acceptedQuotes)),
            $reseller->kpi->quotes_accepted,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->requestedQuotes)),
            $reseller->kpi->quotes_requested,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->receivedQuotes)),
            $reseller->kpi->quotes_received,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->rejectedQuotes)),
            $reseller->kpi->quotes_rejected,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->awaitingQuotes)),
            $reseller->kpi->quotes_awaiting,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->activeAssetsOnContract)),
            $reseller->kpi->assets_active_on_contract,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->activeAssetsOnWarranty)),
            $reseller->kpi->assets_active_on_warranty,
        );
        self::assertEquals(
            (int) $normalizer->unsigned($normalizer->int($company->companyKpis->activeExposedAssets)),
            $reseller->kpi->assets_active_exposed,
        );
        self::assertEquals(
            (float) $normalizer->unsigned($normalizer->float($company->companyKpis->serviceRevenueTotalAmount)),
            $reseller->kpi->service_revenue_total_amount,
        );
        self::assertEquals(
            (float) $normalizer->unsigned($normalizer->float($company->companyKpis->serviceRevenueTotalAmountChange)),
            $reseller->kpi->service_revenue_total_amount_change,
        );

        // Reseller should be updated
        $json     = $this->getTestData()->json('~reseller-changed.json');
        $company  = new Company($json);
        $queries  = $this->getQueryLog()->flush();
        $updated  = $factory->create($company);
        $actual   = array_column($queries->get(), 'query');
        $expected = $this->getTestData()->json('~createFromCompany-update-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($updated);
        self::assertSame($reseller, $updated);
        self::assertEquals($company->id, $updated->getKey());
        self::assertEquals($company->name, $updated->name);
        self::assertEquals($company->updatedAt, $updated->changed_at);
        self::assertCount(1, $updated->statuses);
        self::assertEquals(1, $reseller->statuses_count);
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

        // Events
        Event::assertDispatchedTimes(ResellerUpdated::class, 2);

        // No changes
        $json    = $this->getTestData()->json('~reseller-changed.json');
        $company = new Company($json);
        $queries = $this->getQueryLog()->flush();

        $factory->create($company);

        self::assertCount(0, $queries);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyResellerOnly(): void {
        // Fake
        Event::fake();

        // Prepare
        $factory = $this->app->make(ResellerFactory::class);

        // Test
        $json     = $this->getTestData()->json('~reseller-only.json');
        $company  = new Company($json);
        $reseller = $factory->create($company);

        self::assertNotNull($reseller);
        self::assertTrue($reseller->wasRecentlyCreated);
        self::assertEquals($company->id, $reseller->getKey());
        self::assertEquals($company->name, $reseller->name);

        // Events
        Event::assertDispatchedTimes(ResellerUpdated::class, 1);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyTypeIsCustomer(): void {
        // Fake
        Event::fake();

        // Prepare
        $factory = $this->app->make(ResellerFactory::class);
        $json    = $this->getTestData()->json('~customer.json');
        $company = new Company($json);

        // Test
        self::assertNotNull($factory->create($company));

        // Events
        Event::assertDispatchedTimes(ResellerUpdated::class, 1);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompanyTrashed(): void {
        // Prepare
        $factory = $this->app->make(ResellerFactory::class);
        $json    = $this->getTestData()->json('~reseller-full.json');
        $company = new Company($json);
        $model   = Reseller::factory()->create([
            'id' => $company->id,
        ]);

        self::assertTrue($model->delete());
        self::assertTrue($model->trashed());

        // Test
        $created = $factory->create($company);

        self::assertNotNull($created);
        self::assertFalse($created->trashed());
    }
    // </editor-fold>

    // <editor-fold desc='DataProviders'>
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
