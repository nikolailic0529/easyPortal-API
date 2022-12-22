<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Exceptions\ErrorReport;
use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Data\Location;
use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Models\Data\ServiceGroup;
use App\Models\Data\Status;
use App\Models\Data\Type as TypeModel;
use App\Models\Document;
use App\Models\DocumentEntry as DocumentEntryModel;
use App\Models\Reseller;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Exceptions\FailedToProcessAssetViewDocument;
use App\Services\DataLoader\Exceptions\FailedToProcessViewAssetCoverageEntry;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\CoverageResolver;
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TagResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\CoverageEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Testing\Helper;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\Callbacks\KeysComparator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

use function array_column;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Factories\AssetFactory
 */
class AssetFactoryTest extends TestCase {
    use WithoutGlobalScopes;
    use WithQueryLog;
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory = $this->app->make(AssetFactory::class);
        $json    = $this->getTestData()->json('~asset-full.json');
        $asset   = new ViewAsset($json);
        $queries = $this->getQueryLog()->flush();

        $factory->find($asset);

        self::assertCount(1, $queries);
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(AssetFactory::class);
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
     * @covers ::createFromAsset
     */
    public function testCreateFromAsset(): void {
        // Mock
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
        $this->overrideFinders();

        // Prepare
        $container = $this->app->make(Container::class);

        // Load
        $json    = $this->getTestData()->json('~asset-full.json');
        $asset   = new ViewAsset($json);
        $queries = $this->getQueryLog()->flush();

        // Test
        /** @var AssetFactory $factory */
        $factory  = $container->make(AssetFactory::class);
        $created  = $factory->create($asset);
        $actual   = array_column($queries->get(), 'query');
        $expected = $this->getTestData()->json('~createFromAsset-create-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals($asset->id, $created->getKey());
        self::assertEquals($asset->resellerId, $created->reseller_id);
        self::assertEquals($asset->serialNumber, $created->serial_number);
        self::assertEquals($asset->dataQualityScore, $created->data_quality);
        self::assertEquals($asset->activeContractQuantitySum, $created->contracts_active_quantity);
        self::assertEquals($asset->updatedAt, $this->getDatetime($created->changed_at));
        self::assertEquals($asset->vendor, $created->oem->key ?? null);
        self::assertEquals($asset->assetSkuDescription, $created->product->name ?? null);
        self::assertEquals($asset->assetSku, $created->product->sku ?? null);
        self::assertNull($created->product->eos ?? null);
        self::assertEquals($asset->eosDate, (string) ($created->product->eos ?? null));
        self::assertEquals($asset->eolDate, $this->getDatetime($created->product->eol ?? null));
        self::assertNull($created->eosl);
        self::assertEquals($asset->assetType, $created->type->key ?? null);
        self::assertEquals($asset->status, $created->status->key ?? null);
        self::assertEquals($asset->customerId, $created->customer?->getKey());
        self::assertNotNull($created->warranty_end);
        self::assertEquals($created->warranties->pluck('end')->max(), $created->warranty_end);
        self::assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($created->location),
        );
        self::assertEquals(count($asset->assetCoverage ?? []), $created->coverages_count);
        self::assertEquals(
            $this->getContacts($asset),
            $this->getModelContacts($created),
        );
        self::assertEquals(
            $this->getAssetTags($asset),
            $this->getModelTags($created),
        );
        self::assertEquals(count($asset->assetCoverage ?? []), $created->coverages_count);
        self::assertEquals(
            $this->getAssetCoverages($asset),
            $this->getModelCoverages($created),
        );

        // Documents
        self::assertModelsCount([
            Document::class           => 0,
            DocumentEntryModel::class => 0,
        ]);

        // Warranties
        self::assertEquals(
            [
                // External
                [
                    'type'         => 'FactoryWarranty',
                    'status'       => 'Active',
                    'start'        => '2019-11-07',
                    'end'          => '2022-12-06',
                    'serviceGroup' => null,
                    'serviceLevel' => null,
                    'document'     => null,
                ],
                [
                    'type'         => 'Contract',
                    'status'       => 'Active',
                    'start'        => '2019-12-10',
                    'end'          => '2024-12-09',
                    'serviceGroup' => null,
                    'serviceLevel' => null,
                    'document'     => null,
                ],
                // From document
                [
                    'type'         => null,
                    'status'       => null,
                    'start'        => '2020-03-01',
                    'end'          => '2021-02-28',
                    'serviceGroup' => [
                        'sku'  => 'H7J34AC',
                        'name' => 'HPE NBD w DMR Proactive Care SVC',
                    ],
                    'serviceLevel' => null,
                    'document'     => '0056523287',
                ],
                [
                    'type'         => null,
                    'status'       => null,
                    'start'        => '2020-03-01',
                    'end'          => '2021-02-28',
                    'serviceGroup' => [
                        'sku'  => 'H7J34AC',
                        'name' => 'HPE NBD w DMR Proactive Care SVC',
                    ],
                    'serviceLevel' => [
                        'sku'  => 'HA151AC',
                        'name' => 'HPE Hardware Maintenance Onsite Support',
                    ],
                    'document'     => '0056523287',
                ],
            ],
            $created->warranties
                ->sort(static function (AssetWarranty $a, AssetWarranty $b): int {
                    return $a->start <=> $b->start
                        ?: $a->end <=> $b->end
                            ?: $a->service_group_id <=> $b->service_group_id
                                ?: $a->service_level_id <=> $b->service_level_id;
                })
                ->map(static function (AssetWarranty $warranty): array {
                    return [
                        'type'         => $warranty->type->key ?? null,
                        'status'       => $warranty->status->key ?? null,
                        'start'        => $warranty->start?->toDateString(),
                        'end'          => $warranty->end?->toDateString(),
                        'document'     => $warranty->document_number,
                        'serviceGroup' => $warranty->serviceGroup
                            ? [
                                'sku'  => $warranty->serviceGroup->sku,
                                'name' => $warranty->serviceGroup->name,
                            ]
                            : null,
                        'serviceLevel' => $warranty->serviceLevel
                            ? [
                                'sku'  => $warranty->serviceLevel->sku,
                                'name' => $warranty->serviceLevel->name,
                            ]
                            : null,
                    ];
                })
                ->values()
                ->all(),
        );

        /** @var AssetWarranty $extended */
        $extended = $created->warranties->first(static function (AssetWarranty $warranty): bool {
            return $warranty->document_number !== null && $warranty->type_id === null;
        });

        self::assertEquals($extended->asset_id, $created->getKey());
        self::assertNotNull($extended->document_id);
        self::assertEquals($created->customer_id, $extended->customer_id);
        self::assertNotNull($extended->start);
        self::assertNotNull($extended->end);

        // Asset should be updated
        /** @var AssetFactory $factory */
        $factory  = $container->make(AssetFactory::class);
        $json     = $this->getTestData()->json('~asset-changed.json');
        $asset    = new ViewAsset($json);
        $queries  = $this->getQueryLog()->flush();
        $updated  = $factory->create($asset);
        $actual   = array_column($queries->get(), 'query');
        $expected = $this->getTestData()->json('~createFromAsset-update-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($updated);
        self::assertSame($created, $updated);
        self::assertEquals($asset->id, $updated->getKey());
        self::assertNull($updated->reseller_id);
        self::assertEquals($asset->serialNumber, $updated->serial_number);
        self::assertEquals($asset->dataQualityScore, $updated->data_quality);
        self::assertEquals($asset->activeContractQuantitySum, $updated->contracts_active_quantity);
        self::assertEquals($asset->updatedAt, $this->getDatetime($updated->changed_at));
        self::assertEquals($asset->vendor, $updated->oem->key ?? null);
        self::assertNotNull($created->product);
        self::assertEquals($created->product->name, $updated->product->name ?? null);
        self::assertEquals($asset->assetSku, $updated->product->sku ?? null);
        self::assertEquals($asset->eosDate, $this->getDatetime($updated->product->eos ?? null));
        self::assertEquals($asset->eolDate, $this->getDatetime($updated->product->eol ?? null));
        self::assertEquals($asset->eoslDate, $this->getDatetime($updated->eosl));
        self::assertEquals($asset->assetType, $updated->type->key ?? null);
        self::assertEquals($asset->customerId, $updated->customer?->getKey());
        self::assertNotNull($updated->warranty_end);
        self::assertEquals($updated->warranties->pluck('end')->max(), $updated->warranty_end);
        self::assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($updated->location),
        );
        self::assertEquals(
            $this->getContacts($asset),
            $this->getModelContacts($updated),
        );
        self::assertEquals(
            $this->getAssetTags($asset),
            $this->getModelTags($updated),
        );
        self::assertEquals(count($asset->assetCoverage ?? []), $updated->coverages_count);
        self::assertEquals(
            $this->getAssetCoverages($asset),
            $this->getModelCoverages($updated),
        );

        // Documents
        self::assertModelsCount([
            Document::class           => 0,
            DocumentEntryModel::class => 0,
        ]);

        // No changes
        /** @var AssetFactory $factory */
        $factory = $container->make(AssetFactory::class);
        $json    = $this->getTestData()->json('~asset-changed.json');
        $asset   = new ViewAsset($json);
        $queries = $this->getQueryLog()->flush();

        $factory->create($asset);

        self::assertCount(0, $queries);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetTrashed(): void {
        // Mock
        $this->overrideResellerFinder();
        $this->overrideCustomerFinder();

        // Prepare
        $factory = $this->app->make(AssetFactory::class);
        $json    = $this->getTestData()->json('~asset-full.json');
        $asset   = new ViewAsset($json);
        $model   = Asset::factory()->create([
            'id' => $asset->id,
        ]);

        self::assertTrue($model->delete());
        self::assertTrue($model->trashed());

        // Test
        $created = $factory->create($asset);

        self::assertNotNull($created);
        self::assertFalse($created->trashed());
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetAssetOnly(): void {
        // Prepare
        $container = $this->app->make(Container::class);
        $factory   = $container->make(AssetFactory::class);

        // Test
        $json    = $this->getTestData()->json('~asset-only.json');
        $asset   = new ViewAsset($json);
        $created = $factory->create($asset);

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals($asset->id, $created->getKey());
        self::assertEquals($asset->serialNumber, $created->serial_number);
        self::assertEquals($asset->dataQualityScore, $created->data_quality);
        self::assertEquals($asset->activeContractQuantitySum, $created->contracts_active_quantity);
        self::assertEquals($asset->vendor, $created->oem->key ?? null);
        self::assertEquals($asset->assetSkuDescription, $created->product->name ?? null);
        self::assertEquals($asset->assetSku, $created->product->sku ?? null);
        self::assertNull($created->product->eos ?? null);
        self::assertEquals($asset->eosDate, (string) ($created->product->eos ?? null));
        self::assertEquals($asset->eolDate, (string) ($created->product->eol ?? null));
        self::assertEquals($asset->assetType, $created->type->key ?? null);
        self::assertNull($created->customer_id);
        self::assertNull($created->location_id);
        self::assertEquals(
            $this->getModelContacts($created),
            $this->getContacts($asset),
        );
        self::assertEquals(count($asset->assetCoverage ?? []), $created->coverages_count);
        self::assertEquals(
            $this->getAssetCoverages($asset),
            $this->getModelCoverages($created),
        );
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetAssetNoCustomer(): void {
        // Mock
        $this->overrideResellerFinder();

        // Prepare
        $factory = $this->app->make(AssetFactory::class);
        $json    = $this->getTestData()->json('~asset-full.json');
        $asset   = new ViewAsset($json);

        // Test
        self::expectException(CustomerNotFound::class);

        $factory->create($asset);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetWithoutZip(): void {
        // Mock
        $this->overrideCustomerFinder();

        // Prepare
        $container = $this->app->make(Container::class);
        $factory   = $container->make(AssetFactory::class);

        // Test
        $json    = $this->getTestData()->json('~asset-nozip-address.json');
        $asset   = new ViewAsset($json);
        $created = $factory->create($asset);

        self::assertNotNull($created);
        self::assertNull($created->location);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetAssetTypeNull(): void {
        // Prepare
        $container = $this->app->make(Container::class);
        $factory   = $container->make(AssetFactory::class);

        // Test
        $json    = $this->getTestData()->json('~asset-type-null.json');
        $asset   = new ViewAsset($json);
        $created = $factory->create($asset);

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertNull($created->type);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetWithoutSku(): void {
        // Mock
        $this->overrideResellerFinder();
        $this->overrideCustomerFinder();

        // Prepare
        $container = $this->app->make(Container::class);
        $factory   = $container->make(AssetFactory::class);

        // Test
        $json    = $this->getTestData()->json('~asset-no-sku.json');
        $asset   = new ViewAsset($json);
        $created = $factory->create($asset);

        self::assertNotNull($created);
        self::assertNull($created->product_id);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetOemNull(): void {
        // Mock
        $this->overrideResellerFinder();
        $this->overrideCustomerFinder();

        // Prepare
        $container = $this->app->make(Container::class);
        $factory   = $container->make(AssetFactory::class);

        // Test
        $json    = $this->getTestData()->json('~asset-oem-null.json');
        $asset   = new ViewAsset($json);
        $created = $factory->create($asset);

        self::assertNotNull($created);
        self::assertNull($created->oem_id);
        self::assertNull($created->oem);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetOemEmpty(): void {
        // Mock
        $this->overrideResellerFinder();
        $this->overrideCustomerFinder();

        // Prepare
        $container = $this->app->make(Container::class);
        $factory   = $container->make(AssetFactory::class);

        // Test
        $json    = $this->getTestData()->json('~asset-oem-empty.json');
        $asset   = new ViewAsset($json);
        $created = $factory->create($asset);

        self::assertNotNull($created);
        self::assertNull($created->oem_id);
        self::assertNull($created->oem);
    }

    /**
     * @covers ::assetDocuments
     */
    public function testAssetDocuments(): void {
        // Fake
        Event::fake(ErrorReport::class);

        // Prepare
        $model = Asset::factory()->make();
        $asset = new ViewAsset([
            'assetDocument' => [
                [
                    'documentNumber' => 'a',
                    'document'       => ['id' => 'a'],
                    'startDate'      => '09/07/2020',
                    'endDate'        => '09/07/2021',
                    'deletedAt'      => null,
                ],
                [
                    'documentNumber' => 'b',
                    'document'       => ['id' => 'b'],
                    'startDate'      => '09/01/2020',
                    'endDate'        => '09/07/2021',
                    'deletedAt'      => null,
                ],
                [
                    'document'  => ['id' => 'c'],
                    'startDate' => '09/01/2020',
                    'endDate'   => '09/07/2021',
                    'deletedAt' => null,
                ],
                [
                    'documentNumber' => 'd',
                    'document'       => ['id' => 'd'],
                    'startDate'      => '09/07/2020',
                    'endDate'        => '09/07/2021',
                    'deletedAt'      => '1614470400000',
                ],
            ],
        ]);

        $resolver = Mockery::mock(DocumentResolver::class);
        $resolver
            ->shouldReceive('prefetch')
            ->with(['a' => 'a', 'b' => 'b'], Mockery::any())
            ->once()
            ->andReturns();

        $factory = Mockery::mock(AssetFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getDocumentResolver')
            ->once()
            ->andReturn($resolver);

        self::assertCount(2, $factory->assetDocuments($model, $asset));

        Event::assertNotDispatched(ErrorReport::class);
    }

    /**
     * @covers ::assetDocumentDocument
     */
    public function testAssetDocumentDocumentNoDocumentId(): void {
        // Prepare
        $model   = Asset::factory()->make();
        $asset   = new ViewAssetDocument([
            'documentNumber' => '12345678',
            'startDate'      => '09/07/2020',
            'endDate'        => '09/07/2021',
        ]);
        $handler = $this->app->make(ExceptionHandler::class);
        $factory = new class($handler) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ExceptionHandler $exceptionHandler,
            ) {
                // empty
            }

            public function assetDocumentDocument(Asset $model, ViewAssetDocument $assetDocument): ?Document {
                return parent::assetDocumentDocument($model, $assetDocument);
            }

            public function getDocumentFactory(): DocumentFactory {
                return Mockery::mock(DocumentFactory::class);
            }
        };

        // Test
        self::assertNull($factory->assetDocumentDocument($model, $asset));
    }

    /**
     * @covers ::assetDocumentDocument
     */
    public function testAssetDocumentDocumentFailedCreateDocument(): void {
        // Fake
        Event::fake(ErrorReport::class);

        // Prepare
        $model     = Asset::factory()->make();
        $asset     = new ViewAssetDocument([
            'document'  => ['id' => 'a'],
            'startDate' => '09/07/2020',
            'endDate'   => '09/07/2021',
        ]);
        $handler   = $this->app->make(ExceptionHandler::class);
        $documents = Mockery::mock(DocumentFactory::class);
        $documents
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (): ?Document {
                throw new ResellerNotFound($this->faker->uuid());
            });
        $resolver = Mockery::mock(DocumentResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($asset->document->id ?? null)
            ->once()
            ->andReturn(null);
        $factory = Mockery::mock(AssetFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getExceptionHandler')
            ->once()
            ->andReturn($handler);
        $factory
            ->shouldReceive('getDocumentResolver')
            ->once()
            ->andReturn($resolver);
        $factory
            ->shouldReceive('getDocumentFactory')
            ->once()
            ->andReturn($documents);

        // Test
        self::assertNull($factory->assetDocumentDocument($model, $asset));

        Event::assertDispatched(ErrorReport::class, static function (ErrorReport $event): bool {
            return $event->getError() instanceof FailedToProcessAssetViewDocument
                && $event->getError()->getPrevious() instanceof ResellerNotFound;
        });
    }

    /**
     * @covers ::assetWarranties
     */
    public function testAssetWarranties(): void {
        $model               = Asset::factory()->create();
        $asset               = new ViewAsset([
            'coverageStatusCheck' => [
                'coverageStatusUpdatedAt' => (string) Date::make($this->faker->dateTime())?->getTimestampMs(),
            ],
            'assetDocument'       => [
                [
                    'documentNumber' => $this->faker->uuid(),
                ],
            ],
        ]);
        $coveragesWarranty   = AssetWarranty::factory()->create([
            'asset_id'        => $model,
            'document_number' => null,
        ]);
        $coveragesWarranties = EloquentCollection::make([$coveragesWarranty]);
        $documentsWarranty   = AssetWarranty::factory()->create([
            'asset_id'        => $model,
            'document_number' => $this->faker->uuid(),
        ]);
        $documentsWarranties = EloquentCollection::make([$documentsWarranty]);

        $normalizer = $this->app->make(Normalizer::class);
        $factory    = Mockery::mock(AssetFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getNormalizer')
            ->atLeast()
            ->once()
            ->andReturn($normalizer);
        $factory
            ->shouldReceive('assetWarrantiesCoverages')
            ->with($model, $asset, Mockery::on(static function (mixed $existing) use ($coveragesWarranties): bool {
                return $existing instanceof EloquentCollection
                    && $existing->map(new GetKey())->all() === $coveragesWarranties->map(new GetKey())->all();
            }))
            ->once()
            ->andReturn($coveragesWarranties);
        $factory
            ->shouldReceive('assetWarrantiesDocuments')
            ->with($model, $asset, Mockery::on(static function (mixed $existing) use ($documentsWarranties): bool {
                return $existing instanceof EloquentCollection
                    && $existing->map(new GetKey())->all() === $documentsWarranties->map(new GetKey())->all();
            }))
            ->once()
            ->andReturn($documentsWarranties);

        self::assertEquals(
            EloquentCollection::make([$coveragesWarranty, $documentsWarranty]),
            $factory->assetWarranties($model, $asset),
        );
    }

    /**
     * @covers ::assetWarranties
     */
    public function testAssetWarrantiesCoverageStatusCheckIsExpired(): void {
        $date              = Date::now()->startOfDay();
        $model             = Asset::factory()->create([
            'warranty_changed_at' => $date,
        ]);
        $asset             = new ViewAsset([
            'coverageStatusCheck' => [
                'coverageStatusUpdatedAt' => (string) $date->subDay()->getTimestampMs(),
            ],
        ]);
        $coveragesWarranty = AssetWarranty::factory()->create([
            'asset_id'        => $model,
            'document_number' => null,
        ]);
        $documentsWarranty = AssetWarranty::factory()->create([
            'asset_id'        => $model,
            'document_number' => $this->faker->uuid(),
        ]);

        $normalizer = $this->app->make(Normalizer::class);
        $factory    = Mockery::mock(AssetFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getNormalizer')
            ->atLeast()
            ->once()
            ->andReturn($normalizer);
        $factory
            ->shouldReceive('assetWarrantiesCoverages')
            ->never();
        $factory
            ->shouldReceive('assetWarrantiesDocuments')
            ->never();

        self::assertEquals(
            Collection::make([$coveragesWarranty->getKey(), $documentsWarranty->getKey()])->all(),
            $factory->assetWarranties($model, $asset)->map(new GetKey())->all(),
        );
    }

    /**
     * @covers ::assetWarrantiesDocuments
     */
    public function testAssetWarrantiesDocuments(): void {
        // Prepare
        $normalizer      = $this->app->make(Normalizer::class);
        $container       = $this->app->make(Container::class);
        $factory         = $container->make(AssetFactoryTest_Factory::class);
        $type            = TypeModel::factory()->create();
        $date            = Date::now();
        $model           = Asset::factory()->create([
            'oem_id' => Oem::factory(),
        ]);
        $resellerA       = Reseller::factory()->create();
        $resellerB       = Reseller::factory()->create();
        $customerA       = Customer::factory()->create();
        $customerB       = Customer::factory()->create();
        $documentA       = Document::factory()->create([
            'type_id'     => $type,
            'reseller_id' => $resellerA,
            'customer_id' => $customerA,
        ]);
        $documentB       = Document::factory()->create([
            'type_id'     => $type,
            'reseller_id' => $resellerB,
            'customer_id' => $customerB,
        ]);
        $serviceLevelSku = $this->faker->uuid();
        $serviceGroupSku = $this->faker->uuid();
        $serviceGroup    = ServiceGroup::factory()->create([
            'sku'    => $serviceGroupSku,
            'oem_id' => $documentB->oem_id,
        ]);
        $warranty        = AssetWarranty::factory()->create([
            'start'            => $date,
            'end'              => $date,
            'asset_id'         => $model,
            'service_group_id' => $serviceGroup,
            'reseller_id'      => $resellerB,
            'customer_id'      => $customerB,
            'document_id'      => $documentB,
            'document_number'  => $documentB->number,
        ]);
        $asset           = new ViewAsset([
            'id'            => $model->getKey(),
            'assetDocument' => [
                // Only one of should be created
                [
                    'startDate'       => $this->getDatetime($date),
                    'endDate'         => $this->getDatetime($date),
                    'deletedAt'       => null,
                    'documentNumber'  => $documentA->number,
                    'document'        => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key ?? null,
                        ],
                    ],
                    'reseller'        => null,
                    'customer'        => null,
                    'serviceLevelSku' => $serviceLevelSku,
                    'serviceGroupSku' => $serviceGroupSku,
                ],
                [
                    'startDate'       => $this->getDatetime($date),
                    'endDate'         => $this->getDatetime($date),
                    'deletedAt'       => null,
                    'documentNumber'  => $documentA->number,
                    'document'        => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key ?? null,
                        ],
                    ],
                    'reseller'        => null,
                    'customer'        => null,
                    'serviceLevelSku' => $serviceLevelSku,
                    'serviceGroupSku' => $serviceGroupSku,
                ],

                // Should be created - support not same
                [
                    'startDate'       => $this->getDatetime($date),
                    'endDate'         => $this->getDatetime($date),
                    'deletedAt'       => null,
                    'documentNumber'  => $documentA->number,
                    'document'        => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key ?? null,
                        ],
                    ],
                    'reseller'        => null,
                    'customer'        => null,
                    'serviceLevelSku' => $this->faker->uuid(),
                    'serviceGroupSku' => $this->faker->uuid(),
                ],

                // Should be created - date not same
                [
                    'startDate'       => $this->getDatetime($date->subDay()),
                    'endDate'         => $this->getDatetime($date),
                    'deletedAt'       => null,
                    'documentNumber'  => $documentA->number,
                    'document'        => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key ?? null,
                        ],
                    ],
                    'reseller'        => null,
                    'customer'        => null,
                    'serviceLevelSku' => $serviceLevelSku,
                    'serviceGroupSku' => $serviceGroupSku,
                ],

                // No service is OK
                [
                    'startDate'       => $this->getDatetime($date),
                    'endDate'         => $this->getDatetime($date),
                    'deletedAt'       => null,
                    'documentNumber'  => $documentB->number,
                    'document'        => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key ?? null,
                        ],
                    ],
                    'reseller'        => [
                        'id' => $documentB->reseller_id,
                    ],
                    'customer'        => [
                        'id' => $documentB->customer_id,
                    ],
                    'serviceLevelSku' => null,
                    'serviceGroupSku' => $serviceGroupSku,
                ],

                // Should be created even if document null
                [
                    'documentNumber'  => $documentA->number,
                    'startDate'       => $this->getDatetime($date),
                    'endDate'         => $this->getDatetime($date),
                    'deletedAt'       => null,
                    'reseller'        => null,
                    'customer'        => null,
                    'serviceLevelSku' => $this->faker->uuid(),
                    'serviceGroupSku' => $serviceGroupSku,
                ],

                // Should be skipped - no start and end date
                [
                    'startDate'       => null,
                    'endDate'         => null,
                    'deletedAt'       => null,
                    'documentNumber'  => $documentB->number,
                    'document'        => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key ?? null,
                        ],
                    ],
                    'reseller'        => null,
                    'customer'        => null,
                    'serviceLevelSku' => null,
                    'serviceGroupSku' => $this->faker->uuid(),
                ],

                // Should be skipped - reseller not found
                [
                    'startDate'       => $this->getDatetime($date),
                    'endDate'         => $this->getDatetime($date),
                    'deletedAt'       => null,
                    'documentNumber'  => $documentB->number,
                    'document'        => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key ?? null,
                        ],
                    ],
                    'reseller'        => [
                        'id' => $this->faker->uuid(),
                    ],
                    'customer'        => [
                        'id' => $documentB->customer_id,
                    ],
                    'serviceLevelSku' => null,
                    'serviceGroupSku' => $this->faker->uuid(),
                ],

                // Should be skipped - customer not found
                [
                    'startDate'       => $this->getDatetime($date),
                    'endDate'         => $this->getDatetime($date),
                    'deletedAt'       => null,
                    'documentNumber'  => $documentB->number,
                    'document'        => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key ?? null,
                        ],
                    ],
                    'reseller'        => [
                        'id' => $documentB->reseller_id,
                    ],
                    'customer'        => [
                        'id' => $this->faker->uuid(),
                    ],
                    'serviceLevelSku' => null,
                    'serviceGroupSku' => $this->faker->uuid(),
                ],

                // Should be skipped - deleted
                [
                    'documentNumber'  => $documentA->number,
                    'startDate'       => $this->getDatetime($date),
                    'endDate'         => $this->getDatetime($date),
                    'deletedAt'       => $this->getDatetime($date),
                    'reseller'        => null,
                    'customer'        => null,
                    'serviceLevelSku' => $this->faker->uuid(),
                    'serviceGroupSku' => $serviceGroupSku,
                ],
            ],
        ]);

        // Pre-test
        self::assertEquals(1, $model->warranties()->count());

        // Test
        $warranties = $factory->assetWarrantiesDocuments($model, $asset, $model->warranties);

        self::assertCount(5, $warranties);

        // Existing warranty should be updated
        /** @var AssetWarranty $a */
        $a = $warranties->first(static function (AssetWarranty $warranty) use ($documentA, $date): bool {
            return $warranty->document_id === $documentA->getKey()
                && $date->startOfDay()->equalTo($warranty->start);
        });

        self::assertNotNull($a);
        self::assertEquals(
            (string) new Key($normalizer, [
                'document'     => $documentA->getKey(),
                'reseller'     => null,
                'customer'     => null,
                'serviceGroup' => $serviceGroupSku,
                'serviceLevel' => $serviceLevelSku,
                'start'        => $date,
                'end'          => $date,
            ]),
            $a->key,
        );
        self::assertEquals($date->startOfDay(), $a->start);
        self::assertEquals($date->startOfDay(), $a->end);
        self::assertNull($a->reseller_id);
        self::assertNull($a->customer_id);
        self::assertEquals($documentA->getKey(), $a->document_id);
        self::assertEquals($model->getKey(), $a->asset_id);
        self::assertEquals($serviceGroupSku, $a->serviceGroup->sku ?? null);
        self::assertEquals($serviceLevelSku, $a->serviceLevel->sku ?? null);

        // Document null
        /** @var AssetWarranty $b */
        $b = $warranties->first(static function (AssetWarranty $warranty) use ($documentA): bool {
            return $warranty->document_id === null
                && $warranty->document_number === $documentA->number;
        });

        self::assertNotNull($b);
        self::assertEquals($b->serviceGroup->sku ?? null, $serviceGroupSku);

        // No service
        /** @var AssetWarranty $c */
        $c = $warranties->first(static function (AssetWarranty $warranty) use ($documentB): bool {
            return $warranty->document_id === $documentB->getKey();
        });

        self::assertNotNull($c);
        self::assertEquals(
            (string) new Key($normalizer, [
                'document'     => $documentB->getKey(),
                'reseller'     => $resellerB->getKey(),
                'customer'     => $customerB->getKey(),
                'serviceGroup' => $serviceGroupSku,
                'serviceLevel' => null,
                'start'        => $date,
                'end'          => $date,
            ]),
            $c->key,
        );
        self::assertEquals($date->startOfDay(), $c->start);
        self::assertEquals($date->startOfDay(), $c->end);
        self::assertEquals($resellerB->getKey(), $c->reseller_id);
        self::assertEquals($customerB->getKey(), $c->customer_id);
        self::assertEquals($documentB->getKey(), $c->document_id);
        self::assertEquals($model->getKey(), $c->asset_id);

        // Existing warranty should be updated
        /** @var AssetWarranty $d */
        $d = $warranties->first(static function (AssetWarranty $w) use ($warranty): bool {
            return $w->getKey() === $warranty->getKey();
        });

        self::assertNotNull($d);
    }

    /**
     * @covers ::assetDocumentDocument
     */
    public function testAssetDocumentDocumentWithDocument(): void {
        $asset         = Asset::factory()->make();
        $document      = Document::factory()->make();
        $assetDocument = new ViewAssetDocument([
            'document' => [
                'id' => $this->faker->uuid(),
            ],
        ]);

        $documents = Mockery::mock(DocumentFactory::class);
        $documents
            ->shouldReceive('create')
            ->once()
            ->andReturn($document);

        $resolver = Mockery::mock(DocumentResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($assetDocument->document->id ?? null)
            ->once()
            ->andReturn(null);

        $factory = Mockery::mock(AssetFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getDocumentResolver')
            ->once()
            ->andReturn($resolver);
        $factory
            ->shouldReceive('getDocumentFactory')
            ->once()
            ->andReturn($documents);

        self::assertSame($document, $factory->assetDocumentDocument($asset, $assetDocument));
    }

    /**
     * @covers ::assetDocumentDocument
     */
    public function testAssetDocumentDocumentWithoutDocument(): void {
        $asset    = Asset::factory()->make();
        $document = new ViewAssetDocument();
        $factory  = Mockery::mock(AssetFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getDocumentResolver')
            ->never();
        $factory
            ->shouldReceive('getDocumentFactory')
            ->never();

        self::assertNull($factory->assetDocumentDocument($asset, $document));
    }

    /**
     * @covers ::assetOem
     */
    public function testAssetOem(): void {
        $asset   = new ViewAsset(['vendor' => $this->faker->word()]);
        $factory = Mockery::mock(AssetFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('oem')
            ->with($asset->vendor)
            ->once()
            ->andReturns();

        $factory->assetOem($asset);
    }

    /**
     * @covers ::assetType
     */
    public function testAssetType(): void {
        $normalizer = $this->app->make(Normalizer::class);
        $asset      = new ViewAsset(['assetType' => $this->faker->word()]);
        $factory    = Mockery::mock(AssetFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('getNormalizer')
            ->once()
            ->andReturn($normalizer);
        $factory
            ->shouldReceive('type')
            ->with(Mockery::any(), $asset->assetType)
            ->once()
            ->andReturns();

        $factory->assetType($asset);
    }

    /**
     * @covers ::assetType
     */
    public function testAssetTypeNull(): void {
        $asset   = new ViewAsset();
        $factory = Mockery::mock(AssetFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('type')
            ->never();

        self::assertNull($factory->assetType($asset));
    }

    /**
     * @covers ::assetProduct
     */
    public function testAssetProduct(): void {
        $oem   = Oem::factory()->make();
        $asset = new ViewAsset([
            'vendor'              => $this->faker->word(),
            'assetSku'            => $this->faker->word(),
            'assetSkuDescription' => $this->faker->sentence(),
            'eolDate'             => "{$this->faker->unixTime()}000",
            'eosDate'             => '',
        ]);

        $factory = Mockery::mock(AssetFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('assetOem')
            ->with($asset)
            ->once()
            ->andReturn($oem);
        $factory
            ->shouldReceive('product')
            ->with($oem, $asset->assetSku, $asset->assetSkuDescription, $asset->eolDate, $asset->eosDate)
            ->once()
            ->andReturns();

        $factory->assetProduct($asset);
    }

    /**
     * @covers ::assetLocation
     */
    public function testAssetLocation(): void {
        $customer  = Customer::factory()->make();
        $asset     = new ViewAsset([
            'id'         => $this->faker->uuid(),
            'customerId' => $customer->getKey(),
        ]);
        $location  = Location::factory()->create();
        $locations = Mockery::mock(LocationFactory::class);

        $locations
            ->shouldReceive('isEmpty')
            ->once()
            ->andReturnFalse();
        $locations
            ->shouldReceive('find')
            ->once()
            ->andReturn($location);

        $factory = new class($locations) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(LocationFactory $locations) {
                $this->locationFactory = $locations;
            }

            public function assetLocation(ViewAsset $asset): ?Location {
                return parent::assetLocation($asset);
            }
        };

        self::assertEquals($location, $factory->assetLocation($asset));
    }

    /**
     * @covers ::assetLocation
     */
    public function testAssetLocationNoLocation(): void {
        $asset     = new ViewAsset([
            'id' => $this->faker->uuid(),
        ]);
        $locations = Mockery::mock(LocationFactory::class);
        $locations->makePartial();

        $locations
            ->shouldReceive('find')
            ->once()
            ->andReturnNull();

        $factory = new class($locations) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(LocationFactory $locations) {
                $this->locationFactory = $locations;
            }

            public function assetLocation(ViewAsset $asset): ?Location {
                return parent::assetLocation($asset);
            }
        };

        self::assertNull($factory->assetLocation($asset));
    }

    /**
     * @covers ::assetStatus
     */
    public function testAssetStatus(): void {
        $asset   = new ViewAsset(['status' => $this->faker->word()]);
        $factory = Mockery::mock(AssetFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('status')
            ->with(Mockery::any(), $asset->status)
            ->once()
            ->andReturns();

        $factory->assetStatus($asset);
    }

    /**
     * @covers ::assetTags
     */
    public function testAssetTags(): void {
        $factory = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(TagResolver::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected TagResolver $tagResolver,
            ) {
                // empty
            }

            public function assetTags(ViewAsset $asset): EloquentCollection {
                return parent::assetTags($asset);
            }
        };

        // Null tag
        self::assertEmpty($factory->assetTags(new ViewAsset(['assetTag' => null])));

        // Empty
        self::assertEmpty($factory->assetTags(new ViewAsset(['assetTag' => ' '])));

        // Not empty
        $asset    = new ViewAsset(['assetTag' => 'tag']);
        $tags     = $factory->assetTags($asset);
        $expected = [
            'tag' => [
                'name' => 'tag',
            ],
        ];

        self::assertCount(1, $tags);
        self::assertEquals($expected, $this->getAssetTags($asset));
    }

    /**
     * @covers ::assetCoverages
     */
    public function testAssetCoverages(): void {
        $factory = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(CoverageResolver::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected CoverageResolver $coverageResolver,
            ) {
                // empty
            }

            public function assetCoverages(ViewAsset $asset): EloquentCollection {
                return parent::assetCoverages($asset);
            }
        };

        // Null
        self::assertEmpty($factory->assetCoverages(new ViewAsset(['assetCoverage' => null])));

        // Empty
        self::assertEmpty($factory->assetCoverages(new ViewAsset(['assetCoverage' => ['', null]])));

        // Not empty
        $asset     = new ViewAsset([
            'assetCoverage' => ['a', 'a', 'b'],
        ]);
        $coverages = $factory->assetCoverages($asset);
        $expected  = [
            'a' => [
                'key'  => 'a',
                'name' => 'A',
            ],
            'b' => [
                'key'  => 'b',
                'name' => 'B',
            ],
        ];

        self::assertCount(2, $coverages);
        self::assertEquals($expected, $this->getCoverages($coverages));
    }

    /**
     * @covers ::isWarrantyEqualToCoverage
     */
    public function testIsWarrantyEqualToCoverage(): void {
        // Prepare
        $type     = TypeModel::factory()->create([
            'object_type' => (new AssetWarranty())->getMorphClass(),
        ]);
        $warranty = AssetWarranty::factory()->make([
            'type_id' => $type,
            'start'   => $this->faker->dateTime(),
            'end'     => $this->faker->dateTime(),
        ]);
        $factory  = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(TypeResolver::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected TypeResolver $typeResolver,
            ) {
                // empty
            }

            public function isWarrantyEqualToCoverage(AssetWarranty $warranty, CoverageEntry $entry): bool {
                return parent::isWarrantyEqualToCoverage($warranty, $entry);
            }
        };

        // Test
        self::assertTrue($factory->isWarrantyEqualToCoverage($warranty, new CoverageEntry([
            'type'              => " {$type->key} ",
            'coverageStartDate' => "{$warranty->start?->getTimestampMs()}",
            'coverageEndDate'   => "{$warranty->end?->getTimestampMs()}",
        ])));
        self::assertFalse($factory->isWarrantyEqualToCoverage($warranty, new CoverageEntry([
            'type'              => 'another type',
            'coverageStartDate' => "{$warranty->start?->getTimestampMs()}",
            'coverageEndDate'   => "{$warranty->end?->getTimestampMs()}",
        ])));
        self::assertFalse($factory->isWarrantyEqualToCoverage($warranty, new CoverageEntry([
            'type'              => " {$type->key} ",
            'coverageStartDate' => "{$warranty->start?->addDay()->getTimestampMs()}",
            'coverageEndDate'   => "{$warranty->end?->getTimestampMs()}",
        ])));
        self::assertFalse($factory->isWarrantyEqualToCoverage($warranty, new CoverageEntry([
            'type'              => " {$type->key} ",
            'coverageStartDate' => "{$warranty->start?->getTimestampMs()}",
            'coverageEndDate'   => "{$warranty->end?->addDay()->getTimestampMs()}",
        ])));
    }

    /**
     * @covers ::assetWarranty
     */
    public function testAssetWarranty(): void {
        $asset          = Asset::factory()->make();
        $type           = TypeModel::factory()->create([
            'object_type' => (new AssetWarranty())->getMorphClass(),
        ]);
        $status         = Status::factory()->create([
            'object_type' => (new AssetWarranty())->getMorphClass(),
        ]);
        $entry          = new CoverageEntry([
            'coverageStartDate' => '2019-12-10',
            'coverageEndDate'   => '2024-12-09',
            'type'              => $type->key,
            'status'            => $status->key,
            'description'       => $this->faker->text(),
        ]);
        $normalizer     = $this->app->make(Normalizer::class);
        $typeResolver   = $this->app->make(TypeResolver::class);
        $statusResolver = $this->app->make(StatusResolver::class);
        $factory        = new class($normalizer, $typeResolver, $statusResolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected TypeResolver $typeResolver,
                protected StatusResolver $statusResolver,
            ) {
                // empty
            }

            public function assetWarranty(
                Asset $model,
                CoverageEntry $entry,
                ?AssetWarranty $warranty,
            ): ?AssetWarranty {
                return parent::assetWarranty($model, $entry, $warranty);
            }
        };

        // Create
        $actual   = $factory->assetWarranty($asset, $entry, null);
        $expected = [
            'key'              => "2024-12-09t000000:2019-12-10t000000:{$entry->type}",
            'start'            => '2019-12-10 00:00:00',
            'end'              => '2024-12-09 00:00:00',
            'asset_id'         => $asset->getKey(),
            'type_id'          => $type->getKey(),
            'status_id'        => $status->getKey(),
            'description'      => $entry->description,
            'service_group_id' => null,
            'service_level_id' => null,
            'customer_id'      => null,
            'reseller_id'      => null,
            'document_id'      => null,
            'document_number'  => null,
        ];

        self::assertNotNull($actual);
        self::assertFalse($actual->exists);
        self::assertEquals($expected, $actual->getAttributes());

        // Update
        $warranty = AssetWarranty::factory()->create();
        $actual   = $factory->assetWarranty($asset, $entry, $warranty);
        $expected = [
            'id'               => $warranty->getKey(),
            'key'              => "2024-12-09t000000:2019-12-10t000000:{$entry->type}",
            'start'            => '2019-12-10 00:00:00',
            'end'              => '2024-12-09 00:00:00',
            'asset_id'         => $asset->getKey(),
            'type_id'          => $type->getKey(),
            'status_id'        => $status->getKey(),
            'description'      => $entry->description,
            'service_group_id' => null,
            'service_level_id' => null,
            'customer_id'      => null,
            'reseller_id'      => null,
            'document_id'      => null,
            'document_number'  => null,
            'created_at'       => $warranty->created_at->format($warranty->getDateFormat()),
            'updated_at'       => $warranty->updated_at->format($warranty->getDateFormat()),
            'deleted_at'       => null,
        ];

        self::assertNotNull($actual);
        self::assertTrue($actual->exists);
        self::assertEquals($expected, $actual->getAttributes());
    }

    /**
     * @covers ::assetWarranty
     */
    public function testAssetWarrantyEmpty(): void {
        $asset      = Asset::factory()->make();
        $type       = TypeModel::factory()->create([
            'object_type' => (new AssetWarranty())->getMorphClass(),
        ]);
        $status     = Status::factory()->create([
            'object_type' => (new AssetWarranty())->getMorphClass(),
        ]);
        $entry      = new CoverageEntry([
            'coverageStartDate' => null,
            'coverageEndDate'   => null,
            'type'              => $type->key,
            'status'            => $status->key,
            'description'       => null,
        ]);
        $normalizer = $this->app->make(Normalizer::class);
        $factory    = new class($normalizer) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
            ) {
                // empty
            }

            public function assetWarranty(
                Asset $model,
                CoverageEntry $entry,
                ?AssetWarranty $warranty,
            ): ?AssetWarranty {
                return parent::assetWarranty($model, $entry, $warranty);
            }
        };

        self::assertNull($factory->assetWarranty($asset, $entry, null));
    }

    /**
     * @covers ::assetWarrantiesCoverages
     */
    public function testAssetWarrantiesCoverages(): void {
        $asset                   = Asset::factory()->create();
        $type                    = TypeModel::factory()->create([
            'object_type' => (new AssetWarranty())->getMorphClass(),
        ]);
        $status                  = Status::factory()->create([
            'object_type' => (new AssetWarranty())->getMorphClass(),
        ]);
        $warrantyShouldBeUpdated = AssetWarranty::factory()->create([
            'document_number' => null,
            'reseller_id'     => null,
            'customer_id'     => null,
            'asset_id'        => $asset,
            'type_id'         => $type,
            'start'           => Date::make($this->faker->dateTime())?->startOfDay(),
            'end'             => Date::make($this->faker->dateTime())?->startOfDay(),
        ]);
        $warrantyShouldBeReused  = Collection::make([
            AssetWarranty::factory()->create([
                'document_number' => null,
                'reseller_id'     => null,
                'customer_id'     => null,
                'asset_id'        => $asset,
                'type_id'         => $type,
                'start'           => $this->faker->dateTime(),
                'end'             => $this->faker->dateTime(),
            ]),
            AssetWarranty::factory()->create([
                'document_number' => null,
                'reseller_id'     => null,
                'customer_id'     => null,
                'asset_id'        => $asset,
                'type_id'         => $type,
                'start'           => $this->faker->dateTime(),
                'end'             => $this->faker->dateTime(),
            ]),
        ])
            ->sort(new KeysComparator())
            ->first();
        $entryShouldBeCreated    = new CoverageEntry([
            'coverageStartDate' => $warrantyShouldBeReused?->start?->format('Y-m-d'),
            'coverageEndDate'   => $warrantyShouldBeReused?->end?->format('Y-m-d'),
            'type'              => $type->key,
            'status'            => $status->key,
            'description'       => "(created) {$this->faker->text()}",
        ]);
        $entryShouldBeUpdated    = new CoverageEntry([
            'coverageStartDate' => $warrantyShouldBeUpdated->start?->format('Y-m-d'),
            'coverageEndDate'   => $warrantyShouldBeUpdated->end?->format('Y-m-d'),
            'type'              => $warrantyShouldBeUpdated->type?->key,
            'status'            => $status->key,
            'description'       => "(updated) {$this->faker->text()}",
        ]);
        $entryShouldBeIgnored    = new CoverageEntry([
            'coverageStartDate' => $this->faker->date(),
            'coverageEndDate'   => $this->faker->date(),
            'type'              => $this->faker->word(),
        ]);
        $viewAsset               = new ViewAsset([
            'coverageStatusCheck' => [
                'coverageEntries' => [
                    $entryShouldBeCreated,
                    $entryShouldBeUpdated,
                    $entryShouldBeIgnored,
                ],
            ],
        ]);

        $handler        = $this->override(
            ExceptionHandler::class,
            static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('report')
                    ->with(Mockery::type(FailedToProcessViewAssetCoverageEntry::class))
                    ->once()
                    ->andReturns();
            },
        );
        $normalizer     = $this->app->make(Normalizer::class);
        $typeResolver   = $this->app->make(TypeResolver::class);
        $statusResolver = $this->app->make(StatusResolver::class);
        $factory        = new class($handler, $normalizer, $typeResolver, $statusResolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ExceptionHandler $exceptionHandler,
                protected Normalizer $normalizer,
                protected TypeResolver $typeResolver,
                protected StatusResolver $statusResolver,
            ) {
                // empty
            }

            public function assetWarrantiesCoverages(
                Asset $model,
                ViewAsset $asset,
                EloquentCollection $existing,
            ): EloquentCollection {
                return parent::assetWarrantiesCoverages($model, $asset, $existing);
            }
        };

        $map      = static fn(AssetWarranty $warranty) => $warranty->getAttributes();
        $actual   = $factory
            ->assetWarrantiesCoverages($asset, $viewAsset, $asset->warranties)
            ->sort(new KeysComparator())
            ->map($map)
            ->values();
        $expected = EloquentCollection::make([
            (clone $warrantyShouldBeUpdated)->forceFill([
                'key'         => (string) new Key($normalizer, [
                    'type'  => $entryShouldBeUpdated->type,
                    'start' => $normalizer->datetime($entryShouldBeUpdated->coverageStartDate),
                    'end'   => $normalizer->datetime($entryShouldBeUpdated->coverageEndDate),
                ]),
                'status_id'   => $status->getKey(),
                'description' => $entryShouldBeUpdated->description,
            ]),
            (clone $warrantyShouldBeReused)->forceFill([
                'key'         => (string) new Key($normalizer, [
                    'type'  => $entryShouldBeCreated->type,
                    'start' => $normalizer->datetime($entryShouldBeCreated->coverageStartDate),
                    'end'   => $normalizer->datetime($entryShouldBeCreated->coverageEndDate),
                ]),
                'start'       => $normalizer->datetime($entryShouldBeCreated->coverageStartDate),
                'end'         => $normalizer->datetime($entryShouldBeCreated->coverageEndDate),
                'type_id'     => $type->getKey(),
                'status_id'   => $status->getKey(),
                'description' => $entryShouldBeCreated->description,
            ]),
        ])
            ->sort(new KeysComparator())
            ->map($map)
            ->values();

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
            ViewAsset::class => ['createFromAsset', new ViewAsset()],
            'Unknown'        => [
                null,
                new class() extends Type {
                    // empty
                },
            ],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class AssetFactoryTest_Factory extends AssetFactory {
    // TODO [tests] Remove after https://youtrack.jetbrains.com/issue/WI-25253

    public function assetOem(ViewAsset $asset): ?Oem {
        return parent::assetOem($asset);
    }

    public function assetType(ViewAsset $asset): ?TypeModel {
        return parent::assetType($asset);
    }

    public function assetProduct(ViewAsset $asset): ?Product {
        return parent::assetProduct($asset);
    }

    public function assetStatus(ViewAsset $asset): Status {
        return parent::assetStatus($asset);
    }

    public function assetDocumentDocument(Asset $model, ViewAssetDocument $assetDocument): ?Document {
        return parent::assetDocumentDocument($model, $assetDocument);
    }

    public function assetWarrantiesDocuments(
        Asset $model,
        ViewAsset $asset,
        EloquentCollection $existing,
    ): EloquentCollection {
        return parent::assetWarrantiesDocuments($model, $asset, $existing);
    }
}
