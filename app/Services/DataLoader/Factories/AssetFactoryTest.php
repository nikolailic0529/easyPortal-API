<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
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
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

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
        $factory   = $container->make(AssetFactory::class)
            ->setResellerFactory($resellers)
            ->setCustomersFactory($customers);

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
        $this->assertEquals($asset->productDescription, $updated->product->name);
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
            $container->make(AssetDocumentFactory::class),
            $container->make(AssetResolver::class),
            $container->make(ProductResolver::class),
            $container->make(OemResolver::class),
        ) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected AssetDocumentFactory $documents,
                protected AssetResolver $assets,
                protected ProductResolver $products,
                protected OemResolver $oems,
            ) {
                // empty
            }

            /**
             * @inheritdoc
             */
            public function assetDocuments(Asset $asset): array {
                return parent::assetDocuments($asset); // TODO: Change the autogenerated stub
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

        AssetModel::factory()->create([
            'id'          => $asset->id,
            'reseller_id' => $reseller,
            'customer_id' => $customer,
        ]);

        $documents  = $factory->assetDocuments($asset);
        $collection = (new Collection($documents))->keyBy(static function (Document $document): string {
            return $document->getKey();
        });

        $this->assertCount(3, $documents);
        $this->assertCount(3, $collection);

        // Test
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

        $this->assertEquals('HPE', $e->oem->abbr);
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
        $json       = $this->getTestData()->json('~asset-documents-changed.json');
        $asset      = Asset::create($json);
        $documents  = $factory->assetDocuments($asset);
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
     * @covers ::assetDocument
     */
    public function testAssetDocument(): void {
        $document = new AssetDocument();
        $resolved = new Document();
        $factory  = Mockery::mock(AssetDocumentFactory::class);

        $factory
            ->shouldReceive('create')
            ->with($document)
            ->once()
            ->andReturn($resolved);

        $factory = new class($factory) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected AssetDocumentFactory $documents,
            ) {
                // empty
            }

            public function assetDocument(AssetDocument $document): Document {
                return parent::assetDocument($document);
            }
        };

        $this->assertSame($resolved, $factory->assetDocument($document));
    }

    /**
     * @covers ::assetDocumentEntry
     */
    public function testAssetDocumentEntry(): void {
        $document = AssetDocument::create([
            'skuNumber'      => $this->faker->word,
            'skuDescription' => $this->faker->sentence,
            'document'       => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word,
                ],
            ],
        ]);
        $factory  = new class(
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

            public function assetDocumentEntry(AssetDocument $document): DocumentEntry {
                return parent::assetDocumentEntry($document);
            }
        };

        $entry = $factory->assetDocumentEntry($document);

        $this->assertInstanceOf(DocumentEntry::class, $entry);
        $this->assertNull($entry->document_id);
        $this->assertNull($entry->asset_id);
        $this->assertNotNull($entry->oem_id);
        $this->assertEquals(
            $document->document->vendorSpecificFields->vendor,
            $entry->oem->abbr,
        );
        $this->assertNotNull($entry->product_id);
        $this->assertSame($entry->oem, $entry->product->oem);
        $this->assertEquals(ProductType::service(), $entry->product->type);
        $this->assertEquals($document->skuNumber, $entry->product->sku);
        $this->assertEquals($document->skuDescription, $entry->product->name);
        $this->assertNull($entry->product->eos);
        $this->assertNull($entry->product->eol);
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

        $factory->prefetch([$a, $b]);

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
