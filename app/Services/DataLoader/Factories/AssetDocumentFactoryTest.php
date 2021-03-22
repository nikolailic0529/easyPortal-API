<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Enums\ProductType;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\AssetDocumentFactory
 */
class AssetDocumentFactoryTest extends TestCase {
    use WithQueryLog;
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory  = $this->app->make(AssetDocumentFactory::class);
        $json     = $this->getTestData()->json('~document-full.json');
        $document = AssetDocument::create($json);

        $this->flushQueryLog();

        $factory->find($document);

        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(AssetDocumentFactory::class);
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
     * @covers ::createFromAssetDocument
     */
    public function testCreateFromAssetDocument(): void {
        // Prepare
        $factory    = $this->app->make(AssetDocumentFactory::class);
        $normalizer = $this->app->make(Normalizer::class);

        // Test
        $json     = $this->getTestData()->json('~document-full.json');
        $document = AssetDocument::create($json);

        Reseller::factory()->create([
            'id' => $document->document->resellerId,
        ]);
        Customer::factory()->create([
            'id' => $document->document->customerId,
        ]);

        $created = $factory->create($document);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($document->document->id, $created->getKey());
        $this->assertEquals($document->document->resellerId, $created->reseller_id);
        $this->assertEquals($document->document->customerId, $created->customer_id);
        $this->assertEquals($document->document->vendorSpecificFields->vendor, $created->oem->abbr);
        $this->assertNotNull($created->price);
        $this->assertEquals(
            $normalizer->price($document->document->vendorSpecificFields->totalNetPrice),
            $created->price,
        );
        $this->assertEquals($document->document->documentId, $created->number);
        $this->assertEquals($document->document->startDate, $this->getDatetime($created->start));
        $this->assertEquals($document->document->endDate, $this->getDatetime($created->end));
        $this->assertEquals($document->document->type, $created->type->key);
        $this->assertEquals('EUR', $created->currency->code);
        $this->assertEquals(ProductType::support(), $created->product->type);
        $this->assertEquals($document->supportPackage, $created->product->sku);
        $this->assertEquals($document->supportPackageDescription, $created->product->name);
        $this->assertNull($created->product->eos);
        $this->assertNull($created->product->eol);

        // Customer should be updated
        $json     = $this->getTestData()->json('~document-changed.json');
        $document = AssetDocument::create($json);
        $updated  = $factory->create($document);

        $this->assertNotNull($updated);
        $this->assertSame($created, $updated);
        $this->assertEquals($document->document->id, $updated->getKey());
        $this->assertNotNull($created->price);
        $this->assertEquals(
            $normalizer->price($document->document->vendorSpecificFields->totalNetPrice),
            $created->price,
        );
        $this->assertEquals($document->document->documentId, $updated->number);
    }

    /**
     * @covers ::documentOem
     */
    public function testDocumentOem(): void {
        $document = AssetDocument::create([
            'document' => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word,
                ],
            ],
        ]);
        $factory  = Mockery::mock(AssetDocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('oem')
            ->with(
                $document->document->vendorSpecificFields->vendor,
                $document->document->vendorSpecificFields->vendor,
            )
            ->once()
            ->andReturns();

        $factory->documentOem($document);
    }

    /**
     * @covers ::documentType
     */
    public function testDocumentType(): void {
        $document = AssetDocument::create([
            'document' => [
                'type' => $this->faker->word,
            ],
        ]);
        $factory  = Mockery::mock(AssetDocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('type')
            ->with(Mockery::any(), $document->document->type)
            ->once()
            ->andReturns();

        $factory->documentType($document);
    }

    /**
     * @covers ::documentProduct
     */
    public function testDocumentProduct(): void {
        $oem      = Oem::factory()->make();
        $type     = ProductType::support();
        $document = AssetDocument::create([
            'document'                  => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word,
                ],
            ],
            'supportPackage'            => $this->faker->word,
            'supportPackageDescription' => $this->faker->word,
        ]);

        $factory = Mockery::mock(AssetDocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('documentOem')
            ->with($document)
            ->once()
            ->andReturn($oem);
        $factory
            ->shouldReceive('product')
            ->with($oem, $type, $document->supportPackage, $document->supportPackageDescription, null, null)
            ->once()
            ->andReturns();

        $factory->documentProduct($document);
    }

    /**
     * @covers ::documentReseller
     */
    public function testDocumentReseller(): void {
        $reseller = Reseller::factory()->make();
        $resolver = Mockery::mock(ResellerResolver::class);

        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->once()
            ->andReturn($reseller);

        $factory = new class($resolver) extends AssetDocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(ResellerResolver $resolver) {
                $this->resellers = $resolver;
            }

            public function documentReseller(AssetDocument $document): ?Reseller {
                return parent::documentReseller($document);
            }
        };

        $this->assertEquals($reseller, $factory->documentReseller(AssetDocument::create([
            'document' => [
                'id'         => $this->faker->uuid,
                'resellerId' => $reseller->getKey(),
            ],
        ])));
    }

    /**
     * @covers ::documentReseller
     */
    public function testDocumentResellerResellerNotFound(): void {
        $reseller = Reseller::factory()->make();
        $document = AssetDocument::create([
            'document' => [
                'id'         => $this->faker->uuid,
                'resellerId' => $reseller->getKey(),
            ],
        ]);
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey())
            ->once()
            ->andReturn(null);

        $factory = new class($resolver) extends AssetDocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(ResellerResolver $resolver) {
                $this->resellers = $resolver;
            }

            public function documentReseller(AssetDocument $document): ?Reseller {
                return parent::documentReseller($document);
            }
        };

        $this->expectException(ResellerNotFoundException::class);

        $this->assertEquals($reseller, $factory->documentReseller($document));
    }

    /**
     * @covers ::documentCustomer
     */
    public function testDocumentCustomer(): void {
        $customer = Customer::factory()->make();
        $resolver = Mockery::mock(CustomerResolver::class);

        $resolver
            ->shouldReceive('get')
            ->with($customer->getKey())
            ->once()
            ->andReturn($customer);

        $factory = new class($resolver) extends AssetDocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerResolver $resolver) {
                $this->customers = $resolver;
            }

            public function documentCustomer(AssetDocument $document): ?Customer {
                return parent::documentCustomer($document);
            }
        };

        $this->assertEquals($customer, $factory->documentCustomer(AssetDocument::create([
            'document' => [
                'id'         => $this->faker->uuid,
                'customerId' => $customer->getKey(),
            ],
        ])));
    }

    /**
     * @covers ::documentCustomer
     */
    public function testDocumentCustomerCustomerNotFound(): void {
        $customer = Customer::factory()->make();
        $document = AssetDocument::create([
            'document' => [
                'id'         => $this->faker->uuid,
                'customerId' => $customer->getKey(),
            ],
        ]);
        $resolver = Mockery::mock(CustomerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($customer->getKey())
            ->once()
            ->andReturn(null);

        $factory = new class($resolver) extends AssetDocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CustomerResolver $resolver) {
                $this->customers = $resolver;
            }

            public function documentCustomer(AssetDocument $document): ?Customer {
                return parent::documentCustomer($document);
            }
        };

        $this->expectException(CustomerNotFoundException::class);

        $this->assertEquals($customer, $factory->documentCustomer($document));
    }

    /**
     * @covers ::documentCurrency
     */
    public function testDocumentCurrency(): void {
        $currency = Currency::factory()->make();
        $resolver = Mockery::mock(CurrencyResolver::class);

        $resolver
            ->shouldReceive('get')
            ->with('EUR', Mockery::any())
            ->once()
            ->andReturn($currency);

        $factory = new class($resolver) extends AssetDocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(CurrencyResolver $resolver) {
                $this->currencies = $resolver;
            }

            public function documentCurrency(AssetDocument $document): Currency {
                return parent::documentCurrency($document);
            }
        };

        $this->assertEquals($currency, $factory->documentCurrency(AssetDocument::create([])));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            AssetDocument::class => ['createFromAssetDocument', new AssetDocument()],
            'Unknown'            => [
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
class AssetDocumentFactoryTest_Factory extends AssetDocumentFactory {
    // TODO [tests] Remove after https://youtrack.jetbrains.com/issue/WI-25253

    public function documentOem(AssetDocument $document): Oem {
        return parent::documentOem($document);
    }

    public function documentType(AssetDocument $document): TypeModel {
        return parent::documentType($document);
    }

    public function documentProduct(AssetDocument $document): Product {
        return parent::documentProduct($document);
    }
}
