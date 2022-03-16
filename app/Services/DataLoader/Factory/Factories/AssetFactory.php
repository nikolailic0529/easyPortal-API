<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Asset as AssetModel;
use App\Models\AssetWarranty;
use App\Models\Coverage;
use App\Models\Document;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Status;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\AssetLocationNotFound;
use App\Services\DataLoader\Exceptions\FailedToCreateAssetWarranty;
use App\Services\DataLoader\Exceptions\FailedToProcessAssetViewDocument;
use App\Services\DataLoader\Exceptions\FailedToProcessViewAssetCoverageEntry;
use App\Services\DataLoader\Exceptions\FailedToProcessViewAssetDocumentNoDocument;
use App\Services\DataLoader\Factory\AssetDocumentObject;
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
use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\CoverageResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Throwable;

use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;
use function implode;
use function sprintf;

use const SORT_REGULAR;

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

    protected ?DocumentFactory $documentFactory = null;

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        protected AssetResolver $assetResolver,
        protected OemResolver $oemResolver,
        protected TypeResolver $typeResolver,
        protected ProductResolver $productResolver,
        protected CustomerResolver $customerResolver,
        protected ResellerResolver $resellerResolver,
        protected LocationFactory $locationFactory,
        protected ContactFactory $contactFactory,
        protected StatusResolver $statusResolver,
        protected CoverageResolver $coverageResolver,
        protected TagResolver $tagResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
        protected ?ResellerFinder $resellerFinder = null,
        protected ?CustomerFinder $customerFinder = null,
        protected ?ServiceGroupFinder $serviceGroupFinder = null,
        protected ?ServiceLevelFinder $serviceLevelFinder = null,
        protected ?OemFinder $oemFinder = null,
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

    public function getDocumentFactory(): ?DocumentFactory {
        return $this->documentFactory;
    }

    public function setDocumentFactory(?DocumentFactory $factory): static {
        $this->documentFactory = $factory;

        return $this;
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

    protected function getOemFinder(): ?OemFinder {
        return $this->oemFinder;
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

    protected function getServiceGroupFinder(): ?ServiceGroupFinder {
        return $this->serviceGroupFinder;
    }

    protected function getServiceLevelResolver(): ServiceLevelResolver {
        return $this->serviceLevelResolver;
    }

    protected function getServiceLevelFinder(): ?ServiceLevelFinder {
        return $this->serviceLevelFinder;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function create(Type $type): ?AssetModel {
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
    protected function createFromAsset(ViewAsset $asset): ?AssetModel {
        return $this->assetAsset($asset);
    }

    protected function assetAsset(ViewAsset $asset): AssetModel {
        // Get/Create
        $created = false;
        $factory = $this->factory(function (AssetModel $model) use (&$created, $asset): AssetModel {
            // Asset
            $created              = !$model->exists;
            $normalizer           = $this->getNormalizer();
            $model->id            = $normalizer->uuid($asset->id);
            $model->oem           = $this->assetOem($asset);
            $model->type          = $this->assetType($asset);
            $model->status        = $this->assetStatus($asset);
            $model->product       = $this->assetProduct($asset);
            $model->reseller      = $this->reseller($asset);
            $model->customer      = $this->customer($asset);
            $model->location      = $this->assetLocation($asset);
            $model->changed_at    = $normalizer->datetime($asset->updatedAt);
            $model->serial_number = $normalizer->string($asset->serialNumber);
            $model->data_quality  = $normalizer->string($asset->dataQualityScore);
            $model->contacts      = $this->objectContacts($model, (array) $asset->latestContactPersons);
            $model->tags          = $this->assetTags($asset);
            $model->coverages     = $this->assetCoverages($asset);
            $model->synced_at     = Date::now();

            // Warranties
            if ($created) {
                $model->setRelation('warranties', new EloquentCollection());
            }

            if (isset($asset->coverageStatusCheck)) {
                $warrantyChangedAt = $normalizer->datetime($asset->coverageStatusCheck->coverageStatusUpdatedAt);

                if ($created || $model->warranty_changed_at < $warrantyChangedAt) {
                    $model->warranties          = $this->assetWarranties($model, $asset);
                    $model->warranty_changed_at = $warrantyChangedAt;
                }
            }

            // Save
            if ($created) {
                $model->save();
            }

            // Documents
            if ($this->getDocumentFactory() && isset($asset->assetDocument)) {
                try {
                    // Prefetch
                    if (!$created) {
                        $model->loadMissing('warranties.serviceLevels');
                    }

                    // Update
                    $model->warranties = $this->assetDocumentsWarranties($model, $asset);
                } finally {
                    // Save
                    $model->save();

                    // Cleanup
                    unset($model->warranties);
                }
            }

            // Save
            $model->save();

            // Cleanup
            unset($model->warranties);

            // Return
            return $model;
        });
        $model   = $this->assetResolver->get(
            $asset->id,
            static function () use ($factory): AssetModel {
                return $factory(new AssetModel());
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
     * @return Collection<int, AssetWarranty>
     */
    protected function assetWarranties(AssetModel $model, ViewAsset $asset): Collection {
        // Some warranties generated from Documents, we must not touch them.
        $documents = $model->warranties->filter(static function (AssetWarranty $warranty): bool {
            return !static::isWarranty($warranty);
        });
        $existing  = $model->warranties->filter(static function (AssetWarranty $warranty): bool {
            return static::isWarranty($warranty);
        });
        $updated   = $this->children(
            $existing,
            $asset->coverageStatusCheck->coverageEntries,
            function (CoverageEntry $entry) use ($model): ?AssetWarranty {
                try {
                    return $this->assetWarranty($model, $entry);
                } catch (Throwable $exception) {
                    $this->getExceptionHandler()->report(
                        new FailedToProcessViewAssetCoverageEntry($model, $entry, $exception),
                    );
                }

                return null;
            },
            static function (AssetWarranty $a, AssetWarranty $b): int {
                return static::compareAssetWarranties($a, $b);
            },
        );

        return $documents->toBase()->merge($updated);
    }

    protected function assetWarranty(AssetModel $model, CoverageEntry $entry): ?AssetWarranty {
        // Empty?
        $normalizer  = $this->getNormalizer();
        $description = $normalizer->text($entry->description);
        $start       = $normalizer->datetime($entry->coverageStartDate);
        $end         = $normalizer->datetime($entry->coverageEndDate);

        if ($description === null && $start === null && $end === null) {
            return null;
        }

        // Create
        $warranty                  = new AssetWarranty();
        $warranty->start           = $start;
        $warranty->end             = $end;
        $warranty->asset           = $model;
        $warranty->type            = $this->type($warranty, $entry->type);
        $warranty->status          = $this->status($warranty, $entry->status);
        $warranty->description     = $description;
        $warranty->serviceGroup    = null;
        $warranty->customer        = null;
        $warranty->reseller        = null;
        $warranty->document        = null;
        $warranty->document_number = null;

        // Return
        return $warranty;
    }

    /**
     * @return Collection<int, ViewAssetDocument>
     */
    protected function assetDocuments(AssetModel $model, ViewAsset $asset): Collection {
        // Asset.assetDocument is not a document but an array of entries where
        // each entry is the mixin of Document, DocumentEntry, and additional
        // information (that is not available in Document and DocumentEntry)

        // Log assets were documents is missed
        (new Collection($asset->assetDocument))
            ->filter(static function (ViewAssetDocument $document): bool {
                return isset($document->documentNumber) && !isset($document->document->id);
            })
            ->groupBy(static function (ViewAssetDocument $document): string {
                return $document->documentNumber;
            })
            ->each(function (Collection $entries) use ($model): void {
                $this->getExceptionHandler()->report(
                    new FailedToProcessViewAssetDocumentNoDocument($model, $entries->first()),
                );
            });

        // Return
        return (new Collection($asset->assetDocument))
            ->filter(static function (ViewAssetDocument $document): bool {
                return isset($document->documentNumber);
            })
            ->sort(static function (ViewAssetDocument $a, ViewAssetDocument $b): int {
                return $a->startDate <=> $b->startDate
                    ?: $a->endDate <=> $b->endDate;
            });
    }

    /**
     * @return array<AssetWarranty>
     */
    protected function assetDocumentsWarranties(AssetModel $model, ViewAsset $asset): array {
        $warranties = array_merge(
            $this->assetDocumentsWarrantiesExtended($model, $asset),
            $model->warranties->filter(static function (AssetWarranty $warranty): bool {
                return static::isWarranty($warranty);
            })->all(),
        );

        return $warranties;
    }

    /**
     * @return array<AssetWarranty>
     */
    protected function assetDocumentsWarrantiesExtended(AssetModel $model, ViewAsset $asset): array {
        // Prepare
        $serviceLevels = [];
        $warranties    = [];
        $existing      = $model->warranties
            ->filter(static function (AssetWarranty $warranty): bool {
                return static::isWarrantyExtended($warranty);
            })
            ->keyBy(static function (AssetWarranty $warranty): string {
                return implode('|', [
                    $warranty->document_id,
                    $warranty->document_number,
                    $warranty->reseller_id,
                    $warranty->customer_id,
                    $warranty->service_group_id,
                    $warranty->start?->startOfDay(),
                    $warranty->end?->startOfDay(),
                ]);
            });
        $documents     = $this->assetDocuments($model, $asset);

        // Warranties
        $normalizer = $this->getNormalizer();

        foreach ($documents as $assetDocument) {
            try {
                // Valid?
                $document     = $this->assetDocumentDocument($model, $assetDocument);
                $number       = $assetDocument->documentNumber;
                $serviceGroup = $this->assetDocumentServiceGroup($model, $assetDocument);
                $serviceLevel = $this->assetDocumentServiceLevel($model, $assetDocument);
                $start        = $normalizer->datetime($assetDocument->startDate);
                $end          = $normalizer->datetime($assetDocument->endDate);

                if (!($number && ($start || $end) && ($serviceGroup || $serviceLevel))) {
                    continue;
                }

                // Prepare
                $reseller = $this->reseller($assetDocument);
                $customer = $this->customer($assetDocument);
                $key      = implode('|', [
                    $document?->getKey(),
                    $number,
                    $reseller?->getKey(),
                    $customer?->getKey(),
                    $serviceGroup?->getKey(),
                    Date::make($start)?->startOfDay(),
                    Date::make($end)?->startOfDay(),
                ]);

                // Add service
                $serviceLevels[$key][] = $serviceLevel;

                // Already added?
                if (isset($warranties[$key])) {
                    continue;
                }

                // Create/Update
                /** @var AssetWarranty|null $warranty */
                $warranty                  = $existing->get($key) ?: new AssetWarranty();
                $warranty->start           = $start;
                $warranty->end             = $end;
                $warranty->asset           = $model;
                $warranty->type            = null;
                $warranty->status          = null;
                $warranty->description     = null;
                $warranty->serviceGroup    = $serviceGroup;
                $warranty->customer        = $customer;
                $warranty->reseller        = $reseller;
                $warranty->document        = $document;
                $warranty->document_number = $number;

                // Store
                $warranties[$key] = $warranty;
            } catch (Throwable $exception) {
                $this->getExceptionHandler()->report(
                    new FailedToCreateAssetWarranty($model, $assetDocument, $exception),
                );
            }
        }

        // Update Service Levels
        foreach ($warranties as $key => $warranty) {
            $warranty->serviceLevels = array_filter(array_unique($serviceLevels[$key] ?? [], SORT_REGULAR));
        }

        // Return
        return array_values($warranties);
    }

    protected function assetDocumentDocument(AssetModel $model, ViewAssetDocument $assetDocument): ?Document {
        $document = null;

        if (isset($assetDocument->document->id)) {
            try {
                $document = $this->getDocumentFactory()->create(new AssetDocumentObject([
                    'asset'    => $model,
                    'document' => $assetDocument,
                ]));
            } catch (Throwable $exception) {
                $this->getExceptionHandler()->report(
                    new FailedToProcessAssetViewDocument($model, $assetDocument->document, $exception),
                );
            }
        }

        return $document;
    }

    protected function assetOem(ViewAsset $asset): Oem {
        return $this->oem($asset->vendor);
    }

    protected function assetType(ViewAsset $asset): ?TypeModel {
        return isset($asset->assetType)
            ? $this->type(new AssetModel(), $asset->assetType)
            : null;
    }

    protected function assetProduct(ViewAsset $asset): Product {
        $oem     = $this->assetOem($asset);
        $product = $this->product(
            $oem,
            $asset->sku,
            $asset->productDescription,
            $asset->eolDate,
            $asset->eosDate,
        );

        return $product;
    }

    protected function assetLocation(ViewAsset $asset): ?Location {
        $location = $this->locationFactory->find($asset);
        $required = !$this->locationFactory->isEmpty($asset);

        if ($required && !$location) {
            $location = $this->locationFactory->create($asset);

            if (!$location || !$location->save()) {
                throw new AssetLocationNotFound($asset->id, $asset);
            }
        }

        // Return
        return $location;
    }

    protected function assetStatus(ViewAsset $asset): Status {
        return $this->status(new AssetModel(), $asset->status);
    }

    /**
     * @return array<\App\Models\Tag>
     */
    protected function assetTags(ViewAsset $asset): array {
        $name = $this->getNormalizer()->string($asset->assetTag);

        if ($name) {
            return [$this->tag($name)];
        }

        return [];
    }

    /**
     * @return array<\App\Models\Coverage>
     */
    protected function assetCoverages(ViewAsset $asset): array {
        return (new Collection($asset->assetCoverage ?? []))
            ->filter(function (?string $coverage): bool {
                return (bool) $this->getNormalizer()->string($coverage);
            })
            ->map(function (string $coverage): Coverage {
                return $this->coverage($coverage);
            })
            ->unique()
            ->all();
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected static function isWarranty(AssetWarranty $warranty): bool {
        return $warranty->type_id !== null;
    }

    protected static function isWarrantyExtended(AssetWarranty $warranty): bool {
        return $warranty->document_number !== null && !static::isWarranty($warranty);
    }

    protected static function compareAssetWarranties(AssetWarranty $a, AssetWarranty $b): int {
        return $a->type_id <=> $b->type_id
            ?: ($a->start->isSameDay($b->start) ? 0 : $a->start <=> $b->start)
                ?: ($a->end->isSameDay($b->end) ? 0 : $a->end <=> $b->end);
    }
    // </editor-fold>
}
