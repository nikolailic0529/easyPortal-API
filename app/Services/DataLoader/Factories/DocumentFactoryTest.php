<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Enums\ProductType;
use App\Models\Language;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\DocumentFactory
 */
class DocumentFactoryTest extends TestCase {
    use WithoutOrganizationScope;
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
        $contacts   = $this->app->make(ContactFactory::class);
        $factory    = $this->app->make(DocumentFactory::class)->setContactsFactory($contacts);
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
            $normalizer->number($document->document->totalNetPrice),
            $created->price,
        );
        $this->assertEquals($document->document->documentNumber, $created->number);
        $this->assertEquals($document->document->startDate, $this->getDatetime($created->start));
        $this->assertEquals($document->document->endDate, $this->getDatetime($created->end));
        $this->assertEquals($document->document->type, $created->type->key);
        $this->assertEquals('CUR', $created->currency->code);
        $this->assertEquals('en', $created->language->code);
        $this->assertEquals(ProductType::support(), $created->product->type);
        $this->assertEquals($document->supportPackage, $created->product->sku);
        $this->assertEquals($document->supportPackageDescription, $created->product->name);
        $this->assertNull($created->product->eos);
        $this->assertNull($created->product->eol);
        $this->assertEquals(
            $this->getModelContacts($created),
            $this->getContacts($document->document),
        );

        // Customer should be updated
        $json     = $this->getTestData()->json('~document-changed.json');
        $document = AssetDocument::create($json);
        $updated  = $factory->create($document);

        $this->assertNotNull($updated);
        $this->assertSame($created, $updated);
        $this->assertEquals($document->document->id, $updated->getKey());
        $this->assertNotNull($updated->price);
        $this->assertEquals('EUR', $updated->currency->code);
        $this->assertNull($updated->language);
        $this->assertEquals(
            $normalizer->number($document->document->totalNetPrice),
            $updated->price,
        );
        $this->assertEquals($document->document->documentNumber, $updated->number);
        $this->assertNull($updated->product);
    }

    /**
     * @covers ::documentOem
     */
    public function testDocumentOem(): void {
        $document = Document::create([
            'vendorSpecificFields' => [
                'vendor' => $this->faker->word,
            ],
        ]);
        $factory  = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('oem')
            ->with(
                $document->vendorSpecificFields->vendor,
                $document->vendorSpecificFields->vendor,
            )
            ->once()
            ->andReturns();

        $factory->documentOem($document);
    }

    /**
     * @covers ::documentType
     */
    public function testDocumentType(): void {
        $document = Document::create([
            'type' => $this->faker->word,
        ]);
        $factory  = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('type')
            ->with(Mockery::any(), $document->type)
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
            ->with($document->document)
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

            public function documentReseller(Document $document): ?Reseller {
                return parent::documentReseller($document);
            }
        };

        $this->assertEquals($reseller, $factory->documentReseller(Document::create([
            'resellerId' => $reseller->getKey(),
        ])));
    }

    /**
     * @covers ::documentReseller
     */
    public function testDocumentResellerResellerNotFound(): void {
        $reseller = Reseller::factory()->make();
        $document = Document::create([
            'id'         => $this->faker->uuid,
            'resellerId' => $reseller->getKey(),
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

            public function documentReseller(Document $document): ?Reseller {
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

            public function documentCustomer(Document $document): ?Customer {
                return parent::documentCustomer($document);
            }
        };

        $this->assertEquals($customer, $factory->documentCustomer(Document::create([
            'customerId' => $customer->getKey(),
        ])));
    }

    /**
     * @covers ::documentCustomer
     */
    public function testDocumentCustomerCustomerNotFound(): void {
        $customer = Customer::factory()->make();
        $document = Document::create([
            'id'         => $this->faker->uuid,
            'customerId' => $customer->getKey(),
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

            public function documentCustomer(Document $document): ?Customer {
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
        $document = Document::create([
            'id' => $this->faker->uuid,
        ]);
        $currency = Currency::factory()->make();
        $factory  = Mockery::mock(CurrencyFactory::class);

        $factory
            ->shouldReceive('create')
            ->with($document)
            ->once()
            ->andReturn($currency);

        $factory = new class($factory) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected CurrencyFactory $currencies,
            ) {
                // empty
            }

            public function documentCurrency(Document $document): Currency {
                return parent::documentCurrency($document);
            }
        };

        $this->assertEquals($currency, $factory->documentCurrency($document));
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

        $callback = Mockery::spy(function (EloquentCollection $collection): void {
            $this->assertCount(0, $collection);
        });

        $factory->prefetch(
            [
                Asset::create([
                    'assetDocument' => [$a, $b],
                ]),
                Asset::create([
                    'assetDocument' => [$c],
                ]),
                Asset::create([
                    // should pass
                ]),
            ],
            false,
            Closure::fromCallable($callback),
        );

        $callback->shouldHaveBeenCalled()->once();

        $this->flushQueryLog();

        $factory->find(AssetDocument::create($a));
        $factory->find(AssetDocument::create($b));
        $factory->find(AssetDocument::create($c));

        $this->assertCount(0, $this->getQueryLog());
    }

    /**
     * @covers ::documentLanguage
     */
    public function testDocumentLanguage(): void {
        $document = Document::create([
            'id' => $this->faker->uuid,
        ]);
        $language = Language::factory()->make();
        $factory  = Mockery::mock(LanguageFactory::class);

        $factory
            ->shouldReceive('create')
            ->with($document)
            ->once()
            ->andReturn($language);

        $factory = new class($factory) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected LanguageFactory $languages,
            ) {
                // empty
            }

            public function documentLanguage(Document $document): Language {
                return parent::documentLanguage($document);
            }
        };

        $this->assertEquals($language, $factory->documentLanguage($document));
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

    public function documentOem(Document $document): Oem {
        return parent::documentOem($document);
    }

    public function documentType(Document $document): TypeModel {
        return parent::documentType($document);
    }

    public function documentProduct(AssetDocument $document): Product {
        return parent::documentProduct($document);
    }
}
