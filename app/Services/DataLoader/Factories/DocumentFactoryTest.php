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
use App\Services\DataLoader\Exceptions\ViewAssetDocumentNoDocument;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolvers\ServiceLevelResolver;
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
        $document = new AssetDocumentObject([
            'document' => [
                'document' => [
                    'id' => $this->faker->uuid,
                ],
            ],
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
        // Mock
        $this->overrideFinders();

        // Factory
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);

        // Create
        // ---------------------------------------------------------------------
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
        $created = $factory->createFromAssetDocumentObject($object);

        $this->assertNotNull($created);
        $this->assertEquals($asset->customerId, $created->customer_id);
        $this->assertEquals($asset->resellerId, $created->reseller_id);
        $this->assertEquals($object->document->document->distributorId, $created->distributor_id);
        $this->assertEquals('0056523287', $created->number);
        $this->assertEquals('1292.16', $created->price);
        $this->assertNull($this->getDatetime($created->start));
        $this->assertEquals('1614470400000', $this->getDatetime($created->end));
        $this->assertNull($this->getDatetime($created->changed_at));
        $this->assertEquals('HPE', $created->oem->abbr);
        $this->assertEquals('MultiNational Quote', $created->type->key);
        $this->assertEquals('CUR', $created->currency->code);
        $this->assertEquals('fr', $created->language->code);
        $this->assertEquals('H7J34AC', $created->serviceGroup->sku);
        $this->assertEquals('HPE', $created->serviceGroup->oem->abbr);
        $this->assertEquals('HPE', $created->oem->abbr);
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
        $this->assertEquals('HA151AC', $e->serviceLevel->sku);
        $this->assertEquals('HPE', $e->serviceLevel->oem->abbr);
        $this->assertEquals('145.00', $e->renewal);

        // Changed
        // ---------------------------------------------------------------------
        $json    = $this->getTestData()->json('~asset-document-changed.json');
        $asset   = new ViewAsset($json);
        $object  = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
            'entries'  => $asset->assetDocument,
        ]);
        $changed = $factory->createFromAssetDocumentObject($object);

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

        $this->expectExceptionObject(new ViewAssetDocumentNoDocument($object->document));

        $factory->createFromAssetDocumentObject($object);
    }

    /**
     * @covers ::assetDocumentObjectServiceGroup
     */
    public function testAssetDocumentObjectServiceGroup(): void {
        $group    = ServiceGroup::factory()->make();
        $asset    = AssetModel::factory()->make();
        $document = new AssetDocumentObject([
            'asset'    => $asset,
            'document' => [
                'documentNumber' => $this->faker->word,
            ],
        ]);
        $factory  = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('assetDocumentServiceGroup')
            ->with($asset, $document->document)
            ->once()
            ->andReturn($group);

        $this->assertSame($group, $factory->assetDocumentObjectServiceGroup($document));
    }

    /**
     * @covers ::assetDocumentOem
     */
    public function testAssetDocumentOem(): void {
        $oem      = Oem::factory()->make();
        $asset    = AssetModel::factory()->make();
        $document = new ViewAssetDocument([
            'document' => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word,
                ],
            ],
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('documentOem')
            ->with($document->document)
            ->once()
            ->andReturn($oem);

        $this->assertSame($oem, $factory->assetDocumentOem($asset, $document));
    }

    /**
     * @covers ::assetDocumentOem
     */
    public function testAssetDocumentOemNoDocument(): void {
        $oem        = Oem::factory()->make();
        $asset      = AssetModel::factory()->make();
        $asset->oem = $oem;
        $document   = new ViewAssetDocument([
            'document' => null,
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('documentOem')
            ->never();

        $this->assertSame($oem, $factory->assetDocumentOem($asset, $document));
    }

    /**
     * @covers ::assetDocumentServiceGroup
     */
    public function testAssetDocumentServiceGroup(): void {
        $oem      = Oem::factory()->make();
        $asset    = AssetModel::factory()->make();
        $group    = ServiceGroup::factory()->make();
        $document = new ViewAssetDocument([
            'document'       => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word,
                ],
            ],
            'supportPackage' => $this->faker->word,
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('assetDocumentOem')
            ->with($asset, $document)
            ->once()
            ->andReturn($oem);
        $factory
            ->shouldReceive('serviceGroup')
            ->with(
                $oem,
                $document->supportPackage,
            )
            ->once()
            ->andReturns($group);

        $this->assertSame($group, $factory->assetDocumentServiceGroup($asset, $document));
    }

    /**
     * @covers ::assetDocumentServiceLevel
     */
    public function testAssetDocumentServiceLevel(): void {
        $oem      = Oem::factory()->make();
        $asset    = AssetModel::factory()->make();
        $group    = ServiceGroup::factory()->make();
        $level    = ServiceLevel::factory()->make();
        $document = new ViewAssetDocument([
            'document'  => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word,
                ],
            ],
            'skuNumber' => $this->faker->word,
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('assetDocumentOem')
            ->with($asset, $document)
            ->once()
            ->andReturn($oem);
        $factory
            ->shouldReceive('assetDocumentServiceGroup')
            ->with(
                $asset,
                $document,
            )
            ->once()
            ->andReturns($group);
        $factory
            ->shouldReceive('serviceLevel')
            ->with(
                $oem,
                $group,
                $document->skuNumber,
            )
            ->once()
            ->andReturns($level);

        $this->assertSame($level, $factory->assetDocumentServiceLevel($asset, $document));
    }

    /**
     * @covers ::assetDocumentObjectEntries
     */
    public function testAssetDocumentObjectEntries(): void {
        // Mock
        $this->overrideServiceGroupFinder();
        $this->overrideServiceLevelFinder();

        // Prepare
        $asset      = AssetModel::factory()->create();
        $document   = DocumentModel::factory()->create([
            'service_group_id' => static function (array $args): ServiceGroup {
                return ServiceGroup::factory()->create([
                    'oem_id' => $args['oem_id'],
                ]);
            },
        ]);
        $another    = DocumentEntryModel::factory(2)->create([
            'document_id' => $document,
            'product_id'  => $asset->product_id,
        ]);
        $properties = [
            'document_id'      => $document,
            'asset_id'         => $asset,
            'product_id'       => $asset->product_id,
            'service_level_id' => static function () use ($document): ServiceLevel {
                return ServiceLevel::factory()->create([
                    'oem_id'           => $document->oem_id,
                    'service_group_id' => $document->serviceGroup,
                ]);
            },
        ];
        $a          = DocumentEntryModel::factory()->create($properties);
        $b          = DocumentEntryModel::factory()->create($properties);
        $c          = DocumentEntryModel::factory()->create($properties);
        $d          = DocumentEntryModel::factory()->create($properties);
        $object     = new AssetDocumentObject([
            'asset'   => $asset,
            'entries' => [
                [
                    'skuNumber'             => $a->serviceLevel->sku,
                    'supportPackage'        => $document->serviceGroup->sku,
                    'currencyCode'          => $a->currency->code,
                    'netPrice'              => $a->net_price,
                    'discount'              => $a->discount,
                    'listPrice'             => $a->list_price,
                    'estimatedValueRenewal' => $a->renewal,
                    'document'              => [
                        'vendorSpecificFields' => [
                            'vendor' => $document->oem->abbr,
                        ],
                    ],
                ],
                [
                    'skuNumber'             => $b->serviceLevel->sku,
                    'supportPackage'        => $document->serviceGroup->sku,
                    'currencyCode'          => $a->currency->code,
                    'netPrice'              => $b->net_price,
                    'discount'              => $b->discount,
                    'listPrice'             => $b->list_price,
                    'estimatedValueRenewal' => $b->renewal,
                    'document'              => [
                        'vendorSpecificFields' => [
                            'vendor' => $document->oem->abbr,
                        ],
                    ],
                ],
                [
                    'skuNumber'             => $b->serviceLevel->sku,
                    'supportPackage'        => $document->serviceGroup->sku,
                    'currencyCode'          => null,
                    'netPrice'              => null,
                    'discount'              => null,
                    'listPrice'             => null,
                    'estimatedValueRenewal' => null,
                    'document'              => [
                        'vendorSpecificFields' => [
                            'vendor' => $document->oem->abbr,
                        ],
                    ],
                ],
            ],
        ]);
        $factory    = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyFactory::class),
            $this->app->make(ServiceGroupResolver::class),
            $this->app->make(ServiceLevelResolver::class),
            $this->app->make(ServiceGroupFinder::class),
            $this->app->make(ServiceLevelFinder::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductResolver $products,
                protected OemResolver $oems,
                protected CurrencyFactory $currencies,
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
                    'vendor' => $document->oem->abbr,
                ],
            ],
        ]);
        $factory        = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyFactory::class),
            $this->app->make(ServiceGroupResolver::class),
            $this->app->make(ServiceLevelResolver::class),
            $this->app->make(ServiceGroupFinder::class),
            $this->app->make(ServiceLevelFinder::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ProductResolver $products,
                protected OemResolver $oems,
                protected CurrencyFactory $currencies,
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
     * @covers ::documentOem
     */
    public function testDocumentOem(): void {
        $document = new ViewDocument([
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
     * @covers ::documentOemGroup
     */
    public function testDocumentOemGroup(): void {
        $oem      = Oem::factory()->make();
        $document = new ViewDocument([
            'vendorSpecificFields' => [
                'vendor'           => $this->faker->word,
                'groupId'          => $this->faker->word,
                'groupDescription' => $this->faker->randomElement([null, $this->faker->sentence]),
            ],
        ]);
        $factory  = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('documentOem')
            ->with($document)
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
     */
    public function testDocumentType(): void {
        $document = new ViewDocument([
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
                new ViewAsset([
                    'assetDocument' => [$a, $b],
                ]),
                new ViewAsset([
                    'assetDocument' => [$c],
                ]),
                new ViewAsset([
                    // should pass
                ]),
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

    public function documentOem(ViewDocument $document): Oem {
        return parent::documentOem($document);
    }

    public function documentOemGroup(ViewDocument $document): ?OemGroup {
        return parent::documentOemGroup($document);
    }

    public function documentType(ViewDocument $document): TypeModel {
        return parent::documentType($document);
    }

    public function assetDocumentObjectServiceGroup(AssetDocumentObject $document): ?ServiceGroup {
        return parent::assetDocumentObjectServiceGroup($document);
    }

    public function assetDocumentOem(AssetModel $asset, ViewAssetDocument $assetDocument): Oem {
        return parent::assetDocumentOem($asset, $assetDocument);
    }

    public function assetDocumentServiceGroup(AssetModel $asset, ViewAssetDocument $assetDocument): ?ServiceGroup {
        return parent::assetDocumentServiceGroup($asset, $assetDocument);
    }

    public function assetDocumentServiceLevel(AssetModel $asset, ViewAssetDocument $assetDocument): ?ServiceLevel {
        return parent::assetDocumentServiceLevel($asset, $assetDocument);
    }

    public function createFromAssetDocumentObject(AssetDocumentObject $object): ?DocumentModel {
        return parent::createFromAssetDocumentObject($object);
    }
}
