<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Exceptions\ErrorReport;
use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Location;
use App\Models\Model;
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
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CoverageResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\TagResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

use function array_column;
use function array_map;
use function array_unique;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\AssetFactory
 */
class AssetFactoryTest extends TestCase {
    use WithoutOrganizationScope;
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

        $this->assertCount(1, $this->getQueryLog());
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
            $this->expectException(InvalidArgumentException::class);
            $this->expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAsset(): void {
        // Mock
        $this->overrideFinders();

        // Prepare
        $container = $this->app->make(Container::class);
        $documents = $container->make(DocumentFactory::class);

        // Load
        $json  = $this->getTestData()->json('~asset-full.json');
        $asset = new ViewAsset($json);

        $this->flushQueryLog();

        // Test
        /** @var \App\Services\DataLoader\Factories\AssetFactory $factory */
        $factory = $container->make(AssetFactory::class)->setDocumentFactory($documents);
        $created = $factory->create($asset);

        $this->assertEquals(
            $this->getTestData()->json('~createFromAsset-create-expected.json'),
            array_column($this->getQueryLog(), 'query'),
        );
        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($asset->id, $created->getKey());
        $this->assertEquals($asset->resellerId, $created->reseller_id);
        $this->assertEquals($asset->serialNumber, $created->serial_number);
        $this->assertEquals($asset->dataQualityScore, $created->data_quality);
        $this->assertEquals($asset->updatedAt, $this->getDatetime($created->changed_at));
        $this->assertEquals($asset->vendor, $created->oem->key);
        $this->assertEquals($asset->productDescription, $created->product->name);
        $this->assertEquals($asset->sku, $created->product->sku);
        $this->assertNull($created->product->eos);
        $this->assertEquals($asset->eosDate, (string) $created->product->eos);
        $this->assertEquals($asset->eolDate, $this->getDatetime($created->product->eol));
        $this->assertEquals($asset->assetType, $created->type->key);
        $this->assertEquals($asset->status, $created->status->key);
        $this->assertEquals($asset->customerId, $created->customer->getKey());
        $this->assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($created->location),
        );
        $this->assertEquals(
            $this->getContacts($asset),
            $this->getModelContacts($created),
        );
        $this->assertEquals(
            $this->getAssetTags($asset),
            $this->getModelTags($created),
        );
        $this->assertEquals(
            $this->getAssetCoverages($asset),
            $this->getModelCoverages($created),
        );

        // Documents
        $this->assertEquals(1, Document::query()->count());
        $this->assertEquals(2, DocumentEntry::query()->count());

        // Warranties
        $this->assertEquals(
            [
                [
                    'serviceGroup'  => null,
                    'serviceLevels' => [],
                ],
                [
                    'serviceGroup'  => 'H7J34AC',
                    'serviceLevels' => [
                        [
                            'sku' => 'HA151AC',
                        ],
                    ],
                ],
            ],
            $created->warranties
                ->map(static function (AssetWarranty $warranty): array {
                    return [
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
                ->sortBy('serviceGroup')
                ->values()
                ->all(),
        );

        /** @var \App\Models\AssetWarranty $initial */
        $initial = $created->warranties->first(static function (AssetWarranty $warranty): bool {
            return $warranty->document_number === null;
        });

        $this->assertNotNull($initial);
        $this->assertEquals($initial->asset_id, $created->getKey());
        $this->assertNull($initial->document_id);
        $this->assertNull($initial->document_number);
        $this->assertEquals($created->customer_id, $initial->customer_id);
        $this->assertNull($initial->start);
        $this->assertEquals($asset->assetDocument[0]->warrantyEndDate, $this->getDatetime($initial->end));

        /** @var \App\Models\AssetWarranty $extended */
        $extended = $created->warranties->first(static function (AssetWarranty $warranty): bool {
            return $warranty->document_number !== null;
        });

        $this->assertEquals($extended->asset_id, $created->getKey());
        $this->assertNotNull($extended->document_id);
        $this->assertEquals($created->customer_id, $extended->customer_id);
        $this->assertNotNull($extended->start);
        $this->assertNotNull($extended->end);

        // Entries related to other assets should not be updated
        DocumentEntry::factory()->create([
            'document_id' => Document::query()->first(),
        ]);

        $this->flushQueryLog();

        // Asset should be updated
        /** @var \App\Services\DataLoader\Factories\AssetFactory $factory */
        $factory = $container->make(AssetFactory::class)->setDocumentFactory($documents);
        $json    = $this->getTestData()->json('~asset-changed.json');
        $asset   = new ViewAsset($json);
        $updated = $factory->create($asset);

        $this->assertEquals(
            $this->getTestData()->json('~createFromAsset-update-expected.json'),
            array_column($this->getQueryLog(), 'query'),
        );
        $this->assertNotNull($updated);
        $this->assertSame($created, $updated);
        $this->assertEquals($asset->id, $updated->getKey());
        $this->assertNull($updated->reseller_id);
        $this->assertEquals($asset->serialNumber, $updated->serial_number);
        $this->assertEquals($asset->dataQualityScore, $updated->data_quality);
        $this->assertEquals($asset->updatedAt, $this->getDatetime($updated->changed_at));
        $this->assertEquals($asset->vendor, $updated->oem->key);
        $this->assertEquals($created->product->name, $updated->product->name);
        $this->assertEquals($asset->sku, $updated->product->sku);
        $this->assertEquals($asset->eosDate, $this->getDatetime($updated->product->eos));
        $this->assertEquals($asset->eolDate, $this->getDatetime($updated->product->eol));
        $this->assertEquals($asset->assetType, $updated->type->key);
        $this->assertEquals($asset->customerId, $updated->customer->getKey());
        $this->assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($updated->location),
        );
        $this->assertEquals(
            $this->getContacts($asset),
            $this->getModelContacts($updated),
        );
        $this->assertEquals(
            $this->getAssetTags($asset),
            $this->getModelTags($updated),
        );
        $this->assertEquals(
            $this->getAssetCoverages($asset),
            $this->getModelCoverages($updated),
        );

        // Documents
        $this->assertEquals(1, Document::query()->count());
        $this->assertEquals(2, DocumentEntry::query()->count());

        $document = Document::query()->first();

        $this->assertEquals(
            $asset->assetDocument[0]?->document?->id,
            $document->getKey(),
        );
        $this->assertEquals(
            $asset->assetDocument[0]?->endDate,
            $this->getDatetime($document->end),
        );

        $this->flushQueryLog();

        // No changes
        /** @var \App\Services\DataLoader\Factories\AssetFactory $factory */
        $factory = $container->make(AssetFactory::class)->setDocumentFactory($documents);
        $json    = $this->getTestData()->json('~asset-changed.json');
        $asset   = new ViewAsset($json);

        $factory->create($asset);

        $this->assertCount(7, $this->getQueryLog());
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

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($asset->id, $created->getKey());
        $this->assertEquals($asset->serialNumber, $created->serial_number);
        $this->assertEquals($asset->dataQualityScore, $created->data_quality);
        $this->assertEquals($asset->vendor, $created->oem->key);
        $this->assertEquals($asset->productDescription, $created->product->name);
        $this->assertEquals($asset->sku, $created->product->sku);
        $this->assertNull($created->product->eos);
        $this->assertEquals($asset->eosDate, (string) $created->product->eos);
        $this->assertEquals($asset->eolDate, (string) $created->product->eol);
        $this->assertEquals($asset->assetType, $created->type->key);
        $this->assertNull($created->customer_id);
        $this->assertNull($created->location_id);
        $this->assertEquals(
            $this->getModelContacts($created),
            $this->getContacts($asset),
        );
        $this->assertEquals(
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
        $this->expectException(CustomerNotFound::class);

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

        $this->assertNull($created->location);
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

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertNull($created->type);
    }

    /**
     * @covers ::assetDocuments
     */
    public function testAssetDocuments(): void {
        // Fake
        Event::fake(ErrorReport::class);

        // Prepare
        $model     = Asset::factory()->make();
        $asset     = new ViewAsset([
            'assetDocument' => [
                [
                    'document'  => ['id' => 'a'],
                    'startDate' => '09/07/2020',
                    'endDate'   => '09/07/2021',
                ],
                [
                    'document'  => ['id' => 'a'],
                    'startDate' => '09/01/2020',
                    'endDate'   => '09/07/2021',
                ],
                [
                    'document'  => ['id' => 'b'],
                    'startDate' => '09/01/2020',
                    'endDate'   => '09/07/2021',
                ],
            ],
        ]);
        $documents = Mockery::mock(DocumentFactory::class);
        $documents
            ->shouldReceive('create')
            ->with(Mockery::on(static function (AssetDocumentObject $object) use ($model): bool {
                $ids = array_unique(array_map(static function (ViewAssetDocument $d): string {
                    return $d->document->id;
                }, $object->entries));

                return $object->asset === $model
                    && $object->document instanceof ViewAssetDocument
                    && $object->document->startDate === '09/01/2020'
                    && count($ids) === 1;
            }))
            ->twice()
            ->andReturnUsing(static function (Type $type): ?Document {
                return $type instanceof AssetDocumentObject && $type->document->document->id === 'a'
                    ? new Document()
                    : null;
            });
        $factory = new class($documents) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ?DocumentFactory $documentFactory,
            ) {
                // empty
            }

            public function assetDocuments(Asset $model, ViewAsset $asset): Collection {
                return parent::assetDocuments($model, $asset);
            }
        };

        $this->assertCount(1, $factory->assetDocuments($model, $asset));

        Event::assertNotDispatched(ErrorReport::class);
    }

    /**
     * @covers ::assetDocuments
     */
    public function testAssetDocumentsNoDocumentId(): void {
        // Prepare
        $model   = Asset::factory()->make();
        $asset   = new ViewAsset([
            'assetDocument' => [
                [
                    'documentNumber' => '12345678',
                    'startDate'      => '09/07/2020',
                    'endDate'        => '09/07/2021',
                ],
            ],
        ]);
        $handler = $this->app->make(ExceptionHandler::class);
        $factory = new class($handler) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ExceptionHandler $exceptionHandler,
            ) {
                // empty
            }

            public function assetDocuments(Asset $model, ViewAsset $asset): Collection {
                return parent::assetDocuments($model, $asset);
            }
        };

        // Test
        $this->assertCount(0, $factory->assetDocuments($model, $asset));
    }

    /**
     * @covers ::assetDocuments
     */
    public function testAssetDocumentsFailedCreateDocument(): void {
        // Fake
        Event::fake(ErrorReport::class);

        // Prepare
        $model     = Asset::factory()->make();
        $asset     = new ViewAsset([
            'assetDocument' => [
                [
                    'document'  => ['id' => 'a'],
                    'startDate' => '09/07/2020',
                    'endDate'   => '09/07/2021',
                ],
            ],
        ]);
        $handler   = $this->app->make(ExceptionHandler::class);
        $documents = Mockery::mock(DocumentFactory::class);
        $documents
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (Type $type): ?Document {
                throw new ResellerNotFound($this->faker->uuid);
            });
        $factory = new class($handler, $documents) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ExceptionHandler $exceptionHandler,
                protected ?DocumentFactory $documentFactory,
            ) {
                // empty
            }

            public function assetDocuments(Asset $model, ViewAsset $asset): Collection {
                return parent::assetDocuments($model, $asset);
            }
        };

        // Test
        $this->assertCount(0, $factory->assetDocuments($model, $asset));

        Event::assertDispatched(ErrorReport::class, static function (ErrorReport $event): bool {
            return $event->getError() instanceof FailedToProcessAssetViewDocument
                && $event->getError()->getPrevious() instanceof ResellerNotFound;
        });
    }

    /**
     * @covers ::assetWarranties
     */
    public function testAssetWarranties(): void {
        $a       = AssetWarranty::factory()->make();
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
            ->shouldReceive('assetInitialWarranties')
            ->with($model, $asset)
            ->once()
            ->andReturn([$a]);
        $factory
            ->shouldReceive('assetExtendedWarranties')
            ->with($model, $asset)
            ->once()
            ->andReturn([$b]);

        $this->assertEquals([$a, $b], $factory->assetWarranties($model, $asset));
    }

    /**
     * @covers ::assetInitialWarranties
     */
    public function testAssetInitialWarranties(): void {
        $factory = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(ExceptionHandler::class),
            $this->app->make(ResellerResolver::class),
            $this->app->make(CustomerResolver::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ExceptionHandler $exceptionHandler,
                protected ResellerResolver $resellerResolver,
                protected CustomerResolver $customerResolver,
            ) {
                $this->resellerFinder = null;
                $this->customerFinder = null;
            }

            /**
             * @inheritDoc
             */
            public function assetInitialWarranties(Asset $model, ViewAsset $asset): array {
                return parent::assetInitialWarranties($model, $asset);
            }
        };

        $date      = Date::now()->startOfDay();
        $model     = Asset::factory()->create();
        $resellerA = Reseller::factory()->create();
        $resellerB = Reseller::factory()->create();
        $customerA = Customer::factory()->create();
        $customerB = Customer::factory()->create();
        $document  = Document::factory()->create();
        $warranty  = AssetWarranty::factory()->create([
            'end'         => $date->subYear(),
            'asset_id'    => $model,
            'document_id' => $document,
        ]);
        $existing  = AssetWarranty::factory()->create([
            'start'       => null,
            'end'         => $date->subYear(),
            'asset_id'    => $model,
            'customer_id' => $customerB,
            'reseller_id' => $resellerB,
            'document_id' => null,
        ]);
        $asset     = new ViewAsset([
            'id'            => $model->getKey(),
            'assetDocument' => [
                // Should be added
                [
                    'warrantyEndDate' => $this->getDatetime($date),
                    'reseller'        => [
                        'id' => $resellerB->getKey(),
                    ],
                    'customer'        => [
                        'id' => $customerB->getKey(),
                    ],
                ],
                // Only one should be added
                [
                    'warrantyEndDate' => $this->getDatetime($date),
                    'reseller'        => [
                        'id' => $resellerA->getKey(),
                    ],
                    'customer'        => [
                        'id' => $customerA->getKey(),
                    ],
                ],
                [
                    'warrantyEndDate' => $this->getDatetime($date),
                    'reseller'        => [
                        'id' => $resellerA->getKey(),
                    ],
                    'customer'        => [
                        'id' => $customerA->getKey(),
                    ],
                ],
                // Should be added - another date
                [
                    'warrantyEndDate' => $this->getDatetime($date->addDay()),
                    'reseller'        => [
                        'id' => $resellerA->getKey(),
                    ],
                    'customer'        => [
                        'id' => $customerA->getKey(),
                    ],
                ],
                // Should be added - another reseller
                [
                    'warrantyEndDate' => $this->getDatetime($date),
                    'reseller'        => [
                        'id' => $resellerB->getKey(),
                    ],
                    'customer'        => [
                        'id' => $customerA->getKey(),
                    ],
                ],
                // Should be added - another customer
                [
                    'warrantyEndDate' => $this->getDatetime($date),
                    'reseller'        => [
                        'id' => $resellerA->getKey(),
                    ],
                    'customer'        => [
                        'id' => $customerB->getKey(),
                    ],
                ],
                // Should be skipped - no end date
                [
                    'warrantyEndDate' => null,
                    'reseller'        => [
                        'id' => $resellerA->getKey(),
                    ],
                    'customer'        => [
                        'id' => $customerA->getKey(),
                    ],
                ],
                // Should be skipped - reseller not found
                [
                    'warrantyEndDate' => $this->getDatetime($date),
                    'reseller'        => [
                        'id' => $this->faker->uuid,
                    ],
                    'customer'        => [
                        'id' => $customerA->getKey(),
                    ],
                ],
                // Should be skipped - customer not found
                [
                    'warrantyEndDate' => $this->getDatetime($date),
                    'reseller'        => [
                        'id' => $resellerA->getKey(),
                    ],
                    'customer'        => [
                        'id' => $this->faker->uuid,
                    ],
                ],
            ],
        ]);

        // Test
        $warranties = $factory->assetInitialWarranties($model, $asset);
        $warranties = new Collection($warranties);

        $this->assertCount(5, $warranties);

        // Should not be updated (because document is defined)
        $this->assertEquals($date->subYear()->startOfDay(), $warranty->refresh()->end);

        // Should be created for CustomerA / ResellerA
        /** @var \App\Models\AssetWarranty $b */
        $b = $warranties->first(static function (AssetWarranty $warranty) use ($date, $resellerA, $customerA): bool {
            return $warranty->end->equalTo($date)
                && $warranty->customer_id === $customerA->getKey()
                && $warranty->reseller_id === $resellerA->getKey();
        });

        $this->assertNotNull($b);
        $this->assertFalse($b->exists);
        $this->assertNull($b->document_id);
        $this->assertNull($b->start);
        $this->assertEquals($date, $b->end);
        $this->assertEquals($model->getKey(), $b->asset_id);

        // The existing warranty should be removed because `end` dates mismatch
        // + a new warranty should be created.
        /** @var \App\Models\AssetWarranty $c */
        $c = $warranties->first(static function (AssetWarranty $warranty) use ($customerB, $resellerB): bool {
            return $warranty->customer_id === $customerB->getKey()
                && $warranty->reseller_id === $resellerB->getKey();
        });

        $this->assertNotNull($c);
        $this->assertNotEquals($existing->getKey(), $c->getKey());
        $this->assertFalse($c->exists);
        $this->assertNull($c->document_id);
        $this->assertNull($c->start);
        $this->assertEquals($date, $c->end);
        $this->assertEquals($model->getKey(), $c->asset_id);
    }

    /**
     * @covers ::assetExtendedWarranties
     */
    public function testAssetExtendedWarranties(): void {
        // Mock
        $this->overrideServiceGroupFinder();
        $this->overrideServiceLevelFinder();

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
        $skuNumber      = $this->faker->uuid;
        $supportPackage = $this->faker->uuid;
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
                            'vendor' => $documentA->oem->key,
                        ],
                    ],
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => $this->faker->uuid,
                    'supportPackage' => $supportPackage,
                ],
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentA->number,
                    'document'       => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key,
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
                            'vendor' => $documentA->oem->key,
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
                            'vendor' => $documentA->oem->key,
                        ],
                    ],
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => $this->faker->uuid,
                    'supportPackage' => $this->faker->uuid,
                ],

                // Should be created - date not same
                [
                    'startDate'      => $this->getDatetime($date->subDay()),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentA->number,
                    'document'       => [
                        'id'                   => $documentA->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentA->oem->key,
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
                            'vendor' => $documentB->oem->key,
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
                    'skuNumber'      => $this->faker->uuid,
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
                            'vendor' => $documentB->oem->key,
                        ],
                    ],
                    'reseller'       => null,
                    'customer'       => null,
                    'skuNumber'      => null,
                    'supportPackage' => $this->faker->uuid,
                ],

                // Should be skipped - reseller not found
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentB->number,
                    'document'       => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key,
                        ],
                    ],
                    'reseller'       => [
                        'id' => $this->faker->uuid,
                    ],
                    'customer'       => [
                        'id' => $documentB->customer_id,
                    ],
                    'skuNumber'      => null,
                    'supportPackage' => $this->faker->uuid,
                ],

                // Should be skipped - customer not found
                [
                    'startDate'      => $this->getDatetime($date),
                    'endDate'        => $this->getDatetime($date),
                    'documentNumber' => $documentB->number,
                    'document'       => [
                        'id'                   => $documentB->getKey(),
                        'vendorSpecificFields' => [
                            'vendor' => $documentB->oem->key,
                        ],
                    ],
                    'reseller'       => [
                        'id' => $documentB->reseller_id,
                    ],
                    'customer'       => [
                        'id' => $this->faker->uuid,
                    ],
                    'skuNumber'      => null,
                    'supportPackage' => $this->faker->uuid,
                ],
            ],
        ]);

        // Pre-test
        $this->assertEquals(1, $model->warranties()->count());

        // Test
        $warranties = $factory->assetExtendedWarranties($model, $asset);
        $warranties = new Collection($warranties);

        $this->assertCount(5, $warranties);

        // Existing warranty should be updated
        /** @var \App\Models\AssetWarranty $a */
        $a = $warranties->first(static function (AssetWarranty $warranty) use ($documentA, $date): bool {
            return $warranty->document_id === $documentA->getKey()
                && $date->startOfDay()->equalTo($warranty->start);
        });

        $this->assertNotNull($a);
        $this->assertEquals($date->startOfDay(), $a->start);
        $this->assertEquals($date->startOfDay(), $a->end);
        $this->assertNull($a->reseller_id);
        $this->assertNull($a->customer_id);
        $this->assertEquals($documentA->getKey(), $a->document_id);
        $this->assertEquals($model->getKey(), $a->asset_id);
        $this->assertEquals(2, $a->serviceLevels->count());

        $as = $a->serviceLevels->first(static function (ServiceLevel $level) use ($skuNumber): bool {
            return $level->sku === $skuNumber;
        });

        $this->assertNotNull($as);

        // Document null
        /** @var \App\Models\AssetWarranty $b */
        $b = $warranties->first(static function (AssetWarranty $warranty) use ($documentA): bool {
            return $warranty->document_id === null
                && $warranty->document_number === $documentA->number;
        });

        $this->assertNotNull($b);
        $this->assertEquals($b->serviceGroup->sku, $supportPackage);

        // No service
        /** @var \App\Models\AssetWarranty $c */
        $c = $warranties->first(static function (AssetWarranty $warranty) use ($documentB): bool {
            return $warranty->document_id === $documentB->getKey();
        });

        $this->assertNotNull($c);
        $this->assertEquals($date->startOfDay(), $c->start);
        $this->assertEquals($date->startOfDay(), $c->end);
        $this->assertEquals($resellerB->getKey(), $c->reseller_id);
        $this->assertEquals($customerB->getKey(), $c->customer_id);
        $this->assertEquals($documentB->getKey(), $c->document_id);
        $this->assertEquals($model->getKey(), $c->asset_id);
        $this->assertEquals(0, $c->serviceLevels->count());

        // Existing warranty should be updated
        /** @var \App\Models\AssetWarranty $d */
        $d = $warranties->first(static function (AssetWarranty $w) use ($warranty): bool {
            return $w->getKey() === $warranty->getKey();
        });

        $this->assertNotNull($d);
    }

    /**
     * @covers ::assetDocumentDocument
     */
    public function testAssetDocumentDocumentWithDocument(): void {
        $asset         = Asset::factory()->make();
        $document      = Document::factory()->make();
        $assetDocument = new ViewAssetDocument([
            'document' => [
                'id' => $this->faker->uuid,
            ],
        ]);
        $documents     = Mockery::mock(DocumentFactory::class);
        $documents
            ->shouldReceive('find')
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

        $this->assertSame($document, $factory->assetDocumentDocument($asset, $assetDocument));
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

        $this->assertNull($factory->assetDocumentDocument($asset, $document));
    }

    /**
     * @covers ::assetOem
     */
    public function testAssetOem(): void {
        $asset   = new ViewAsset(['vendor' => $this->faker->word]);
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
        $asset   = new ViewAsset(['assetType' => $this->faker->word]);
        $factory = Mockery::mock(AssetFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

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

        $this->assertNull($factory->assetType($asset));
    }

    /**
     * @covers ::assetProduct
     */
    public function testAssetProduct(): void {
        $oem   = Oem::factory()->make();
        $asset = new ViewAsset([
            'vendor'             => $this->faker->word,
            'sku'                => $this->faker->word,
            'eolDate'            => "{$this->faker->unixTime}000",
            'eosDate'            => '',
            'productDescription' => $this->faker->sentence,
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
            'id'         => $this->faker->uuid,
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

        $this->assertEquals($location, $factory->assetLocation($asset));
    }

    /**
     * @covers ::assetLocation
     */
    public function testAssetLocationNoLocation(): void {
        $asset     = new ViewAsset([
            'id' => $this->faker->uuid,
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

        $this->assertNull($factory->assetLocation($asset));
    }

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $a          = new ViewAsset([
            'id'           => $this->faker->uuid,
            'serialNumber' => $this->faker->uuid,
        ]);
        $b          = new ViewAsset([
            'id'           => $this->faker->uuid,
            'serialNumber' => $this->faker->uuid,
        ]);
        $resolver   = $this->app->make(AssetResolver::class);
        $normalizer = $this->app->make(Normalizer::class);
        $products   = Mockery::mock(ProductResolver::class);
        $locations  = Mockery::mock(LocationFactory::class);

        Asset::factory()->create([
            'id' => $a->id,
        ]);

        $factory = new class($normalizer, $resolver, $products, $locations) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected AssetResolver $assetResolver,
                protected ProductResolver $productResolver,
                protected LocationFactory $locationFactory,
            ) {
                // empty
            }
        };

        $callback = Mockery::spy(function (EloquentCollection $collection): void {
            $this->assertCount(1, $collection);
        });

        $factory->prefetch([$a, $b], false, Closure::fromCallable($callback));

        $callback->shouldHaveBeenCalled()->once();

        $this->flushQueryLog();

        $factory->find($a);
        $factory->find($b);

        $this->assertCount(0, $this->getQueryLog());
    }

    /**
     * @covers ::assetStatus
     */
    public function testAssetStatus(): void {
        $asset   = new ViewAsset(['status' => $this->faker->word]);
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
        $this->assertEmpty($factory->assetTags(new ViewAsset(['assetTag' => null])));

        // Empty
        $this->assertEmpty($factory->assetTags(new ViewAsset(['assetTag' => ' '])));

        // Not empty
        $asset    = new ViewAsset(['assetTag' => 'tag']);
        $tags     = $factory->assetTags($asset);
        $expected = [
            'tag' => [
                'name' => 'tag',
            ],
        ];

        $this->assertCount(1, $tags);
        $this->assertEquals($expected, $this->getAssetTags($asset));
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
        $this->assertEmpty($factory->assetCoverages(new ViewAsset(['assetCoverage' => null])));

        // Empty
        $this->assertEmpty($factory->assetCoverages(new ViewAsset(['assetCoverage' => ['', null]])));

        // Not empty
        $asset     = new ViewAsset([
            'assetCoverage' => ['a', 'a', 'b'],
        ]);
        $coverages = $factory->assetCoverages($asset);
        $expected  = [
            'a' => [
                'key'  => 'a',
                'name' => 'a',
            ],
            'b' => [
                'key'  => 'b',
                'name' => 'b',
            ],
        ];

        $this->assertCount(2, $coverages);
        $this->assertEquals($expected, $this->getCoverages($coverages));
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

    public function assetOem(ViewAsset $asset): Oem {
        return parent::assetOem($asset);
    }

    public function assetType(ViewAsset $asset): ?TypeModel {
        return parent::assetType($asset);
    }

    public function assetProduct(ViewAsset $asset): Product {
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
    public function assetExtendedWarranties(Asset $model, ViewAsset $asset): array {
        return parent::assetExtendedWarranties($model, $asset);
    }
}
