<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Exceptions\ErrorReport;
use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentEntry as DocumentEntryModel;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\Status;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Exceptions\FailedToProcessAssetViewDocument;
use App\Services\DataLoader\Exceptions\FailedToProcessViewAssetCoverageEntry;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Factory\AssetDocumentObject;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\CoverageResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TagResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\CoverageEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Testing\Helper;
use App\Utils\Eloquent\Callbacks\KeysComparator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

use function array_column;
use function count;

/**
 * @internal
 * @coversDefaultClass AssetFactory
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

        $this->flushQueryLog();

        $factory->find($asset);

        self::assertCount(1, $this->getQueryLog());
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
        $documents = $container->make(DocumentFactory::class);

        // Load
        $json  = $this->getTestData()->json('~asset-full.json');
        $asset = new ViewAsset($json);

        $this->flushQueryLog();

        // Test
        /** @var AssetFactory $factory */
        $factory  = $container->make(AssetFactory::class)->setDocumentFactory($documents);
        $created  = $factory->create($asset);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromAsset-create-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals($asset->id, $created->getKey());
        self::assertEquals($asset->resellerId, $created->reseller_id);
        self::assertEquals($asset->serialNumber, $created->serial_number);
        self::assertEquals($asset->dataQualityScore, $created->data_quality);
        self::assertEquals($asset->updatedAt, $this->getDatetime($created->changed_at));
        self::assertEquals($asset->vendor, $created->oem->key ?? null);
        self::assertEquals($asset->productDescription, $created->product->name ?? null);
        self::assertEquals($asset->sku, $created->product->sku ?? null);
        self::assertNull($created->product->eos ?? null);
        self::assertEquals($asset->eosDate, (string) ($created->product->eos ?? null));
        self::assertEquals($asset->eolDate, $this->getDatetime($created->product->eol ?? null));
        self::assertEquals($asset->assetType, $created->type->key);
        self::assertEquals($asset->status, $created->status->key);
        self::assertEquals($asset->customerId, $created->customer->getKey());
        self::assertNotNull($created->warranty_end);
        self::assertEquals($created->warranties->pluck('end')->max(), $created->warranty_end);
        self::assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($created->location),
        );
        self::assertEquals(count($asset->assetCoverage), $created->coverages_count);
        self::assertEquals(
            $this->getContacts($asset),
            $this->getModelContacts($created),
        );
        self::assertEquals(
            $this->getAssetTags($asset),
            $this->getModelTags($created),
        );
        self::assertEquals(count($asset->assetCoverage), $created->coverages_count);
        self::assertEquals(
            $this->getAssetCoverages($asset),
            $this->getModelCoverages($created),
        );

        // Documents
        self::assertModelsCount([
            Document::class           => 1,
            DocumentEntryModel::class => 0,
        ]);

        // Warranties
        self::assertEquals(
            [
                // External
                [
                    'type'          => 'FactoryWarranty',
                    'status'        => 'Active',
                    'start'         => '2019-11-07',
                    'end'           => '2022-12-06',
                    'serviceGroup'  => null,
                    'serviceLevels' => [],
                    'document'      => null,
                ],
                [
                    'type'          => 'Contract',
                    'status'        => 'Active',
                    'start'         => '2019-12-10',
                    'end'           => '2024-12-09',
                    'serviceGroup'  => null,
                    'serviceLevels' => [],
                    'document'      => null,
                ],
                // From document
                [
                    'type'          => null,
                    'status'        => null,
                    'start'         => '2020-03-01',
                    'end'           => '2021-02-28',
                    'serviceGroup'  => 'H7J34AC',
                    'serviceLevels' => [
                        [
                            'sku' => 'HA151AC',
                        ],
                    ],
                    'document'      => '0056523287',
                ],
            ],
            $created->warranties
                ->sort(static function (AssetWarranty $a, AssetWarranty $b): int {
                    return $a->start <=> $b->start ?: $a->end <=> $b->end;
                })
                ->map(static function (AssetWarranty $warranty): array {
                    return [
                        'type'          => $warranty->type->key ?? null,
                        'status'        => $warranty->status->key ?? null,
                        'start'         => $warranty->start?->toDateString(),
                        'end'           => $warranty->end?->toDateString(),
                        'document'      => $warranty->document_number,
                        'serviceGroup'  => $warranty->serviceGroup?->sku,
                        'serviceLevels' => $warranty->serviceLevels
                            ->map(static function (ServiceLevel $level): array {
                                return [
                                    'sku' => $level->sku,
                                ];
                            })
                            ->all(),
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

        $this->flushQueryLog();

        // Asset should be updated
        /** @var AssetFactory $factory */
        $factory  = $container->make(AssetFactory::class)->setDocumentFactory($documents);
        $json     = $this->getTestData()->json('~asset-changed.json');
        $asset    = new ViewAsset($json);
        $updated  = $factory->create($asset);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromAsset-update-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($updated);
        self::assertSame($created, $updated);
        self::assertEquals($asset->id, $updated->getKey());
        self::assertNull($updated->reseller_id);
        self::assertEquals($asset->serialNumber, $updated->serial_number);
        self::assertEquals($asset->dataQualityScore, $updated->data_quality);
        self::assertEquals($asset->updatedAt, $this->getDatetime($updated->changed_at));
        self::assertEquals($asset->vendor, $updated->oem->key ?? null);
        self::assertNotNull($created->product);
        self::assertEquals($created->product->name, $updated->product->name ?? null);
        self::assertEquals($asset->sku, $updated->product->sku ?? null);
        self::assertEquals($asset->eosDate, $this->getDatetime($updated->product->eos ?? null));
        self::assertEquals($asset->eolDate, $this->getDatetime($updated->product->eol ?? null));
        self::assertEquals($asset->assetType, $updated->type->key);
        self::assertEquals($asset->customerId, $updated->customer->getKey());
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
        self::assertEquals(count($asset->assetCoverage), $updated->coverages_count);
        self::assertEquals(
            $this->getAssetCoverages($asset),
            $this->getModelCoverages($updated),
        );

        // Documents
        self::assertModelsCount([
            Document::class           => 1,
            DocumentEntryModel::class => 0,
        ]);

        $this->flushQueryLog();

        // No changes
        /** @var AssetFactory $factory */
        $factory = $container->make(AssetFactory::class)->setDocumentFactory($documents);
        $json    = $this->getTestData()->json('~asset-changed.json');
        $asset   = new ViewAsset($json);

        $factory->create($asset);

        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromAsset-nochanges-expected.json');

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetAssetOnly(): void {
        // Mock
        $this->overrideOemFinder();

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
        self::assertEquals($asset->vendor, $created->oem->key ?? null);
        self::assertEquals($asset->productDescription, $created->product->name ?? null);
        self::assertEquals($asset->sku, $created->product->sku ?? null);
        self::assertNull($created->product->eos ?? null);
        self::assertEquals($asset->eosDate, (string) ($created->product->eos ?? null));
        self::assertEquals($asset->eolDate, (string) ($created->product->eol ?? null));
        self::assertEquals($asset->assetType, $created->type->key);
        self::assertNull($created->customer_id);
        self::assertNull($created->location_id);
        self::assertEquals(
            $this->getModelContacts($created),
            $this->getContacts($asset),
        );
        self::assertEquals(count($asset->assetCoverage), $created->coverages_count);
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
        $this->overrideOemFinder();

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
        $this->overrideOemFinder();
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
        // Mock
        $this->overrideOemFinder();

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
        $this->overrideOemFinder();
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
        $this->overrideOemFinder();
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
        $this->overrideOemFinder();
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
        $model   = Asset::factory()->make();
        $asset   = new ViewAsset([
            'assetDocument' => [
                [
                    'documentNumber' => 'a',
                    'document'       => ['id' => 'a'],
                    'startDate'      => '09/07/2020',
                    'endDate'        => '09/07/2021',
                ],
                [
                    'documentNumber' => 'b',
                    'document'       => ['id' => 'b'],
                    'startDate'      => '09/01/2020',
                    'endDate'        => '09/07/2021',
                ],
                [
                    'document'  => ['id' => 'c'],
                    'startDate' => '09/01/2020',
                    'endDate'   => '09/07/2021',
                ],
            ],
        ]);
        $factory = new class() extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function assetDocuments(Asset $model, ViewAsset $asset): Collection {
                return parent::assetDocuments($model, $asset);
            }
        };

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
        $factory = new class($handler, $documents) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ExceptionHandler $exceptionHandler,
                protected ?DocumentFactory $documentFactory,
            ) {
                // empty
            }

            public function assetDocumentDocument(Asset $model, ViewAssetDocument $assetDocument): ?Document {
                return parent::assetDocumentDocument($model, $assetDocument);
            }
        };

        // Test
        self::assertNull($factory->assetDocumentDocument($model, $asset));

        Event::assertDispatched(ErrorReport::class, static function (ErrorReport $event): bool {
            return $event->getError() instanceof FailedToProcessAssetViewDocument
                && $event->getError()->getPrevious() instanceof ResellerNotFound;
        });
    }

    /**
     * @covers ::assetDocumentsWarranties
     */
    public function testAssetDocumentsWarranties(): void {
        $b       = AssetWarranty::factory()->make();
        $model   = Asset::factory()->make();
        $asset   = new ViewAsset();
        $factory = Mockery::mock(AssetFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('assetDocuments')
            ->with($model, $asset)
            ->never();
        $factory
            ->shouldReceive('assetDocumentsWarrantiesExtended')
            ->with($model, $asset)
            ->once()
            ->andReturn([$b]);

        self::assertEquals([$b], $factory->assetDocumentsWarranties($model, $asset));
    }

    /**
     * @covers ::assetDocumentsWarrantiesExtended
     */
    public function testAssetDocumentsWarrantiesExtended(): void {
        // Prepare
        $container      = $this->app->make(Container::class);
        $documents      = $container->make(DocumentFactory::class);
        $factory        = $container->make(AssetFactoryTest_Factory::class)->setDocumentFactory($documents);
        $date           = Date::now();
        $model          = Asset::factory()->create();
        $resellerA      = Reseller::factory()->create();
        $resellerB      = Reseller::factory()->create();
        $customerA      = Customer::factory()->create();
        $customerB      = Customer::factory()->create();
        $documentA      = Document::factory()->create([
            'reseller_id' => $resellerA,
            'customer_id' => $customerA,
        ]);
        $documentB      = Document::factory()->create([
            'reseller_id' => $resellerB,
            'customer_id' => $customerB,
        ]);
        $skuNumber      = $this->faker->uuid();
        $supportPackage = $this->faker->uuid();
        $serviceGroup   = ServiceGroup::factory()->create([
            'sku'    => $supportPackage,
            'oem_id' => $documentB->oem_id,
        ]);
        $warranty       = AssetWarranty::factory()->create([
            'start'            => $date,
            'end'              => $date,
            'asset_id'         => $model,
            'service_group_id' => $serviceGroup,
            'reseller_id'      => $resellerB,
            'customer_id'      => $customerB,
            'document_id'      => $documentB,
            'document_number'  => $documentB->number,
        ]);
        $asset          = new ViewAsset([
            'id'            => $model->getKey(),
            'assetDocument' => [
                // Only one of should be created but Services should be merged
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentA->number,
                    'document'       => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key ?? null,
                        ],
                    ],
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => $this->faker->uuid(),
                    'supportPackage' => $supportPackage,
                ],
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentA->number,
                    'document'       => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key ?? null,
                        ],
                    ],
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => $skuNumber,
                    'supportPackage' => $supportPackage,
                ],
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentA->number,
                    'document'       => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key ?? null,
                        ],
                    ],
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => $skuNumber,
                    'supportPackage' => $supportPackage,
                ],

                // Should be created - support not same
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentA->number,
                    'document'       => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key ?? null,
                        ],
                    ],
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => $this->faker->uuid(),
                    'supportPackage' => $this->faker->uuid(),
                ],

                // Should be created - date not same
                [
                    'startDate'      => $this->getDatetime($date->subDay()),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentA->number,
                    'document'       => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key ?? null,
                        ],
                    ],
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => $skuNumber,
                    'supportPackage' => $supportPackage,
                ],

                // No service is OK
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentB->number,
                    'document'       => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key ?? null,
                        ],
                    ],
                    'reseller'       => [
                        'id' => $documentB->reseller_id,
                    ],
                    'customer'       => [
                        'id' => $documentB->customer_id,
                    ],
                    'skuNumber'      => null,
                    'supportPackage' => $supportPackage,
                ],

                // Should be created even if document null
                [
                    'documentNumber' => $documentA->number,
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => $this->faker->uuid(),
                    'supportPackage' => $supportPackage,
                ],

                // Should be skipped - no start and end date
                [
                    'startDate'      => null,
                    'endDate'        => null,
                    'documentNumber' => $documentB->number,
                    'document'       => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key ?? null,
                        ],
                    ],
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => null,
                    'supportPackage' => $this->faker->uuid(),
                ],

                // Should be skipped - reseller not found
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentB->number,
                    'document'       => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key ?? null,
                        ],
                    ],
                    'reseller'       => [
                        'id' => $this->faker->uuid(),
                    ],
                    'customer'       => [
                        'id' => $documentB->customer_id,
                    ],
                    'skuNumber'      => null,
                    'supportPackage' => $this->faker->uuid(),
                ],

                // Should be skipped - customer not found
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentB->number,
                    'document'       => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key ?? null,
                        ],
                    ],
                    'reseller'       => [
                        'id' => $documentB->reseller_id,
                    ],
                    'customer'       => [
                        'id' => $this->faker->uuid(),
                    ],
                    'skuNumber'      => null,
                    'supportPackage' => $this->faker->uuid(),
                ],
            ],
        ]);

        // Pre-test
        self::assertEquals(1, $model->warranties()->count());

        // Test
        $warranties = $factory->assetDocumentsWarrantiesExtended($model, $asset);
        $warranties = new Collection($warranties);

        self::assertCount(5, $warranties);

        // Existing warranty should be updated
        /** @var AssetWarranty $a */
        $a = $warranties->first(static function (AssetWarranty $warranty) use ($documentA, $date): bool {
            return $warranty->document_id === $documentA->getKey()
                && $date->startOfDay()->equalTo($warranty->start);
        });

        self::assertNotNull($a);
        self::assertEquals($date->startOfDay(), $a->start);
        self::assertEquals($date->startOfDay(), $a->end);
        self::assertNull($a->reseller_id);
        self::assertNull($a->customer_id);
        self::assertEquals($documentA->getKey(), $a->document_id);
        self::assertEquals($model->getKey(), $a->asset_id);
        self::assertEquals(2, $a->serviceLevels->count());

        $as = $a->serviceLevels->first(static function (ServiceLevel $level) use ($skuNumber): bool {
            return $level->sku === $skuNumber;
        });

        self::assertNotNull($as);

        // Document null
        /** @var AssetWarranty $b */
        $b = $warranties->first(static function (AssetWarranty $warranty) use ($documentA): bool {
            return $warranty->document_id === null
                && $warranty->document_number === $documentA->number;
        });

        self::assertNotNull($b);
        self::assertEquals($b->serviceGroup->sku, $supportPackage);

        // No service
        /** @var AssetWarranty $c */
        $c = $warranties->first(static function (AssetWarranty $warranty) use ($documentB): bool {
            return $warranty->document_id === $documentB->getKey();
        });

        self::assertNotNull($c);
        self::assertEquals($date->startOfDay(), $c->start);
        self::assertEquals($date->startOfDay(), $c->end);
        self::assertEquals($resellerB->getKey(), $c->reseller_id);
        self::assertEquals($customerB->getKey(), $c->customer_id);
        self::assertEquals($documentB->getKey(), $c->document_id);
        self::assertEquals($model->getKey(), $c->asset_id);
        self::assertEquals(0, $c->serviceLevels->count());

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
        $documents     = Mockery::mock(DocumentFactory::class);
        $documents
            ->shouldReceive('create')
            ->withArgs(static function (mixed $object) use ($asset, $assetDocument): bool {
                return $object instanceof AssetDocumentObject
                    && $object->document === $assetDocument
                    && $object->asset === $asset;
            })
            ->once()
            ->andReturn($document);

        $factory = Mockery::mock(AssetFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
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
        $factory  = Mockery::mock(AssetFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
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
            'vendor'             => $this->faker->word(),
            'sku'                => $this->faker->word(),
            'eolDate'            => "{$this->faker->unixTime()}000",
            'eosDate'            => '',
            'productDescription' => $this->faker->sentence(),
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
            ->with($oem, $asset->sku, $asset->productDescription, $asset->eolDate, $asset->eosDate)
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

            /**
             * @inheritDoc
             */
            public function assetTags(ViewAsset $asset): array {
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

            /**
             * @inheritDoc
             */
            public function assetCoverages(ViewAsset $asset): array {
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
     * @covers ::isWarranty
     * @covers ::isWarrantyExtended
     *
     * @dataProvider dataProviderIsWarranty
     *
     * @param array<string,mixed> $properties
     */
    public function testIsWarranty(
        bool $isWarranty,
        bool $isExtendedWarranty,
        array $properties,
    ): void {
        $warranty = AssetWarranty::factory()->make($properties);
        $factory  = new class() extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public static function isWarranty(AssetWarranty $warranty): bool {
                return parent::isWarranty($warranty);
            }

            public static function isWarrantyExtended(AssetWarranty $warranty): bool {
                return parent::isWarrantyExtended($warranty);
            }
        };

        self::assertEquals($isWarranty, $factory::isWarranty($warranty));
        self::assertEquals($isExtendedWarranty, $factory::isWarrantyExtended($warranty));
    }

    /**
     * @covers ::compareAssetWarranties
     */
    public function testCompareAssetWarranties(): void {
        // Prepare
        $a       = AssetWarranty::factory()->make([
            'type_id' => $this->faker->uuid(),
            'start'   => $this->faker->dateTime(),
            'end'     => $this->faker->dateTime(),
        ]);
        $b       = AssetWarranty::factory()->make([
            'type_id' => $this->faker->uuid(),
            'start'   => $this->faker->dateTime(),
            'end'     => $this->faker->dateTime(),
        ]);
        $factory = new class() extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public static function compareAssetWarranties(AssetWarranty $a, AssetWarranty $b): int {
                return parent::compareAssetWarranties($a, $b);
            }
        };

        // Test
        self::assertNotEquals(0, $factory::compareAssetWarranties($a, $b));

        // Make same
        $a->type_id = $b->type_id;
        $a->start   = $b->start->midDay();
        $a->end     = $b->end;

        self::assertEquals(0, $factory::compareAssetWarranties($a, $b));
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

            public function assetWarranty(Asset $model, CoverageEntry $entry): ?AssetWarranty {
                return parent::assetWarranty($model, $entry);
            }
        };

        $actual   = $factory->assetWarranty($asset, $entry)?->getAttributes();
        $expected = [
            'start'            => '2019-12-10 00:00:00',
            'end'              => '2024-12-09 00:00:00',
            'asset_id'         => $asset->getKey(),
            'type_id'          => $type->getKey(),
            'status_id'        => $status->getKey(),
            'description'      => $entry->description,
            'service_group_id' => null,
            'customer_id'      => null,
            'reseller_id'      => null,
            'document_id'      => null,
            'document_number'  => null,
        ];

        self::assertEquals($expected, $actual);
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

            public function assetWarranty(Asset $model, CoverageEntry $entry): ?AssetWarranty {
                return parent::assetWarranty($model, $entry);
            }
        };

        self::assertNull($factory->assetWarranty($asset, $entry));
    }

    /**
     * @covers ::assetWarranties
     */
    public function testAssetWarranties(): void {
        $asset                   = Asset::factory()->create();
        $type                    = TypeModel::factory()->create([
            'object_type' => (new AssetWarranty())->getMorphClass(),
        ]);
        $status                  = Status::factory()->create([
            'object_type' => (new AssetWarranty())->getMorphClass(),
        ]);
        $documentWarranty        = AssetWarranty::factory()->create([
            'asset_id' => $asset,
            'type_id'  => null,
            'start'    => $this->faker->dateTime(),
            'end'      => $this->faker->dateTime(),
        ]);
        $warrantyShouldBeUpdated = AssetWarranty::factory()->create([
            'reseller_id' => null,
            'customer_id' => null,
            'asset_id'    => $asset,
            'type_id'     => $type,
            'start'       => Date::make($this->faker->dateTime())->startOfDay(),
            'end'         => Date::make($this->faker->dateTime())->startOfDay(),
        ]);
        $warrantyShouldBeReused  = (new Collection([
            AssetWarranty::factory()->create([
                'reseller_id' => null,
                'customer_id' => null,
                'asset_id'    => $asset,
                'type_id'     => $type,
                'start'       => $this->faker->dateTime(),
                'end'         => $this->faker->dateTime(),
            ]),
            AssetWarranty::factory()->create([
                'reseller_id' => null,
                'customer_id' => null,
                'asset_id'    => $asset,
                'type_id'     => $type,
                'start'       => $this->faker->dateTime(),
                'end'         => $this->faker->dateTime(),
            ]),
        ]))
            ->sort(new KeysComparator())
            ->first();
        $entryShouldBeCreated    = new CoverageEntry([
            'coverageStartDate' => $warrantyShouldBeReused->start->format('Y-m-d'),
            'coverageEndDate'   => $warrantyShouldBeReused->end->format('Y-m-d'),
            'type'              => $type->key,
            'status'            => $status->key,
            'description'       => "(created) {$this->faker->text()}",
        ]);
        $entryShouldBeUpdated    = new CoverageEntry([
            'coverageStartDate' => $warrantyShouldBeUpdated->start->format('Y-m-d'),
            'coverageEndDate'   => $warrantyShouldBeUpdated->end->format('Y-m-d'),
            'type'              => $warrantyShouldBeUpdated->type->key,
            'status'            => $status->key,
            'description'       => "(updated) {$this->faker->text()}",
        ]);
        $entryShouldBeIgnored    = new CoverageEntry();
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

            public function assetWarranties(Asset $model, ViewAsset $asset): Collection {
                return parent::assetWarranties($model, $asset);
            }
        };

        $map      = static fn(AssetWarranty $warranty) => $warranty->getAttributes();
        $actual   = $factory
            ->assetWarranties($asset, $viewAsset)
            ->sort(new KeysComparator())
            ->map($map)
            ->values();
        $expected = (new EloquentCollection([
            $documentWarranty->fresh(),
            (clone $warrantyShouldBeUpdated)->forceFill([
                'status_id'   => $status->getKey(),
                'description' => $entryShouldBeUpdated->description,
            ]),
            (clone $warrantyShouldBeReused)->forceFill([
                'start'       => $normalizer->datetime($entryShouldBeCreated->coverageStartDate),
                'end'         => $normalizer->datetime($entryShouldBeCreated->coverageEndDate),
                'type_id'     => $type->getKey(),
                'status_id'   => $status->getKey(),
                'description' => $entryShouldBeCreated->description,
            ]),
        ]))
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

    /**
     * @return array<string,array{bool,bool,array<string,mixed>}>
     */
    public function dataProviderIsWarranty(): array {
        return [
            'warranty'                           => [
                true,
                false,
                [
                    'type_id' => '4f820bae-79a5-4558-b90c-d8d7060688b8',
                ],
            ],
            'warranty (document number != null)' => [
                true,
                false,
                [
                    'type_id'         => 'ac1a2af5-2f07-47d4-a390-8d701ce50a13',
                    'document_number' => '123',
                ],
            ],
            'initial warranty'                   => [
                false,
                false,
                [
                    'type_id'         => null,
                    'document_number' => null,
                ],
            ],
            'extended warranty'                  => [
                false,
                true,
                [
                    'type_id'         => null,
                    'document_number' => 123,
                ],
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

    /**
     * @inheritDoc
     */
    public function assetDocumentsWarrantiesExtended(Asset $model, ViewAsset $asset): array {
        return parent::assetDocumentsWarrantiesExtended($model, $asset);
    }
}
