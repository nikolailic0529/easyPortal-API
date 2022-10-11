<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Asset;
use App\Models\Data\Oem;
use App\Models\Data\ProductGroup;
use App\Models\Data\ProductLine;
use App\Models\Data\Psp;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Type as TypeModel;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry as DocumentEntryModel;
use App\Models\OemGroup;
use App\Services\DataLoader\Exceptions\FailedToProcessDocumentEntryNoAsset;
use App\Services\DataLoader\Factory\AssetDocumentObject;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Resolver\Resolvers\LanguageResolver;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductLineResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolver\Resolvers\PspResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;
use Throwable;

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
        $queries = $this->getQueryLog()->flush();

        if (!$expected) {
            self::expectException(InvalidArgumentException::class);
            self::expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->find($type);

        self::assertCount(1, $queries);
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
        $model  = Asset::factory()->create([
            'id' => $asset->id,
        ]);
        $object = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
        ]);

        // Test
        $queries  = $this->getQueryLog()->flush();
        $created  = $factory->createFromAssetDocumentObject($object);
        $actual   = array_column($queries->get(), 'query');
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
        self::assertEquals('HPE', $created->oem->key ?? null);
        self::assertEquals('MultiNational Quote', $created->type->key ?? null);
        self::assertEquals('CUR', $created->currency->code ?? null);
        self::assertEquals('fr', $created->language->code ?? null);
        self::assertEquals('1234 4678 9012', $created->oem_said);
        self::assertEquals('abc-de', $created->oemGroup->key ?? null);
        self::assertEquals(0, $created->assets_count);
        self::assertEquals(0, $created->entries_count);
        self::assertEquals(1, $created->contacts_count);
        self::assertCount(0, $created->entries);
        self::assertCount(1, $created->contacts);

        // Changed
        // ---------------------------------------------------------------------
        $json    = $this->getTestData()->json('~asset-document-changed.json');
        $asset   = new ViewAsset($json);
        $object  = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
        ]);
        $queries = $this->getQueryLog()->flush();

        $factory->createFromAssetDocumentObject($object);

        self::assertCount(0, $queries);
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
        $model  = Asset::factory()->create([
            'id' => $asset->id,
        ]);
        $object = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
        ]);

        self::assertNull($factory->createFromAssetDocumentObject($object));
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
        $model   = Asset::factory()->create([
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
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObjectTypeNull(): void {
        // Mock
        $this->overrideFinders();

        // Factory
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);

        // Create
        // ---------------------------------------------------------------------
        $json    = $this->getTestData()->json('~asset-document-type-null.json');
        $asset   = new ViewAsset($json);
        $model   = Asset::factory()->create([
            'id' => $asset->id,
        ]);
        $object  = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
        ]);
        $created = $factory->createFromAssetDocumentObject($object);

        self::assertNotNull($created);
        self::assertNull($created->type_id);
    }

    /**
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObjectTrashed(): void {
        // Mock
        $this->overrideFinders();

        // Prepare
        $factory  = $this->app->make(DocumentFactoryTest_Factory::class);
        $json     = $this->getTestData()->json('~asset-document-full.json');
        $asset    = new ViewAsset($json);
        $model    = Asset::factory()->create([
            'id' => $asset->id,
        ]);
        $object   = new AssetDocumentObject([
            'asset'    => $model,
            'document' => reset($asset->assetDocument),
        ]);
        $document = DocumentModel::factory()->create([
            'id' => $object->document->document->id ?? null,
        ]);

        self::assertTrue($document->delete());
        self::assertTrue($document->trashed());

        // Test
        $created = $factory->createFromAssetDocumentObject($object);

        self::assertNotNull($created);
        self::assertTrue($created->trashed());
    }

    /**
     * @covers ::isEntryEqualDocumentEntry
     */
    public function testIsEntryEqualDocumentEntry(): void {
        // Prepare
        $oem      = Oem::factory()->create();
        $group    = ServiceGroup::factory()->create([
            'oem_id' => $oem,
        ]);
        $level    = ServiceLevel::factory()->create([
            'oem_id'           => $oem,
            'service_group_id' => $group,
        ]);
        $document = DocumentModel::factory()->create([
            'oem_id' => $oem,
        ]);
        $entry    = DocumentEntryModel::factory()->create([
            'document_id'      => $document,
            'service_group_id' => $group,
            'service_level_id' => $level,
        ]);
        $factory  = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(CurrencyResolver::class),
            $this->app->make(ServiceGroupResolver::class),
            $this->app->make(ServiceLevelResolver::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected CurrencyResolver $currencyResolver,
                protected ServiceGroupResolver $serviceGroupResolver,
                protected ServiceLevelResolver $serviceLevelResolver,
            ) {
                // empty
            }

            public function isEntryEqualDocumentEntry(
                DocumentModel $model,
                DocumentEntryModel $entry,
                DocumentEntry $documentEntry,
            ): bool {
                return parent::isEntryEqualDocumentEntry($model, $entry, $documentEntry);
            }
        };

        // Test
        self::assertTrue($factory->isEntryEqualDocumentEntry($document, $entry, new DocumentEntry([
            'assetId'                    => $entry->asset_id,
            'startDate'                  => (string) $entry->start?->getTimestampMs(),
            'endDate'                    => (string) $entry->end?->getTimestampMs(),
            'currencyCode'               => $entry->currency->code ?? null,
            'listPrice'                  => $entry->list_price,
            'estimatedValueRenewal'      => $entry->renewal,
            'serviceGroupSku'            => $entry->serviceGroup->sku ?? null,
            'serviceLevelSku'            => $entry->serviceLevel->sku ?? null,
            'lineItemListPrice'          => $entry->monthly_list_price,
            'lineItemMonthlyRetailPrice' => $entry->monthly_retail_price,
            'equipmentNumber'            => $entry->equipment_number,
        ])));
        self::assertFalse($factory->isEntryEqualDocumentEntry($document, $entry, new DocumentEntry([
            'assetId'                    => $this->faker->uuid(),
            'startDate'                  => (string) $entry->start?->getTimestampMs(),
            'endDate'                    => (string) $entry->end?->getTimestampMs(),
            'currencyCode'               => $entry->currency->code ?? null,
            'listPrice'                  => $entry->list_price,
            'estimatedValueRenewal'      => $entry->renewal,
            'serviceGroupSku'            => $entry->serviceGroup->sku ?? null,
            'serviceLevelSku'            => $entry->serviceLevel->sku ?? null,
            'lineItemListPrice'          => $entry->monthly_list_price,
            'lineItemMonthlyRetailPrice' => $entry->monthly_retail_price,
            'equipmentNumber'            => $entry->equipment_number,
        ])));
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
        $model   = Asset::factory()->create([
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
     * @template T of \App\Services\DataLoader\Schema\Document|\App\Services\DataLoader\Schema\ViewDocument
     *
     * @param Closure(static): T $documentFactory
     */
    public function testDocumentType(Closure $documentFactory): void {
        $normalizer = $this->app->make(Normalizer::class);
        $document   = $documentFactory($this);
        $factory    = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        if ($document->type) {
            $factory
                ->shouldReceive('getNormalizer')
                ->once()
                ->andReturn($normalizer);
            $factory
                ->shouldReceive('type')
                ->with(Mockery::any(), $document->type)
                ->once()
                ->andReturns();
        } else {
            $factory
                ->shouldReceive('getNormalizer')
                ->never();
            $factory
                ->shouldReceive('type')
                ->never();
        }

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

            public function documentStatuses(DocumentModel $model, Document $document): EloquentCollection {
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
        $document           = DocumentModel::factory()->make();
        $psp                = Psp::factory()->create([
            'key' => $this->faker->uuid(),
        ]);
        $asset              = Asset::factory()->create([
            'id'            => $this->faker->uuid(),
            'serial_number' => $this->faker->uuid(),
        ]);
        $assetType          = TypeModel::factory()->create([
            'key' => $this->faker->word(),
        ]);
        $productLine        = ProductLine::factory()->create([
            'key' => $this->faker->word(),
        ]);
        $productGroup       = ProductGroup::factory()->create([
            'key' => $this->faker->word(),
        ]);
        $serviceLevelSku    = $this->faker->word();
        $serviceGroupSku    = $this->faker->word();
        $currencyCode       = $this->faker->currencyCode();
        $listPrice          = number_format($this->faker->randomFloat(2), 2, '.', '');
        $renewal            = number_format($this->faker->randomFloat(2), 2, '.', '');
        $start              = Date::make($this->faker->dateTime())?->startOfDay();
        $end                = Date::make($this->faker->dateTime())?->startOfDay();
        $said               = $this->faker->word();
        $sarNumber          = $this->faker->word();
        $monthlyListPrice   = number_format($this->faker->randomFloat(2), 2, '.', '');
        $monthlyRetailPrice = number_format($this->faker->randomFloat(2), 2, '.', '');
        $environmentId      = $this->faker->word();
        $equipmentNumber    = $this->faker->word();
        $language           = $this->faker->languageCode();
        $documentEntry      = new DocumentEntry([
            'assetId'                      => " {$asset->getKey()} ",
            'assetProductType'             => " {$assetType->key} ",
            'assetProductLine'             => " {$productLine->key} ",
            'assetProductGroupDescription' => " {$productGroup->key} ",
            'serviceGroupSku'              => " {$serviceGroupSku} ",
            'serviceLevelSku'              => " {$serviceLevelSku} ",
            'listPrice'                    => " {$listPrice} ",
            'estimatedValueRenewal'        => " {$renewal} ",
            'currencyCode'                 => " {$currencyCode} ",
            'startDate'                    => $start?->format('Y-m-d'),
            'endDate'                      => $end?->format('Y-m-d'),
            'lineItemListPrice'            => " {$monthlyListPrice} ",
            'lineItemMonthlyRetailPrice'   => " {$monthlyRetailPrice} ",
            'said'                         => " {$said} ",
            'sarNumber'                    => " {$sarNumber} ",
            'environmentId'                => " {$environmentId} ",
            'equipmentNumber'              => " {$equipmentNumber} ",
            'languageCode'                 => " {$language} ",
            'pspId'                        => " {$psp->key} ",
            'pspName'                      => " {$psp->name} ",
        ]);

        $factory = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(TypeResolver::class),
            $this->app->make(LanguageResolver::class),
            $this->app->make(AssetResolver::class),
            $this->app->make(ProductResolver::class),
            $this->app->make(ProductLineResolver::class),
            $this->app->make(ProductGroupResolver::class),
            $this->app->make(OemResolver::class),
            $this->app->make(CurrencyResolver::class),
            $this->app->make(ServiceGroupResolver::class),
            $this->app->make(ServiceLevelResolver::class),
            $this->app->make(PspResolver::class),
        ) extends DocumentFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected TypeResolver $typeResolver,
                protected LanguageResolver $languageResolver,
                protected AssetResolver $assetResolver,
                protected ProductResolver $productResolver,
                protected ProductLineResolver $productLineResolver,
                protected ProductGroupResolver $productGroupResolver,
                protected OemResolver $oemResolver,
                protected CurrencyResolver $currencyResolver,
                protected ServiceGroupResolver $serviceGroupResolver,
                protected ServiceLevelResolver $serviceLevelResolver,
                protected PspResolver $pspResolver,
            ) {
                // empty
            }

            public function documentEntry(
                DocumentModel $model,
                DocumentEntry $documentEntry,
                ?DocumentEntryModel $entry,
            ): DocumentEntryModel {
                return parent::documentEntry($model, $documentEntry, $entry);
            }
        };

        $entry = $factory->documentEntry($document, $documentEntry, null);

        self::assertEquals($asset->getKey(), $entry->asset_id);
        self::assertEquals($assetType->key, $entry->assetType->key ?? null);
        self::assertEquals((new Asset())->getMorphClass(), $entry->assetType->object_type ?? null);
        self::assertEquals($document->getKey(), $entry->document_id);
        self::assertEquals($asset->serial_number, $entry->serial_number);
        self::assertEquals($asset->product, $entry->product);
        self::assertEquals($productLine->getKey(), $entry->product_line_id);
        self::assertEquals($productGroup->getKey(), $entry->product_group_id);
        self::assertNotNull($entry->service_level_id);
        self::assertEquals($document->oem_id, $entry->serviceLevel->oem_id ?? null);
        self::assertEquals($serviceGroupSku, $entry->serviceGroup->sku ?? null);
        self::assertEquals($serviceLevelSku, $entry->serviceLevel->sku ?? null);
        self::assertEquals($currencyCode, $entry->currency->code ?? null);
        self::assertEquals($listPrice, $entry->list_price);
        self::assertEquals($renewal, $entry->renewal);
        self::assertEquals($start, $entry->start);
        self::assertEquals($end, $entry->end);
        self::assertEquals($monthlyListPrice, $entry->monthly_list_price);
        self::assertEquals($monthlyRetailPrice, $entry->monthly_retail_price);
        self::assertEquals($said, $entry->oem_said);
        self::assertEquals($sarNumber, $entry->oem_sar_number);
        self::assertEquals($environmentId, $entry->environment_id);
        self::assertEquals($equipmentNumber, $entry->equipment_number);
        self::assertEquals($language, $entry->language->code ?? null);
        self::assertEquals($psp->getKey(), $entry->psp_id);
        self::assertEquals($psp->key, $entry->psp->key ?? null);
        self::assertEquals($psp->name, $entry->psp->name ?? null);
    }

    /**
     * @covers ::documentEntryAsset
     */
    public function testDocumentEntryAsset(): void {
        $asset = Asset::factory()->make();
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

        $handler = Mockery::mock(ExceptionHandler::class);
        $handler
            ->shouldReceive('report')
            ->withArgs(static function (Throwable $error) use ($model, $entry): bool {
                return $error instanceof FailedToProcessDocumentEntryNoAsset
                    && $error->getDocument() === $model
                    && $error->getEntry() === $entry;
            })
            ->once()
            ->andReturns();

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getExceptionHandler')
            ->once()
            ->andReturn($handler);
        $factory
            ->shouldReceive('asset')
            ->with($entry)
            ->once()
            ->andReturn(null);

        $factory->documentEntryAsset($model, $entry);
    }

    /**
     * @covers ::documentEntryAssetType
     */
    public function testDocumentEntryAssetType(): void {
        $type       = TypeModel::factory()->make();
        $model      = DocumentModel::factory()->create();
        $entry      = new DocumentEntry([
            'assetProductType' => $type->key,
        ]);
        $normalizer = $this->app->make(Normalizer::class);
        $factory    = Mockery::mock(DocumentFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getNormalizer')
            ->once()
            ->andReturn($normalizer);
        $factory
            ->shouldReceive('type')
            ->with(Mockery::any(), $type->key)
            ->once()
            ->andReturn($type);

        self::assertNotNull(
            $factory->documentEntryAssetType($model, $entry),
        );
    }

    /**
     * @covers ::documentEntryProductLine
     */
    public function testDocumentEntryProductLine(): void {
        $line    = ProductLine::factory()->make();
        $model   = DocumentModel::factory()->create();
        $entry   = new DocumentEntry([
            'assetProductLine' => $line->key,
        ]);
        $factory = Mockery::mock(DocumentFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('productLine')
            ->with($line->key)
            ->once()
            ->andReturn($line);

        self::assertNotNull(
            $factory->documentEntryProductLine($model, $entry),
        );
    }

    /**
     * @covers ::documentEntryPsp
     */
    public function testDocumentEntryPsp(): void {
        $psp     = Psp::factory()->make();
        $model   = DocumentModel::factory()->create();
        $entry   = new DocumentEntry([
            'pspId'   => $psp->key,
            'pspName' => $psp->name,
        ]);
        $factory = Mockery::mock(DocumentFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('psp')
            ->with($psp->key, $psp->name)
            ->once()
            ->andReturn($psp);

        self::assertNotNull(
            $factory->documentEntryPsp($model, $entry),
        );
    }

    /**
     * @covers ::documentEntryProductGroup
     */
    public function testDocumentEntryProductGroup(): void {
        $line    = ProductGroup::factory()->make();
        $model   = DocumentModel::factory()->create();
        $entry   = new DocumentEntry([
            'assetProductGroupDescription' => $line->key,
        ]);
        $factory = Mockery::mock(DocumentFactory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('productGroup')
            ->with($line->key)
            ->once()
            ->andReturn($line);

        self::assertNotNull(
            $factory->documentEntryProductGroup($model, $entry),
        );
    }

    /**
     * @covers ::documentEntryServiceGroup
     */
    public function testDocumentEntryServiceGroup(): void {
        $oem   = Oem::factory()->make();
        $group = ServiceGroup::factory()->make();
        $model = DocumentModel::factory()->create()->setRelation('oem', $oem);
        $entry = new DocumentEntry([
            'serviceGroupSku'            => $this->faker->word(),
            'serviceGroupSkuDescription' => $this->faker->word(),
        ]);

        $factory = Mockery::mock(DocumentFactoryTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('serviceGroup')
            ->with(
                $oem,
                $entry->serviceGroupSku,
                $entry->serviceGroupSkuDescription,
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
            'serviceLevelSku'            => $this->faker->word(),
            'serviceLevelSkuDescription' => $this->faker->word(),
            'serviceFullDescription'     => $this->faker->sentence(),
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
                $entry->serviceLevelSku,
                $entry->serviceLevelSkuDescription,
                $entry->serviceFullDescription,
            )
            ->once()
            ->andReturn($level);

        self::assertSame($level, $factory->documentEntryServiceLevel($model, $entry));
    }

    /**
     * @covers ::documentEntries
     */
    public function testDocumentEntries(): void {
        // Prepare
        $assetA       = Asset::factory()->create();
        $assetB       = Asset::factory()->create();
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
                'vendor' => $document->oem->key ?? null,
            ],
            'documentEntries'      => [
                [
                    'assetId'                      => $a->asset_id,
                    'serviceLevelSku'              => $a->serviceLevel->sku ?? null,
                    'serviceGroupSku'              => $a->serviceGroup->sku ?? null,
                    'currencyCode'                 => $a->currency->code ?? null,
                    'listPrice'                    => $a->list_price,
                    'estimatedValueRenewal'        => $a->renewal,
                    'startDate'                    => $a->start,
                    'endDate'                      => $a->end,
                    'assetProductType'             => $a->assetType->key ?? null,
                    'assetProductLine'             => $a->productLine->key ?? null,
                    'assetProductGroupDescription' => $a->productGroup->key ?? null,
                    'lineItemListPrice'            => $a->monthly_list_price,
                    'lineItemMonthlyRetailPrice'   => $a->monthly_retail_price,
                    'said'                         => $a->oem_said,
                    'sarNumber'                    => $a->oem_sar_number,
                    'environmentId'                => $a->environment_id,
                    'equipmentNumber'              => $a->equipment_number,
                    'languageCode'                 => $a->language->code ?? null,
                    'pspId'                        => $a->psp->key ?? null,
                    'pspName'                      => $a->psp->name ?? null,
                ],
                [
                    'assetId'                      => $b->asset_id,
                    'serviceLevelSku'              => $b->serviceLevel->sku ?? null,
                    'serviceGroupSku'              => $b->serviceGroup->sku ?? null,
                    'currencyCode'                 => $b->currency->code ?? null,
                    'listPrice'                    => $b->list_price,
                    'estimatedValueRenewal'        => $b->renewal,
                    'startDate'                    => $b->start,
                    'endDate'                      => $b->end,
                    'assetProductType'             => $b->assetType->key ?? null,
                    'assetProductLine'             => $b->productLine->key ?? null,
                    'assetProductGroupDescription' => $b->productGroup->key ?? null,
                    'lineItemListPrice'            => $b->monthly_list_price,
                    'lineItemMonthlyRetailPrice'   => $b->monthly_retail_price,
                    'said'                         => $b->oem_said,
                    'sarNumber'                    => $b->oem_sar_number,
                    'environmentId'                => $b->environment_id,
                    'equipmentNumber'              => $b->equipment_number,
                    'languageCode'                 => $b->language->code ?? null,
                    'pspId'                        => $b->psp->key ?? null,
                    'pspName'                      => $b->psp->name ?? null,
                ],
                [
                    'assetId'                      => $assetB->getKey(),
                    'serviceLevelSku'              => $b->serviceLevel->sku ?? null,
                    'serviceGroupSku'              => $b->serviceGroup->sku ?? null,
                    'currencyCode'                 => null,
                    'listPrice'                    => null,
                    'estimatedValueRenewal'        => null,
                    'startDate'                    => null,
                    'endDate'                      => null,
                    'assetProductType'             => null,
                    'assetProductLine'             => null,
                    'assetProductGroupDescription' => null,
                    'lineItemListPrice'            => null,
                    'lineItemMonthlyRetailPrice'   => null,
                    'said'                         => null,
                    'sarNumber'                    => null,
                    'environmentId'                => null,
                    'equipmentNumber'              => null,
                    'languageCode'                 => null,
                    'pspId'                        => null,
                    'pspName'                      => null,
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
            ) {
                // empty
            }

            public function documentEntries(DocumentModel $model, Document $document): EloquentCollection {
                return parent::documentEntries($model, $document);
            }
        };

        // Test
        $actual   = $factory->documentEntries($document, $object);
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
            ->map(static function (DocumentEntryModel $entry): string {
                return $entry->getKey();
            })
            ->sort()
            ->values();

        self::assertCount(3, $actual);
        self::assertCount(3, $existing);
        self::assertEquals($expected, $existing);
        self::assertNotNull($created);
        self::assertNull($created->list_price);
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
        $this->overrideUuidFactory('6c3e24b8-c5f5-42e4-acc0-a66b0f424af3');

        // Factory
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);

        // Create
        // ---------------------------------------------------------------------
        $json   = $this->getTestData()->json('~createFromDocument-document-full.json');
        $object = new Document($json);

        // Test
        $queries  = $this->getQueryLog()->flush();
        $created  = $factory->createFromDocument($object);
        $actual   = array_column($queries->get(), 'query');
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
        self::assertEquals('HPE', $created->oem->key ?? null);
        self::assertEquals('MultiNational Quote', $created->type->key ?? null);
        self::assertEquals('CUR', $created->currency->code ?? null);
        self::assertEquals('fr', $created->language->code ?? null);
        self::assertEquals('1234 4678 9012', $created->oem_said);
        self::assertEquals('abc-de', $created->oemGroup->key ?? null);
        self::assertEquals(3, $created->assets_count);
        self::assertEquals(8, $created->entries_count);
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
        self::assertEquals('48.00', $e->list_price);
        self::assertEquals($created->getKey(), $e->document_id);
        self::assertEquals('c0200a6c-1b8a-4365-9f1b-32d753194335', $e->asset_id);
        self::assertEquals('H7J34AC', $e->serviceGroup->sku ?? null);
        self::assertEquals('HPE NBD w DMR Proactive Care SVC', $e->serviceGroup->name ?? null);
        self::assertEquals('HA151AC', $e->serviceLevel->sku ?? null);
        self::assertEquals('HPE Hardware Maintenance Onsite Support', $e->serviceLevel->name ?? null);
        self::assertEquals('HPE', $e->serviceLevel->oem->key ?? null);
        self::assertEquals('145.00', $e->renewal);
        self::assertNull($this->getDatetime($e->end));
        self::assertEquals('1614470400000', $this->getDatetime($e->start));
        self::assertEquals('Hardware', $e->assetType->key ?? null);
        self::assertEquals('45.00', $e->monthly_list_price);
        self::assertEquals('55.00', $e->monthly_retail_price);
        self::assertNull($e->oem_said);
        self::assertEquals('SAR#1', $e->oem_sar_number);
        self::assertNull($e->environment_id);
        self::assertNull($e->equipment_number);
        self::assertEquals('en', $e->language->code ?? null);
        self::assertNotNull($e->psp);
        self::assertEquals('c0200a6c-1b8a-4365-9f1b-32d753194335', $e->psp->key);
        self::assertEquals('PSP#A', $e->psp->name);

        // Changed
        // ---------------------------------------------------------------------
        $json     = $this->getTestData()->json('~createFromDocument-document-changed.json');
        $object   = new Document($json);
        $queries  = $this->getQueryLog()->flush();
        $changed  = $factory->createFromDocument($object);
        $actual   = array_column($queries->get(), 'query');
        $expected = $this->getTestData()->json('~createFromDocument-document-changed-queries.json');

        self::assertEquals($expected, $actual);
        self::assertNotNull($changed);
        self::assertNull($changed->distributor_id);
        self::assertEquals('3292.16', $changed->price);
        self::assertEquals('1625642660000', $this->getDatetime($changed->changed_at));
        self::assertEquals('EUR', $changed->currency->code ?? null);
        self::assertEquals('en', $changed->language->code ?? null);
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

        /** @var DocumentEntryModel|null $e */
        $e = $changed->entries->first(static function (DocumentEntryModel $entry): bool {
            return is_null($entry->renewal);
        });

        self::assertNotNull($e);
        self::assertNull($e->list_price);
        self::assertNull($e->renewal);

        // No changes
        // ---------------------------------------------------------------------
        $json    = $this->getTestData()->json('~createFromDocument-document-changed.json');
        $object  = new Document($json);
        $queries = $this->getQueryLog()->flush();

        $factory->createFromDocument($object);

        $actual   = array_column($queries->get(), 'query');
        $expected = $this->getTestData()->json('~createFromDocument-document-unchanged-queries.json');

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocumentTypeNull(): void {
        // Mock
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
        $this->overrideFinders();
        $this->overrideAssetFinder();

        // Factory
        $factory = $this->app->make(DocumentFactoryTest_Factory::class);

        // Create
        // ---------------------------------------------------------------------
        $json   = $this->getTestData()->json('~createFromDocument-document-type-null.json');
        $object = new Document($json);

        // Test
        $created = $factory->createFromDocument($object);

        self::assertNotNull($created);
        self::assertNull($created->type_id);
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocumentTrashed(): void {
        // Mock
        $this->overrideFinders();

        // Prepare
        $factory  = $this->app->make(DocumentFactoryTest_Factory::class);
        $json     = $this->getTestData()->json('~createFromDocument-document-type-null.json');
        $object   = new Document($json);
        $document = DocumentModel::factory()->create([
            'id' => $object->id,
        ]);

        self::assertTrue($document->delete());
        self::assertTrue($document->trashed());

        // Test
        $created = $factory->createFromDocument($object);

        self::assertNotNull($created);
        self::assertFalse($created->trashed());
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
            Document::class                    => [
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
            Document::class.' (type null)'     => [
                static function (TestCase $test): Type {
                    return new Document([
                        'type'                 => null,
                        'vendorSpecificFields' => [
                            'vendor'           => $test->faker->word(),
                            'groupId'          => $test->faker->word(),
                            'groupDescription' => $test->faker->randomElement([null, $test->faker->sentence()]),
                        ],
                    ]);
                },
            ],
            ViewDocument::class                => [
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
            ViewDocument::class.' (type null)' => [
                static function (TestCase $test): Type {
                    return new ViewDocument([
                        'type'                 => null,
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

    public function documentType(Document|ViewDocument $document): ?TypeModel {
        return parent::documentType($document);
    }

    public function createFromAssetDocumentObject(AssetDocumentObject $object): ?DocumentModel {
        return parent::createFromAssetDocumentObject($object);
    }

    public function createFromDocument(Document $document): ?DocumentModel {
        return parent::createFromDocument($document);
    }

    public function documentEntryAsset(DocumentModel $model, DocumentEntry $documentEntry): ?Asset {
        return parent::documentEntryAsset($model, $documentEntry);
    }

    public function documentEntryServiceGroup(DocumentModel $model, DocumentEntry $documentEntry): ?ServiceGroup {
        return parent::documentEntryServiceGroup($model, $documentEntry);
    }

    public function documentEntryServiceLevel(DocumentModel $model, DocumentEntry $documentEntry): ?ServiceLevel {
        return parent::documentEntryServiceLevel($model, $documentEntry);
    }
}
