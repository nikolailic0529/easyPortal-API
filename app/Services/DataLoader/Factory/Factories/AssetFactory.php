<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Data\Coverage;
use App\Models\Data\Location as LocationModel;
use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Models\Data\Status;
use App\Models\Data\Tag;
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
use App\Services\DataLoader\Factory\Concerns\WithLocations;
use App\Services\DataLoader\Factory\Concerns\WithOem;
use App\Services\DataLoader\Factory\Concerns\WithProduct;
use App\Services\DataLoader\Factory\Concerns\WithReseller;
use App\Services\DataLoader\Factory\Concerns\WithServiceGroup;
use App\Services\DataLoader\Factory\Concerns\WithServiceLevel;
use App\Services\DataLoader\Factory\Concerns\WithStatus;
use App\Services\DataLoader\Factory\Concerns\WithTag;
use App\Services\DataLoader\Factory\Concerns\WithType;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\CityResolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CountryResolver;
use App\Services\DataLoader\Resolver\Resolvers\CoverageResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Resolver\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolver\Resolvers\TagResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\CoverageEntry;
use App\Services\DataLoader\Schema\Types\Location;
use App\Services\DataLoader\Schema\Types\ViewAsset;
use App\Services\DataLoader\Schema\Types\ViewAssetDocument;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use Throwable;

use function array_filter;
use function implode;
use function max;
use function sprintf;
use function usort;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory {
    use Children;
    use WithReseller;
    use WithCustomer;
    use WithOem;
    use WithType;
    use WithProduct;
    use WithStatus;
    use WithContacts;
    use WithLocations;
    use WithTag;
    use WithCoverage;
    use WithServiceGroup;
    use WithServiceLevel;
    use WithAssetDocument;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        protected AssetResolver $assetResolver,
        protected OemResolver $oemResolver,
        protected TypeResolver $typeResolver,
        protected ProductResolver $productResolver,
        protected CustomerResolver $customerResolver,
        protected ResellerResolver $resellerResolver,
        protected DocumentResolver $documentResolver,
        protected DocumentFactory $documentFactory,
        protected ContactResolver $contactResolver,
        protected StatusResolver $statusResolver,
        protected CoverageResolver $coverageResolver,
        protected TagResolver $tagResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
        protected LocationResolver $locationResolver,
        protected CountryResolver $countryResolver,
        protected CityResolver $cityResolver,
        protected ?ResellerFinder $resellerFinder = null,
        protected ?CustomerFinder $customerFinder = null,
    ) {
        parent::__construct($exceptionHandler);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getAssetResolver(): AssetResolver {
        return $this->assetResolver;
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

    protected function getContactsResolver(): ContactResolver {
        return $this->contactResolver;
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

    protected function getLocationResolver(): LocationResolver {
        return $this->locationResolver;
    }

    protected function getCountryResolver(): CountryResolver {
        return $this->countryResolver;
    }

    protected function getCityResolver(): CityResolver {
        return $this->cityResolver;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function getModel(): string {
        return Asset::class;
    }

    public function create(Type $type, bool $force = false): ?Asset {
        $model = null;

        if ($type instanceof ViewAsset) {
            $model = $this->createFromAsset($type, $force);
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
    protected function createFromAsset(ViewAsset $asset, bool $force): ?Asset {
        return $this->assetAsset($asset, $force);
    }

    protected function assetAsset(ViewAsset $asset, bool $force): Asset {
        return $this->getAssetResolver()->get($asset->id, function (?Asset $model) use ($force, $asset): Asset {
            // Unchanged?
            $hash = $asset->getHash();

            if ($force === false && $model !== null && $hash === $model->hash) {
                return $model;
            }

            // Asset
            $created                          = $model === null;
            $model                          ??= new Asset();
            $model->id                        = $asset->id;
            $model->hash                      = $hash;
            $model->oem                       = $this->assetOem($asset);
            $model->type                      = $this->assetType($asset);
            $model->status                    = $this->assetStatus($asset);
            $model->product                   = $this->assetProduct($asset);
            $model->reseller                  = $this->reseller($asset);
            $model->customer                  = $this->customer($asset);
            $model->location                  = $this->assetLocation($asset);
            $model->eosl                      = $asset->eosDate;
            $model->changed_at                = $asset->updatedAt;
            $model->serial_number             = $asset->serialNumber;
            $model->data_quality              = $asset->dataQualityScore;
            $model->contracts_active_quantity = $asset->activeContractQuantitySum;
            $model->contacts                  = $this->contacts($model, (array) $asset->latestContactPersons);
            $model->tags                      = $this->assetTags($asset);
            $model->coverages                 = $this->assetCoverages($asset);

            // Warranties
            if ($created) {
                $model->setRelation('warranties', new EloquentCollection());
            }

            $warrantyChangedAt          = $asset->coverageStatusCheck->coverageStatusUpdatedAt ?? null;
            $model->warranties          = $this->assetWarranties($model, $asset, $force);
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
        });
    }

    /**
     * @return EloquentCollection<int, AssetWarranty>
     */
    protected function assetWarranties(Asset $model, ViewAsset $asset, bool $force): EloquentCollection {
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
            $warrantyChangedAt = $asset->coverageStatusCheck->coverageStatusUpdatedAt;

            if ($model->warranty_changed_at <= $warrantyChangedAt) {
                $coverages = $this->assetWarrantiesCoverages($model, $asset, $coverages, $force);
            }
        }

        // Documents
        if (isset($asset->assetDocument)) {
            $documents = $this->assetWarrantiesDocuments($model, $asset, $documents, $force);
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
        bool $force,
    ): EloquentCollection {
        return $this->children(
            $existing,
            $asset->coverageStatusCheck->coverageEntries ?? [],
            null,
            function (AssetWarranty|CoverageEntry $warranty): string {
                return $this->getWarrantyKey($warranty);
            },
            function (CoverageEntry $entry, ?AssetWarranty $warranty) use ($force, $model): ?AssetWarranty {
                try {
                    return $this->assetWarranty($model, $entry, $warranty, $force);
                } catch (Throwable $exception) {
                    $this->getExceptionHandler()->report(
                        new FailedToProcessViewAssetCoverageEntry($model, $entry, $exception),
                    );
                }

                return null;
            },
        );
    }

    protected function assetWarranty(
        Asset $model,
        CoverageEntry $entry,
        ?AssetWarranty $warranty,
        bool $force,
    ): ?AssetWarranty {
        // Empty?
        if ($entry->description === null && $entry->coverageStartDate === null && $entry->coverageEndDate === null) {
            return null;
        }

        // Unchanged?
        $hash = $entry->getHash();

        if ($warranty && $force === false && $hash === $warranty->hash) {
            return $warranty;
        }

        // Create
        $warranty                ??= new AssetWarranty();
        $warranty->hash            = $hash;
        $warranty->key             = $this->getWarrantyKey($entry);
        $warranty->start           = $entry->coverageStartDate;
        $warranty->end             = $entry->coverageEndDate;
        $warranty->asset           = $model;
        $warranty->type            = $this->type($warranty, $entry->type);
        $warranty->status          = $this->status($warranty, $entry->status);
        $warranty->description     = $entry->description;
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

        $resolver->prefetch($keys);

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
        bool $force,
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
                $force,
                $model,
                &$created,
            ): ?AssetWarranty {
                try {
                    // Valid?
                    $number = $assetDocument->documentNumber;
                    $group  = $this->assetDocumentServiceGroup($model, $assetDocument);
                    $level  = $this->assetDocumentServiceLevel($model, $assetDocument);
                    $start  = $assetDocument->startDate ?? $assetDocument->document->startDate ?? null;
                    $end    = $assetDocument->endDate ?? $assetDocument->document->endDate ?? null;

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

                    // Unchanged?
                    $hash = $assetDocument->getHash();

                    if ($warranty && $force === false && $hash === $warranty->hash) {
                        return $warranty;
                    }

                    // Create/Update
                    $warranty                ??= new AssetWarranty();
                    $warranty->hash            = $hash;
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
        return isset($asset->assetType) && $asset->assetType
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

    protected function assetLocation(ViewAsset $asset): ?LocationModel {
        $model    = null;
        $location = new Location([
            'zip'         => $asset->zip ?? null,
            'city'        => $asset->city ?? null,
            'address'     => implode(' ', array_filter([$asset->address ?? null, $asset->address2 ?? null])),
            'country'     => $asset->country ?? null,
            'countryCode' => $asset->countryCode ?? null,
            'latitude'    => $asset->latitude ?? null,
            'longitude'   => $asset->longitude ?? null,
        ]);

        if (!$this->isLocationEmpty($location)) {
            // fixme(DataLoader): Coordinates are not the same across all
            //      locations :( So to avoid race conditions, we are disallow
            //      to update them from here.
            $model = $this->location($location, false);

            if (!$model) {
                $this->getExceptionHandler()->report(
                    new AssetLocationNotFound($asset->id, $asset),
                );
            }
        }

        // Return
        return $model;
    }

    protected function assetStatus(ViewAsset $asset): Status {
        return $this->status(new Asset(), $asset->status);
    }

    /**
     * @return EloquentCollection<array-key, Tag>
     */
    protected function assetTags(ViewAsset $asset): EloquentCollection {
        /** @var EloquentCollection<array-key, Tag> $tags */
        $tags = new EloquentCollection();
        $name = $asset->assetTag;

        if ($name) {
            $tags[] = $this->tag($name);
        }

        return $tags;
    }

    /**
     * @return EloquentCollection<array-key, Coverage>
     */
    protected function assetCoverages(ViewAsset $asset): EloquentCollection {
        /** @var EloquentCollection<array-key, Coverage> $statuses */
        $statuses = new EloquentCollection();

        foreach ($asset->assetCoverage ?? [] as $coverage) {
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
        $key = null;

        if ($warranty instanceof AssetWarranty) {
            $key = new Key([
                'key' => $warranty->key,
            ]);
        } elseif ($warranty instanceof CoverageEntry) {
            $key = new Key([
                'type'  => $warranty->type,
                'start' => $warranty->coverageStartDate,
                'end'   => $warranty->coverageEndDate,
            ]);
        } else {
            $key = new Key([
                'document'     => $warranty->document->id ?? $warranty->documentNumber,
                'reseller'     => $warranty->reseller->id ?? null,
                'customer'     => $warranty->customer->id ?? null,
                'serviceGroup' => $warranty->serviceGroupSku,
                'serviceLevel' => $warranty->serviceLevelSku,
                'start'        => $warranty->startDate ?? $warranty->document->startDate ?? null,
                'end'          => $warranty->endDate ?? $warranty->document->endDate ?? null,
            ]);
        }

        return (string) $key;
    }
    // </editor-fold>
}
