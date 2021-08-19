<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\AssetWarranty;
use App\Models\Coverage;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Document as DocumentModel;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Status;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Events\ObjectSkipped;
use App\Services\DataLoader\Exceptions\LocationNotFoundException;
use App\Services\DataLoader\Exceptions\ViewAssetDocumentNoDocument;
use App\Services\DataLoader\Factories\Concerns\WithAssetDocument;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithCoverage;
use App\Services\DataLoader\Factories\Concerns\WithCustomer;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithProduct;
use App\Services\DataLoader\Factories\Concerns\WithReseller;
use App\Services\DataLoader\Factories\Concerns\WithServiceGroup;
use App\Services\DataLoader\Factories\Concerns\WithServiceLevel;
use App\Services\DataLoader\Factories\Concerns\WithStatus;
use App\Services\DataLoader\Factories\Concerns\WithTag;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\FactoryPrefetchable;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolvers\CoverageResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TagResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function sprintf;

use const SORT_REGULAR;

class AssetFactory extends ModelFactory implements FactoryPrefetchable {
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
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected Dispatcher $dispatcher,
        protected AssetResolver $assetResolver,
        protected OemResolver $oemResolver,
        protected TypeResolver $typeResolver,
        protected ProductResolver $productResolver,
        protected CustomerResolver $customerResolver,
        protected ResellerResolver $resellerResolver,
        protected LocationFactory $locationFactory,
        protected ContactFactory $contactFactory,
        protected ContactResolver $contactResolver,
        protected StatusResolver $statusResolver,
        protected CoverageResolver $coverageResolver,
        protected TagResolver $tagResolver,
        protected DocumentResolver $documentResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
        protected ?ResellerFinder $resellerFinder = null,
        protected ?CustomerFinder $customerFinder = null,
        protected ?ServiceGroupFinder $serviceGroupFinder = null,
        protected ?ServiceLevelFinder $serviceLevelFinder = null,
        protected ?OemFinder $oemFinder = null,
    ) {
        parent::__construct($logger, $normalizer);
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

    protected function getContactsResolver(): ContactResolver {
        return $this->contactResolver;
    }

    public function getDocumentResolver(): ?DocumentResolver {
        return $this->documentResolver;
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

    // <editor-fold desc="Prefetch">
    // =========================================================================
    /**
     * @param array<\App\Services\DataLoader\Schema\ViewAsset> $assets
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $assets, bool $reset = false, Closure|null $callback = null): static {
        // Assets
        $keys = array_unique(array_map(static function (ViewAsset $asset): string {
            return $asset->id;
        }, $assets));

        $this->assetResolver->prefetch($keys, $reset, $callback);

        // Products
        $products = (new Collection($assets))
            ->filter(static function (ViewAsset $asset): bool {
                return isset($asset->sku);
            })
            ->map(function (ViewAsset $asset): array {
                return [
                    'sku' => $this->getNormalizer()->string($asset->sku),
                ];
            })
            ->unique()
            ->all();

        $this->getProductResolver()->prefetch($products, $reset);

        // Locations
        $this->locationFactory->prefetch($assets);

        // Return
        return $this;
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
            $reseller = $this->reseller($asset);
            $customer = $this->customer($asset);
            $location = $this->assetLocation($asset, $customer, $reseller);

            $created              = !$model->exists;
            $normalizer           = $this->getNormalizer();
            $model->id            = $normalizer->uuid($asset->id);
            $model->oem           = $this->assetOem($asset);
            $model->type          = $this->assetType($asset);
            $model->status        = $this->assetStatus($asset);
            $model->product       = $this->assetProduct($asset);
            $model->reseller      = $reseller;
            $model->customer      = $customer;
            $model->location      = $location;
            $model->changed_at    = $normalizer->datetime($asset->updatedAt);
            $model->serial_number = $normalizer->string($asset->serialNumber);
            $model->data_quality  = $normalizer->string($asset->dataQualityScore);
            $model->contacts      = $this->objectContacts($model, $asset->latestContactPersons);
            $model->tags          = $this->assetTags($asset);
            $model->coverages     = $this->assetCoverages($asset);

            $model->save();

            // Documents
            if ($this->getDocumentFactory() && isset($asset->assetDocument)) {
                // Prefetch documents
                if ($created) {
                    $model->setRelation('warranties', new EloquentCollection());
                    $model->setRelation('documentEntries', new EloquentCollection());
                } else {
                    $this->getDocumentFactory()->prefetch([$asset], false, function (Collection $documents): void {
                        $documents->loadMissing('entries');
                        $documents->loadMissing('contacts');
                        $documents->loadMissing('contacts.types');

                        $this->getContactsResolver()->add($documents->pluck('contacts')->flatten());
                    });
                }

                try {
                    $documents              = $this->assetDocuments($model, $asset);
                    $model->warranties      = $this->assetWarranties($model, $asset);
                    $model->documentEntries = $documents
                        ->map(static function (Document $document): Collection {
                            return $document->entries;
                        })
                        ->flatten();
                } finally {
                    $this->getDocumentResolver()->reset();
                }
            }

            $model->save();

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
     * @return \Illuminate\Support\Collection<\App\Models\Document>
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
                /** @var \App\Services\DataLoader\Schema\ViewAssetDocument $document */
                $document = $entries->first();

                $this->dispatcher->dispatch(
                    new ObjectSkipped($document, new ViewAssetDocumentNoDocument($document)),
                );
                $this->logger->notice('Failed to process ViewAssetDocument: document is null.', [
                    'asset'    => $model->getKey(),
                    'document' => $document->documentNumber,
                    'entries'  => count($entries),
                ]);
            });

        // Create documents
        return (new Collection($asset->assetDocument))
            ->filter(static function (ViewAssetDocument $document): bool {
                return isset($document->document->id);
            })
            ->sort(static function (ViewAssetDocument $a, ViewAssetDocument $b): int {
                return $a->startDate <=> $b->startDate
                    ?: $a->endDate <=> $b->endDate;
            })
            ->groupBy(static function (ViewAssetDocument $document): string {
                return $document->document->id;
            })
            ->map(function (Collection $entries) use ($model): ?DocumentModel {
                try {
                    return $this->getDocumentFactory()->create(new AssetDocumentObject([
                        'asset'    => $model,
                        'document' => $entries->first(),
                        'entries'  => $entries->all(),
                    ]));
                } catch (Throwable $exception) {
                    $this->dispatcher->dispatch(new ObjectSkipped($entries->first(), $exception));
                    $this->logger->notice('Failed to process ViewAssetDocument.', [
                        'asset'     => $model,
                        'entries'   => $entries->all(),
                        'exception' => $exception,
                    ]);
                }

                return null;
            })
            ->filter(static function (?DocumentModel $document): bool {
                return (bool) $document;
            });
    }

    /**
     * @return array<\App\Models\AssetWarranty>
     */
    protected function assetWarranties(AssetModel $model, ViewAsset $asset): array {
        $warranties = array_merge(
            $this->assetInitialWarranties($model, $asset),
            $this->assetExtendedWarranties($model, $asset),
        );

        return $warranties;
    }

    /**
     * @return array<\App\Models\AssetWarranty>
     */
    protected function assetInitialWarranties(AssetModel $model, ViewAsset $asset): array {
        // @LastDragon: If I understand correctly, after purchasing the Asset
        // has an initial warranty up to "warrantyEndDate" and then the user
        // can buy additional warranty.

        $normalizer = $this->getNormalizer();
        $warranties = [];
        $existing   = $model->warranties
            ->filter(static function (AssetWarranty $warranty): bool {
                return $warranty->document_number === null;
            })
            ->keyBy(static function (AssetWarranty $warranty): string {
                return implode('|', [$warranty->end?->getTimestamp(), $warranty->reseller_id, $warranty->customer_id]);
            });

        foreach ($asset->assetDocument as $assetDocument) {
            try {
                // Warranty exists?
                $end = $normalizer->datetime($assetDocument->warrantyEndDate);

                if (!$end) {
                    continue;
                }

                // Already added?
                $reseller = $this->reseller($assetDocument);
                $customer = $this->customer($assetDocument);
                $key      = implode('|', [$end->getTimestamp(), $reseller?->getKey(), $customer?->getKey()]);

                if (isset($warranties[$key])) {
                    continue;
                }

                // Create/Update
                /** @var \App\Models\AssetWarranty|null $warranty */
                $warranty                  = $existing->get($key) ?: new AssetWarranty();
                $warranty->start           = null;
                $warranty->end             = $end;
                $warranty->asset           = $model;
                $warranty->serviceGroup    = null;
                $warranty->customer        = $customer;
                $warranty->reseller        = $reseller;
                $warranty->document        = null;
                $warranty->document_number = null;

                // Store
                $warranties[$key] = $warranty;
            } catch (Throwable $exception) {
                $this->dispatcher->dispatch(new ObjectSkipped($assetDocument, $exception));
                $this->logger->notice('Failed to create Initial Warranty for ViewAssetDocument.', [
                    'asset'     => $model->getKey(),
                    'entry'     => $assetDocument,
                    'exception' => $exception,
                ]);
            }
        }

        // Return
        return array_values($warranties);
    }

    /**
     * @return array<\App\Models\AssetWarranty>
     */
    protected function assetExtendedWarranties(AssetModel $model, ViewAsset $asset): array {
        // Prepare
        $serviceLevels = [];
        $warranties    = [];
        $existing      = $model->warranties
            ->filter(static function (AssetWarranty $warranty): bool {
                return $warranty->document_number !== null;
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
        $documents     = (new Collection($asset->assetDocument))
            ->filter(static function (ViewAssetDocument $document): bool {
                return isset($document->documentNumber);
            })
            ->sort(static function (ViewAssetDocument $a, ViewAssetDocument $b): int {
                return $a->startDate <=> $b->startDate
                    ?: $a->endDate <=> $b->endDate;
            });

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
                /** @var \App\Models\AssetWarranty|null $warranty */
                $warranty                  = $existing->get($key) ?: new AssetWarranty();
                $warranty->start           = $start;
                $warranty->end             = $end;
                $warranty->asset           = $model;
                $warranty->serviceGroup    = $serviceGroup;
                $warranty->customer        = $customer;
                $warranty->reseller        = $reseller;
                $warranty->document        = $document;
                $warranty->document_number = $number;

                // Store
                $warranties[$key] = $warranty;
            } catch (Throwable $exception) {
                $this->dispatcher->dispatch(new ObjectSkipped($assetDocument, $exception));
                $this->logger->notice('Failed to create Warranty for ViewAssetDocument.', [
                    'asset'     => $model->getKey(),
                    'entry'     => $assetDocument,
                    'exception' => $exception,
                ]);
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

        if (isset($assetDocument->document)) {
            $document = $this->getDocumentFactory()->find(new AssetDocumentObject([
                'asset'    => $model,
                'document' => $assetDocument,
            ]));
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

    protected function assetLocation(ViewAsset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
        $location = $this->locationFactory->find(new AssetModel(), $asset);
        $required = !$this->locationFactory->isEmpty($asset);

        if ($customer && !$location) {
            $location = $this->locationFactory->find($customer, $asset);
        }

        if ($reseller && !$location) {
            $location = $this->locationFactory->find($reseller, $asset);
        }

        if ($required && !$location) {
            $location = $this->locationFactory->create(new AssetModel(), $asset);

            if (!$location) {
                throw new LocationNotFoundException(sprintf(
                    'Customer `%s` location not found (asset `%s`).',
                    $customer->getKey(),
                    $asset->id,
                ));
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
}
