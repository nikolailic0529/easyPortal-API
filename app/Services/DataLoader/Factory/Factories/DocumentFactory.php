<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry as DocumentEntryModel;
use App\Models\OemGroup;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\Status;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\AssetNotFound;
use App\Services\DataLoader\Exceptions\FailedToProcessDocumentEntry;
use App\Services\DataLoader\Exceptions\FailedToProcessDocumentEntryNoAsset;
use App\Services\DataLoader\Factory\AssetDocumentObject;
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
use App\Services\DataLoader\Importer\ImporterChunkData;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\LanguageResolver;
use App\Services\DataLoader\Resolver\Resolvers\OemGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewDocument;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Throwable;

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
    use WithCurrency;
    use WithLanguage;
    use WithContacts;
    use WithReseller;
    use WithCustomer;
    use WithDistributor;
    use WithAssetDocument;

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
        protected CurrencyResolver $currencyResolver,
        protected DocumentResolver $documentResolver,
        protected LanguageResolver $languageResolver,
        protected DistributorResolver $distributorResolver,
        protected ContactFactory $contactFactory,
        protected OemGroupResolver $oemGroupResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
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

    protected function getAssetFinder(): ?AssetFinder {
        return $this->assetFinder;
    }

    protected function getAssetResolver(): AssetResolver {
        return $this->assetResolver;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function create(Type $type): ?DocumentModel {
        $model = null;

        if ($type instanceof Document) {
            $model = $this->createFromDocument($type);
        } elseif ($type instanceof AssetDocumentObject) {
            $model = $this->createFromAssetDocumentObject($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    Document::class,
                    AssetDocumentObject::class,
                ]),
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="AssetDocumentObject">
    // =========================================================================
    /**
     * Create Document without entries.
     *
     * We are not creating entries here because they may be outdated. In this
     * case we will have race conditions with {@see createFromDocument()}.
     * Also, there is no way to delete outdated entries.
     */
    protected function createFromAssetDocumentObject(AssetDocumentObject $object): ?DocumentModel {
        // Document exists?
        if (!isset($object->document->document->id)) {
            return null;
        }

        // Get/Create/Update
        $created = false;
        $factory = $this->factory(function (DocumentModel $model) use (&$created, $object): DocumentModel {
            // Update
            $created    = !$model->exists;
            $document   = $object->document->document;
            $normalizer = $this->getNormalizer();

            // The asset may contain outdated documents so to prevent conflicts
            // we should not update existing documents.
            if ($created) {
                $model->id            = $normalizer->uuid($document->id);
                $model->oem           = $this->documentOem($document);
                $model->oemGroup      = $this->documentOemGroup($document);
                $model->oem_said      = $normalizer->string($document->vendorSpecificFields->said ?? null);
                $model->type          = $this->documentType($document);
                $model->reseller      = $this->reseller($document);
                $model->customer      = $this->customer($document);
                $model->currency      = $this->currency($document->currencyCode);
                $model->language      = $this->language($document->languageCode);
                $model->distributor   = $this->distributor($document);
                $model->start         = $normalizer->datetime($document->startDate);
                $model->end           = $normalizer->datetime($document->endDate);
                $model->price         = $normalizer->decimal($document->totalNetPrice);
                $model->number        = $normalizer->string($document->documentNumber) ?: null;
                $model->changed_at    = $normalizer->datetime($document->updatedAt);
                $model->contacts      = $this->objectContacts($model, (array) $document->contactPersons);
                $model->synced_at     = Date::now();
                $model->assets_count  = 0;
                $model->entries_count = 0;
            }

            // We cannot save document if assets doesn't exist
            if ($object->asset->exists) {
                $model->save();
            }

            // Return
            return $model;
        });
        $model   = $this->documentResolver->get(
            $object->document->document->id,
            static function () use ($factory): DocumentModel {
                return $factory(new DocumentModel());
            },
        );

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($model);
        }

        // Return
        return $model;
    }

    protected function isEntryEqualDocumentEntry(
        DocumentModel $model,
        DocumentEntryModel $entry,
        DocumentEntry $documentEntry,
    ): bool {
        $normalizer = $this->getNormalizer();
        $start      = $normalizer->datetime($documentEntry->startDate);
        $end        = $normalizer->datetime($documentEntry->endDate);
        $isEqual    = $entry->asset_id === $normalizer->uuid($documentEntry->assetId)
            && ($entry->start === $documentEntry->startDate || $entry->start?->isSameDay($start) === true)
            && ($entry->end === $documentEntry->endDate || $entry->end?->isSameDay($end) === true)
            && $entry->currency_id === $this->currency($documentEntry->currencyCode)?->getKey()
            && $entry->net_price === $normalizer->decimal($documentEntry->netPrice)
            && $entry->list_price === $normalizer->decimal($documentEntry->listPrice)
            && $entry->discount === $normalizer->decimal($documentEntry->discount)
            && $entry->renewal === $normalizer->decimal($documentEntry->estimatedValueRenewal)
            && $entry->service_group_id === $this->documentEntryServiceGroup($model, $documentEntry)?->getKey()
            && $entry->service_level_id === $this->documentEntryServiceLevel($model, $documentEntry)?->getKey();

        return $isEqual;
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

            $model->id          = $normalizer->uuid($document->id);
            $model->oem         = $this->documentOem($document);
            $model->oemGroup    = $this->documentOemGroup($document);
            $model->oem_said    = $normalizer->string($document->vendorSpecificFields->said ?? null);
            $model->type        = $this->documentType($document);
            $model->reseller    = $this->reseller($document);
            $model->customer    = $this->customer($document);
            $model->currency    = $this->currency($document->currencyCode);
            $model->language    = $this->language($document->languageCode);
            $model->distributor = $this->distributor($document);
            $model->start       = $normalizer->datetime($document->startDate);
            $model->end         = $normalizer->datetime($document->endDate);
            $model->price       = $normalizer->decimal($document->totalNetPrice);
            $model->number      = $normalizer->string($document->documentNumber) ?: null;
            $model->changed_at  = $normalizer->datetime($document->updatedAt);
            $model->contacts    = $this->objectContacts($model, (array) $document->contactPersons);
            $model->synced_at   = Date::now();
            $model->statuses    = $this->documentStatuses($model, $document);

            // Save
            $model->save();

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
                    (new ImporterChunkData($document->documentEntries))->get(AssetModel::class),
                    static function (EloquentCollection $assets): void {
                        $assets->loadMissing('oem');
                    },
                );

                // Entries
                try {
                    $model->entries   = $this->documentEntries($model, $document);
                    $model->synced_at = Date::now();

                    $model->save();
                } finally {
                    unset($model->entries);
                }

                // Warranties
                // TODO: Not implemented
            } finally {
                $this->getAssetResolver()->reset();
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
     * @return EloquentCollection<array-key, Status>
     */
    protected function documentStatuses(DocumentModel $model, Document $document): EloquentCollection {
        /** @var EloquentCollection<string, Status> $statuses */
        $statuses   = new EloquentCollection();
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
     * @return EloquentCollection<array-key, DocumentEntryModel>
     */
    protected function documentEntries(DocumentModel $model, Document $document): EloquentCollection {
        return $this->children(
            $model->entries,
            $document->documentEntries ?? [],
            function (DocumentEntry $documentEntry, DocumentEntryModel $entry) use ($model): bool {
                return $this->isEntryEqualDocumentEntry($model, $entry, $documentEntry);
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
    }

    protected function documentEntry(
        DocumentModel $model,
        DocumentEntry $documentEntry,
        ?DocumentEntryModel $entry,
    ): DocumentEntryModel {
        $asset                = $this->documentEntryAsset($model, $documentEntry);
        $entry              ??= new DocumentEntryModel();
        $normalizer           = $this->getNormalizer();
        $entry->asset         = $asset;
        $entry->product_id    = $asset->product_id ?? null;
        $entry->serial_number = $asset->serial_number ?? null;
        $entry->start         = $normalizer->datetime($documentEntry->startDate);
        $entry->end           = $normalizer->datetime($documentEntry->endDate);
        $entry->currency      = $this->currency($documentEntry->currencyCode);
        $entry->net_price     = $normalizer->decimal($documentEntry->netPrice);
        $entry->list_price    = $normalizer->decimal($documentEntry->listPrice);
        $entry->discount      = $normalizer->decimal($documentEntry->discount);
        $entry->renewal       = $normalizer->decimal($documentEntry->estimatedValueRenewal);
        $entry->serviceGroup  = $this->documentEntryServiceGroup($model, $documentEntry);
        $entry->serviceLevel  = $this->documentEntryServiceLevel($model, $documentEntry);

        return $entry;
    }

    protected function documentEntryAsset(DocumentModel $model, DocumentEntry $documentEntry): ?AssetModel {
        $asset = null;

        try {
            $asset = $this->asset($documentEntry);

            if (!$asset) {
                $this->getExceptionHandler()->report(
                    new FailedToProcessDocumentEntryNoAsset($model, $documentEntry),
                );
            }
        } catch (AssetNotFound $exception) {
            $this->getExceptionHandler()->report($exception);
        }

        return $asset;
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
        $name  = $documentEntry->serviceLevelSkuDescription ?? null;
        $group = $this->documentEntryServiceGroup($model, $documentEntry);
        $level = null;

        if ($group && $sku && $model->oem) {
            $level = $this->serviceLevel($model->oem, $group, $sku, $name);
        }

        return $level;
    }
    // </editor-fold>
}
