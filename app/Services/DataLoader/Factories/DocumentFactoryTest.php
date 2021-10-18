<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry as DocumentEntryModel;
use App\Models\Oem;
use App\Models\OemGroup;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\FailedToProcessDocumentEntryNoAsset;
use App\Services\DataLoader\Exceptions\FailedToProcessViewAssetDocumentNoDocument;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutOrganizationScope;

use function array_column;
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
     *
     * @dataProvider dataProviderCreate
     */
    public function testFind(?string $expected, Closure $typeFactory): void {
        $type    = $typeFactory($this);
        $factory = $this->app->make(DocumentFactory::class);

        $this->flushQueryLog();

        if (!$expected) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->find($type);

        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Closure $typeFactory): void {
        $type    = $typeFactory($this);
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
        // Mock
        $this->overrideDateFactory();
        $this->overrideFinders();

        // Factory
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);

        // Create
        // ---------------------------------------------------------------------
        $json   = $this->getTestData()->json('~asset-document-full.json');
        $asset  = new ViewAsset($json);
        $model  = AssetModel::factory()->create([
            'id' => $asset->id,
        ]);
        $object = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);

        $this->flushQueryLog();

        // Test
        $created  = $factory->createFromAssetDocumentObject($object);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromAssetDocumentObject-create-expected.json');

        $this->assertEquals($expected, $actual);
        $this->assertNotNull($created);
        $this->assertEquals($asset->customerId, $created->customer_id);
        $this->assertEquals($asset->resellerId, $created->reseller_id);
        $this->assertEquals($object->document->document->distributorId, $created->distributor_id);
        $this->assertEquals('0056523287', $created->number);
        $this->assertEquals('1292.16', $created->price);
        $this->assertNull($this->getDatetime($created->start));
        $this->assertEquals('1614470400000', $this->getDatetime($created->end));
        $this->assertNull($this->getDatetime($created->changed_at));
        $this->assertEquals('HPE', $created->oem->key);
        $this->assertEquals('MultiNational Quote', $created->type->key);
        $this->assertEquals('CUR', $created->currency->code);
        $this->assertEquals('fr', $created->language->code);
        $this->assertEquals('HPE', $created->oem->key);
        $this->assertEquals('1234 4678 9012', $created->oem_said);
        $this->assertEquals('abc-de', $created->oemGroup->key);
        $this->assertEquals(1, $created->assets_count);
        $this->assertEquals(7, $created->entries_count);
        $this->assertEquals(1, $created->contacts_count);
        $this->assertCount(7, $created->entries);
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
        $this->assertEquals('H7J34AC', $e->serviceGroup->sku);
        $this->assertEquals('HA151AC', $e->serviceLevel->sku);
        $this->assertEquals('HPE', $e->serviceLevel->oem->key);
        $this->assertEquals('145.00', $e->renewal);

        $this->flushQueryLog();

        // Changed
        // ---------------------------------------------------------------------
        $json     = $this->getTestData()->json('~asset-document-changed.json');
        $asset    = new ViewAsset($json);
        $object   = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);
        $changed  = $factory->createFromAssetDocumentObject($object);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromAssetDocumentObject-update-expected.json');

        $this->assertEquals($expected, $actual);
        $this->assertEquals($model->getKey(), $asset->id);
        $this->assertNotNull($changed);
        $this->assertNull($created->distributor_id);
        $this->assertEquals('3292.16', $changed->price);
        $this->assertEquals('1625642660000', $this->getDatetime($changed->changed_at));
        $this->assertEquals('EUR', $changed->currency->code);
        $this->assertEquals('en', $changed->language->code);
        $this->assertNull($changed->oem_said);
        $this->assertNull($changed->oemGroup);
        $this->assertCount(0, $changed->contacts);
        $this->assertEquals(0, $changed->contacts_count);
        $this->assertEquals(2, $changed->entries_count);
        $this->assertEquals(1, $changed->assets_count);
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

        $this->flushQueryLog();

        // No changes
        // ---------------------------------------------------------------------
        $json   = $this->getTestData()->json('~asset-document-changed.json');
        $asset  = new ViewAsset($json);
        $object = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);

        $factory->createFromAssetDocumentObject($object);

        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // Outdated
        // ---------------------------------------------------------------------
        $json    = $this->getTestData()->json('~asset-document-outdated.json');
        $asset   = new ViewAsset($json);
        $object  = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);
        $changed = $factory->createFromAssetDocumentObject($object);

        $this->assertCount(0, $this->getQueryLog());
        $this->assertEquals('3292.16', $changed->price);

        $this->flushQueryLog();
    }

    /**
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObjectDocumentNull(): void {
        // Factory
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);

        // Create
        // ---------------------------------------------------------------------
        $json   = $this->getTestData()->json('~asset-document-no-document.json');
        $asset  = new ViewAsset($json);
        $model  = AssetModel::factory()->create([
            'id' => $asset->id,
        ]);
        $object = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);

        $this->expectExceptionObject(new FailedToProcessViewAssetDocumentNoDocument(
            $object->asset,
            $object->document,
            new Collection($object->entries),
        ));

        $factory->createFromAssetDocumentObject($object);
    }

    /**
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObjectCustomerNull(): void {
        // Mock
        $this->overrideFinders();

        // Factory
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);

        // Create
        // ---------------------------------------------------------------------
        $json    = $this->getTestData()->json('~asset-document-no-customer.json');
        $asset   = new ViewAsset($json);
        $model   = AssetModel::factory()->create([
            'id' => $asset->id,
        ]);
        $object  = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);
        $created = $factory->createFromAssetDocumentObject($object);

        $this->assertNotNull($created);
        $this->assertNull($created->reseller_id);
        $this->assertNull($created->customer_id);
    }

    /**
     * @covers ::assetDocumentObjectEntries
     */
    public function testAssetDocumentObjectEntries(): void {
        // Mock
        $this->overrideServiceGroupFinder();
        $this->overrideServiceLevelFinder();

        // Prepare
        $asset        = AssetModel::factory()->create();
        $document     = DocumentModel::factory()->create();
        $serviceGroup = ServiceGroup::factory()->create([
            'oem_id' => $document->oem_id,
        ]);
        $serviceLevel = ServiceLevel::factory()->create([
            'oem_id'           => $document->oem_id,
            'service_group_id' => $serviceGroup,
        ]);
        $another      = DocumentEntryModel::factory(2)->create([
            'document_id' => $document,
            'product_id'  => $asset->product_id,
        ]);
        $properties   = [
            'document_id'      => $document,
            'asset_id'         => $asset,
            'product_id'       => $asset->product_id,
            'service_group_id' => $serviceGroup,
            'service_level_id' => $serviceLevel,
        ];
        $a            = DocumentEntryModel::factory()->create($properties);
        $b            = DocumentEntryModel::factory()->create($properties);
        $c            = DocumentEntryModel::factory()->create($properties);
        $d            = DocumentEntryModel::factory()->create($properties);
        $object       = new AssetDocumentObject([
            'asset'   => $asset,
            'entries' => [
                [
                    'skuNumber'             => $a->serviceLevel->sku,
                    'supportPackage'        => $a->serviceGroup->sku,
                    'currencyCode'          => $a->currency->code,
                    'netPrice'              => $a->net_price,
                    'discount'              => $a->discount,
                    'listPrice'             => $a->list_price,
                    'estimatedValueRenewal' => $a->renewal,
                    'document'              => [
                        'vendorSpecificFields' => [
                            'vendor' => $document->oem->key,
                        ],
                    ],
                ],
                [
                    'skuNumber'             => $b->serviceLevel->sku,
                    'supportPackage'        => $b->serviceGroup->sku,
                    'currencyCode'          => $a->currency->code,
                    'netPrice'              => $b->net_price,
                    'discount'              => $b->discount,
                    'listPrice'             => $b->list_price,
                    'estimatedValueRenewal' => $b->renewal,
                    'document'              => [
                        'vendorSpecificFields' => [
                            'vendor' => $document->oem->key,
                        ],
                    ],
                ],
                [
                    'skuNumber'             => $b->serviceLevel->sku,
                    'supportPackage'        => $b->serviceGroup->sku,
                    'currencyCode'          => null,
                    'netPrice'              => null,
                    'discount'              => null,
                    'listPrice'             => null,
                    'estimatedValueRenewal' => null,
                    'document'              => [
                        'vendorSpecificFields' => [
                            'vendor' => $document->oem->key,
                        ],
                    ],
                ],
            ],
        ]);
        $factory      = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyResolver::class),
            $this->app->make(ServiceGroupResolver::class),
            $this->app->make(ServiceLevelResolver::class),
            $this->app->make(ServiceGroupFinder::class),
            $this->app->make(ServiceLevelFinder::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductResolver $productResolver,
                protected OemResolver $oemResolver,
                protected CurrencyResolver $currencyResolver,
                protected ServiceGroupResolver $serviceGroupResolver,
                protected ServiceLevelResolver $serviceLevelResolver,
                protected ?ServiceGroupFinder $serviceGroupFinder = null,
                protected ?ServiceLevelFinder $serviceLevelFinder = null,
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
        $created  = $actual
            ->filter(static function (DocumentEntryModel $entry): bool {
                return !$entry->exists;
            })
            ->first();
        $existing = $actual
            ->filter(static function (DocumentEntryModel $entry): bool {
                return $entry->exists;
            })
            ->map(static function (DocumentEntryModel $entry) {
                return $entry->getKey();
            })
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
        $this->assertNotNull($created);
        $this->assertNull($created->list_price);
        $this->assertNull($created->net_price);
        $this->assertNull($created->discount);
        $this->assertNull($created->renewal);
    }

    /**
     * @covers ::assetDocumentEntry
     */
    public function testAssetDocumentEntry(): void {
        $this->overrideServiceGroupFinder();
        $this->overrideServiceLevelFinder();

        $document       = DocumentModel::factory()->make();
        $asset          = AssetModel::factory()->make([
            'id'            => $this->faker->uuid,
            'serial_number' => $this->faker->uuid,
        ]);
        $skuNumber      = $this->faker->word;
        $supportPackage = $this->faker->word;
        $currencyCode   = $this->faker->currencyCode;
        $netPrice       = number_format($this->faker->randomFloat(2), 2, '.', '');
        $discount       = number_format($this->faker->randomFloat(2), 2, '.', '');
        $listPrice      = number_format($this->faker->randomFloat(2), 2, '.', '');
        $renewal        = number_format($this->faker->randomFloat(2), 2, '.', '');
        $assetDocument  = new ViewAssetDocument([
            'supportPackage'        => " {$supportPackage} ",
            'skuNumber'             => " {$skuNumber} ",
            'netPrice'              => " {$netPrice} ",
            'discount'              => " {$discount} ",
            'listPrice'             => " {$listPrice} ",
            'estimatedValueRenewal' => " {$renewal} ",
            'currencyCode'          => " {$currencyCode} ",
            'document'              => [
                'vendorSpecificFields' => [
                    'vendor' => $document->oem->key,
                ],
            ],
        ]);
        $factory        = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyResolver::class),
            $this->app->make(ServiceGroupResolver::class),
            $this->app->make(ServiceLevelResolver::class),
            $this->app->make(ServiceGroupFinder::class),
            $this->app->make(ServiceLevelFinder::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductResolver $productResolver,
                protected OemResolver $oemResolver,
                protected CurrencyResolver $currencyResolver,
                protected ServiceGroupResolver $serviceGroupResolver,
                protected ServiceLevelResolver $serviceLevelResolver,
                protected ?ServiceGroupFinder $serviceGroupFinder = null,
                protected ?ServiceLevelFinder $serviceLevelFinder = null,
            ) {
                // empty
            }

            public function assetDocumentEntry(
                AssetModel $asset,
                DocumentModel $document,
                ViewAssetDocument $assetDocument,
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
        $this->assertNotNull($entry->service_level_id);
        $this->assertEquals($document->oem_id, $entry->serviceLevel->oem_id);
        $this->assertEquals($supportPackage, $entry->serviceGroup->sku);
        $this->assertEquals($skuNumber, $entry->serviceLevel->sku);
        $this->assertEquals($currencyCode, $entry->currency->code);
        $this->assertEquals($netPrice, $entry->net_price);
        $this->assertEquals($listPrice, $entry->list_price);
        $this->assertEquals($discount, $entry->discount);
        $this->assertEquals($renewal, $entry->renewal);
    }

    /**
     * @covers ::assetDocumentEntry
     */
    public function testAssetDocumentEntrySkuNumberNull(): void {
        $asset         = AssetModel::factory()->make([
            'id'            => $this->faker->uuid,
            'serial_number' => $this->faker->uuid,
        ]);
        $assetDocument = new ViewAssetDocument([
            'skuNumber'             => null,
            'netPrice'              => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'discount'              => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'listPrice'             => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'estimatedValueRenewal' => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'currencyCode'          => $this->faker->currencyCode,
        ]);
        $document      = DocumentModel::factory()->make();
        $factory       = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyResolver::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductResolver $productResolver,
                protected OemResolver $oemResolver,
                protected CurrencyResolver $currencyResolver,
            ) {
                // empty
            }

            public function assetDocumentEntry(
                AssetModel $asset,
                DocumentModel $document,
                ViewAssetDocument $assetDocument,
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
        $this->assertNull($entry->service_level_id);
        $this->assertNull($entry->serviceLevel);
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
        $a->asset_id         = $b->asset_id;
        $a->currency_id      = $b->currency_id;
        $a->net_price        = $b->net_price;
        $a->list_price       = $b->list_price;
        $a->discount         = $b->discount;
        $a->renewal          = $b->renewal;
        $a->service_level_id = $b->service_level_id;

        $this->assertEquals(0, $factory->compareDocumentEntries($a, $b));
    }

    /**
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObjectContactPersonsIsNull(): void {
        // Mock
        $this->overrideFinders();

        // Prepare
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);
        $json    = $this->getTestData()->json('~asset-document-full.json');
        $asset   = new ViewAsset($json);
        $model   = AssetModel::factory()->create([
            'id' => $asset->id,
        ]);
        $object  = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);

        // Set property to null
        $object->document->document->contactPersons = null;

        // Test
        $created = $factory->createFromAssetDocumentObject($object);

        $this->assertNotNull($created);
        $this->assertCount(0, $created->contacts);
    }

    /**
     * @covers ::documentOemGroup
     *
     * @dataProvider dataProviderDocument
     *
     * @template     T of \App\Services\DataLoader\Schema\Document|\App\Services\DataLoader\Schema\ViewDocument
     *
     * @param \Closure(): T $documentFactory
     */
    public function testDocumentOemGroup(Closure $documentFactory): void {
        $oem      = Oem::factory()->make();
        $document = $documentFactory($this);
        $factory  = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('documentOem')
            ->with($document)
            ->once()
            ->andReturn($oem);
        $factory
            ->shouldReceive('oemGroup')
            ->with(
                $oem,
                $document->vendorSpecificFields->groupId,
                (string) $document->vendorSpecificFields->groupDescription,
            )
            ->once()
            ->andReturns();

        $factory->documentOemGroup($document);
    }

    /**
     * @covers ::documentType
     *
     * @dataProvider dataProviderDocument
     *
     * @template     T of \App\Services\DataLoader\Schema\Document|\App\Services\DataLoader\Schema\ViewDocument
     *
     * @param \Closure(): T $documentFactory
     */
    public function testDocumentType(Closure $documentFactory): void {
        $document = $documentFactory($this);
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
        $d       = [
            'id' => '5c631e1e-d363-4992-a818-398966606441',
        ];
        $factory = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(DocumentResolver::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected DocumentResolver $documentResolver,
            ) {
                // empty
            }
        };

        $callback = Mockery::spy(function (EloquentCollection $collection): void {
            $this->assertCount(0, $collection);
        });

        $factory->prefetch(
            [
                new ViewAsset([
                    'assetDocument' => [$a, $b],
                ]),
                new ViewAsset([
                    'assetDocument' => [$c],
                ]),
                new ViewAsset([
                    // should pass
                ]),
                new Document($d),
            ],
            false,
            Closure::fromCallable($callback),
        );

        $callback->shouldHaveBeenCalled()->once();

        $this->flushQueryLog();

        $factory->find(new AssetDocumentObject([
            'document' => $a,
        ]));
        $factory->find(new AssetDocumentObject([
            'document' => $b,
        ]));
        $factory->find(new AssetDocumentObject([
            'document' => $c,
        ]));
        $factory->find(new Document($d));

        $this->assertCount(0, $this->getQueryLog());
    }

    /**
     * @covers ::documentStatuses
     */
    public function testDocumentStatuses(): void {
        // Prepare
        $owner   = new DocumentModel();
        $factory = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(StatusResolver::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected StatusResolver $statusResolver,
            ) {
                // empty
            }

            public function create(Type $type): ?DocumentModel {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function documentStatuses(DocumentModel $model, Document $document): array {
                return parent::documentStatuses($model, $document);
            }
        };

        // Null
        $this->assertEmpty($factory->documentStatuses($owner, new Document(['status' => null])));

        // Empty
        $this->assertEmpty($factory->documentStatuses($owner, new Document(['status' => ['', null]])));

        // Not empty
        $document = new Document([
            'status' => ['a', 'A', 'b'],
        ]);
        $statuses = $factory->documentStatuses($owner, $document);
        $expected = [
            'a' => [
                'key'  => 'a',
                'name' => 'a',
            ],
            'b' => [
                'key'  => 'b',
                'name' => 'b',
            ],
        ];

        $this->assertCount(2, $statuses);
        $this->assertEquals($expected, $this->statuses($statuses));
    }

    /**
     * @covers ::documentEntry
     */
    public function testDocumentEntry(): void {
        $this->overrideServiceGroupFinder();
        $this->overrideServiceLevelFinder();

        $document       = DocumentModel::factory()->make();
        $asset          = AssetModel::factory()->create([
            'id'            => $this->faker->uuid,
            'serial_number' => $this->faker->uuid,
        ]);
        $skuNumber      = $this->faker->word;
        $supportPackage = $this->faker->word;
        $currencyCode   = $this->faker->currencyCode;
        $netPrice       = number_format($this->faker->randomFloat(2), 2, '.', '');
        $discount       = number_format($this->faker->randomFloat(2), 2, '.', '');
        $listPrice      = number_format($this->faker->randomFloat(2), 2, '.', '');
        $renewal        = number_format($this->faker->randomFloat(2), 2, '.', '');
        $documentEntry  = new DocumentEntry([
            'assetId'               => " {$asset->getKey()} ",
            'supportPackage'        => " {$supportPackage} ",
            'skuNumber'             => " {$skuNumber} ",
            'netPrice'              => " {$netPrice} ",
            'discount'              => " {$discount} ",
            'listPrice'             => " {$listPrice} ",
            'estimatedValueRenewal' => " {$renewal} ",
            'currencyCode'          => " {$currencyCode} ",
        ]);
        $factory        = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(AssetResolver::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyResolver::class),
            $this->app->make(ServiceGroupResolver::class),
            $this->app->make(ServiceLevelResolver::class),
            $this->app->make(ServiceGroupFinder::class),
            $this->app->make(ServiceLevelFinder::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected AssetResolver $assetResolver,
                protected ProductResolver $productResolver,
                protected OemResolver $oemResolver,
                protected CurrencyResolver $currencyResolver,
                protected ServiceGroupResolver $serviceGroupResolver,
                protected ServiceLevelResolver $serviceLevelResolver,
                protected ?ServiceGroupFinder $serviceGroupFinder = null,
                protected ?ServiceLevelFinder $serviceLevelFinder = null,
            ) {
                // empty
            }

            public function documentEntry(DocumentModel $model, DocumentEntry $documentEntry): DocumentEntryModel {
                return parent::documentEntry($model, $documentEntry);
            }
        };

        $entry = $factory->documentEntry($document, $documentEntry);

        $this->assertInstanceOf(DocumentEntryModel::class, $entry);
        $this->assertEquals($asset->getKey(), $entry->asset_id);
        $this->assertNull($entry->document_id);
        $this->assertEquals($asset->serial_number, $entry->serial_number);
        $this->assertEquals($asset->product, $entry->product);
        $this->assertNotNull($entry->service_level_id);
        $this->assertEquals($document->oem_id, $entry->serviceLevel->oem_id);
        $this->assertEquals($supportPackage, $entry->serviceGroup->sku);
        $this->assertEquals($skuNumber, $entry->serviceLevel->sku);
        $this->assertEquals($currencyCode, $entry->currency->code);
        $this->assertEquals($netPrice, $entry->net_price);
        $this->assertEquals($listPrice, $entry->list_price);
        $this->assertEquals($discount, $entry->discount);
        $this->assertEquals($renewal, $entry->renewal);
    }

    /**
     * @covers ::documentEntry
     */
    public function testDocumentEntrySkuNumberNull(): void {
        $document      = DocumentModel::factory()->make();
        $asset         = AssetModel::factory()->create([
            'id'            => $this->faker->uuid,
            'serial_number' => $this->faker->uuid,
        ]);
        $currencyCode  = $this->faker->currencyCode;
        $netPrice      = number_format($this->faker->randomFloat(2), 2, '.', '');
        $discount      = number_format($this->faker->randomFloat(2), 2, '.', '');
        $listPrice     = number_format($this->faker->randomFloat(2), 2, '.', '');
        $renewal       = number_format($this->faker->randomFloat(2), 2, '.', '');
        $documentEntry = new DocumentEntry([
            'assetId'               => " {$asset->getKey()} ",
            'skuNumber'             => null,
            'netPrice'              => " {$netPrice} ",
            'discount'              => " {$discount} ",
            'listPrice'             => " {$listPrice} ",
            'estimatedValueRenewal' => " {$renewal} ",
            'currencyCode'          => " {$currencyCode} ",
        ]);
        $factory       = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(AssetResolver::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyResolver::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected AssetResolver $assetResolver,
                protected ProductResolver $productResolver,
                protected OemResolver $oemResolver,
                protected CurrencyResolver $currencyResolver,
            ) {
                // empty
            }

            public function documentEntry(DocumentModel $model, DocumentEntry $documentEntry): DocumentEntryModel {
                return parent::documentEntry($model, $documentEntry);
            }
        };

        $entry = $factory->documentEntry($document, $documentEntry);

        $this->assertInstanceOf(DocumentEntryModel::class, $entry);
        $this->assertEquals($asset->getKey(), $entry->asset_id);
        $this->assertNull($entry->document_id);
        $this->assertEquals($asset->serial_number, $entry->serial_number);
        $this->assertEquals($asset->product, $entry->product);
        $this->assertEquals($currencyCode, $entry->currency->code);
        $this->assertEquals($netPrice, $entry->net_price);
        $this->assertEquals($listPrice, $entry->list_price);
        $this->assertEquals($discount, $entry->discount);
        $this->assertEquals($renewal, $entry->renewal);
    }

    /**
     * @covers ::documentEntryAsset
     */
    public function testDocumentEntryAsset(): void {
        $asset = AssetModel::factory()->make();
        $model = DocumentModel::factory()->create();
        $entry = new DocumentEntry([
            'assetId' => $asset->getKey(),
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('asset')
            ->with($entry)
            ->once()
            ->andReturn($asset);

        $this->assertSame($asset, $factory->documentEntryAsset($model, $entry));
    }

    /**
     * @covers ::documentEntryAsset
     */
    public function testDocumentEntryAssetNoAsset(): void {
        $model = DocumentModel::factory()->create();
        $entry = new DocumentEntry([
            'assetId' => $model->getKey(),
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('asset')
            ->with($entry)
            ->once()
            ->andReturn(null);

        $this->expectExceptionObject(new FailedToProcessDocumentEntryNoAsset($model, $entry));

        $factory->documentEntryAsset($model, $entry);
    }

    /**
     * @covers ::documentEntryServiceGroup
     */
    public function testDocumentEntryServiceGroup(): void {
        $oem   = Oem::factory()->make();
        $group = ServiceGroup::factory()->make();
        $model = DocumentModel::factory()->create()->setRelation('oem', $oem);
        $entry = new DocumentEntry([
            'supportPackage' => $this->faker->word,
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('serviceGroup')
            ->with(
                $oem,
                $entry->supportPackage,
            )
            ->once()
            ->andReturn($group);

        $this->assertSame($group, $factory->documentEntryServiceGroup($model, $entry));
    }

    /**
     * @covers ::documentEntryServiceLevel
     */
    public function testDocumentEntryServiceLevel(): void {
        $oem   = Oem::factory()->make();
        $group = ServiceGroup::factory()->make();
        $model = DocumentModel::factory()->create()->setRelation('oem', $oem);
        $level = ServiceLevel::factory()->make();
        $entry = new DocumentEntry([
            'skuNumber' => $this->faker->word,
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('documentEntryServiceGroup')
            ->with($model, $entry)
            ->once()
            ->andReturn($group);
        $factory
            ->shouldReceive('serviceLevel')
            ->with(
                $oem,
                $group,
                $entry->skuNumber,
            )
            ->once()
            ->andReturn($level);

        $this->assertSame($level, $factory->documentEntryServiceLevel($model, $entry));
    }

    /**
     * @covers ::documentEntries
     */
    public function testDocumentEntries(): void {
        // Mock
        $this->overrideServiceGroupFinder();
        $this->overrideServiceLevelFinder();

        // Prepare
        $assetA       = AssetModel::factory()->create();
        $assetB       = AssetModel::factory()->create();
        $document     = DocumentModel::factory()->create();
        $serviceGroup = ServiceGroup::factory()->create([
            'oem_id' => $document->oem_id,
        ]);
        $serviceLevel = ServiceLevel::factory()->create([
            'oem_id'           => $document->oem_id,
            'service_group_id' => $serviceGroup,
        ]);
        $properties   = [
            'document_id'      => $document,
            'asset_id'         => $assetA,
            'product_id'       => $assetA->product_id,
            'service_group_id' => $serviceGroup,
            'service_level_id' => $serviceLevel,
        ];
        [$a, $b]      = DocumentEntryModel::factory()->count(4)->create($properties);
        $object       = new Document([
            'id'                   => $document->getKey(),
            'vendorSpecificFields' => [
                'vendor' => $document->oem->key,
            ],
            'documentEntries'      => [
                [
                    'assetId'               => $a->asset_id,
                    'skuNumber'             => $a->serviceLevel->sku,
                    'supportPackage'        => $a->serviceGroup->sku,
                    'currencyCode'          => $a->currency->code,
                    'netPrice'              => $a->net_price,
                    'discount'              => $a->discount,
                    'listPrice'             => $a->list_price,
                    'estimatedValueRenewal' => $a->renewal,
                ],
                [
                    'assetId'               => $b->asset_id,
                    'skuNumber'             => $b->serviceLevel->sku,
                    'supportPackage'        => $b->serviceGroup->sku,
                    'currencyCode'          => $a->currency->code,
                    'netPrice'              => $b->net_price,
                    'discount'              => $b->discount,
                    'listPrice'             => $b->list_price,
                    'estimatedValueRenewal' => $b->renewal,
                ],
                [
                    'assetId'               => $assetB->getKey(),
                    'skuNumber'             => $b->serviceLevel->sku,
                    'supportPackage'        => $b->serviceGroup->sku,
                    'currencyCode'          => null,
                    'netPrice'              => null,
                    'discount'              => null,
                    'listPrice'             => null,
                    'estimatedValueRenewal' => null,
                ],
            ],
        ]);
        $factory      = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(AssetResolver::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyResolver::class),
            $this->app->make(ServiceGroupResolver::class),
            $this->app->make(ServiceLevelResolver::class),
            $this->app->make(ServiceGroupFinder::class),
            $this->app->make(ServiceLevelFinder::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected AssetResolver $assetResolver,
                protected ProductResolver $productResolver,
                protected OemResolver $oemResolver,
                protected CurrencyResolver $currencyResolver,
                protected ServiceGroupResolver $serviceGroupResolver,
                protected ServiceLevelResolver $serviceLevelResolver,
                protected ?ServiceGroupFinder $serviceGroupFinder = null,
                protected ?ServiceLevelFinder $serviceLevelFinder = null,
            ) {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function documentEntries(DocumentModel $model, Document $document): array {
                return parent::documentEntries($model, $document);
            }
        };

        // Test
        $actual   = new Collection($factory->documentEntries($document, $object));
        $created  = $actual
            ->filter(static function (DocumentEntryModel $entry) use ($assetB): bool {
                return $entry->asset_id === $assetB->getKey();
            })
            ->first();
        $existing = $actual
            ->filter(static function (DocumentEntryModel $entry): bool {
                return $entry->exists;
            })
            ->map(static function (DocumentEntryModel $entry) {
                return $entry->getKey();
            })
            ->sort()
            ->values();
        $expected = (new Collection([$a, $b, $created]))
            ->map(static function (DocumentEntryModel $entry) {
                return $entry->getKey();
            })
            ->sort()
            ->values();

        $this->assertCount(3, $actual);
        $this->assertCount(3, $existing);
        $this->assertEquals($expected, $existing);
        $this->assertNotNull($created);
        $this->assertNull($created->list_price);
        $this->assertNull($created->net_price);
        $this->assertNull($created->discount);
        $this->assertNull($created->renewal);
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocument(): void {
        // Mock
        $this->overrideDateFactory();
        $this->overrideFinders();
        $this->overrideAssetFinder();

        // Factory
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);

        // Create
        // ---------------------------------------------------------------------
        $json   = $this->getTestData()->json('~createFromDocument-document-full.json');
        $object = new Document($json);

        $this->flushQueryLog();

        // Test
        $created  = $factory->createFromDocument($object);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromDocument-document-full-queries.json');

        $this->assertEquals($expected, $actual);
        $this->assertNotNull($created);
        $this->assertEquals($object->customerId, $created->customer_id);
        $this->assertEquals($object->resellerId, $created->reseller_id);
        $this->assertEquals($object->distributorId, $created->distributor_id);
        $this->assertEquals($object->documentNumber, $created->number);
        $this->assertCount(1, $created->statuses);
        $this->assertEquals($this->getStatuses($object), $this->getModelStatuses($created));
        $this->assertEquals('1292.16', $created->price);
        $this->assertNull($this->getDatetime($created->start));
        $this->assertEquals('1614470400000', $this->getDatetime($created->end));
        $this->assertNull($this->getDatetime($created->changed_at));
        $this->assertEquals('HPE', $created->oem->key);
        $this->assertEquals('MultiNational Quote', $created->type->key);
        $this->assertEquals('CUR', $created->currency->code);
        $this->assertEquals('fr', $created->language->code);
        $this->assertEquals('HPE', $created->oem->key);
        $this->assertEquals('1234 4678 9012', $created->oem_said);
        $this->assertEquals('abc-de', $created->oemGroup->key);
        $this->assertEquals(1, $created->assets_count);
        $this->assertEquals(6, $created->entries_count);
        $this->assertEquals(1, $created->contacts_count);
        $this->assertCount($created->entries_count, $created->entries);
        $this->assertCount($created->contacts_count, $created->contacts);

        /** @var \App\Models\DocumentEntry $e */
        $e = $created->entries->first(static function (DocumentEntryModel $entry): bool {
            return $entry->renewal === '145.00';
        });

        $this->assertNotNull($e);
        $this->assertEquals('23.40', $e->net_price);
        $this->assertEquals('48.00', $e->list_price);
        $this->assertEquals('-2.05', $e->discount);
        $this->assertEquals($created->getKey(), $e->document_id);
        $this->assertEquals('c0200a6c-1b8a-4365-9f1b-32d753194335', $e->asset_id);
        $this->assertEquals('H7J34AC', $e->serviceGroup->sku);
        $this->assertEquals('HA151AC', $e->serviceLevel->sku);
        $this->assertEquals('HPE', $e->serviceLevel->oem->key);
        $this->assertEquals('145.00', $e->renewal);

        $this->flushQueryLog();

        // Changed
        // ---------------------------------------------------------------------
        $json     = $this->getTestData()->json('~createFromDocument-document-changed.json');
        $object   = new Document($json);
        $changed  = $factory->createFromDocument($object);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromDocument-document-changed-queries.json');

        $this->assertEquals($expected, $actual);
        $this->assertNotNull($changed);
        $this->assertNull($changed->distributor_id);
        $this->assertEquals('3292.16', $changed->price);
        $this->assertEquals('1625642660000', $this->getDatetime($changed->changed_at));
        $this->assertEquals('EUR', $changed->currency->code);
        $this->assertEquals('en', $changed->language->code);
        $this->assertNull($changed->oem_said);
        $this->assertNull($changed->oemGroup);
        $this->assertCount(0, $changed->statuses);
        $this->assertCount(0, $changed->contacts);
        $this->assertEquals(0, $changed->contacts_count);
        $this->assertEquals(2, $changed->entries_count);
        $this->assertEquals(1, $changed->assets_count);
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

        $this->flushQueryLog();

        // No changes
        // ---------------------------------------------------------------------
        $json     = $this->getTestData()->json('~createFromDocument-document-changed.json');
        $object   = new Document($json);
        $expected = [
            'select * from `assets` where ((`assets`.`id` = ?)) and `assets`.`deleted_at` is null',
            'select * from `oems` where `oems`.`id` in (?) and `oems`.`deleted_at` is null',
            'select * from `assets` where (`assets`.`id` = ?) and `assets`.`deleted_at` is null limit 1',
            'select * from `products` where `products`.`id` = ? and `products`.`deleted_at` is null limit 1',
            'update `documents` set `synced_at` = ?, `documents`.`updated_at` = ? where `id` = ?',
        ];

        $factory->createFromDocument($object);
        $this->assertEquals($expected, array_column($this->getQueryLog(), 'query'));

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
            AssetDocumentObject::class => [
                'createFromAssetDocumentObject',
                static function (TestCase $test): Type {
                    return new AssetDocumentObject([
                        'document' => [
                            'document' => [
                                'id' => $test->faker->uuid,
                            ],
                        ],
                    ]);
                },
            ],
            Document::class            => [
                'createFromDocument',
                static function (TestCase $test): Type {
                    return new Document([
                        'id' => $test->faker->uuid,
                    ]);
                },
            ],
            'Unknown'                  => [
                null,
                static function (TestCase $test): Type {
                    return new class() extends Type {
                        // empty
                    };
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderDocument(): array {
        return [
            Document::class     => [
                static function (TestCase $test): Type {
                    return new Document([
                        'type'                 => $test->faker->word,
                        'vendorSpecificFields' => [
                            'vendor'           => $test->faker->word,
                            'groupId'          => $test->faker->word,
                            'groupDescription' => $test->faker->randomElement([null, $test->faker->sentence]),
                        ],
                    ]);
                },
            ],
            ViewDocument::class => [
                static function (TestCase $test): Type {
                    return new ViewDocument([
                        'type'                 => $test->faker->word,
                        'vendorSpecificFields' => [
                            'vendor'           => $test->faker->word,
                            'groupId'          => $test->faker->word,
                            'groupDescription' => $test->faker->randomElement([null, $test->faker->sentence]),
                        ],
                    ]);
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

    public function documentOemGroup(Document|ViewDocument $document): ?OemGroup {
        return parent::documentOemGroup($document);
    }

    public function documentType(Document|ViewDocument $document): TypeModel {
        return parent::documentType($document);
    }

    public function createFromAssetDocumentObject(AssetDocumentObject $object): ?DocumentModel {
        return parent::createFromAssetDocumentObject($object);
    }

    public function createFromDocument(Document $document): ?DocumentModel {
        return parent::createFromDocument($document);
    }

    public function documentEntryAsset(DocumentModel $model, DocumentEntry $documentEntry): AssetModel {
        return parent::documentEntryAsset($model, $documentEntry);
    }

    public function documentEntryServiceGroup(DocumentModel $model, DocumentEntry $documentEntry): ?ServiceGroup {
        return parent::documentEntryServiceGroup($model, $documentEntry);
    }

    public function documentEntryServiceLevel(DocumentModel $model, DocumentEntry $documentEntry): ?ServiceLevel {
        return parent::documentEntryServiceLevel($model, $documentEntry);
    }
}
