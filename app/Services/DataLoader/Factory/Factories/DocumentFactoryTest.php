<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

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
use App\Services\DataLoader\Factory\AssetDocumentObject;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

use function array_column;
use function is_null;
use function number_format;
use function reset;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Factories\DocumentFactory
 */
class DocumentFactoryTest extends TestCase {
    use WithoutGlobalScopes;
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
            self::expectException(InvalidArgumentException::class);
            self::expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->find($type);

        self::assertCount(1, $this->getQueryLog());
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
            self::expectException(InvalidArgumentException::class);
            self::expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type);
    }

    /**
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObject(): void {
        // Mock
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
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
        ]);

        $this->flushQueryLog();

        // Test
        $created  = $factory->createFromAssetDocumentObject($object);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromAssetDocumentObject-create-expected.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($created);
        self::assertEquals($asset->customerId, $created->customer_id);
        self::assertEquals($asset->resellerId, $created->reseller_id);
        self::assertEquals($object->document->document->distributorId, $created->distributor_id);
        self::assertEquals('0056523287', $created->number);
        self::assertEquals('1292.16', $created->price);
        self::assertNull($this->getDatetime($created->start));
        self::assertEquals('1614470400000', $this->getDatetime($created->end));
        self::assertNull($this->getDatetime($created->changed_at));
        self::assertEquals('HPE', $created->oem->key);
        self::assertEquals('MultiNational Quote', $created->type->key);
        self::assertEquals('CUR', $created->currency->code);
        self::assertEquals('fr', $created->language->code);
        self::assertEquals('HPE', $created->oem->key);
        self::assertEquals('1234 4678 9012', $created->oem_said);
        self::assertEquals('abc-de', $created->oemGroup->key);
        self::assertEquals(0, $created->assets_count);
        self::assertEquals(0, $created->entries_count);
        self::assertEquals(1, $created->contacts_count);
        self::assertCount(0, $created->entries);
        self::assertCount(1, $created->contacts);

        $this->flushQueryLog();

        // Changed
        // ---------------------------------------------------------------------
        $json   = $this->getTestData()->json('~asset-document-changed.json');
        $asset  = new ViewAsset($json);
        $object = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
        ]);

        $factory->createFromAssetDocumentObject($object);

        self::assertCount(0, $this->getQueryLog());

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
        ]);

        self::expectExceptionObject(new FailedToProcessViewAssetDocumentNoDocument(
            $object->asset,
            $object->document,
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
        ]);
        $created = $factory->createFromAssetDocumentObject($object);

        self::assertNotNull($created);
        self::assertNull($created->reseller_id);
        self::assertNull($created->customer_id);
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
        self::assertNotEquals(0, $factory->compareDocumentEntries($a, $b));

        // Make same
        $a->asset_id         = $b->asset_id;
        $a->currency_id      = $b->currency_id;
        $a->net_price        = $b->net_price;
        $a->list_price       = $b->list_price;
        $a->discount         = $b->discount;
        $a->renewal          = $b->renewal;
        $a->service_level_id = $b->service_level_id;
        $a->start            = $b->start;
        $a->end              = $b->end;

        self::assertEquals(0, $factory->compareDocumentEntries($a, $b));
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
        ]);

        // Set property to null
        $object->document->document->contactPersons = null;

        // Test
        $created = $factory->createFromAssetDocumentObject($object);

        self::assertNotNull($created);
        self::assertCount(0, $created->contacts);
    }

    /**
     * @covers ::documentOemGroup
     *
     * @dataProvider dataProviderDocument
     *
     * @template     T of \App\Services\DataLoader\Schema\Document|\App\Services\DataLoader\Schema\ViewDocument
     *
     * @param Closure(static): T $documentFactory
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
     * @param Closure(static): T $documentFactory
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
        self::assertEmpty($factory->documentStatuses($owner, new Document(['status' => null])));

        // Empty
        self::assertEmpty($factory->documentStatuses($owner, new Document(['status' => ['', null]])));

        // Not empty
        $document = new Document([
            'status' => ['a', 'A', 'b'],
        ]);
        $statuses = $factory->documentStatuses($owner, $document);
        $expected = [
            'a' => [
                'key'  => 'a',
                'name' => 'A',
            ],
            'b' => [
                'key'  => 'b',
                'name' => 'B',
            ],
        ];

        self::assertCount(2, $statuses);
        self::assertEquals($expected, $this->statuses($statuses));
    }

    /**
     * @covers ::documentEntry
     */
    public function testDocumentEntry(): void {
        $this->overrideServiceGroupFinder();
        $this->overrideServiceLevelFinder();

        $document       = DocumentModel::factory()->make();
        $asset          = AssetModel::factory()->create([
            'id'            => $this->faker->uuid(),
            'serial_number' => $this->faker->uuid(),
        ]);
        $skuNumber      = $this->faker->word();
        $supportPackage = $this->faker->word();
        $currencyCode   = $this->faker->currencyCode();
        $netPrice       = number_format($this->faker->randomFloat(2), 2, '.', '');
        $discount       = number_format($this->faker->randomFloat(2), 2, '.', '');
        $listPrice      = number_format($this->faker->randomFloat(2), 2, '.', '');
        $renewal        = number_format($this->faker->randomFloat(2), 2, '.', '');
        $start          = Date::make($this->faker->dateTime())->startOfDay();
        $end            = Date::make($this->faker->dateTime())->startOfDay();
        $documentEntry  = new DocumentEntry([
            'assetId'               => " {$asset->getKey()} ",
            'supportPackage'        => " {$supportPackage} ",
            'skuNumber'             => " {$skuNumber} ",
            'netPrice'              => " {$netPrice} ",
            'discount'              => " {$discount} ",
            'listPrice'             => " {$listPrice} ",
            'estimatedValueRenewal' => " {$renewal} ",
            'currencyCode'          => " {$currencyCode} ",
            'startDate'             => $start->format('Y-m-d'),
            'endDate'               => $end->format('Y-m-d'),
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

        self::assertInstanceOf(DocumentEntryModel::class, $entry);
        self::assertEquals($asset->getKey(), $entry->asset_id);
        self::assertNull($entry->document_id);
        self::assertEquals($asset->serial_number, $entry->serial_number);
        self::assertEquals($asset->product, $entry->product);
        self::assertNotNull($entry->service_level_id);
        self::assertEquals($document->oem_id, $entry->serviceLevel->oem_id);
        self::assertEquals($supportPackage, $entry->serviceGroup->sku);
        self::assertEquals($skuNumber, $entry->serviceLevel->sku);
        self::assertEquals($currencyCode, $entry->currency->code);
        self::assertEquals($netPrice, $entry->net_price);
        self::assertEquals($listPrice, $entry->list_price);
        self::assertEquals($discount, $entry->discount);
        self::assertEquals($renewal, $entry->renewal);
        self::assertEquals($start, $entry->start);
        self::assertEquals($end, $entry->end);
    }

    /**
     * @covers ::documentEntry
     */
    public function testDocumentEntrySkuNumberNull(): void {
        $document      = DocumentModel::factory()->make();
        $asset         = AssetModel::factory()->create([
            'id'            => $this->faker->uuid(),
            'serial_number' => $this->faker->uuid(),
        ]);
        $currencyCode  = $this->faker->currencyCode();
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
            'startDate'             => null,
            'endDate'               => null,
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

        self::assertInstanceOf(DocumentEntryModel::class, $entry);
        self::assertEquals($asset->getKey(), $entry->asset_id);
        self::assertNull($entry->document_id);
        self::assertEquals($asset->serial_number, $entry->serial_number);
        self::assertEquals($asset->product, $entry->product);
        self::assertEquals($currencyCode, $entry->currency->code);
        self::assertEquals($netPrice, $entry->net_price);
        self::assertEquals($listPrice, $entry->list_price);
        self::assertEquals($discount, $entry->discount);
        self::assertEquals($renewal, $entry->renewal);
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

        self::assertSame($asset, $factory->documentEntryAsset($model, $entry));
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

        self::expectExceptionObject(new FailedToProcessDocumentEntryNoAsset($model, $entry));

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
            'supportPackage' => $this->faker->word(),
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

        self::assertSame($group, $factory->documentEntryServiceGroup($model, $entry));
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
            'skuNumber' => $this->faker->word(),
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

        self::assertSame($level, $factory->documentEntryServiceLevel($model, $entry));
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
            'start'            => null,
            'end'              => null,
        ];
        [$a, $b]      = [
            DocumentEntryModel::factory()->create($properties),
            DocumentEntryModel::factory()->create($properties),
            DocumentEntryModel::factory()->create($properties),
            DocumentEntryModel::factory()->create($properties),
        ];
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
                    'startDate'             => null,
                    'endDate'               => null,
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
                    'startDate'             => null,
                    'endDate'               => null,
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
                    'startDate'             => null,
                    'endDate'               => null,
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

        self::assertCount(3, $actual);
        self::assertCount(3, $existing);
        self::assertEquals($expected, $existing);
        self::assertNotNull($created);
        self::assertNull($created->list_price);
        self::assertNull($created->net_price);
        self::assertNull($created->discount);
        self::assertNull($created->renewal);
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocument(): void {
        // Mock
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
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

        self::assertEquals($expected, $actual);
        self::assertNotNull($created);
        self::assertEquals($object->customerId, $created->customer_id);
        self::assertEquals($object->resellerId, $created->reseller_id);
        self::assertEquals($object->distributorId, $created->distributor_id);
        self::assertEquals($object->documentNumber, $created->number);
        self::assertCount(1, $created->statuses);
        self::assertEquals($this->getStatuses($object), $this->getModelStatuses($created));
        self::assertEquals('1292.16', $created->price);
        self::assertNull($this->getDatetime($created->start));
        self::assertEquals('1614470400000', $this->getDatetime($created->end));
        self::assertNull($this->getDatetime($created->changed_at));
        self::assertEquals('HPE', $created->oem->key);
        self::assertEquals('MultiNational Quote', $created->type->key);
        self::assertEquals('CUR', $created->currency->code);
        self::assertEquals('fr', $created->language->code);
        self::assertEquals('HPE', $created->oem->key);
        self::assertEquals('1234 4678 9012', $created->oem_said);
        self::assertEquals('abc-de', $created->oemGroup->key);
        self::assertEquals(1, $created->assets_count);
        self::assertEquals(6, $created->entries_count);
        self::assertEquals(1, $created->contacts_count);
        self::assertEquals(1, $created->statuses_count);
        self::assertCount($created->entries_count, $created->entries);
        self::assertCount($created->contacts_count, $created->contacts);
        self::assertCount($created->statuses_count, $created->statuses);

        /** @var DocumentEntryModel $e */
        $e = $created->entries->first(static function (DocumentEntryModel $entry): bool {
            return $entry->renewal === '145.00';
        });

        self::assertNotNull($e);
        self::assertEquals('23.40', $e->net_price);
        self::assertEquals('48.00', $e->list_price);
        self::assertEquals('-2.05', $e->discount);
        self::assertEquals($created->getKey(), $e->document_id);
        self::assertEquals('c0200a6c-1b8a-4365-9f1b-32d753194335', $e->asset_id);
        self::assertEquals('H7J34AC', $e->serviceGroup->sku);
        self::assertEquals('HA151AC', $e->serviceLevel->sku);
        self::assertEquals('HPE', $e->serviceLevel->oem->key);
        self::assertEquals('145.00', $e->renewal);
        self::assertNull($this->getDatetime($e->end));
        self::assertEquals('1614470400000', $this->getDatetime($e->start));

        $this->flushQueryLog();

        // Changed
        // ---------------------------------------------------------------------
        $json     = $this->getTestData()->json('~createFromDocument-document-changed.json');
        $object   = new Document($json);
        $changed  = $factory->createFromDocument($object);
        $actual   = array_column($this->getQueryLog(), 'query');
        $expected = $this->getTestData()->json('~createFromDocument-document-changed-queries.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($changed);
        self::assertNull($changed->distributor_id);
        self::assertEquals('3292.16', $changed->price);
        self::assertEquals('1625642660000', $this->getDatetime($changed->changed_at));
        self::assertEquals('EUR', $changed->currency->code);
        self::assertEquals('en', $changed->language->code);
        self::assertNull($changed->oem_said);
        self::assertNull($changed->oemGroup);
        self::assertCount(0, $changed->statuses);
        self::assertCount(0, $changed->contacts);
        self::assertEquals(0, $changed->contacts_count);
        self::assertEquals(2, $changed->entries_count);
        self::assertEquals(1, $changed->assets_count);
        self::assertEquals(0, $changed->statuses_count);
        self::assertCount(2, $changed->entries);
        self::assertCount(2, $changed->refresh()->entries);
        self::assertCount(0, $changed->statuses);
        self::assertCount(0, $changed->refresh()->statuses);

        /** @var DocumentEntryModel $e */
        $e = $changed->entries->first(static function (DocumentEntryModel $entry): bool {
            return is_null($entry->renewal);
        });

        self::assertNotNull($e);
        self::assertNull($e->net_price);
        self::assertNull($e->list_price);
        self::assertNull($e->discount);
        self::assertNull($e->renewal);

        $this->flushQueryLog();

        // No changes
        // ---------------------------------------------------------------------
        $json     = $this->getTestData()->json('~createFromDocument-document-changed.json');
        $object   = new Document($json);
        $expected = [
            'update `documents` set `synced_at` = ?, `documents`.`updated_at` = ? where `id` = ?',
            'select `assets`.* from `assets` where ((`assets`.`id` = ?)) and `assets`.`deleted_at` is null',
            'select `oems`.* from `oems` where `oems`.`id` in (?) and `oems`.`deleted_at` is null',
            'update `documents` set `synced_at` = ?, `documents`.`updated_at` = ? where `id` = ?',
        ];

        $factory->createFromDocument($object);
        self::assertEquals($expected, array_column($this->getQueryLog(), 'query'));

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
                                'id' => $test->faker->uuid(),
                            ],
                        ],
                    ]);
                },
            ],
            Document::class            => [
                'createFromDocument',
                static function (TestCase $test): Type {
                    return new Document([
                        'id' => $test->faker->uuid(),
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
                        'type'                 => $test->faker->word(),
                        'vendorSpecificFields' => [
                            'vendor'           => $test->faker->word(),
                            'groupId'          => $test->faker->word(),
                            'groupDescription' => $test->faker->randomElement([null, $test->faker->sentence()]),
                        ],
                    ]);
                },
            ],
            ViewDocument::class => [
                static function (TestCase $test): Type {
                    return new ViewDocument([
                        'type'                 => $test->faker->word(),
                        'vendorSpecificFields' => [
                            'vendor'           => $test->faker->word(),
                            'groupId'          => $test->faker->word(),
                            'groupDescription' => $test->faker->randomElement([null, $test->faker->sentence()]),
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
