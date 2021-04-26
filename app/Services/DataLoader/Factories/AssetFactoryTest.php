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
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\DocumentNotFoundException;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

use function is_null;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\AssetFactory
 */
class AssetFactoryTest extends TestCase {
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
        $asset   = Asset::create($json);

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
        $locations = $container->make(LocationFactory::class);
        $resellers = $container->make(ResellerFactory::class)
            ->setLocationFactory($locations);
        $customers = $container->make(CustomerFactory::class)
            ->setLocationFactory($locations);
        $documents = $container->make(DocumentFactory::class);

        /** @var \App\Services\DataLoader\Factories\AssetFactory $factory */
        $factory = $container->make(AssetFactory::class)
            ->setResellerFactory($resellers)
            ->setCustomersFactory($customers)
            ->setDocumentFactory($documents);

        // Test
        $json    = $this->getTestData()->json('~asset-full.json');
        $asset   = Asset::create($json);
        $created = $factory->create($asset);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($asset->id, $created->getKey());
        $this->assertEquals($asset->resellerId, $created->reseller_id);
        $this->assertEquals($asset->serialNumber, $created->serial_number);
        $this->assertEquals($asset->vendor, $created->oem->abbr);
        $this->assertEquals(ProductType::asset(), $created->product->type);
        $this->assertEquals($asset->productDescription, $created->product->name);
        $this->assertEquals($asset->sku, $created->product->sku);
        $this->assertNull($created->product->eos);
        $this->assertEquals($asset->eosDate, (string) $created->product->eos);
        $this->assertEquals($asset->eolDate, $this->getDatetime($created->product->eol));
        $this->assertEquals($asset->assetType, $created->type->key);
        $this->assertEquals($asset->customerId, $created->customer->getKey());
        $this->assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($created->location, false),
        );

        // Documents
        $this->assertEquals(1, Document::query()->count());

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
                        'sku'      => $warranty->package?->sku,
                        'package'  => $warranty->package?->name,
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
        $asset   = Asset::create($json);
        $updated = $factory->create($asset);

        $this->assertNotNull($updated);
        $this->assertSame($created, $updated);
        $this->assertEquals($asset->id, $updated->getKey());
        $this->assertNull($updated->ogranization_id);
        $this->assertEquals($asset->serialNumber, $updated->serial_number);
        $this->assertEquals($asset->vendor, $updated->oem->abbr);
        $this->assertEquals(ProductType::asset(), $updated->product->type);
        $this->assertEquals($created->product->name, $updated->product->name);
        $this->assertEquals($asset->sku, $updated->product->sku);
        $this->assertEquals($asset->eosDate, $this->getDatetime($updated->product->eos));
        $this->assertEquals($asset->eolDate, $this->getDatetime($updated->product->eol));
        $this->assertEquals($asset->assetType, $updated->type->key);
        $this->assertEquals($asset->customerId, $updated->customer->getKey());
        $this->assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($updated->location, false),
        );
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
        $asset   = Asset::create($json);
        $created = $factory->create($asset);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($asset->id, $created->getKey());
        $this->assertEquals($asset->serialNumber, $created->serial_number);
        $this->assertEquals($asset->vendor, $created->oem->abbr);
        $this->assertEquals(ProductType::asset(), $created->product->type);
        $this->assertEquals($asset->productDescription, $created->product->name);
        $this->assertEquals($asset->sku, $created->product->sku);
        $this->assertNull($created->product->eos);
        $this->assertEquals($asset->eosDate, (string) $created->product->eos);
        $this->assertEquals($asset->eolDate, (string) $created->product->eol);
        $this->assertEquals($asset->assetType, $created->type->key);
        $this->assertNull($created->customer_id);
        $this->assertNull($created->location_id);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAssetAssetNoCustomer(): void {
        // Prepare
        $container = $this->app->make(Container::class);
        $locations = $container->make(LocationFactory::class);
        $resellers = $container->make(ResellerFactory::class)
            ->setLocationFactory($locations);
        $factory   = $container->make(AssetFactory::class)
            ->setResellerFactory($resellers);

        // Test
        $json  = $this->getTestData()->json('~asset-full.json');
        $asset = Asset::create($json);

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
        $asset = Asset::create($json);

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
        $asset = Asset::create($json);

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
        $locations = $container->make(LocationFactory::class);
        $resellers = $container->make(ResellerFactory::class)
            ->setLocationFactory($locations);
        $customers = $container->make(CustomerFactory::class)
            ->setLocationFactory($locations);
        $factory   = $container->make(AssetFactory::class)
            ->setResellerFactory($resellers)
            ->setCustomersFactory($customers);

        // Test
        $json    = $this->getTestData()->json('~asset-reseller-location.json');
        $asset   = Asset::create($json);
        $created = $factory->create($asset);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($asset->id, $created->getKey());
        $this->assertEquals($asset->resellerId, $created->reseller_id);
        $this->assertEquals($asset->serialNumber, $created->serial_number);
        $this->assertEquals($asset->vendor, $created->oem->abbr);
        $this->assertEquals(ProductType::asset(), $created->product->type);
        $this->assertEquals($asset->productDescription, $created->product->name);
        $this->assertEquals($asset->sku, $created->product->sku);
        $this->assertNull($created->product->eos);
        $this->assertEquals($asset->eosDate, (string) $created->product->eos);
        $this->assertEquals($asset->eolDate, $this->getDatetime($created->product->eol));
        $this->assertEquals($asset->assetType, $created->type->key);
        $this->assertEquals($asset->customerId, $created->customer->getKey());
        $this->assertEquals(
            $this->getAssetLocation($asset),
            $this->getLocation($created->location, false),
        );
    }

    /**
     * @covers ::assetDocuments
     */
    public function testAssetDocuments(): void {
        // Factory
        $container = $this->app->make(Container::class);
        $factory   = new class(
            $container->make(Normalizer::class),
            $container->make(AssetResolver::class),
            $container->make(ProductResolver::class),
            $container->make(OemResolver::class),
            $container->make(DocumentFactory::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected AssetResolver $assets,
                protected ProductResolver $products,
                protected OemResolver $oems,
                DocumentFactory $documentFactory,
            ) {
                $this->setDocumentFactory($documentFactory);
            }

            public function assetDocuments(AssetModel $model, Asset $asset): Collection {
                return parent::assetDocuments($model, $asset);
            }
        };

        // Prepare
        $json     = $this->getTestData()->json('~asset-documents-full.json');
        $asset    = Asset::create($json);
        $reseller = Reseller::factory()->create([
            'id' => $asset->resellerId,
        ]);
        $customer = Customer::factory()->create([
            'id' => $asset->customerId,
        ]);
        $model    = AssetModel::factory()->create([
            'id'          => $asset->id,
            'reseller_id' => $reseller,
            'customer_id' => $customer,
        ]);

        $documents  = $factory->assetDocuments($model, $asset);
        $collection = (new Collection($documents))->keyBy(static function (Document $document): string {
            return $document->getKey();
        });

        $this->assertCount(3, $documents);
        $this->assertCount(3, $collection);

        // Test
        // ---------------------------------------------------------------------
        /** @var \App\Models\Document $a */
        $a = $collection->get('688b9621-3244-464b-9468-3cd74f5eaacf');

        $this->assertNotNull($a);
        $this->assertEquals($customer, $a->customer);
        $this->assertEquals($reseller, $a->reseller);
        $this->assertEquals('0056523287', $a->number);
        $this->assertEquals('1292.16', $a->price);
        $this->assertEquals('1583020800000', $this->getDatetime($a->start));
        $this->assertEquals('1614470400000', $this->getDatetime($a->end));
        $this->assertEquals('HPE', $a->oem->abbr);
        $this->assertEquals('MultiNational Quote', $a->type->key);
        $this->assertEquals('EUR', $a->currency->code);
        $this->assertEquals('H7J34AC', $a->product->sku);
        $this->assertEquals('HPE Foundation Care 24x7 SVC', $a->product->name);
        $this->assertEquals(ProductType::support(), $a->product->type);
        $this->assertEquals('HPE', $a->product->oem->abbr);
        $this->assertEquals('HPE', $a->oem->abbr);

        $this->assertCount(1, $a->entries);

        /** @var \App\Models\DocumentEntry $e */
        $e = $a->entries->first();

        $this->assertEquals(2, $e->quantity);
        $this->assertEquals($a->getKey(), $e->document_id);
        $this->assertEquals($asset->id, $e->asset_id);
        $this->assertEquals('HA151AC', $e->product->sku);
        $this->assertEquals('HPE Hardware Maintenance Onsite Support', $e->product->name);
        $this->assertEquals(ProductType::service(), $e->product->type);
        $this->assertEquals('HPE', $e->product->oem->abbr);

        /** @var \App\Models\Document $b */
        $b = $collection->get('dbd3f08b-6bd1-4e28-8122-3004257879c0');

        $this->assertNotNull($b);
        $this->assertEquals($customer, $b->customer);
        $this->assertEquals($reseller, $b->reseller);
        $this->assertEquals('0056490551', $b->number);
        $this->assertEquals('6376.15', $b->price);

        $this->assertCount(2, $b->entries);

        /** @var \App\Models\DocumentEntry $f */
        $f = $b->entries->first();
        /** @var \App\Models\DocumentEntry $l */
        $l = $b->entries->last();

        $this->assertEquals(1, $f->quantity);
        $this->assertEquals(1, $l->quantity);
        $this->assertNotEquals($f->product_id, $l->product_id);

        /** @var \App\Models\Document $c */
        $c = $collection->get('7e6b6976-cb1b-4b2a-9cf1-de9e768e8802');

        $this->assertNotNull($c);
        $this->assertCount(2, $c->entries);

        // Changed
        // ---------------------------------------------------------------------
        $json       = $this->getTestData()->json('~asset-documents-changed.json');
        $asset      = Asset::create($json);
        $documents  = $factory->assetDocuments($model, $asset);
        $collection = (new Collection($documents))->keyBy(static function (Document $document): string {
            return $document->getKey();
        });

        $this->assertCount(2, $documents);
        $this->assertCount(2, $collection);

        /** @var \App\Models\Document $a */
        $a = $collection->get('688b9621-3244-464b-9468-3cd74f5eaacf');

        $this->assertNotNull($a);
        $this->assertEquals('3292.16', $a->price);

        $this->assertCount(1, $a->entries);
        $this->assertCount(1, $a->refresh()->entries);

        /** @var \App\Models\DocumentEntry $e */
        $e = $a->entries->first();

        $this->assertNotNull($e);
        $this->assertEquals(2, $e->quantity);
        $this->assertEquals(2, $e->refresh()->quantity);

        /** @var \App\Models\Document $b */
        $b = $collection->get('dbd3f08b-6bd1-4e28-8122-3004257879c0');

        $this->assertNotNull($b);
        $this->assertCount(1, $b->entries);
        $this->assertCount(1, $b->refresh()->entries);

        /** @var \App\Models\DocumentEntry $e */
        $e = $a->entries->first();

        $this->assertNotNull($e);
        $this->assertEquals(2, $e->quantity);
        $this->assertEquals(2, $e->refresh()->quantity);
    }

    /**
     * @covers ::assetDocuments
     *
     * @see https://thefas.atlassian.net/browse/EAP-60
     */
    public function testAssetDocumentsDocumentNull(): void {
        // Factory
        $container = $this->app->make(Container::class);
        $factory   = new class(
            $container->make(Normalizer::class),
            $container->make(AssetResolver::class),
            $container->make(ProductResolver::class),
            $container->make(OemResolver::class),
            $container->make(TypeResolver::class),
            $container->make(DocumentResolver::class),
            $container->make(CurrencyResolver::class),
            $container->make(DocumentFactory::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected AssetResolver $assets,
                protected ProductResolver $products,
                protected OemResolver $oems,
                protected TypeResolver $types,
                protected DocumentResolver $documentResolver,
                protected CurrencyResolver $currencies,
                DocumentFactory $documentFactory,
            ) {
                $this->setDocumentFactory($documentFactory);
            }

            public function assetDocuments(AssetModel $model, Asset $asset): Collection {
                return parent::assetDocuments($model, $asset);
            }
        };

        // Prepare
        $json     = $this->getTestData()->json('~asset-documents-no-document.json');
        $asset    = Asset::create($json);
        $reseller = Reseller::factory()->create([
            'id' => $asset->resellerId,
        ]);
        $customer = Customer::factory()->create([
            'id' => $asset->customerId,
        ]);
        $model    = AssetModel::factory()->create([
            'id'          => $asset->id,
            'reseller_id' => $reseller,
            'customer_id' => $customer,
        ]);

        $documents  = $factory->assetDocuments($model, $asset);
        $collection = (new Collection($documents))->keyBy(static function (Document $document): string {
            return $document->getKey();
        });

        $this->assertCount(3, $documents);
        $this->assertCount(3, $collection);

        // Test
        // ---------------------------------------------------------------------
        /** @var \App\Models\Document $a */
        $a = $collection->get('688b9621-3244-464b-9468-3cd74f5eaacf');

        $this->assertNotNull($a);
        $this->assertEquals($customer, $a->customer);
        $this->assertEquals($reseller, $a->reseller);
        $this->assertEquals('688b9621-3244-464b-9468-3cd74f5eaacf', $a->number);
        $this->assertEquals('0.00', $a->price);
        $this->assertEquals('1583020800000', $this->getDatetime($a->start));
        $this->assertEquals('1614470400000', $this->getDatetime($a->end));
        $this->assertEquals($model->oem->abbr, $a->oem->abbr);
        $this->assertEquals('??', $a->type->key);
        $this->assertEquals('EUR', $a->currency->code);
        $this->assertEquals('H7J34AC', $a->product->sku);
        $this->assertEquals('HPE Foundation Care 24x7 SVC', $a->product->name);
        $this->assertEquals(ProductType::support(), $a->product->type);
        $this->assertEquals($model->oem->abbr, $a->product->oem->abbr);

        $this->assertCount(1, $a->entries);

        /** @var \App\Models\DocumentEntry $e */
        $e = $a->entries->first();

        $this->assertEquals(2, $e->quantity);
        $this->assertEquals($a->getKey(), $e->document_id);
        $this->assertEquals($asset->id, $e->asset_id);
        $this->assertEquals('HA151AC', $e->product->sku);
        $this->assertEquals('HPE Hardware Maintenance Onsite Support', $e->product->name);
        $this->assertEquals(ProductType::service(), $e->product->type);
        $this->assertEquals($model->oem->abbr, $e->product->oem->abbr);

        /** @var \App\Models\Document $b */
        $b = $collection->get('dbd3f08b-6bd1-4e28-8122-3004257879c0');

        $this->assertNotNull($b);
        $this->assertEquals($customer, $b->customer);
        $this->assertEquals($reseller, $b->reseller);
        $this->assertEquals('dbd3f08b-6bd1-4e28-8122-3004257879c0', $b->number);
        $this->assertEquals('0.00', $b->price);

        $this->assertCount(2, $b->entries);

        /** @var \App\Models\DocumentEntry $f */
        $f = $b->entries->first();
        /** @var \App\Models\DocumentEntry $l */
        $l = $b->entries->last();

        $this->assertEquals(1, $f->quantity);
        $this->assertEquals(1, $l->quantity);
        $this->assertNotEquals($f->product_id, $l->product_id);

        /** @var \App\Models\Document $c */
        $c = $collection->get('7e6b6976-cb1b-4b2a-9cf1-de9e768e8802');

        $this->assertNotNull($c);
        $this->assertCount(2, $c->entries);
    }

    /**
     * @covers ::assetDocument
     */
    public function testAssetDocument(): void {
        $asset    = new AssetModel();
        $document = AssetDocument::create(['document' => ['id' => $this->faker->uuid]]);
        $resolved = new Document();
        $factory  = Mockery::mock(DocumentFactory::class);

        $factory
            ->shouldReceive('create')
            ->with($document)
            ->once()
            ->andReturn($resolved);

        $factory = new class($factory) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                DocumentFactory $documentFactory,
            ) {
                $this->setDocumentFactory($documentFactory);
            }

            public function assetDocument(AssetModel $asset, AssetDocument $assetDocument): Document {
                return parent::assetDocument($asset, $assetDocument);
            }
        };

        $this->assertSame($resolved, $factory->assetDocument($asset, $document));
    }

    /**
     * @covers ::assetDocument
     */
    public function testAssetDocumentDocumentNotFound(): void {
        $asset    = new AssetModel();
        $document = AssetDocument::create([
            'documentNumber' => '2182cd66-321f-47ac-8992-e295c018b8a4',
        ]);
        $factory  = new class() extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function assetDocument(AssetModel $asset, AssetDocument $assetDocument): Document {
                return parent::assetDocument($asset, $assetDocument);
            }
        };

        $this->expectException(DocumentNotFoundException::class);

        $factory->assetDocument($asset, $document);
    }

    /**
     * @covers ::assetDocumentEntry
     */
    public function testAssetDocumentEntry(): void {
        $assetDocument = AssetDocument::create([
            'skuNumber'      => $this->faker->word,
            'skuDescription' => $this->faker->sentence,
        ]);
        $document      = Document::factory()->make();
        $factory       = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductResolver $products,
                protected OemResolver $oems,
            ) {
                // empty
            }

            public function assetDocumentEntry(Document $document, AssetDocument $assetDocument): DocumentEntry {
                return parent::assetDocumentEntry($document, $assetDocument);
            }
        };

        $entry = $factory->assetDocumentEntry($document, $assetDocument);

        $this->assertInstanceOf(DocumentEntry::class, $entry);
        $this->assertNull($entry->document_id);
        $this->assertNull($entry->asset_id);
        $this->assertNotNull($entry->product_id);
        $this->assertSame($document->oem, $entry->product->oem);
        $this->assertEquals(ProductType::service(), $entry->product->type);
        $this->assertEquals($assetDocument->skuNumber, $entry->product->sku);
        $this->assertEquals($assetDocument->skuDescription, $entry->product->name);
        $this->assertNull($entry->product->eos);
        $this->assertNull($entry->product->eol);
    }

    /**
     * @covers ::assetInitialWarranties
     */
    public function testAssetInitialWarranties(): void {
        $factory = new class(
            $this->app->make(Normalizer::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
            ) {
                // empty
            }

            public function assetInitialWarranties(AssetModel $model, Asset $asset, Collection $documents): Collection {
                return parent::assetInitialWarranties($model, $asset, $documents);
            }
        };

        $date     = Date::now();
        $customer = Customer::factory()->create();
        $model    = AssetModel::factory()->create([
            'customer_id' => $customer,
        ]);
        $docA     = Document::factory()->create();
        $docB     = Document::factory()->create();
        $docC     = Document::factory()->create();
        $warranty = AssetWarranty::factory()->create([
            'end'         => $date->subYear(),
            'asset_id'    => $model,
            'document_id' => $docB,
        ]);
        $existing = AssetWarranty::factory()->create([
            'end'         => $date->subYear(),
            'asset_id'    => $model,
            'customer_id' => $docC->customer_id,
            'document_id' => null,
        ]);
        $asset    = Asset::create([
            'id'            => $model->getKey(),
            'assetDocument' => [
                [
                    'documentNumber'  => $docA->getKey(),
                    'warrantyEndDate' => null,
                ],
                [
                    'documentNumber'  => $docB->getKey(),
                    'warrantyEndDate' => $this->getDatetime($date),
                ],
                [
                    'documentNumber'  => $docC->getKey(),
                    'warrantyEndDate' => $this->getDatetime($date),
                ],
                [
                    'documentNumber'  => '9e602148-7767-448e-b593-ba6bcff00cac',
                    'warrantyEndDate' => $this->getDatetime($date),
                ],
            ],
        ]);

        // Test
        $warranties = $factory->assetInitialWarranties($model, $asset, new Collection([$docA, $docB, $docC]));

        $this->assertCount(2, $warranties);

        // Should not be updated (because document is defined)
        $this->assertEquals($date->subYear()->startOfDay(), $warranty->refresh()->end);

        // Should be created for DocB Customer
        /** @var \App\Models\AssetWarranty $b */
        $b = $warranties->first(static function (AssetWarranty $warranty) use ($docB): bool {
            return $warranty->customer_id === $docB->customer_id;
        });

        $this->assertNotNull($b);
        $this->assertTrue($b->wasRecentlyCreated);
        $this->assertNull($b->document_id);
        $this->assertNull($b->start);
        $this->assertEquals($date->startOfDay(), $b->end);
        $this->assertEquals($model->getKey(), $b->asset_id);

        // Should be updated for DocC Customer
        /** @var \App\Models\AssetWarranty $c */
        $c = $warranties->first(static function (AssetWarranty $warranty) use ($docC): bool {
            return $warranty->customer_id === $docC->customer_id;
        });

        $this->assertNotNull($c);
        $this->assertEquals($existing->getKey(), $c->getKey());
        $this->assertFalse($c->wasRecentlyCreated);
        $this->assertNull($c->document_id);
        $this->assertNull($c->start);
        $this->assertEquals($date->startOfDay(), $c->end);
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

            public function assetExtendedWarranties(AssetModel $asset, Collection $documents): Collection {
                return parent::assetExtendedWarranties($asset, $documents);
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
        $warranty = AssetWarranty::factory()->create([
            'start'       => $date->subYear(),
            'end'         => $date,
            'asset_id'    => $asset,
            'document_id' => $docA,
        ]);
        $entryA   = DocumentEntry::factory()->create([
            'asset_id'    => $asset,
            'document_id' => $docA,
        ]);
        $entryB   = DocumentEntry::factory()->create([
            'asset_id'    => $asset,
            'document_id' => $docB,
        ]);

        $warranty->services()->sync($service->getKey());

        Document::factory()->create();

        // Pre-test
        $this->assertEquals(1, $asset->warranties()->count());
        $this->assertNotEquals($docA->customer_id, $warranty->customer_id);

        // Test
        $warranties = $factory->assetExtendedWarranties($asset, new Collection([$docA, $docB, $docC]));

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
        $this->assertEquals($entryA->product_id, $a->services()->first()->getKey());
        $this->assertEquals($entryA->product_id, $a->services->first()->getKey());

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
        $this->assertEquals($entryB->product_id, $b->services()->first()->getKey());
        $this->assertEquals($entryB->product_id, $b->services->first()->getKey());

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
        $asset   = Asset::create(['vendor' => $this->faker->word]);
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
        $asset   = Asset::create(['assetType' => $this->faker->word]);
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
        $asset = Asset::create([
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
     * @covers ::assetReseller
     */
    public function testAssetResellerExistsThroughProvider(): void {
        $reseller = Reseller::factory()->make();
        $resolver = Mockery::mock(ResellerResolver::class);

        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->twice()
            ->andReturn($reseller);

        $factory = new class($resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(ResellerResolver $resolver) {
                $this->resellerResolver = $resolver;
            }

            public function assetReseller(Asset $asset): ?Reseller {
                return parent::assetReseller($asset);
            }
        };

        $this->assertEquals($reseller, $factory->assetReseller(Asset::create([
            'id'         => $this->faker->uuid,
            'resellerId' => $reseller->getKey(),
        ])));

        $this->assertEquals($reseller, $factory->assetReseller(Asset::create([
            'id'       => $this->faker->uuid,
            'reseller' => [
                'id' => $reseller->getKey(),
            ],
        ])));
    }

    /**
     * @covers ::assetReseller
     */
    public function testAssetResellerAssetWithoutReseller(): void {
        $reseller = Mockery::mock(ResellerResolver::class);

        $reseller
            ->shouldReceive('get')
            ->never();

        $factory = new class($reseller) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(ResellerResolver $resolver) {
                $this->resellerResolver = $resolver;
            }

            public function assetReseller(Asset $asset): ?Reseller {
                return parent::assetReseller($asset);
            }
        };

        $this->assertNull($factory->assetReseller(Asset::create([
            'id' => $this->faker->uuid,
        ])));
    }

    /**
     * @covers ::assetReseller
     */
    public function testAssetResellerResellerNotFound(): void {
        $reseller = Reseller::factory()->make();
        $asset    = Asset::create([
            'id'       => $this->faker->uuid,
            'reseller' => [
                'id' => $reseller->getKey(),
            ],
        ]);
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->once()
            ->andReturn(null);

        $factory = new class($resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(ResellerResolver $resolver) {
                $this->resellerResolver = $resolver;
            }

            public function assetReseller(Asset $asset): ?Reseller {
                return parent::assetReseller($asset);
            }
        };

        $this->expectException(ResellerNotFoundException::class);

        $this->assertEquals($reseller, $factory->assetReseller($asset));
    }

    /**
     * @covers ::assetReseller
     */
    public function testAssetResellerExistsThroughFactory(): void {
        $reseller = Reseller::factory()->make();
        $asset    = Asset::create([
            'id'       => $this->faker->uuid,
            'reseller' => [
                'id' => $reseller->getKey(),
            ],
        ]);
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->once()
            ->andReturn(null);

        $resellers = Mockery::mock(ResellerFactory::class);
        $resellers
            ->shouldReceive('create')
            ->with($asset->reseller)
            ->once()
            ->andReturn($reseller);

        $factory = new class($resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(ResellerResolver $resolver) {
                $this->resellerResolver = $resolver;
            }

            public function assetReseller(Asset $asset): ?Reseller {
                return parent::assetReseller($asset);
            }
        };

        $factory->setResellerFactory($resellers);

        $this->assertEquals($reseller, $factory->assetReseller($asset));
    }

    /**
     * @covers ::assetReseller
     */
    public function testAssetResellerNotFoundThroughFactory(): void {
        $reseller = Reseller::factory()->make();
        $asset    = Asset::create([
            'id'       => $this->faker->uuid,
            'reseller' => [
                'id' => $reseller->getKey(),
            ],
        ]);
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->once()
            ->andReturn(null);

        $resellers = Mockery::mock(ResellerFactory::class);
        $resellers
            ->shouldReceive('create')
            ->with($asset->reseller)
            ->once()
            ->andReturn(null);

        $factory = new class($resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(ResellerResolver $resolver) {
                $this->resellerResolver = $resolver;
            }

            public function assetReseller(Asset $asset): ?Reseller {
                return parent::assetReseller($asset);
            }
        };

        $factory->setResellerFactory($resellers);

        $this->expectException(ResellerNotFoundException::class);

        $this->assertEquals($reseller, $factory->assetReseller($asset));
    }

    /**
     * @covers ::assetCustomer
     */
    public function testAssetCustomerExistsThroughProvider(): void {
        $customer = Customer::factory()->make();
        $resolver = Mockery::mock(CustomerResolver::class);

        $resolver
            ->shouldReceive('get')
            ->with($customer->getKey())
            ->twice()
            ->andReturn($customer);

        $factory = new class($resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerResolver $resolver) {
                $this->customerResolver = $resolver;
            }

            public function assetCustomer(Asset $asset): ?Customer {
                return parent::assetCustomer($asset);
            }
        };

        $this->assertEquals($customer, $factory->assetCustomer(Asset::create([
            'id'         => $this->faker->uuid,
            'customerId' => $customer->getKey(),
        ])));

        $this->assertEquals($customer, $factory->assetCustomer(Asset::create([
            'id'       => $this->faker->uuid,
            'customer' => [
                'id' => $customer->getKey(),
            ],
        ])));
    }

    /**
     * @covers ::assetCustomer
     */
    public function testAssetCustomerAssetWithoutCustomer(): void {
        $resolver = Mockery::mock(CustomerResolver::class);

        $resolver
            ->shouldReceive('get')
            ->never();

        $factory = new class($resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerResolver $resolver) {
                $this->customerResolver = $resolver;
            }

            public function assetCustomer(Asset $asset): ?Customer {
                return parent::assetCustomer($asset);
            }
        };

        $this->assertNull($factory->assetCustomer(Asset::create([
            'id' => $this->faker->uuid,
        ])));
    }

    /**
     * @covers ::assetCustomer
     */
    public function testAssetCustomerCustomerNotFound(): void {
        $customer = Customer::factory()->make();
        $asset    = Asset::create([
            'id'       => $this->faker->uuid,
            'customer' => [
                'id' => $customer->getKey(),
            ],
        ]);
        $resolver = Mockery::mock(CustomerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($customer->getKey())
            ->once()
            ->andReturn(null);

        $factory = new class($resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerResolver $resolver) {
                $this->customerResolver = $resolver;
            }

            public function assetCustomer(Asset $asset): ?Customer {
                return parent::assetCustomer($asset);
            }
        };

        $this->expectException(CustomerNotFoundException::class);

        $this->assertEquals($customer, $factory->assetCustomer($asset));
    }

    /**
     * @covers ::assetCustomer
     */
    public function testAssetCustomerExistsThroughFactory(): void {
        $customer = Customer::factory()->make();
        $asset    = Asset::create([
            'id'       => $this->faker->uuid,
            'customer' => [
                'id' => $customer->getKey(),
            ],
        ]);
        $resolver = Mockery::mock(CustomerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($customer->getKey())
            ->once()
            ->andReturn(null);

        $customers = Mockery::mock(CustomerFactory::class);
        $customers
            ->shouldReceive('create')
            ->with($asset->customer)
            ->once()
            ->andReturn($customer);

        $factory = new class($resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerResolver $resolver) {
                $this->customerResolver = $resolver;
            }

            public function assetCustomer(Asset $asset): ?Customer {
                return parent::assetCustomer($asset);
            }
        };

        $factory->setCustomersFactory($customers);

        $this->assertEquals($customer, $factory->assetCustomer($asset));
    }

    /**
     * @covers ::assetCustomer
     */
    public function testAssetCustomerNotFoundThroughFactory(): void {
        $customer = Customer::factory()->make();
        $asset    = Asset::create([
            'id'       => $this->faker->uuid,
            'customer' => [
                'id' => $customer->getKey(),
            ],
        ]);
        $resolver = Mockery::mock(CustomerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($customer->getKey())
            ->once()
            ->andReturn(null);

        $customers = Mockery::mock(CustomerFactory::class);
        $customers
            ->shouldReceive('create')
            ->with($asset->customer)
            ->once()
            ->andReturn(null);

        $factory = new class($resolver) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerResolver $resolver) {
                $this->customerResolver = $resolver;
            }

            public function assetCustomer(Asset $asset): ?Customer {
                return parent::assetCustomer($asset);
            }
        };

        $factory->setCustomersFactory($customers);

        $this->expectException(CustomerNotFoundException::class);

        $this->assertEquals($customer, $factory->assetCustomer($asset));
    }

    /**
     * @covers ::assetLocation
     */
    public function testAssetLocation(): void {
        $customer  = Customer::factory()->make();
        $asset     = Asset::create([
            'id'          => $this->faker->uuid,
            'customer_id' => $customer->getKey(),
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

            public function assetLocation(Asset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
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
        $asset     = Asset::create([
            'id'          => $this->faker->uuid,
            'reseller_id' => $reseller->getKey(),
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

            public function assetLocation(Asset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
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

            public function assetLocation(Asset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
                return parent::assetLocation($asset, $customer, $reseller);
            }
        };

        $this->assertNull($factory->assetLocation(new Asset(), null, null));
    }

    /**
     * @covers ::assetLocation
     */
    public function testAssetLocationNoLocation(): void {
        $customer  = Customer::factory()->make();
        $asset     = Asset::create([
            'id'          => $this->faker->uuid,
            'customer_id' => $customer->getKey(),
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

            public function assetLocation(Asset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
                return parent::assetLocation($asset, $customer, $reseller);
            }
        };

        $this->assertNull($factory->assetLocation($asset, $customer, null));
    }

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $a          = Asset::create([
            'id'           => $this->faker->uuid,
            'serialNumber' => $this->faker->uuid,
        ]);
        $b          = Asset::create([
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
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            Asset::class => ['createFromAsset', new Asset()],
            'Unknown'    => [
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

    public function assetOem(Asset $asset): Oem {
        return parent::assetOem($asset);
    }

    public function assetType(Asset $asset): TypeModel {
        return parent::assetType($asset);
    }

    public function assetProduct(Asset $asset): Product {
        return parent::assetProduct($asset);
    }
}
