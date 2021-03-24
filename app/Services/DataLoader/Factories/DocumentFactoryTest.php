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
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\DocumentFactory
 */
class DocumentFactoryTest extends TestCase {
    use WithQueryLog;
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory  = $this->app->make(DocumentFactory::class);
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
        $factory = Mockery::mock(DocumentFactory::class);
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
        $factory    = $this->app->make(DocumentFactory::class);
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
        $factory  = Mockery::mock(DocumentFactoryTest_Factory::class);
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
        $factory  = Mockery::mock(DocumentFactoryTest_Factory::class);
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

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
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

        $factory = new class($resolver) extends DocumentFactory {
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

        $factory = new class($resolver) extends DocumentFactory {
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

        $factory = new class($resolver) extends DocumentFactory {
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

        $factory = new class($resolver) extends DocumentFactory {
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

        $factory = new class($resolver) extends DocumentFactory {
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

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $a       = [
            'document' => [
                'id' => 'c11353d9-0560-41b7-92b1-e151d195e867',
            ],
        ];
        $b       = [
            'document' => [
                'id' => '9b8dccbc-a72c-401b-bdc3-79b0ae6a2d67',
            ],
        ];
        $c       = [
            'document' => [
                'id' => '2baf7184-af04-40f8-a652-f38b3fb56770',
            ],
        ];
        $factory = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(DocumentResolver::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected DocumentResolver $documents,
            ) {
                // empty
            }
        };

        $factory->prefetch([
            Asset::create([
                'assetDocument' => [$a, $b],
            ]),
            Asset::create([
                'assetDocument' => [$c],
            ]),
        ]);

        $this->flushQueryLog();

        $factory->find(AssetDocument::create($a));
        $factory->find(AssetDocument::create($b));
        $factory->find(AssetDocument::create($c));

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
class DocumentFactoryTest_Factory extends DocumentFactory {
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
