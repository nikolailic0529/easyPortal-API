<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Data\Coverage;
use App\Models\Data\Location;
use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Models\Data\Status;
use App\Models\Data\Type as TypeModel;
use App\Models\Document;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Exceptions\AssetLocationNotFound;
use App\Services\DataLoader\Exceptions\FailedToCreateAssetWarranty;
use App\Services\DataLoader\Exceptions\FailedToProcessAssetViewDocument;
use App\Services\DataLoader\Exceptions\FailedToProcessViewAssetCoverageEntry;
use App\Services\DataLoader\Factory\Concerns\Children;
use App\Services\DataLoader\Factory\Concerns\WithAssetDocument;
use App\Services\DataLoader\Factory\Concerns\WithContacts;
use App\Services\DataLoader\Factory\Concerns\WithCoverage;
use App\Services\DataLoader\Factory\Concerns\WithCustomer;
use App\Services\DataLoader\Factory\Concerns\WithOem;
use App\Services\DataLoader\Factory\Concerns\WithProduct;
use App\Services\DataLoader\Factory\Concerns\WithReseller;
use App\Services\DataLoader\Factory\Concerns\WithServiceGroup;
use App\Services\DataLoader\Factory\Concerns\WithServiceLevel;
use App\Services\DataLoader\Factory\Concerns\WithStatus;
use App\Services\DataLoader\Factory\Concerns\WithTag;
use App\Services\DataLoader\Factory\Concerns\WithType;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\CoverageResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TagResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\CoverageEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use Throwable;

use function array_filter;
use function max;
use function sprintf;
use function usort;

/**
 * @extends ModelFactory<Asset>
 */
class AssetFactory extends ModelFactory {
    use Children;
    use WithReseller;
    use WithCustomer;
    use WithOem;
    use WithType;
    use WithProduct;
    use WithStatus;
    use WithContacts;
    use WithTag;
    use WithCoverage;
    use WithServiceGroup;
    use WithServiceLevel;
    use WithAssetDocument;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        protected AssetResolver $assetResolver,
        protected OemResolver $oemResolver,
        protected TypeResolver $typeResolver,
        protected ProductResolver $productResolver,
        protected CustomerResolver $customerResolver,
        protected ResellerResolver $resellerResolver,
        protected DocumentResolver $documentResolver,
        protected DocumentFactory $documentFactory,
        protected LocationFactory $locationFactory,
        protected ContactFactory $contactFactory,
        protected StatusResolver $statusResolver,
        protected CoverageResolver $coverageResolver,
        protected TagResolver $tagResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
        protected ?ResellerFinder $resellerFinder = null,
        protected ?CustomerFinder $customerFinder = null,
    ) {
        parent::__construct($exceptionHandler, $normalizer);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
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

    protected function getDocumentFactory(): DocumentFactory {
        return $this->documentFactory;
    }

    protected function getDocumentResolver(): DocumentResolver {
        return $this->documentResolver;
    }

    protected function getCoverageResolver(): CoverageResolver {
        return $this->coverageResolver;
    }

    protected function getStatusResolver(): StatusResolver {
        return $this->statusResolver;
    }

    protected function getOemResolver(): OemResolver {
        return $this->oemResolver;
    }

    protected function getProductResolver(): ProductResolver {
        return $this->productResolver;
    }

    protected function getTagResolver(): TagResolver {
        return $this->tagResolver;
    }

    protected function getTypeResolver(): TypeResolver {
        return $this->typeResolver;
    }

    protected function getServiceGroupResolver(): ServiceGroupResolver {
        return $this->serviceGroupResolver;
    }

    protected function getServiceLevelResolver(): ServiceLevelResolver {
        return $this->serviceLevelResolver;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function getModel(): string {
        return Asset::class;
    }

    public function create(Type $type): ?Asset {
        $model = null;

        if ($type instanceof ViewAsset) {
            $model = $this->createFromAsset($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                ViewAsset::class,
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromAsset(ViewAsset $asset): ?Asset {
        return $this->assetAsset($asset);
    }

    protected function assetAsset(ViewAsset $asset): Asset {
        // Get/Create
        $created = false;
        $factory = function (Asset $model) use (&$created, $asset): Asset {
            // Asset
            $created                          = !$model->exists;
            $normalizer                       = $this->getNormalizer();
            $model->id                        = $asset->id;
            $model->oem                       = $this->assetOem($asset);
            $model->type                      = $this->assetType($asset);
            $model->status                    = $this->assetStatus($asset);
            $model->product                   = $this->assetProduct($asset);
            $model->reseller                  = $this->reseller($asset);
            $model->customer                  = $this->customer($asset);
            $model->location                  = $this->assetLocation($asset);
            $model->eosl                      = $normalizer->datetime($asset->eosDate);
            $model->changed_at                = $normalizer->datetime($asset->updatedAt);
            $model->serial_number             = $normalizer->string($asset->serialNumber);
            $model->data_quality              = $normalizer->string($asset->dataQualityScore);
            $model->contracts_active_quantity = $normalizer->int($asset->activeContractQuantitySum);
            $model->contacts                  = $this->objectContacts($model, (array) $asset->latestContactPersons);
            $model->tags                      = $this->assetTags($asset);
            $model->coverages                 = $this->assetCoverages($asset);

            // Warranties
            if ($created) {
                $model->setRelation('warranties', new EloquentCollection());
            }

            $warrantyChangedAt          = $asset->coverageStatusCheck->coverageStatusUpdatedAt ?? null;
            $warrantyChangedAt          = $normalizer->datetime($warrantyChangedAt);
            $model->warranties          = $this->assetWarranties($model, $asset);
            $model->warranty_changed_at = max($warrantyChangedAt, $model->warranty_changed_at);

            // Save
            if ($model->trashed()) {
                $model->restore();
            } else {
                $model->save();
            }

            // Cleanup
            unset($model->warranties);

            // Return
            return $model;
        };
        $model   = $this->assetResolver->get(
            $asset->id,
            static function () use ($factory): Asset {
                return $factory(new Asset());
            },
        );

        // Update
        if (!$created) {
            $factory($model);
        }

        // Return
        return $model;
    }

    /**
     * @return EloquentCollection<int, AssetWarranty>
     */
    protected function assetWarranties(Asset $model, ViewAsset $asset): EloquentCollection {
        // Split by source (ViewAssetDocument or CoverageEntry)
        /** @var EloquentCollection<int, AssetWarranty> $warranties */
        $warranties = new EloquentCollection();
        $coverages  = clone $warranties;
        $documents  = clone $warranties;

        foreach ($model->warranties as $warranty) {
            if ($warranty->isExtended()) {
                $documents[] = $warranty;
            } else {
                $coverages[] = $warranty;
            }
        }

        // Normal
        if (isset($asset->coverageStatusCheck)) {
            $warrantyChangedAt = $this->getNormalizer()->datetime($asset->coverageStatusCheck->coverageStatusUpdatedAt);

            if ($model->warranty_changed_at <= $warrantyChangedAt) {
                $coverages = $this->assetWarrantiesCoverages($model, $asset, $coverages);
            }
        }

        // Documents
        if (isset($asset->assetDocument)) {
            $documents = $this->assetWarrantiesDocuments($model, $asset, $documents);
        }

        // Return
        return $warranties
            ->merge($coverages)
            ->merge($documents);
    }

    /**
     * @param EloquentCollection<int, AssetWarranty> $existing
     *
     * @return EloquentCollection<int, AssetWarranty>
     */
    protected function assetWarrantiesCoverages(
        Asset $model,
        ViewAsset $asset,
        EloquentCollection $existing,
    ): EloquentCollection {
        return $this->children(
            $existing,
            $asset->coverageStatusCheck->coverageEntries ?? [],
            null,
            function (AssetWarranty|CoverageEntry $warranty): string {
                return $this->getWarrantyKey($warranty);
            },
            function (CoverageEntry $entry, ?AssetWarranty $warranty) use ($model): ?AssetWarranty {
                try {
                    return $this->assetWarranty($model, $entry, $warranty);
                } catch (Throwable $exception) {
                    $this->getExceptionHandler()->report(
                        new FailedToProcessViewAssetCoverageEntry($model, $entry, $exception),
                    );
                }

                return null;
            },
        );
    }

    protected function assetWarranty(Asset $model, CoverageEntry $entry, ?AssetWarranty $warranty): ?AssetWarranty {
        // Empty?
        $normalizer  = $this->getNormalizer();
        $description = $normalizer->text($entry->description);
        $start       = $normalizer->datetime($entry->coverageStartDate);
        $end         = $normalizer->datetime($entry->coverageEndDate);

        if ($description === null && $start === null && $end === null) {
            return null;
        }

        // Create
        $warranty                ??= new AssetWarranty();
        $warranty->key             = $this->getWarrantyKey($entry);
        $warranty->start           = $start;
        $warranty->end             = $end;
        $warranty->asset           = $model;
        $warranty->type            = $this->type($warranty, $entry->type);
        $warranty->status          = $this->status($warranty, $entry->status);
        $warranty->description     = $description;
        $warranty->serviceGroup    = null;
        $warranty->serviceLevel    = null;
        $warranty->customer        = null;
        $warranty->reseller        = null;
        $warranty->document        = null;
        $warranty->document_number = null;

        // Return
        return $warranty;
    }

    /**
     * @return array<int, ViewAssetDocument>
     */
    protected function assetDocuments(Asset $model, ViewAsset $asset): array {
        // Asset.assetDocument is not a document but an array of entries where
        // each entry is the mixin of Document, DocumentEntry, and additional
        // information (that is not available in Document and DocumentEntry)
        $documents = array_filter($asset->assetDocument, static function (ViewAssetDocument $document): bool {
            return isset($document->documentNumber)
                && $document->deletedAt === null;
        });

        usort($documents, static function (ViewAssetDocument $a, ViewAssetDocument $b): int {
            return $a->startDate <=> $b->startDate
                ?: $a->endDate <=> $b->endDate;
        });

        // Prefetch
        $resolver = $this->getDocumentResolver();
        $keys     = [];

        foreach ($documents as $assetDocument) {
            if (isset($assetDocument->document->id)) {
                $keys[$assetDocument->document->id] = $assetDocument->document->id;
            }
        }

        $resolver->prefetch($keys, static function (EloquentCollection $documents): void {
            $documents->loadMissing('statuses');
        });

        // Return
        return $documents;
    }

    /**
     * @param EloquentCollection<int, AssetWarranty> $existing
     *
     * @return EloquentCollection<array-key, AssetWarranty>
     */
    protected function assetWarrantiesDocuments(
        Asset $model,
        ViewAsset $asset,
        EloquentCollection $existing,
    ): EloquentCollection {
        $created    = [];
        $documents  = $this->assetDocuments($model, $asset);
        $warranties = $this->children(
            $existing,
            $documents,
            null,
            function (AssetWarranty|ViewAssetDocument $warranty): string {
                return $this->getWarrantyKey($warranty);
            },
            function (
                ViewAssetDocument $assetDocument,
                ?AssetWarranty $warranty,
            ) use (
                $model,
                &$created,
            ): ?AssetWarranty {
                try {
                    // Valid?
                    $normalizer = $this->getNormalizer();
                    $number     = $assetDocument->documentNumber;
                    $group      = $this->assetDocumentServiceGroup($model, $assetDocument);
                    $level      = $this->assetDocumentServiceLevel($model, $assetDocument);
                    $start      = $normalizer->datetime(
                        $assetDocument->startDate ?? $assetDocument->document->startDate ?? null,
                    );
                    $end        = $normalizer->datetime(
                        $assetDocument->endDate ?? $assetDocument->document->endDate ?? null,
                    );

                    if (!($number && ($start !== null || $end !== null) && ($group !== null || $level !== null))) {
                        return null;
                    }

                    // Already added?
                    $key = $this->getWarrantyKey($assetDocument);

                    if (isset($created[$key])) {
                        $created[$key]->start = max($created[$key]->start, $start);
                        $created[$key]->end   = max($created[$key]->end, $end);

                        return null;
                    }

                    // Create/Update
                    $warranty                ??= new AssetWarranty();
                    $warranty->key             = $key;
                    $warranty->start           = $start;
                    $warranty->end             = $end;
                    $warranty->asset           = $model;
                    $warranty->type            = null;
                    $warranty->status          = null;
                    $warranty->description     = null;
                    $warranty->serviceGroup    = $group;
                    $warranty->serviceLevel    = $level;
                    $warranty->customer        = $this->customer($assetDocument);
                    $warranty->reseller        = $this->reseller($assetDocument);
                    $warranty->document        = $this->assetDocumentDocument($model, $assetDocument);
                    $warranty->document_number = $number;

                    // Store
                    $created[$key] = $warranty;

                    // Return
                    return $warranty;
                } catch (Throwable $exception) {
                    $this->getExceptionHandler()->report(
                        new FailedToCreateAssetWarranty($model, $assetDocument, $exception),
                    );
                }

                return null;
            },
        );

        return $warranties;
    }

    protected function assetDocumentDocument(Asset $model, ViewAssetDocument $assetDocument): ?Document {
        $document = null;

        if (isset($assetDocument->document->id)) {
            try {
                $document = $this->getDocumentResolver()->get($assetDocument->document->id)
                    ?? $this->getDocumentFactory()->create($assetDocument);
            } catch (Throwable $exception) {
                $this->getExceptionHandler()->report(
                    new FailedToProcessAssetViewDocument($model, $assetDocument->document, $exception),
                );
            }
        }

        return $document;
    }

    protected function assetOem(ViewAsset $asset): ?Oem {
        return $this->oem($asset->vendor);
    }

    protected function assetType(ViewAsset $asset): ?TypeModel {
        return isset($asset->assetType) && $this->getNormalizer()->string($asset->assetType)
            ? $this->type(new Asset(), $asset->assetType)
            : null;
    }

    protected function assetProduct(ViewAsset $asset): ?Product {
        $product = null;

        if ($asset->assetSku) {
            $oem     = $this->assetOem($asset);
            $product = $oem
                ? $this->product($oem, $asset->assetSku, $asset->assetSkuDescription, $asset->eolDate, $asset->eosDate)
                : null;
        }

        return $product;
    }

    protected function assetLocation(ViewAsset $asset): ?Location {
        // fixme(DataLoader): Coordinates are not the same across all
        //      locations :( So to avoid race conditions, we are disallow
        //      to update them from here.
        $location = $this->locationFactory->create($asset, false);

        if (!$location) {
            $this->getExceptionHandler()->report(
                new AssetLocationNotFound($asset->id, $asset),
            );
        }

        // Return
        return $location;
    }

    protected function assetStatus(ViewAsset $asset): Status {
        return $this->status(new Asset(), $asset->status);
    }

    /**
     * @return EloquentCollection<array-key, \App\Models\Data\Tag>
     */
    protected function assetTags(ViewAsset $asset): EloquentCollection {
        /** @var EloquentCollection<array-key, \App\Models\Data\Tag> $tags */
        $tags = new EloquentCollection();
        $name = $this->getNormalizer()->string($asset->assetTag);

        if ($name) {
            $tags[] = $this->tag($name);
        }

        return $tags;
    }

    /**
     * @return EloquentCollection<array-key, Coverage>
     */
    protected function assetCoverages(ViewAsset $asset): EloquentCollection {
        /** @var EloquentCollection<array-key, \App\Models\Data\Coverage> $statuses */
        $statuses   = new EloquentCollection();
        $normalizer = $this->getNormalizer();

        foreach ($asset->assetCoverage ?? [] as $coverage) {
            $coverage = $normalizer->string($coverage);

            if ($coverage) {
                $coverage                 = $this->coverage($coverage);
                $statuses[$coverage->key] = $coverage;
            }
        }

        return $statuses->values();
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function getWarrantyKey(AssetWarranty|CoverageEntry|ViewAssetDocument $warranty): string {
        $normalizer = $this->getNormalizer();
        $key        = null;

        if ($warranty instanceof AssetWarranty) {
            $key = new Key($normalizer, [
                'key' => $warranty->key,
            ]);
        } elseif ($warranty instanceof CoverageEntry) {
            $key = new Key($normalizer, [
                'type'  => $normalizer->string($warranty->type),
                'start' => $normalizer->datetime($warranty->coverageStartDate),
                'end'   => $normalizer->datetime($warranty->coverageEndDate),
            ]);
        } else {
            $key = new Key($normalizer, [
                'document'     => $normalizer->string(
                    $warranty->document->id ?? $warranty->documentNumber,
                ),
                'reseller'     => $warranty->reseller->id ?? null,
                'customer'     => $warranty->customer->id ?? null,
                'serviceGroup' => $normalizer->string($warranty->serviceGroupSku),
                'serviceLevel' => $normalizer->string($warranty->serviceLevelSku),
                'start'        => $normalizer->datetime(
                    $warranty->startDate ?? $warranty->document->startDate ?? null,
                ),
                'end'          => $normalizer->datetime(
                    $warranty->endDate ?? $warranty->document->endDate ?? null,
                ),
            ]);
        }

        return (string) $key;
    }
    // </editor-fold>
}
