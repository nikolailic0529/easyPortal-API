<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry as DocumentEntryModel;
use App\Models\Enums\ProductType;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

use function is_null;
use function number_format;
use function reset;

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
        $document = AssetDocumentObject::create([
            'document' => $json,
        ]);

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
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObject(): void {
        // Factory
        $contacts = $this->app->make(ContactFactory::class);
        $factory  = $this->app
            ->make(DocumentFactoryTest_Factory::class)
            ->setContactsFactory($contacts);

        // Create
        // ---------------------------------------------------------------------
        $json    = $this->getTestData()->json('~asset-document-full.json');
        $asset   = Asset::create($json);
        $model   = AssetModel::factory()->create([
            'id' => $asset->id,
        ]);
        $object  = AssetDocumentObject::create([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);
        $created = $factory->createFromAssetDocumentObject($object);

        $this->assertNotNull($created);
        $this->assertEquals($asset->customerId, $created->customer_id);
        $this->assertEquals($asset->resellerId, $created->reseller_id);
        $this->assertEquals('0056523287', $created->number);
        $this->assertEquals('1292.16', $created->price);
        $this->assertEquals('1583020800000', $this->getDatetime($created->start));
        $this->assertEquals('1614470400000', $this->getDatetime($created->end));
        $this->assertEquals('HPE', $created->oem->abbr);
        $this->assertEquals('MultiNational Quote', $created->type->key);
        $this->assertEquals('CUR', $created->currency->code);
        $this->assertEquals('fr', $created->language->code);
        $this->assertEquals('H7J34AC', $created->support->sku);
        $this->assertEquals('HPE Foundation Care 24x7 SVC', $created->support->name);
        $this->assertEquals(ProductType::support(), $created->support->type);
        $this->assertEquals('HPE', $created->support->oem->abbr);
        $this->assertEquals('HPE', $created->oem->abbr);
        $this->assertEquals(6, $created->entries_count);
        $this->assertEquals(1, $created->contacts_count);
        $this->assertCount(6, $created->entries);
        $this->assertCount(1, $created->contacts);

        /** @var \App\Models\DocumentEntry $e */
        $e = $created->entries->first(static function (DocumentEntryModel $entry): bool {
            return $entry->renewal === '145.00';
        });

        $this->assertNotNull($e);
        $this->assertEquals('23.40', $e->net_price);
        $this->assertEquals('48.00', $e->list_price);
        $this->assertEquals('-2.05', $e->discount);
        $this->assertEquals($created->getKey(), $e->document_id);
        $this->assertEquals($asset->id, $e->asset_id);
        $this->assertEquals('HA151AC', $e->service->sku);
        $this->assertEquals('HPE Hardware Maintenance Onsite Support', $e->service->name);
        $this->assertEquals(ProductType::service(), $e->service->type);
        $this->assertEquals('HPE', $e->service->oem->abbr);
        $this->assertEquals('145.00', $e->renewal);

        // Changed
        // ---------------------------------------------------------------------
        $json    = $this->getTestData()->json('~asset-document-changed.json');
        $asset   = Asset::create($json);
        $object  = AssetDocumentObject::create([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);
        $changed = $factory->createFromAssetDocumentObject($object);

        $this->assertEquals($model->getKey(), $asset->id);
        $this->assertNotNull($changed);
        $this->assertEquals('3292.16', $changed->price);
        $this->assertEquals('EUR', $changed->currency->code);
        $this->assertEquals('en', $changed->language->code);
        $this->assertCount(0, $changed->contacts);
        $this->assertEquals(0, $changed->contacts_count);
        $this->assertEquals(2, $changed->entries_count);
        $this->assertCount(2, $changed->entries);
        $this->assertCount(2, $changed->refresh()->entries);

        /** @var \App\Models\DocumentEntry $e */
        $e = $changed->entries->first(static function (DocumentEntryModel $entry): bool {
            return is_null($entry->renewal);
        });

        $this->assertNotNull($e);
        $this->assertNull($e->net_price);
        $this->assertNull($e->list_price);
        $this->assertNull($e->discount);
        $this->assertNull($e->renewal);
    }

    /**
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObjectDocumentNull(): void {
        // Factory
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);

        // Create
        // ---------------------------------------------------------------------
        $json    = $this->getTestData()->json('~asset-document-no-document.json');
        $asset   = Asset::create($json);
        $model   = AssetModel::factory()->create([
            'id' => $asset->id,
        ]);
        $object  = AssetDocumentObject::create([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);
        $created = $factory->createFromAssetDocumentObject($object);

        // Test
        // ---------------------------------------------------------------------
        $this->assertNotNull($created);
        $this->assertEquals($asset->customerId, $created->customer_id);
        $this->assertEquals($asset->resellerId, $created->reseller_id);
        $this->assertEquals('688b9621-3244-464b-9468-3cd74f5eaacf', $created->number);
        $this->assertEquals(null, $created->price);
        $this->assertEquals('1583020800000', $this->getDatetime($created->start));
        $this->assertEquals('1614470400000', $this->getDatetime($created->end));
        $this->assertEquals($model->oem->abbr, $created->oem->abbr);
        $this->assertEquals('??', $created->type->key);
        $this->assertEquals('CUR', $created->currency->code);
        $this->assertEquals('fr', $created->language->code);
        $this->assertEquals('H7J34AC', $created->support->sku);
        $this->assertEquals('HPE Foundation Care 24x7 SVC', $created->support->name);
        $this->assertEquals(ProductType::support(), $created->support->type);
        $this->assertEquals($model->oem->abbr, $created->support->oem->abbr);

        $this->assertCount(2, $created->entries);

        /** @var \App\Models\DocumentEntry $e */
        $e = $created->entries->first(static function (DocumentEntryModel $entry): bool {
            return $entry->renewal === '145.00';
        });

        $this->assertEquals('23.40', $e->net_price);
        $this->assertEquals('48.00', $e->list_price);
        $this->assertEquals('-2.05', $e->discount);
        $this->assertEquals($created->getKey(), $e->document_id);
        $this->assertEquals($asset->id, $e->asset_id);
        $this->assertEquals('HA151AC', $e->service->sku);
        $this->assertEquals('HPE Hardware Maintenance Onsite Support', $e->service->name);
        $this->assertEquals(ProductType::service(), $e->service->type);
        $this->assertEquals($model->oem->abbr, $e->service->oem->abbr);
    }

    /**
     * @covers ::assetDocumentObjectSupport
     */
    public function testAssetDocumentObjectSupport(): void {
        $oem      = Oem::factory()->make();
        $type     = ProductType::support();
        $document = AssetDocumentObject::create([
            'document' => [
                'document'                  => [
                    'vendorSpecificFields' => [
                        'vendor' => $this->faker->word,
                    ],
                ],
                'supportPackage'            => $this->faker->word,
                'supportPackageDescription' => $this->faker->word,
            ],
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('documentOem')
            ->with($document->document->document)
            ->once()
            ->andReturn($oem);
        $factory
            ->shouldReceive('product')
            ->with(
                $oem,
                $type,
                $document->document->supportPackage,
                $document->document->supportPackageDescription,
                null,
                null,
            )
            ->once()
            ->andReturns();

        $factory->assetDocumentObjectSupport($document);
    }

    /**
     * @covers ::assetDocumentObjectEntries
     */
    public function testAssetDocumentObjectEntries(): void {
        // Prepare
        $asset      = AssetModel::factory()->create();
        $document   = DocumentModel::factory()->create([
            'support_id' => static function (): Product {
                return Product::factory()->create([
                    'type' => ProductType::support(),
                ]);
            },
        ]);
        $another    = DocumentEntryModel::factory(2)->create([
            'document_id' => $document,
            'product_id'  => $asset->product_id,
        ]);
        $properties = [
            'document_id' => $document,
            'asset_id'    => $asset,
            'product_id'  => $asset->product_id,
            'service_id'  => static function () use ($document): Product {
                return Product::factory()->create([
                    'type'   => ProductType::service(),
                    'oem_id' => $document->oem_id,
                ]);
            },
        ];
        $a          = DocumentEntryModel::factory()->create($properties);
        $b          = DocumentEntryModel::factory()->create($properties);
        $c          = DocumentEntryModel::factory()->create($properties);
        $d          = DocumentEntryModel::factory()->create($properties);
        $object     = AssetDocumentObject::create([
            'asset'   => $asset,
            'entries' => [
                [
                    'skuNumber'                 => $a->service->sku,
                    'skuDescription'            => $a->service->name,
                    'supportPackage'            => $document->support->sku,
                    'supportPackageDescription' => $document->support->name,
                    'currencyCode'              => $a->currency->code,
                    'netPrice'                  => $a->net_price,
                    'discount'                  => $a->discount,
                    'listPrice'                 => $a->list_price,
                    'estimatedValueRenewal'     => $a->renewal,
                ],
                [
                    'skuNumber'                 => $b->service->sku,
                    'skuDescription'            => $b->service->name,
                    'supportPackage'            => $document->support->sku,
                    'supportPackageDescription' => $document->support->name,
                    'currencyCode'              => $a->currency->code,
                    'netPrice'                  => $b->net_price,
                    'discount'                  => $b->discount,
                    'listPrice'                 => $b->list_price,
                    'estimatedValueRenewal'     => $b->renewal,
                ],
                [
                    'skuNumber'                 => $b->service->sku,
                    'skuDescription'            => $b->service->name,
                    'supportPackage'            => $document->support->sku,
                    'supportPackageDescription' => $document->support->name,
                    'currencyCode'              => null,
                    'netPrice'                  => null,
                    'discount'                  => null,
                    'listPrice'                 => null,
                    'estimatedValueRenewal'     => null,
                ],
            ],
        ]);
        $factory    = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyFactory::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductResolver $products,
                protected OemResolver $oems,
                protected CurrencyFactory $currencies,
            ) {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function assetDocumentObjectEntries(DocumentModel $model, AssetDocumentObject $document): array {
                return parent::assetDocumentObjectEntries($model, $document); // TODO: Change the autogenerated stub
            }
        };

        // Test
        $actual   = new Collection($factory->assetDocumentObjectEntries($document, $object));
        $added    = $actual
            ->filter(static function (DocumentEntryModel $entry) {
                return is_null($entry->getKey());
            })
            ->first();
        $existing = $actual
            ->map(static function (DocumentEntryModel $entry) {
                return $entry->getKey();
            })
            ->filter()
            ->sort()
            ->values();
        $expected = $another
            ->push($a, $b)
            ->map(static function (DocumentEntryModel $entry) {
                return $entry->getKey();
            })
            ->sort()
            ->values();

        $this->assertCount(5, $actual);
        $this->assertCount(4, $existing);
        $this->assertEquals($expected, $existing);
        $this->assertFalse($existing->contains($c->getKey()));
        $this->assertFalse($existing->contains($d->getKey()));
        $this->assertNotNull($added);
        $this->assertNull($added->list_price);
        $this->assertNull($added->net_price);
        $this->assertNull($added->discount);
        $this->assertNull($added->renewal);
    }

    /**
     * @covers ::assetDocumentEntry
     */
    public function testAssetDocumentEntry(): void {
        $asset          = AssetModel::factory()->make([
            'id'            => $this->faker->uuid,
            'serial_number' => $this->faker->uuid,
        ]);
        $skuNumber      = $this->faker->word;
        $skuDescription = $this->faker->sentence;
        $currencyCode   = $this->faker->currencyCode;
        $netPrice       = number_format($this->faker->randomFloat(2), 2, '.', '');
        $discount       = number_format($this->faker->randomFloat(2), 2, '.', '');
        $listPrice      = number_format($this->faker->randomFloat(2), 2, '.', '');
        $renewal        = number_format($this->faker->randomFloat(2), 2, '.', '');
        $assetDocument  = AssetDocument::create([
            'skuNumber'             => " {$skuNumber} ",
            'skuDescription'        => " {$skuDescription} ",
            'netPrice'              => " {$netPrice} ",
            'discount'              => " {$discount} ",
            'listPrice'             => " {$listPrice} ",
            'estimatedValueRenewal' => " {$renewal} ",
            'currencyCode'          => " {$currencyCode} ",
        ]);
        $document       = DocumentModel::factory()->make();
        $factory        = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyFactory::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductResolver $products,
                protected OemResolver $oems,
                protected CurrencyFactory $currencies,
            ) {
                // empty
            }

            public function assetDocumentEntry(
                AssetModel $asset,
                DocumentModel $document,
                AssetDocument $assetDocument,
            ): DocumentEntryModel {
                return parent::assetDocumentEntry($asset, $document, $assetDocument);
            }
        };

        $entry = $factory->assetDocumentEntry($asset, $document, $assetDocument);

        $this->assertInstanceOf(DocumentEntryModel::class, $entry);
        $this->assertEquals($asset->getKey(), $entry->asset_id);
        $this->assertNull($entry->document_id);
        $this->assertEquals($asset->serial_number, $entry->serial_number);
        $this->assertSame($asset->product, $entry->product);
        $this->assertNotNull($entry->service_id);
        $this->assertSame($document->oem, $entry->service->oem);
        $this->assertEquals(ProductType::service(), $entry->service->type);
        $this->assertEquals($skuNumber, $entry->service->sku);
        $this->assertEquals($skuDescription, $entry->service->name);
        $this->assertNull($entry->service->eos);
        $this->assertNull($entry->service->eol);
        $this->assertEquals($currencyCode, $entry->currency->code);
        $this->assertEquals($netPrice, $entry->net_price);
        $this->assertEquals($listPrice, $entry->list_price);
        $this->assertEquals($discount, $entry->discount);
        $this->assertEquals($renewal, $entry->renewal);
    }

    /**
     * @covers ::compareDocumentEntries
     */
    public function testCompareDocumentEntries(): void {
        // Prepare
        $a       = DocumentEntryModel::factory()->make();
        $b       = DocumentEntryModel::factory()->make();
        $factory = new class() extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function compareDocumentEntries(DocumentEntryModel $a, DocumentEntryModel $b): int {
                return parent::compareDocumentEntries($a, $b);
            }
        };

        // Test
        $this->assertNotEquals(0, $factory->compareDocumentEntries($a, $b));

        // Make same
        $a->currency_id = $b->currency_id;
        $a->net_price   = $b->net_price;
        $a->list_price  = $b->list_price;
        $a->discount    = $b->discount;
        $a->renewal     = $b->renewal;
        $a->service_id  = $b->service_id;

        $this->assertEquals(0, $factory->compareDocumentEntries($a, $b));
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocument(): void {
        // Prepare
        $contacts   = $this->app->make(ContactFactory::class);
        $normalizer = $this->app->make(Normalizer::class);
        $factory    = $this->app->make(DocumentFactoryTest_Factory::class)->setContactsFactory($contacts);

        // Test
        $json     = $this->getTestData()->json('~document-full.json');
        $document = AssetDocument::create($json);
        $created  = $factory->createFromDocument($document->document);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($document->document->id, $created->getKey());
        $this->assertEquals($document->document->reseller->id, $created->reseller_id);
        $this->assertEquals($document->document->customer->id, $created->customer_id);
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
        $this->assertNull($created->support);
        $this->assertEquals(
            $this->getModelContacts($created),
            $this->getContacts($document->document),
        );

        // Customer should be updated
        $json     = $this->getTestData()->json('~document-changed.json');
        $document = AssetDocument::create($json);
        $updated  = $factory->createFromDocument($document->document);

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
        $this->assertNull($updated->support);
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocumentDocumentWithNumberExists(): void {
        // Prepare
        $factory  = $this->app->make(DocumentFactoryTest_Factory::class);
        $json     = $this->getTestData()->json('~document-full.json');
        $document = AssetDocument::create($json);
        $model    = DocumentModel::factory()->create([
            'id'     => $document->document->documentNumber,
            'number' => $document->document->documentNumber,
        ]);

        // Pretest
        $this->assertEquals(1, DocumentModel::query()->count());

        // Test
        $created = $factory->createFromDocument($document->document);

        $this->assertNotNull($created);
        $this->assertEquals($document->document->id, $created->getKey());
        $this->assertNull(DocumentModel::query()->find($model->getKey()));
        $this->assertEquals(1, DocumentModel::query()->withoutGlobalScopes()->count());
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocumentDocumentsWithNumberAndIdExists(): void {
        // Prepare
        $factory  = $this->app->make(DocumentFactoryTest_Factory::class);
        $json     = $this->getTestData()->json('~document-full.json');
        $document = AssetDocument::create($json);

        DocumentModel::factory()->create([
            'id'     => $document->document->documentNumber,
            'number' => $document->document->documentNumber,
        ]);
        DocumentModel::factory()->create([
            'id'     => $document->document->id,
            'number' => $document->document->documentNumber,
        ]);

        // Pretest
        $this->assertEquals(2, DocumentModel::query()->count());

        // Test
        $created = $factory->createFromDocument($document->document);

        $this->assertNotNull($created);
        $this->assertEquals($document->document->id, $created->getKey());
        $this->assertEquals(2, DocumentModel::query()->withoutGlobalScopes()->count());
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

        $factory->find(AssetDocumentObject::create([
            'document' => $a,
        ]));
        $factory->find(AssetDocumentObject::create([
            'document' => $b,
        ]));
        $factory->find(AssetDocumentObject::create([
            'document' => $c,
        ]));

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
            AssetDocumentObject::class => ['createFromAssetDocumentObject', new AssetDocumentObject()],
            AssetDocument::class       => [null, new AssetDocument()],
            Document::class            => [null, new Document()],
            'Unknown'                  => [
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

    public function assetDocumentObjectSupport(AssetDocumentObject $document): Product {
        return parent::assetDocumentObjectSupport($document);
    }

    public function createFromDocument(
        Document $document,
        Closure $product = null,
        Closure $entries = null,
    ): ?DocumentModel {
        return parent::createFromDocument($document, $product, $entries);
    }

    public function createFromAssetDocumentObject(AssetDocumentObject $document): ?DocumentModel {
        return parent::createFromAssetDocumentObject($document);
    }
}
