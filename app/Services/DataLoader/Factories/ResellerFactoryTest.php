<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Services\DataLoader\Events\ResellerUpdated;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

use function array_column;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\ResellerFactory
 */
class ResellerFactoryTest extends TestCase {
    use WithoutOrganizationScope;
    use WithQueryLog;
    use Helper;

    // <editor-fold desc='Tests'>
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $company = new Company([
            'id'           => $this->faker->uuid,
            'name'         => $this->faker->company,
            'companyTypes' => [['type' => 'RESELLER']],
        ]);
        $factory = $this->app->make(ResellerFactory::class);

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
        $factory = Mockery::mock(ResellerFactory::class);
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
        // Fake
        Event::fake();

        // Mock
        $this->overrideDateFactory();

        // Prepare
        $factory    = $this->app->make(ResellerFactory::class);
        $normalizer = $this->app->make(Normalizer::class);

        // Load
        $json    = $this->getTestData()->json('~reseller-full.json');
        $company = new Company($json);

        $this->flushQueryLog();

        // Test
        $reseller = $factory->create($company);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromCompany-create-expected.json');

        $this->assertEquals($expected, $actual);
        $this->assertNotNull($reseller);
        $this->assertTrue($reseller->wasRecentlyCreated);
        $this->assertEquals($company->id, $reseller->getKey());
        $this->assertEquals($company->name, $reseller->name);
        $this->assertEquals($company->updatedAt, $this->getDatetime($reseller->changed_at));
        $this->assertCount(2, $reseller->statuses);
        $this->assertEquals($this->getStatuses($company), $this->getModelStatuses($reseller));
        $this->assertCount(2, $reseller->locations);
        $this->assertEquals(2, $reseller->locations_count);
        $this->assertEqualsCanonicalizing(
            $this->getCompanyLocations($company),
            $this->getCompanyModelLocations($reseller),
        );
        $this->assertCount(4, $reseller->contacts);
        $this->assertEquals(4, $reseller->contacts_count);
        $this->assertEquals(
            $this->getContacts($company),
            $this->getModelContacts($reseller),
        );

        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->totalAssets),
            $reseller->kpi->assets_total,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeAssets),
            $reseller->kpi->assets_active,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->activeAssetsPercentage),
            $reseller->kpi->assets_active_percent,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeCustomers),
            $reseller->kpi->customers_active,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->newActiveCustomers),
            $reseller->kpi->customers_active_new,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeContracts),
            $reseller->kpi->contracts_active,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->activeContractTotalAmount),
            $reseller->kpi->contracts_active_amount,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->newActiveContracts),
            $reseller->kpi->contracts_active_new,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->expiringContracts),
            $reseller->kpi->contracts_expiring,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeQuotes),
            $reseller->kpi->quotes_active,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->activeQuotesTotalAmount),
            $reseller->kpi->quotes_active_amount,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->newActiveQuotes),
            $reseller->kpi->quotes_active_new,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->expiringQuotes),
            $reseller->kpi->quotes_expiring,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->expiredQuotes),
            $reseller->kpi->quotes_expired,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->expiredContracts),
            $reseller->kpi->contracts_expired,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->orderedQuotes),
            $reseller->kpi->quotes_ordered,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->acceptedQuotes),
            $reseller->kpi->quotes_accepted,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->requestedQuotes),
            $reseller->kpi->quotes_requested,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->receivedQuotes),
            $reseller->kpi->quotes_received,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->rejectedQuotes),
            $reseller->kpi->quotes_rejected,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->awaitingQuotes),
            $reseller->kpi->quotes_awaiting,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeAssetsOnContract),
            $reseller->kpi->assets_active_on_contract,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeAssetsOnWarranty),
            $reseller->kpi->assets_active_on_warranty,
        );
        $this->assertEquals(
            (int) $normalizer->number($company->companyKpis->activeExposedAssets),
            $reseller->kpi->assets_active_exposed,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->serviceRevenueTotalAmount),
            $reseller->kpi->service_revenue_total_amount,
        );
        $this->assertEquals(
            (float) $normalizer->number($company->companyKpis->serviceRevenueTotalAmountChange),
            $reseller->kpi->service_revenue_total_amount_change,
        );

        $this->flushQueryLog();

        // Reseller should be updated
        $json     = $this->getTestData()->json('~reseller-changed.json');
        $company  = new Company($json);
        $updated  = $factory->create($company);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromCompany-update-expected.json');

        $this->assertEquals($expected, $actual);
        $this->assertNotNull($updated);
        $this->assertSame($reseller, $updated);
        $this->assertEquals($company->id, $updated->getKey());
        $this->assertEquals($company->name, $updated->name);
        $this->assertEquals($company->updatedAt, $this->getDatetime($updated->changed_at));
        $this->assertCount(1, $updated->statuses);
        $this->assertEquals($this->getStatuses($company), $this->getModelStatuses($updated));
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

        // Events
        Event::assertDispatchedTimes(ResellerUpdated::class, 2);

        // No changes
        $json    = $this->getTestData()->json('~reseller-changed.json');
        $company = new Company($json);

        $factory->create($company);

        $this->assertCount(1, $this->getQueryLog());
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

        $this->assertNotNull($reseller);
        $this->assertTrue($reseller->wasRecentlyCreated);
        $this->assertEquals($company->id, $reseller->getKey());
        $this->assertEquals($company->name, $reseller->name);

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
        $this->assertNotNull($factory->create($company));

        // Events
        Event::assertDispatchedTimes(ResellerUpdated::class, 1);
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
            'resellerId'    => $b->id,
            'assetDocument' => [
                [
                    'reseller' => [
                        'id' => $this->faker->uuid,
                    ],
                    'document' => [
                        'resellerId' => $this->faker->uuid,
                    ],
                ],
            ],
        ]);
        $document   = new Document([
            'resellerId' => $this->faker->uuid,
        ]);
        $resolver   = $this->app->make(ResellerResolver::class);
        $normalizer = $this->app->make(Normalizer::class);

        $factory = new class($normalizer, $resolver) extends ResellerFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ResellerResolver $resellerResolver,
            ) {
                // empty
            }
        };

        $callback = Mockery::spy(function (EloquentCollection $collection): void {
            $this->assertCount(0, $collection);
        });

        $factory->prefetch(
            [$a, $asset, new ViewAsset(['resellerId' => null]), $document],
            false,
            Closure::fromCallable($callback),
        );

        $callback->shouldHaveBeenCalled()->once();

        $this->flushQueryLog();

        $factory->find($a);
        $factory->find($b);
        $resolver->get($asset->assetDocument[0]->reseller->id);
        $resolver->get($asset->assetDocument[0]->document->resellerId);
        $resolver->get($document->resellerId);

        $this->assertCount(0, $this->getQueryLog());
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
