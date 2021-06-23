<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Enums\ProductType;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Status;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Events\ObjectSkipped;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Exceptions\ViewAssetDocumentNoDocument;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

use function array_map;
use function array_unique;
use function count;
use function is_null;

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
        // Prepare
        $container = $this->app->make(Container::class);
        $documents = $container->make(DocumentFactory::class);

        /** @var \App\Services\DataLoader\Factories\AssetFactory $factory */
        $factory = $container->make(AssetFactory::class)->setDocumentFactory($documents);

        // Load
        $json  = $this->getTestData()->json('~asset-full.json');
        $asset = new ViewAsset($json);

        Reseller::factory()->create([
            'id' => $asset->resellerId,
        ]);

        Customer::factory()->create([
            'id' => $asset->customerId,
        ]);

        // Test
        $created = $factory->create($asset);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($asset->id, $created->getKey());
        $this->assertEquals($asset->resellerId, $created->reseller_id);
        $this->assertEquals($asset->serialNumber, $created->serial_number);
        $this->assertEquals($asset->dataQualityScore, $created->data_quality);
        $this->assertEquals($asset->vendor, $created->oem->abbr);
        $this->assertEquals(ProductType::asset(), $created->product->type);
        $this->assertEquals($asset->productDescription, $created->product->name);
        $this->assertEquals($asset->sku, $created->product->sku);
        $this->assertNull($created->product->eos);
        $this->assertEquals($asset->eosDate, (string) $created->product->eos);
        $this->assertEquals($asset->eolDate, $this->getDatetime($created->product->eol));
        $this->assertEquals($asset->assetType, $created->type->key);
        $this->assertEquals($asset->status, $created->status->key);
        $this->assertEquals($asset->customerId, $created->customer->getKey());
        $this->assertEquals($asset->assetCoverage, $created->coverage->key);
        $this->assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($created->location, false),
        );
        $this->assertEquals(
            $this->getModelContacts($created),
            $this->getContacts($asset),
        );
        $this->assertEquals(
            $this->getModelTags($created),
            $this->getTags($asset),
        );

        // Documents
        $this->assertEquals(1, Document::query()->count());
        $this->assertEquals(2, DocumentEntry::query()->count());

        // Warranties
        $this->assertEquals(
            [
                [
                    'sku'      => null,
                    'package'  => null,
                    'services' => [],
                ],
                [
                    'sku'      => 'H7J34AC',
                    'package'  => 'HPE Foundation Care 24x7 SVC',
                    'services' => [
                        [
                            'sku'     => 'HA151AC',
                            'package' => 'HPE Hardware Maintenance Onsite Support',
                        ],
                    ],
                ],
            ],
            $created->warranties
                ->map(static function (AssetWarranty $warranty): array {
                    return [
                        'sku'      => $warranty->support?->sku,
                        'package'  => $warranty->support?->name,
                        'services' => $warranty->services
                            ->map(static function (Product $product): array {
                                return [
                                    'sku'     => $product->sku,
                                    'package' => $product->name,
                                ];
                            })
                            ->all(),
                    ];
                })
                ->all(),
        );

        /** @var \App\Models\AssetWarranty $initial */
        $initial = $created->warranties->first(static function (AssetWarranty $warranty): bool {
            return is_null($warranty->document_id);
        });

        $this->assertNotNull($initial);
        $this->assertEquals($initial->asset_id, $created->getKey());
        $this->assertNull($initial->document_id);
        $this->assertEquals($created->customer_id, $initial->customer_id);
        $this->assertNull($initial->start);
        $this->assertEquals($asset->assetDocument[0]->warrantyEndDate, $this->getDatetime($initial->end));

        /** @var \App\Models\AssetWarranty $extended */
        $extended = $created->warranties->first(static function (AssetWarranty $warranty): bool {
            return (bool) $warranty->document_id;
        });

        $this->assertEquals($extended->asset_id, $created->getKey());
        $this->assertNotNull($extended->document_id);
        $this->assertEquals($created->customer_id, $extended->customer_id);
        $this->assertNotNull($extended->start);
        $this->assertNotNull($extended->end);

        // Customer should be updated
        $json    = $this->getTestData()->json('~asset-changed.json');
        $asset   = new ViewAsset($json);
        $updated = $factory->create($asset);

        $this->assertNotNull($updated);
        $this->assertSame($created, $updated);
        $this->assertEquals($asset->id, $updated->getKey());
        $this->assertNull($updated->ogranization_id);
        $this->assertEquals($asset->serialNumber, $updated->serial_number);
        $this->assertEquals($asset->dataQualityScore, $updated->data_quality);
        $this->assertEquals($asset->vendor, $updated->oem->abbr);
        $this->assertEquals(ProductType::asset(), $updated->product->type);
        $this->assertEquals($created->product->name, $updated->product->name);
        $this->assertEquals($asset->sku, $updated->product->sku);
        $this->assertEquals($asset->eosDate, $this->getDatetime($updated->product->eos));
        $this->assertEquals($asset->eolDate, $this->getDatetime($updated->product->eol));
        $this->assertEquals($asset->assetType, $updated->type->key);
        $this->assertEquals($asset->customerId, $updated->customer->getKey());
        $this->assertEquals($asset->assetCoverage, $updated->coverage->key);
        $this->assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($updated->location, false),
        );
        $this->assertEquals(
            $this->getModelContacts($updated),
            $this->getContacts($asset),
        );
        $this->assertEquals(
            $this->getModelTags($updated),
            $this->getTags($asset),
        );

        // Documents
        $this->assertEquals(1, Document::query()->count());
        $this->assertEquals(0, DocumentEntry::query()->count());
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

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($asset->id, $created->getKey());
        $this->assertEquals($asset->serialNumber, $created->serial_number);
        $this->assertEquals($asset->dataQualityScore, $created->data_quality);
        $this->assertEquals($asset->vendor, $created->oem->abbr);
        $this->assertEquals(ProductType::asset(), $created->product->type);
        $this->assertEquals($asset->productDescription, $created->product->name);
        $this->assertEquals($asset->sku, $created->product->sku);
        $this->assertNull($created->product->eos);
        $this->assertEquals($asset->eosDate, (string) $created->product->eos);
        $this->assertEquals($asset->eolDate, (string) $created->product->eol);
        $this->assertEquals($asset->assetType, $created->type->key);
        $this->assertEquals($asset->assetCoverage, $created->coverage->key);
        $this->assertNull($created->customer_id);
        $this->assertNull($created->location_id);
        $this->assertEquals(
            $this->getModelContacts($created),
            $this->getContacts($asset),
        );
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetAssetNoCustomer(): void {
        // Prepare
        $factory = $this->app->make(AssetFactory::class);
        $json    = $this->getTestData()->json('~asset-full.json');
        $asset   = new ViewAsset($json);

        Reseller::factory()->create([
            'id' => $asset->resellerId,
        ]);

        // Test
        $this->expectException(CustomerNotFoundException::class);

        $factory->create($asset);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetAssetInvalidAddress(): void {
        // Prepare
        $container = $this->app->make(Container::class);
        $factory   = $container->make(AssetFactory::class);

        // Test
        $json  = $this->getTestData()->json('~asset-invalid-address.json');
        $asset = new ViewAsset($json);

        Customer::factory()->create([
            'id' => $asset->customerId,
        ]);

        $created = $factory->create($asset);

        $this->assertNotNull($created->location);
        $this->assertNull($created->location->object_id);
        $this->assertEquals($created->getMorphClass(), $created->location->object_type);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetWithoutZip(): void {
        // Prepare
        $container = $this->app->make(Container::class);
        $factory   = $container->make(AssetFactory::class);

        // Test
        $json  = $this->getTestData()->json('~asset-nozip-address.json');
        $asset = new ViewAsset($json);

        Customer::factory()->create([
            'id' => $asset->customerId,
        ]);

        $created = $factory->create($asset);

        $this->assertNull($created->location);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetOnResellerLocation(): void {
        // Prepare
        $container = $this->app->make(Container::class);
        $factory   = $container->make(AssetFactory::class);

        // Load
        $json  = $this->getTestData()->json('~asset-reseller-location.json');
        $asset = new ViewAsset($json);

        Reseller::factory()->create([
            'id' => $asset->resellerId,
        ]);

        Customer::factory()->create([
            'id' => $asset->customerId,
        ]);

        // Test
        $created = $factory->create($asset);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($asset->id, $created->getKey());
        $this->assertEquals($asset->resellerId, $created->reseller_id);
        $this->assertEquals($asset->serialNumber, $created->serial_number);
        $this->assertEquals($asset->dataQualityScore, $created->data_quality);
        $this->assertEquals($asset->vendor, $created->oem->abbr);
        $this->assertEquals(ProductType::asset(), $created->product->type);
        $this->assertEquals($asset->productDescription, $created->product->name);
        $this->assertEquals($asset->sku, $created->product->sku);
        $this->assertNull($created->product->eos);
        $this->assertEquals($asset->eosDate, (string) $created->product->eos);
        $this->assertEquals($asset->eolDate, $this->getDatetime($created->product->eol));
        $this->assertEquals($asset->assetType, $created->type->key);
        $this->assertEquals($asset->customerId, $created->customer->getKey());
        $this->assertEquals($asset->assetCoverage, $created->coverage->key);
        $this->assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($created->location, false),
        );
    }

    /**
     * @covers ::assetDocuments
     */
    public function testAssetDocuments(): void {
        // Fake
        Event::fake();

        // Prepare
        $model     = AssetModel::factory()->make();
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

            public function assetDocuments(AssetModel $model, ViewAsset $asset): Collection {
                return parent::assetDocuments($model, $asset);
            }
        };

        $this->assertCount(1, $factory->assetDocuments($model, $asset));

        Event::assertNotDispatched(ObjectSkipped::class);
    }

    /**
     * @covers ::assetDocuments
     */
    public function testAssetDocumentsNoDocumentId(): void {
        // Fake
        Event::fake();

        // Prepare
        $model      = AssetModel::factory()->make();
        $asset      = new ViewAsset([
            'assetDocument' => [
                [
                    'documentNumber' => '12345678',
                    'startDate'      => '09/07/2020',
                    'endDate'        => '09/07/2021',
                ],
            ],
        ]);
        $dispatcher = $this->app->make(Dispatcher::class);
        $logger     = $this->app->make(LoggerInterface::class);
        $factory    = new class($logger, $dispatcher) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected LoggerInterface $logger,
                protected Dispatcher $dispatcher,
            ) {
                // empty
            }

            public function assetDocuments(AssetModel $model, ViewAsset $asset): Collection {
                return parent::assetDocuments($model, $asset);
            }
        };

        // Test
        $this->assertCount(0, $factory->assetDocuments($model, $asset));

        Event::assertDispatched(ObjectSkipped::class, static function (ObjectSkipped $event): bool {
            return $event->getReason() instanceof ViewAssetDocumentNoDocument;
        });
    }

    /**
     * @covers ::assetDocuments
     */
    public function testAssetDocumentsFailedCreateDocument(): void {
        // Fake
        Event::fake();

        // Prepare
        $model      = AssetModel::factory()->make();
        $asset      = new ViewAsset([
            'assetDocument' => [
                [
                    'document'  => ['id' => 'a'],
                    'startDate' => '09/07/2020',
                    'endDate'   => '09/07/2021',
                ],
            ],
        ]);
        $logger     = $this->app->make(LoggerInterface::class);
        $dispatcher = $this->app->make(Dispatcher::class);
        $documents  = Mockery::mock(DocumentFactory::class);
        $documents
            ->shouldReceive('create')
            ->once()
            ->andReturnUsing(function (Type $type): ?Document {
                throw new ResellerNotFoundException($this->faker->uuid);
            });
        $factory = new class($logger, $dispatcher, $documents) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected LoggerInterface $logger,
                protected Dispatcher $dispatcher,
                protected ?DocumentFactory $documentFactory,
            ) {
                // empty
            }

            public function assetDocuments(AssetModel $model, ViewAsset $asset): Collection {
                return parent::assetDocuments($model, $asset);
            }
        };

        // Test
        $this->assertCount(0, $factory->assetDocuments($model, $asset));

        Event::assertDispatched(ObjectSkipped::class, static function (ObjectSkipped $event): bool {
            return $event->getReason() instanceof ResellerNotFoundException;
        });
    }

    /**
     * @covers ::assetWarranties
     */
    public function testAssetWarranties(): void {
        $a         = AssetWarranty::factory()->make();
        $b         = AssetWarranty::factory()->make();
        $model     = AssetModel::factory()->make();
        $asset     = new ViewAsset();
        $documents = new Collection([Document::factory()->make()]);
        $factory   = Mockery::mock(AssetFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('assetDocuments')
            ->with($model, $asset)
            ->never();
        $factory
            ->shouldReceive('assetInitialWarranties')
            ->with($model, $asset, $documents)
            ->once()
            ->andReturn([$a]);
        $factory
            ->shouldReceive('assetExtendedWarranties')
            ->with($model, $documents)
            ->once()
            ->andReturn([$b]);

        $this->assertEquals([$a, $b], $factory->assetWarranties($model, $asset, $documents));
    }

    /**
     * @covers ::assetInitialWarranties
     */
    public function testAssetInitialWarranties(): void {
        $factory = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(Dispatcher::class),
            $this->app->make(LoggerInterface::class),
            $this->app->make(ResellerResolver::class),
            $this->app->make(CustomerResolver::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected Dispatcher $dispatcher,
                protected LoggerInterface $logger,
                protected ResellerResolver $resellerResolver,
                protected CustomerResolver $customerResolver,
            ) {
                $this->resellerFinder = null;
                $this->customerFinder = null;
            }

            public function assetInitialWarranties(AssetModel $model, ViewAsset $asset): array {
                return parent::assetInitialWarranties($model, $asset);
            }
        };

        $date      = Date::now()->startOfDay();
        $model     = AssetModel::factory()->create();
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
        $asset    = new ViewAsset([
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
        $this->assertTrue($b->wasRecentlyCreated);
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
        $this->assertTrue($c->wasRecentlyCreated);
        $this->assertNull($c->document_id);
        $this->assertNull($c->start);
        $this->assertEquals($date, $c->end);
        $this->assertEquals($model->getKey(), $c->asset_id);
    }

    /**
     * @covers ::assetExtendedWarranties
     */
    public function testAssetExtendedWarranties(): void {
        $factory = new class() extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function assetExtendedWarranties(AssetModel $model, Collection $documents): array {
                return parent::assetExtendedWarranties($model, $documents);
            }
        };

        $date     = Date::now();
        $customer = Customer::factory()->create();
        $service  = Product::factory()->create();
        $asset    = AssetModel::factory()->create([
            'customer_id' => $customer,
        ]);
        $docA     = Document::factory()->create([
            'customer_id' => $customer,
        ]);
        $docB     = Document::factory()->create([
            'customer_id' => $customer,
        ]);
        $docC     = Document::factory()->create();
        $docD     = Document::factory()->create([
            'start' => null,
        ]);
        $docE     = Document::factory()->create([
            'end' => null,
        ]);
        $warranty = AssetWarranty::factory()->create([
            'start'       => $date->subYear(),
            'end'         => $date,
            'asset_id'    => $asset,
            'document_id' => $docA,
        ]);
        $entryA   = DocumentEntry::factory()->create([
            'asset_id'    => $asset,
            'product_id'  => $asset->product_id,
            'document_id' => $docA,
        ]);
        $entryB   = DocumentEntry::factory()->create([
            'asset_id'    => $asset,
            'product_id'  => $asset->product_id,
            'document_id' => $docB,
        ]);

        $warranty->services()->sync($service->getKey());

        Document::factory()->create();

        // Pre-test
        $this->assertEquals(1, $asset->warranties()->count());
        $this->assertNotEquals($docA->customer_id, $warranty->customer_id);

        // Test
        $warranties = $factory->assetExtendedWarranties($asset, new Collection([$docA, $docB, $docC, $docD, $docE]));
        $warranties = new Collection($warranties);

        $this->assertCount(3, $warranties);

        // Existing warranty should be updated
        /** @var \App\Models\AssetWarranty $a */
        $a = $warranties->first(static function (AssetWarranty $warranty) use ($docA): bool {
            return $warranty->document_id === $docA->getKey();
        });

        $this->assertNotNull($a);
        $this->assertEquals($docA->start, $a->start);
        $this->assertEquals($docA->end, $a->end);
        $this->assertEquals($docA->customer_id, $a->customer_id);
        $this->assertEquals($docA->getKey(), $a->document_id);
        $this->assertEquals($asset->getKey(), $a->asset_id);
        $this->assertEquals(1, $a->services->count());
        $this->assertEquals($entryA->service_id, $a->services()->first()->getKey());
        $this->assertEquals($entryA->service_id, $a->services->first()->getKey());

        // Not existing - created
        $b = $warranties->first(static function (AssetWarranty $warranty) use ($docB): bool {
            return $warranty->document_id === $docB->getKey();
        });

        $this->assertNotNull($b);
        $this->assertEquals($docB->start, $b->start);
        $this->assertEquals($docB->end, $b->end);
        $this->assertEquals($docB->customer_id, $b->customer_id);
        $this->assertEquals($docB->getKey(), $b->document_id);
        $this->assertEquals($asset->getKey(), $b->asset_id);
        $this->assertEquals(1, $b->services->count());
        $this->assertEquals($entryB->service_id, $b->services()->first()->getKey());
        $this->assertEquals($entryB->service_id, $b->services->first()->getKey());

        // If existing warranty related to another customer it should be updated
        $c = $warranties->first(static function (AssetWarranty $warranty) use ($docC): bool {
            return $warranty->document_id === $docC->getKey();
        });

        $this->assertNotNull($b);
        $this->assertEquals($docC->customer_id, $c->customer_id);
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
            ->with($asset->vendor, $asset->vendor)
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
     * @covers ::assetProduct
     */
    public function testAssetProduct(): void {
        $oem   = Oem::factory()->make();
        $type  = ProductType::asset();
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
            ->with($oem, $type, $asset->sku, $asset->productDescription, $asset->eolDate, $asset->eosDate)
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
        $location  = Location::factory()->create([
            'object_type' => $customer->getMorphClass(),
            'object_id'   => $customer->getKey(),
        ]);
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
                $this->locations = $locations;
            }

            public function assetLocation(ViewAsset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
                return parent::assetLocation($asset, $customer, $reseller);
            }
        };

        $this->assertEquals($location, $factory->assetLocation($asset, $customer, null));
    }

    /**
     * @covers ::assetLocation
     */
    public function testAssetLocationNoCustomer(): void {
        $reseller  = Reseller::factory()->make();
        $asset     = new ViewAsset([
            'id'         => $this->faker->uuid,
            'resellerId' => $reseller->getKey(),
        ]);
        $location  = Location::factory()->create([
            'object_type' => $reseller->getMorphClass(),
            'object_id'   => $reseller->getKey(),
        ]);
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
                $this->locations = $locations;
            }

            public function assetLocation(ViewAsset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
                return parent::assetLocation($asset, $customer, $reseller);
            }
        };

        $this->assertEquals($location, $factory->assetLocation($asset, null, $reseller));
    }

    /**
     * @covers ::assetLocation
     */
    public function testAssetLocationNoCustomerNoReseller(): void {
        $locations = $this->app->make(LocationFactory::class);
        $factory   = new class($locations) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(LocationFactory $factory) {
                $this->locations = $factory;
            }

            public function assetLocation(ViewAsset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
                return parent::assetLocation($asset, $customer, $reseller);
            }
        };

        $this->assertNull($factory->assetLocation(new ViewAsset(), null, null));
    }

    /**
     * @covers ::assetLocation
     */
    public function testAssetLocationNoLocation(): void {
        $customer  = Customer::factory()->make();
        $asset     = new ViewAsset([
            'id'         => $this->faker->uuid,
            'customerId' => $customer->getKey(),
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
                $this->locations = $locations;
            }

            public function assetLocation(ViewAsset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
                return parent::assetLocation($asset, $customer, $reseller);
            }
        };

        $this->assertNull($factory->assetLocation($asset, $customer, null));
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

        $factory = new class($normalizer, $resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(Normalizer $normalizer, AssetResolver $resolver) {
                $this->normalizer = $normalizer;
                $this->assets     = $resolver;
            }
        };

        $callback = Mockery::spy(function (EloquentCollection $collection): void {
            $this->assertCount(0, $collection);
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
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
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
        $asset = new ViewAsset(['assetTag' => null]);
        $factory->assetTags($asset);
        $this->assertEmpty($factory->assetTags($asset));

        // Normalized empty
        $asset = new ViewAsset(['assetTag' => ' ']);
        $factory->assetTags($asset);
        $this->assertEmpty($factory->assetTags($asset));
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

    public function assetType(ViewAsset $asset): TypeModel {
        return parent::assetType($asset);
    }

    public function assetProduct(ViewAsset $asset): Product {
        return parent::assetProduct($asset);
    }

    public function assetStatus(ViewAsset $asset): Status {
        return parent::assetStatus($asset);
    }
}
