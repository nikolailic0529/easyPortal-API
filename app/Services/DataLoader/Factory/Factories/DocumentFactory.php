<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Asset;
use App\Models\Asset as AssetModel;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry as DocumentEntryModel;
use App\Models\OemGroup;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\Status;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\FailedToProcessDocumentEntry;
use App\Services\DataLoader\Exceptions\FailedToProcessDocumentEntryNoAsset;
use App\Services\DataLoader\Exceptions\FailedToProcessViewAssetDocument;
use App\Services\DataLoader\Exceptions\FailedToProcessViewAssetDocumentNoDocument;
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
use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
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
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Throwable;

use function array_merge;
use function implode;
use function sprintf;

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
        protected AssetFactory $assetFactory,
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
        protected ?ServiceGroupFinder $serviceGroupFinder = null,
        protected ?ServiceLevelFinder $serviceLevelFinder = null,
        protected ?OemFinder $oemFinder = null,
    ) {
        parent::__construct($exceptionHandler, $normalizer);
    }

    public function find(Type $type): ?DocumentModel {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
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

    protected function getOemFinder(): ?OemFinder {
        return $this->oemFinder;
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

    protected function getServiceGroupFinder(): ?ServiceGroupFinder {
        return $this->serviceGroupFinder;
    }

    protected function getServiceLevelResolver(): ServiceLevelResolver {
        return $this->serviceLevelResolver;
    }

    protected function getServiceLevelFinder(): ?ServiceLevelFinder {
        return $this->serviceLevelFinder;
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

    protected function getAssetFactory(): ?AssetFactory {
        return $this->assetFactory;
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
    protected function createFromAssetDocumentObject(AssetDocumentObject $object): ?DocumentModel {
        // Document exists?
        if (!isset($object->document->document->id)) {
            throw new FailedToProcessViewAssetDocumentNoDocument(
                $object->asset,
                $object->document,
                new Collection($object->entries),
            );
        }

        // Get/Create/Update
        $created = false;
        $factory = $this->factory(function (DocumentModel $model) use (&$created, $object): DocumentModel {
            // Update
            $created    = !$model->exists;
            $normalizer = $this->getNormalizer();
            $document   = $object->document->document;
            $changedAt  = $normalizer->datetime($document->updatedAt);

            // The asset may contain outdated documents so to prevent conflicts
            // we should update properties only if the document is new or
            // freshest.
            if ($created || $model->changed_at <= $changedAt) {
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
                $model->price       = $normalizer->number($document->totalNetPrice);
                $model->number      = $normalizer->string($document->documentNumber);
                $model->changed_at  = $changedAt;
                $model->contacts    = $this->objectContacts($model, (array) $document->contactPersons);
                $model->synced_at   = Date::now();
            }

            // Entries should be updated always because they related to the Asset
            $model->entries = $this->assetDocumentObjectEntries($model, $object);

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

    /**
     * @return array<\App\Models\DocumentEntry>
     */
    protected function assetDocumentObjectEntries(DocumentModel $model, AssetDocumentObject $document): array {
        // AssetDocumentObject contains entries only for related Asset thus we
        // must not touch other entries.

        // Separate asset's entries
        $all      = [];
        $existing = [];
        $assetId  = $document->asset->getKey();

        if ($model->exists) {
            foreach ($model->entries as $entry) {
                /** @var \App\Models\DocumentEntry $entry */
                if ($entry->asset_id === $assetId) {
                    $existing[] = $entry;
                } else {
                    $all[] = $entry;
                }
            }
        }

        // Update entries:
        $entries = $this->entries(
            new Collection($existing),
            $document->entries,
            function (ViewAssetDocument $entry) use ($model, $document): ?DocumentEntryModel {
                try {
                    return $this->assetDocumentEntry($document->asset, $model, $entry);
                } catch (Throwable $exception) {
                    $this->getExceptionHandler()->report(
                        new FailedToProcessViewAssetDocument($document->asset, $entry, $exception),
                    );
                }

                return null;
            },
        );
        $all     = array_merge($all, $entries);

        // Return
        return $all;
    }

    protected function assetDocumentEntry(
        AssetModel $asset,
        DocumentModel $document,
        ViewAssetDocument $assetDocument,
    ): DocumentEntryModel {
        $entry                = new DocumentEntryModel();
        $normalizer           = $this->getNormalizer();
        $entry->asset         = $asset;
        $entry->product       = $asset->product;
        $entry->serial_number = $asset->serial_number;
        $entry->currency      = $this->currency($assetDocument->currencyCode);
        $entry->net_price     = $normalizer->number($assetDocument->netPrice);
        $entry->list_price    = $normalizer->number($assetDocument->listPrice);
        $entry->discount      = $normalizer->number($assetDocument->discount);
        $entry->renewal       = $normalizer->number($assetDocument->estimatedValueRenewal);
        $entry->serviceGroup  = $this->assetDocumentServiceGroup($asset, $assetDocument);
        $entry->serviceLevel  = $this->assetDocumentServiceLevel($asset, $assetDocument);

        return $entry;
    }

    protected function compareDocumentEntries(DocumentEntryModel $a, DocumentEntryModel $b): int {
        return $a->asset_id <=> $b->asset_id
            ?: $a->currency_id <=> $b->currency_id
            ?: $a->net_price <=> $b->net_price
            ?: $a->list_price <=> $b->list_price
            ?: $a->discount <=> $b->discount
            ?: $a->renewal <=> $b->renewal
            ?: $a->service_group_id <=> $b->service_group_id
            ?: $a->service_level_id <=> $b->service_level_id
            ?: 0;
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
            $model->price       = $normalizer->number($document->totalNetPrice);
            $model->number      = $normalizer->string($document->documentNumber);
            $model->changed_at  = $normalizer->datetime($document->updatedAt);
            $model->contacts    = $this->objectContacts($model, (array) $document->contactPersons);
            $model->statuses    = $this->documentStatuses($model, $document);
            $model->synced_at   = Date::now();

            // Entries & Warranties
            if (isset($document->documentEntries)) {
                try {
                    // Prefetch
                    $this->getAssetResolver()->prefetch(
                        (new ImporterChunkData($document->documentEntries))->get(Asset::class),
                        static function (Collection $assets): void {
                            $assets->loadMissing('oem');
                        },
                    );

                    // Entries
                    try {
                        $model->entries = $this->documentEntries($model, $document);

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

        // Return
        return $model;
    }

    protected function documentOemGroup(Document|ViewDocument $document): ?OemGroup {
        $key   = $document->vendorSpecificFields->groupId ?? null;
        $desc  = $document->vendorSpecificFields->groupDescription ?? null;
        $group = null;

        if ($key) {
            $oem   = $this->documentOem($document);
            $group = $this->oemGroup($oem, $key, (string) $desc);
        }

        return $group;
    }

    protected function documentType(Document|ViewDocument $document): TypeModel {
        return $this->type(new DocumentModel(), $document->type);
    }

    /**
     * @return array<\App\Models\Status>
     */
    protected function documentStatuses(DocumentModel $model, Document $document): array {
        return (new Collection($document->status ?? []))
            ->filter(function (?string $status): bool {
                return (bool) $this->getNormalizer()->string($status);
            })
            ->map(function (string $status) use ($model): Status {
                return $this->status($model, $status);
            })
            ->unique()
            ->all();
    }

    /**
     * @return array<\App\Models\DocumentEntry>
     */
    protected function documentEntries(DocumentModel $model, Document $document): array {
        return $this->entries(
            $model->entries,
            $document->documentEntries,
            function (DocumentEntry $entry) use ($model): ?DocumentEntryModel {
                try {
                    return $this->documentEntry($model, $entry);
                } catch (Throwable $exception) {
                    $this->getExceptionHandler()->report(
                        new FailedToProcessDocumentEntry($model, $entry, $exception),
                    );
                }

                return null;
            },
        );
    }

    protected function documentEntry(DocumentModel $model, DocumentEntry $documentEntry): DocumentEntryModel {
        $asset                = $this->documentEntryAsset($model, $documentEntry);
        $entry                = new DocumentEntryModel();
        $normalizer           = $this->getNormalizer();
        $entry->asset         = $asset;
        $entry->product       = $asset->product;
        $entry->serial_number = $asset->serial_number;
        $entry->currency      = $this->currency($documentEntry->currencyCode);
        $entry->net_price     = $normalizer->number($documentEntry->netPrice);
        $entry->list_price    = $normalizer->number($documentEntry->listPrice);
        $entry->discount      = $normalizer->number($documentEntry->discount);
        $entry->renewal       = $normalizer->number($documentEntry->estimatedValueRenewal);
        $entry->serviceGroup  = $this->documentEntryServiceGroup($model, $documentEntry);
        $entry->serviceLevel  = $this->documentEntryServiceLevel($model, $documentEntry);

        return $entry;
    }

    protected function documentEntryAsset(DocumentModel $model, DocumentEntry $documentEntry): AssetModel {
        $asset = $this->asset($documentEntry);

        if (!$asset) {
            throw new FailedToProcessDocumentEntryNoAsset($model, $documentEntry);
        }

        return $asset;
    }

    protected function documentEntryServiceGroup(DocumentModel $model, DocumentEntry $documentEntry): ?ServiceGroup {
        $sku   = $documentEntry->supportPackage ?? null;
        $group = null;

        if ($sku) {
            $group = $this->serviceGroup($model->oem, $sku);
        }

        return $group;
    }

    protected function documentEntryServiceLevel(DocumentModel $model, DocumentEntry $documentEntry): ?ServiceLevel {
        $sku   = $documentEntry->skuNumber ?? null;
        $group = $this->documentEntryServiceGroup($model, $documentEntry);
        $level = null;

        if ($group && $sku) {
            $level = $this->serviceLevel($model->oem, $group, $sku);
        }

        return $level;
    }
    // </editor-fold>

    // <editor-fold desc="Entries">
    // =========================================================================
    /**
     * @template T of \App\Services\DataLoader\Schema\Type
     * @template M of \App\Models\DocumentEntry
     *
     * @param \Illuminate\Support\Collection<M> $existing
     * @param array<T>                          $entries
     * @param \Closure(T): ?M                   $factory
     *
     * @return array<M>
     */
    protected function entries(Collection $existing, array $entries, Closure $factory): array {
        return $this
            ->children(
                $existing,
                $entries,
                $factory,
                function (DocumentEntryModel $a, DocumentEntryModel $b): int {
                    return $this->compareDocumentEntries($a, $b);
                },
            )
            ->all();
    }
    // </editor-fold>
}