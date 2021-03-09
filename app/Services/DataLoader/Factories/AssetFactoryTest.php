<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Providers\AssetProvider;
use App\Services\DataLoader\Providers\CustomerProvider;
use App\Services\DataLoader\Providers\ProductProvider;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Illuminate\Support\Facades\Date;
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
        $customers = $container->make(CustomerFactory::class)
            ->setLocationFactory($locations);
        $factory   = $container->make(AssetFactory::class)
            ->setCustomersFactory($customers);

        // Test
        $json    = $this->getTestData()->json('~asset-full.json');
        $asset   = Asset::create($json);
        $created = $factory->create($asset);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($asset->id, $created->getKey());
        $this->assertEquals($asset->serialNumber, $created->serial_number);
        $this->assertEquals($asset->vendor, $created->oem->abbr);
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
        $this->assertTrue($updated->wasRecentlyCreated);
        $this->assertEquals($asset->id, $updated->getKey());
        $this->assertEquals($asset->serialNumber, $updated->serial_number);
        $this->assertEquals($asset->vendor, $updated->oem->abbr);
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
        $factory   = $container->make(AssetFactory::class);

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
            ->with($oem, $asset->sku, $asset->productDescription, $asset->eolDate, $asset->eosDate)
            ->once()
            ->andReturns();

        $factory->assetProduct($asset);
    }

    /**
     * @covers ::assetCustomer
     */
    public function testAssetCustomerExistsThroughProvider(): void {
        $customer = Customer::factory()->make();
        $provider = Mockery::mock(CustomerProvider::class);

        $provider
            ->shouldReceive('get')
            ->with($customer->getKey())
            ->twice()
            ->andReturn($customer);

        $factory = new class($provider) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerProvider $provider) {
                $this->customerProvider = $provider;
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
        $provider = Mockery::mock(CustomerProvider::class);

        $provider
            ->shouldReceive('get')
            ->never();

        $factory = new class($provider) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerProvider $provider) {
                $this->customerProvider = $provider;
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
        $provider = Mockery::mock(CustomerProvider::class);
        $provider
            ->shouldReceive('get')
            ->with($customer->getKey())
            ->once()
            ->andReturn(null);

        $factory = new class($provider) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerProvider $provider) {
                $this->customerProvider = $provider;
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
        $provider = Mockery::mock(CustomerProvider::class);
        $provider
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

        $factory = new class($provider) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerProvider $provider) {
                $this->customerProvider = $provider;
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
        $provider = Mockery::mock(CustomerProvider::class);
        $provider
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

        $factory = new class($provider) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerProvider $provider) {
                $this->customerProvider = $provider;
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
            ->shouldReceive('find')
            ->once()
            ->andReturn($location);

        $factory = new class($locations) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(LocationFactory $locations) {
                $this->locations = $locations;
            }

            public function assetLocation(Asset $asset, ?Customer $customer): ?Location {
                return parent::assetLocation($asset, $customer);
            }
        };

        $this->assertEquals($location, $factory->assetLocation($asset, $customer));
    }

    /**
     * @covers ::assetLocation
     */
    public function testAssetLocationNoCustomer(): void {
        $factory = new class() extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function assetLocation(Asset $asset, ?Customer $customer): ?Location {
                return parent::assetLocation($asset, $customer);
            }
        };

        $this->assertNull($factory->assetLocation(new Asset(), null));
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

        $locations
            ->shouldReceive('find')
            ->once()
            ->andReturnNull();

        $factory = new class($locations) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(LocationFactory $locations) {
                $this->locations = $locations;
            }

            public function assetLocation(Asset $asset, ?Customer $customer): ?Location {
                return parent::assetLocation($asset, $customer);
            }
        };

        $this->assertNull($factory->assetLocation($asset, $customer));
    }

    /**
     * @covers ::product
     */
    public function testProduct(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $provider   = $this->app->make(ProductProvider::class);
        $product    = Product::factory()->create();
        $oem        = $product->oem;

        $factory = new class($normalizer, $provider) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(Normalizer $normalizer, ProductProvider $provider) {
                $this->normalizer = $normalizer;
                $this->products   = $provider;
            }

            public function product(Oem $oem, string $sku, string $name, ?string $eol, ?string $eos): Product {
                return parent::product($oem, $sku, $name, $eol, $eos);
            }
        };

        $this->flushQueryLog();

        // If model exists and not changed - no action required
        $this->assertEquals(
            $product->withoutRelations(),
            $factory->product(
                $oem,
                $product->sku,
                $product->name,
                "{$product->eol->getTimestamp()}000",
                "{$product->eos->getTimestamp()}000",
            )->withoutRelations(),
        );
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If model exists and changed - it should be updated
        $newEos  = $this->faker->randomElement(['', null]);
        $newEol  = Date::now();
        $newName = $this->faker->sentence;
        $updated = $factory->product(
            $oem,
            $product->sku,
            $newName,
            "{$newEol->getTimestamp()}000",
            $newEos,
        );

        $this->assertEquals($newName, $updated->name);
        $this->assertEquals($newEol, $newEol);
        $this->assertNull($updated->eos);

        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $sku     = $this->faker->uuid;
        $name    = $this->faker->sentence;
        $created = $factory->product(
            $oem,
            $sku,
            $name,
            null,
            null,
        );

        $this->assertNotNull($created);
        $this->assertEquals($oem->getKey(), $created->oem_id);
        $this->assertEquals($sku, $created->sku);
        $this->assertEquals($name, $created->name);

        $this->flushQueryLog();
    }

    /**
     * @covers ::asset
     */
    public function testAsset(): void {
        // Prepare
        $normalizer   = $this->app->make(Normalizer::class);
        $provider     = $this->app->make(AssetProvider::class);
        $product      = Product::factory()->create();
        $customer     = Customer::factory()->create();
        $location     = Location::factory()
            ->create([
                'object_type' => $customer->getMorphClass(),
                'object_id'   => $customer->getKey(),
            ]);
        $serialNumber = $this->faker->uuid;
        $asset        = AssetModel::factory()->create([
            'oem_id'        => $product->oem,
            'product_id'    => $product,
            'customer_id'   => $customer,
            'location_id'   => $location,
            'serial_number' => $serialNumber,
        ]);
        $oem          = $product->oem;
        $type         = $asset->type;

        $factory = new class($normalizer, $provider) extends AssetFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(Normalizer $normalizer, AssetProvider $provider) {
                $this->normalizer = $normalizer;
                $this->assets     = $provider;
            }

            public function asset(
                string $id,
                Oem $oem,
                TypeModel $type,
                Product $product,
                ?Customer $customer,
                ?Location $location,
                string $serialNumber,
            ): AssetModel {
                return parent::asset($id, $oem, $type, $product, $customer, $location, $serialNumber);
            }
        };

        $this->flushQueryLog();

        // If model exists and not changed - no action required
        $this->assertEquals(
            $asset->withoutRelations(),
            $factory->asset(
                $asset->id,
                $oem,
                $type,
                $product,
                $customer,
                $location,
                $serialNumber,
            )->withoutRelations(),
        );
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If model exists and changed - it should be updated
        $newSerialNumber = $this->faker->uuid;
        $updated         = $factory->asset(
            $asset->id,
            $oem,
            $type,
            $product,
            $customer,
            $location,
            $newSerialNumber,
        );

        $this->assertEquals($newSerialNumber, $updated->serial_number);
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $createdId = $this->faker->uuid;
        $created   = $factory->asset(
            $createdId,
            $oem,
            $type,
            $product,
            $customer,
            $location,
            $serialNumber,
        );

        $this->assertNotNull($created);
        $this->assertEquals($createdId, $created->getKey());
        $this->assertEquals($oem->getKey(), $created->oem_id);
        $this->assertEquals($product->getKey(), $created->product_id);
        $this->assertEquals($type->getKey(), $created->type_id);
        $this->assertEquals($customer->getKey(), $created->customer_id);
        $this->assertEquals($location->getKey(), $created->location_id);
        $this->assertEquals($serialNumber, $created->serial_number);

        $this->flushQueryLog();
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
