<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Data\ProductGroup;
use App\Models\Data\ProductLine;
use App\Models\Data\Psp;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Status;
use App\Models\Data\Type as TypeModel;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry as DocumentEntryModel;
use App\Models\OemGroup;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Exceptions\AssetNotFound;
use App\Services\DataLoader\Exceptions\FailedToProcessDocumentEntry;
use App\Services\DataLoader\Exceptions\FailedToProcessDocumentEntryNoAsset;
use App\Services\DataLoader\Factory\Concerns\Children;
use App\Services\DataLoader\Factory\Concerns\WithAsset;
use App\Services\DataLoader\Factory\Concerns\WithAssetDocument;
use App\Services\DataLoader\Factory\Concerns\WithContacts;
use App\Services\DataLoader\Factory\Concerns\WithCurrency;
use App\Services\DataLoader\Factory\Concerns\WithCustomer;
use App\Services\DataLoader\Factory\Concerns\WithDistributor;
use App\Services\DataLoader\Factory\Concerns\WithLanguage;
use App\Services\DataLoader\Factory\Concerns\WithOem;
use App\Services\DataLoader\Factory\Concerns\WithOemGroup;
use App\Services\DataLoader\Factory\Concerns\WithProduct;
use App\Services\DataLoader\Factory\Concerns\WithProductGroup;
use App\Services\DataLoader\Factory\Concerns\WithProductLine;
use App\Services\DataLoader\Factory\Concerns\WithPsp;
use App\Services\DataLoader\Factory\Concerns\WithReseller;
use App\Services\DataLoader\Factory\Concerns\WithServiceGroup;
use App\Services\DataLoader\Factory\Concerns\WithServiceLevel;
use App\Services\DataLoader\Factory\Concerns\WithStatus;
use App\Services\DataLoader\Factory\Concerns\WithType;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\LanguageResolver;
use App\Services\DataLoader\Resolver\Resolvers\OemGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductLineResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolver\Resolvers\PspResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Throwable;

use function array_map;
use function implode;
use function sprintf;

/**
 * @extends ModelFactory<DocumentModel>
 */
class DocumentFactory extends ModelFactory {
    use Children;
    use WithOem;
    use WithOemGroup;
    use WithServiceGroup;
    use WithServiceLevel;
    use WithType;
    use WithStatus;
    use WithAsset;
    use WithProduct;
    use WithProductLine;
    use WithProductGroup;
    use WithCurrency;
    use WithLanguage;
    use WithContacts;
    use WithReseller;
    use WithCustomer;
    use WithDistributor;
    use WithAssetDocument;
    use WithPsp;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        protected OemResolver $oemResolver,
        protected TypeResolver $typeResolver,
        protected StatusResolver $statusResolver,
        protected AssetResolver $assetResolver,
        protected ResellerResolver $resellerResolver,
        protected CustomerResolver $customerResolver,
        protected ProductResolver $productResolver,
        protected ProductLineResolver $productLineResolver,
        protected ProductGroupResolver $productGroupResolver,
        protected CurrencyResolver $currencyResolver,
        protected DocumentResolver $documentResolver,
        protected LanguageResolver $languageResolver,
        protected DistributorResolver $distributorResolver,
        protected ContactFactory $contactFactory,
        protected OemGroupResolver $oemGroupResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
        protected PspResolver $pspResolver,
        protected ?DistributorFinder $distributorFinder = null,
        protected ?ResellerFinder $resellerFinder = null,
        protected ?CustomerFinder $customerFinder = null,
        protected ?AssetFinder $assetFinder = null,
    ) {
        parent::__construct($exceptionHandler, $normalizer);
    }

    public function find(Type $type): ?DocumentModel {
        return parent::find($type);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function getDocumentResolver(): DocumentResolver {
        return $this->documentResolver;
    }

    protected function getDistributorResolver(): DistributorResolver {
        return $this->distributorResolver;
    }

    protected function getDistributorFinder(): ?DistributorFinder {
        return $this->distributorFinder;
    }

    protected function getResellerResolver(): ResellerResolver {
        return $this->resellerResolver;
    }

    protected function getResellerFinder(): ?ResellerFinder {
        return $this->resellerFinder;
    }

    protected function getCustomerResolver(): CustomerResolver {
        return $this->customerResolver;
    }

    protected function getCustomerFinder(): ?CustomerFinder {
        return $this->customerFinder;
    }

    protected function getContactsFactory(): ContactFactory {
        return $this->contactFactory;
    }

    protected function getOemResolver(): OemResolver {
        return $this->oemResolver;
    }

    protected function getProductResolver(): ProductResolver {
        return $this->productResolver;
    }

    protected function getProductLineResolver(): ProductLineResolver {
        return $this->productLineResolver;
    }

    protected function getProductGroupResolver(): ProductGroupResolver {
        return $this->productGroupResolver;
    }

    protected function getTypeResolver(): TypeResolver {
        return $this->typeResolver;
    }

    protected function getStatusResolver(): StatusResolver {
        return $this->statusResolver;
    }

    protected function getOemGroupResolver(): OemGroupResolver {
        return $this->oemGroupResolver;
    }

    protected function getServiceGroupResolver(): ServiceGroupResolver {
        return $this->serviceGroupResolver;
    }

    protected function getServiceLevelResolver(): ServiceLevelResolver {
        return $this->serviceLevelResolver;
    }

    protected function getCurrencyResolver(): CurrencyResolver {
        return $this->currencyResolver;
    }

    protected function getLanguageResolver(): LanguageResolver {
        return $this->languageResolver;
    }

    protected function getPspResolver(): PspResolver {
        return $this->pspResolver;
    }

    protected function getAssetFinder(): ?AssetFinder {
        return $this->assetFinder;
    }

    protected function getAssetResolver(): AssetResolver {
        return $this->assetResolver;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function getModel(): string {
        return DocumentModel::class;
    }

    public function create(Type $type): ?DocumentModel {
        $model = null;

        if ($type instanceof Document) {
            $model = $this->createFromDocument($type);
        } elseif ($type instanceof ViewAssetDocument) {
            $model = $this->createFromViewAssetDocument($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    Document::class,
                    ViewAssetDocument::class,
                ]),
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="ViewAssetDocument">
    // =========================================================================
    /**
     * Creates Document with limited properties and without entries.
     *
     * Is some cases (eg for Asset warranties) Document required before full
     * Document data is available. To avoid showing these incomplete documents
     * to users, the method will mark the Document as "deleted" (it will be
     * filled and restored later, when all data is available).
     *
     * Because data is incomplete and/or maybe outdated, the method will not
     * update the existing Document and will not try to create entries.
     */
    protected function createFromViewAssetDocument(ViewAssetDocument $object): ?DocumentModel {
        // Document exists?
        if (!isset($object->document->id)) {
            return null;
        }

        // Get/Create
        $factory = $this->factory(function (DocumentModel $model) use ($object): DocumentModel {
            // Update
            /** @var Collection<int, Status> $statuses */
            $statuses              = new Collection();
            $document              = $object->document;
            $normalizer            = $this->getNormalizer();
            $model->id             = $normalizer->uuid($document->id);
            $model->oem            = $this->documentOem($document);
            $model->oemGroup       = $this->documentOemGroup($document);
            $model->oem_said       = $normalizer->string($document->vendorSpecificFields->said ?? null);
            $model->oem_amp_id     = $normalizer->string($document->vendorSpecificFields->ampId ?? null);
            $model->oem_sar_number = $normalizer->string($document->vendorSpecificFields->sar ?? null);
            $model->type           = $this->documentType($document);
            $model->statuses       = $statuses;
            $model->reseller       = $this->reseller($document);
            $model->customer       = $this->customer($document);
            $model->currency       = $this->currency($document->currencyCode);
            $model->language       = $this->language($document->languageCode);
            $model->distributor    = $this->distributor($document);
            $model->start          = $normalizer->datetime($document->startDate);
            $model->end            = $normalizer->datetime($document->endDate);
            $model->price_origin   = null;
            $model->number         = $normalizer->string($document->documentNumber) ?: null;
            $model->changed_at     = $normalizer->datetime($document->updatedAt);
            $model->contacts       = $this->objectContacts($model, (array) $document->contactPersons);
            $model->deleted_at     = Date::now();
            $model->assets_count   = 0;
            $model->entries_count  = 0;

            $model->save();

            // Return
            return $model;
        });
        $model   = $this->documentResolver->get(
            $object->document->id,
            static function () use ($factory): DocumentModel {
                return $factory(new DocumentModel());
            },
        );

        // Return
        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="Document">
    // =========================================================================
    protected function createFromDocument(Document $document): ?DocumentModel {
        // Get/Create/Update
        $created = false;
        $factory = $this->factory(function (DocumentModel $model) use (&$created, $document): DocumentModel {
            // Update
            $created    = !$model->exists;
            $normalizer = $this->getNormalizer();

            $model->id             = $normalizer->uuid($document->id);
            $model->oem            = $this->documentOem($document);
            $model->oemGroup       = $this->documentOemGroup($document);
            $model->oem_said       = $normalizer->string($document->vendorSpecificFields->said ?? null);
            $model->oem_amp_id     = $normalizer->string($document->vendorSpecificFields->ampId ?? null);
            $model->oem_sar_number = $normalizer->string($document->vendorSpecificFields->sar ?? null);
            $model->type           = $this->documentType($document);
            $model->statuses       = $this->documentStatuses($model, $document);
            $model->reseller       = $this->reseller($document);
            $model->customer       = $this->customer($document);
            $model->currency       = $this->currency($document->currencyCode);
            $model->language       = $this->language($document->languageCode);
            $model->distributor    = $this->distributor($document);
            $model->start          = $normalizer->datetime($document->startDate);
            $model->end            = $normalizer->datetime($document->endDate);
            $model->price_origin   = $normalizer->decimal($document->totalNetPrice);
            $model->number         = $normalizer->string($document->documentNumber) ?: null;
            $model->changed_at     = $normalizer->datetime($document->updatedAt);
            $model->contacts       = $this->objectContacts($model, (array) $document->contactPersons);

            // Save
            if ($model->trashed()) {
                $model->restore();
            } else {
                $model->save();
            }

            // Return
            return $model;
        });
        $model   = $this->documentResolver->get(
            $document->id,
            static function () use ($factory): DocumentModel {
                return $factory(new DocumentModel());
            },
        );

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($model);
        }

        // Entries & Warranties
        if (!$this->isSearchMode() && isset($document->documentEntries)) {
            try {
                // Prefetch
                $this->getAssetResolver()->prefetch(
                    array_map(static fn($entry) => $entry->assetId, $document->documentEntries),
                );

                // Entries
                $model->entries = $this->documentEntries($model, $document);
            } finally {
                $this->getAssetResolver()->reset();

                unset($model->entries);

                $model->save();
            }
        }

        // Return
        return $model;
    }

    protected function documentOemGroup(Document|ViewDocument $document): ?OemGroup {
        $key   = $document->vendorSpecificFields->groupId ?? null;
        $desc  = $document->vendorSpecificFields->groupDescription ?? null;
        $group = null;

        if ($key) {
            $oem   = $this->documentOem($document);
            $group = $oem
                ? $this->oemGroup($oem, $key, (string) $desc)
                : null;
        }

        return $group;
    }

    protected function documentType(Document|ViewDocument $document): ?TypeModel {
        return isset($document->type) && $this->getNormalizer()->string($document->type)
            ? $this->type(new DocumentModel(), $document->type)
            : null;
    }

    /**
     * @return Collection<array-key, Status>
     */
    protected function documentStatuses(DocumentModel $model, Document $document): Collection {
        /** @var Collection<string, Status> $statuses */
        $statuses   = new Collection();
        $normalizer = $this->getNormalizer();

        foreach ($document->status ?? [] as $status) {
            $status = $normalizer->string($status);

            if ($status) {
                $status                 = $this->status($model, $status);
                $statuses[$status->key] = $status;
            }
        }

        return $statuses->values();
    }

    /**
     * @return Collection<array-key, DocumentEntryModel>
     */
    protected function documentEntries(DocumentModel $model, Document $document): Collection {
        // Preload existing entries
        $entries = $model->exists
            ? $model->entries()->withTrashed()->get()
            : $model->entries()->makeMany([]);

        $model->setRelation('entries', $entries);

        // Process
        $entries = $this->children(
            $entries,
            $document->documentEntries ?? [],
            null,
            function (DocumentEntryModel|DocumentEntry $entry): string {
                return $this->getEntryKey($entry);
            },
            function (DocumentEntry $documentEntry, ?DocumentEntryModel $entry) use ($model): ?DocumentEntryModel {
                try {
                    return $this->documentEntry($model, $documentEntry, $entry);
                } catch (Throwable $exception) {
                    $this->getExceptionHandler()->report(
                        new FailedToProcessDocumentEntry($model, $documentEntry, $exception),
                    );
                }

                return null;
            },
        );

        // Return
        return $entries;
    }

    protected function documentEntry(
        DocumentModel $model,
        DocumentEntry $documentEntry,
        ?DocumentEntryModel $entry,
    ): DocumentEntryModel {
        $asset                              = $this->documentEntryAsset($model, $documentEntry);
        $entry                            ??= new DocumentEntryModel();
        $normalizer                         = $this->getNormalizer();
        $entry->uid                         = $this->getEntryKey($documentEntry);
        $entry->document                    = $model;
        $entry->asset                       = $asset;
        $entry->assetType                   = $this->documentEntryAssetType($model, $documentEntry);
        $entry->product_id                  = $asset->product_id ?? null;
        $entry->productLine                 = $this->documentEntryProductLine($model, $documentEntry);
        $entry->productGroup                = $this->documentEntryProductGroup($model, $documentEntry);
        $entry->serial_number               = $asset->serial_number ?? null;
        $entry->start                       = $normalizer->datetime($documentEntry->startDate);
        $entry->end                         = $normalizer->datetime($documentEntry->endDate);
        $entry->currency                    = $this->currency($documentEntry->currencyCode);
        $entry->list_price_origin           = $normalizer->decimal($documentEntry->listPrice);
        $entry->monthly_list_price_origin   = $normalizer->decimal($documentEntry->lineItemListPrice);
        $entry->monthly_retail_price_origin = $normalizer->decimal($documentEntry->lineItemMonthlyRetailPrice);
        $entry->renewal_origin              = $normalizer->decimal($documentEntry->estimatedValueRenewal);
        $entry->oem_said                    = $normalizer->string($documentEntry->said);
        $entry->oem_sar_number              = $normalizer->string($documentEntry->sarNumber);
        $entry->environment_id              = $normalizer->string($documentEntry->environmentId);
        $entry->equipment_number            = $normalizer->string($documentEntry->equipmentNumber);
        $entry->language                    = $this->language($documentEntry->languageCode);
        $entry->serviceGroup                = $this->documentEntryServiceGroup($model, $documentEntry);
        $entry->serviceLevel                = $this->documentEntryServiceLevel($model, $documentEntry);
        $entry->psp                         = $this->documentEntryPsp($model, $documentEntry);
        $entry->removed_at                  = $normalizer->datetime($documentEntry->deletedAt);
        $entry->deleted_at                  = $entry->removed_at
            ? ($entry->deleted_at ?? Date::now())
            : null;

        return $entry;
    }

    protected function documentEntryAsset(DocumentModel $model, DocumentEntry $documentEntry): ?AssetModel {
        $asset = null;

        try {
            $asset = $this->asset($documentEntry);

            if ($asset === null && $this->getNormalizer()->uuid($documentEntry->assetId) !== null) {
                $this->getExceptionHandler()->report(
                    new FailedToProcessDocumentEntryNoAsset($model, $documentEntry),
                );
            }
        } catch (AssetNotFound $exception) {
            $this->getExceptionHandler()->report($exception);
        }

        return $asset;
    }

    protected function documentEntryAssetType(DocumentModel $model, DocumentEntry $documentEntry): ?TypeModel {
        $type = $this->getNormalizer()->string($documentEntry->assetProductType);
        $type = $type
            ? $this->type(new AssetModel(), $type)
            : null;

        return $type;
    }

    protected function documentEntryProductLine(DocumentModel $model, DocumentEntry $documentEntry): ?ProductLine {
        return $this->productLine($documentEntry->assetProductLine);
    }

    protected function documentEntryProductGroup(DocumentModel $model, DocumentEntry $documentEntry): ?ProductGroup {
        return $this->productGroup($documentEntry->assetProductGroupDescription);
    }

    protected function documentEntryPsp(DocumentModel $model, DocumentEntry $documentEntry): ?Psp {
        return $this->psp($documentEntry->pspId, $documentEntry->pspName);
    }

    protected function documentEntryServiceGroup(DocumentModel $model, DocumentEntry $documentEntry): ?ServiceGroup {
        $sku   = $documentEntry->serviceGroupSku ?? null;
        $name  = $documentEntry->serviceGroupSkuDescription ?? null;
        $group = null;

        if ($sku && $model->oem) {
            $group = $this->serviceGroup($model->oem, $sku, $name);
        }

        return $group;
    }

    protected function documentEntryServiceLevel(DocumentModel $model, DocumentEntry $documentEntry): ?ServiceLevel {
        $sku   = $documentEntry->serviceLevelSku ?? null;
        $group = $this->documentEntryServiceGroup($model, $documentEntry);
        $level = null;

        if ($group && $sku && $model->oem) {
            $name  = $documentEntry->serviceLevelSkuDescription ?? null;
            $desc  = $documentEntry->serviceFullDescription ?? null;
            $level = $this->serviceLevel($model->oem, $group, $sku, $name, $desc);
        }

        return $level;
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function getEntryKey(DocumentEntryModel|DocumentEntry $entry): string {
        $normalizer = $this->getNormalizer();
        $key        = null;

        if ($entry instanceof DocumentEntryModel) {
            $key = new Key($normalizer, [
                'key' => $entry->uid,
            ]);
        } elseif (isset($entry->assetDocumentId) && $entry->assetDocumentId) {
            $key = new Key($normalizer, [
                'key' => $entry->assetDocumentId,
            ]);
        } else {
            $key = new Key($normalizer, [
                'asset_id'         => $normalizer->uuid($entry->assetId),
                'start'            => $normalizer->datetime($entry->startDate),
                'end'              => $normalizer->datetime($entry->endDate),
                'service_group_id' => $normalizer->string($entry->serviceGroupSku),
                'service_level_id' => $normalizer->string($entry->serviceLevelSku),
            ]);
        }

        return (string) $key;
    }
    // </editor-fold>
}
