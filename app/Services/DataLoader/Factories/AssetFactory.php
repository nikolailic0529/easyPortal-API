<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry;
use App\Models\Enums\ProductType;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Status;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Events\ObjectSkipped;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\InvalidData;
use App\Services\DataLoader\Exceptions\LocationNotFoundException;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithProduct;
use App\Services\DataLoader\Factories\Concerns\WithReseller;
use App\Services\DataLoader\Factories\Concerns\WithStatus;
use App\Services\DataLoader\Factories\Concerns\WithTag;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Resolvers\TagResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_map;
use function array_merge;
use function array_unique;
use function sprintf;

class AssetFactory extends ModelFactory {
    use WithReseller;
    use WithOem;
    use WithType;
    use WithProduct;
    use WithStatus;
    use WithContacts;
    use WithTag;

    protected ?CustomerFactory $customerFactory = null;
    protected ?DocumentFactory $documentFactory = null;
    protected ?ContactFactory  $contactFactory  = null;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected Dispatcher $dispatcher,
        protected AssetResolver $assets,
        protected OemResolver $oems,
        protected TypeResolver $types,
        protected ProductResolver $products,
        protected CustomerResolver $customerResolver,
        protected ResellerResolver $resellers,
        protected LocationFactory $locations,
        protected StatusResolver $statuses,
        protected AssetCoverageFactory $coverages,
        protected TagResolver $tags,
    ) {
        parent::__construct($logger, $normalizer);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getResellerResolver(): ResellerResolver {
        return $this->resellers;
    }

    public function getCustomerFactory(): ?CustomerFactory {
        return $this->customerFactory;
    }

    public function setCustomersFactory(?CustomerFactory $factory): static {
        $this->customerFactory = $factory;

        return $this;
    }

    public function getDocumentFactory(): ?DocumentFactory {
        return $this->documentFactory;
    }

    public function setDocumentFactory(?DocumentFactory $factory): static {
        $this->documentFactory = $factory;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function create(Type $type): ?AssetModel {
        $model = null;

        if ($type instanceof Asset) {
            $model = $this->createFromAsset($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                Asset::class,
            ));
        }

        return $model;
    }
    // </editor-fold>

    // <editor-fold desc="Prefetch">
    // =========================================================================
    /**
     * @param array<\App\Services\DataLoader\Schema\Asset> $assets
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    public function prefetch(array $assets, bool $reset = false, Closure|null $callback = null): static {
        $keys = array_unique(array_map(static function (Asset $asset): string {
            return $asset->id;
        }, $assets));

        $this->assets->prefetch($keys, $reset, $callback);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromAsset(Asset $asset): ?AssetModel {
        return $this->assetAsset($asset);
    }

    protected function assetAsset(Asset $asset): AssetModel {
        // Get/Create
        $created = false;
        $factory = $this->factory(function (AssetModel $model) use (&$created, $asset): AssetModel {
            $reseller = $this->reseller($asset);
            $customer = $this->assetCustomer($asset);
            $location = $this->assetLocation($asset, $customer, $reseller);

            $created              = !$model->exists;
            $model->id            = $this->normalizer->uuid($asset->id);
            $model->oem           = $this->assetOem($asset);
            $model->type          = $this->assetType($asset);
            $model->status        = $this->assetStatus($asset);
            $model->product       = $this->assetProduct($asset);
            $model->reseller      = $reseller;
            $model->customer      = $customer;
            $model->location      = $location;
            $model->serial_number = $this->normalizer->string($asset->serialNumber);
            $model->data_quality  = $this->normalizer->string($asset->dataQualityScore);
            $model->contacts      = $this->objectContacts($model, $asset->latestContactPersons);
            $model->tags          = $this->assetTags($asset);
            $model->coverage      = $this->coverages->create($asset);

            if ($this->getDocumentFactory() && isset($asset->assetDocument)) {
                $model->warranties = $this->assetWarranties($model, $asset);
            }

            $model->save();

            return $model;
        });
        $model   = $this->assets->get(
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
    protected function assetDocuments(AssetModel $model, Asset $asset): Collection {
        // Asset.assetDocument is not a document but an array of entries where
        // each entry is the mixin of Document, DocumentEntry, and additional
        // information (that is not available in Document and DocumentEntry)

        return (new Collection($asset->assetDocument))
            ->filter(static function (AssetDocument $document): bool {
                return (bool) $document->documentNumber;
            })
            ->sort(static function (AssetDocument $a, AssetDocument $b): int {
                return $a->startDate <=> $b->startDate
                    ?: $a->endDate <=> $b->endDate;
            })
            ->groupBy(static function (AssetDocument $document): string {
                return $document->documentNumber;
            })
            ->map(function (Collection $entries) use ($model): ?DocumentModel {
                try {
                    return $this->getDocumentFactory()->create(new AssetDocumentObject([
                        'asset'    => $model,
                        'document' => $entries->first(),
                        'entries'  => $entries->all(),
                    ]));
                } catch (InvalidData $exception) {
                    $this->dispatcher->dispatch(new ObjectSkipped($entries->first(), $exception));
                } catch (Throwable $exception) {
                    $this->logger->error('Failed to process AssetDocument.', [
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
    protected function assetWarranties(AssetModel $model, Asset $asset): array {
        $documents  = $this->assetDocuments($model, $asset);
        $warranties = array_merge(
            $this->assetInitialWarranties($model, $asset, $documents),
            $this->assetExtendedWarranties($model, $documents),
        );

        return $warranties;
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Document> $documents
     *
     * @return array<\App\Models\AssetWarranty>
     */
    protected function assetInitialWarranties(AssetModel $model, Asset $asset, Collection $documents): array {
        // @LastDragon: If I understand correctly, after purchasing the Asset
        // has an initial warranty up to "warrantyEndDate" and then the user
        // can buy additional warranty.

        $warranties = [];
        $documents  = $documents
            ->keyBy(static function (DocumentModel $document): string {
                return $document->number;
            });
        $existing   = $model->warranties
            ->filter(static function (AssetWarranty $warranty): bool {
                return $warranty->document_id === null;
            });

        foreach ($asset->assetDocument as $assetDocument) {
            // Warranty exists?
            $end = $this->normalizer->datetime($assetDocument->warrantyEndDate);

            if (!$end) {
                continue;
            }

            // Document exists?
            /** @var \App\Models\Document $document */
            $document = $documents->get($assetDocument->documentNumber);

            if (!$document) {
                continue;
            }

            // Create/Update
            /** @var \App\Models\AssetWarranty|null $warranty */
            $warranty = $existing
                ->first(static function (AssetWarranty $warranty) use ($document, $end): bool {
                    return $warranty->end->equalTo($end)
                        && $warranty->customer_id === $document->customer_id
                        && $warranty->reseller_id === $document->reseller_id;
                });

            if (!$warranty) {
                $warranty = new AssetWarranty();
            }

            $warranty->start    = null;
            $warranty->end      = $end;
            $warranty->asset    = $model;
            $warranty->customer = $document->customer;
            $warranty->reseller = $document->reseller;
            $warranty->document = null;

            $warranty->save();

            // Store
            $warranties[] = $warranty;
        }

        // Return
        return $warranties;
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Document> $documents
     *
     * @return array<\App\Models\AssetWarranty>
     */
    protected function assetExtendedWarranties(AssetModel $asset, Collection $documents): array {
        $warranties = [];
        $existing   = $asset->warranties
            ->filter(static function (AssetWarranty $warranty): bool {
                return (bool) $warranty->document_id;
            })
            ->keyBy(static function (AssetWarranty $warranty): string {
                return $warranty->document_id;
            });

        foreach ($documents as $document) {
            // Create/Update
            /** @var \App\Models\Document $document */
            $warranty = $existing->get($document->getKey());

            if (!$warranty) {
                $warranty = new AssetWarranty();
            }

            $warranty->start    = $document->start;
            $warranty->end      = $document->end;
            $warranty->asset    = $asset;
            $warranty->customer = $document->customer;
            $warranty->reseller = $document->reseller;
            $warranty->document = $document;
            $warranty->services = $document->entries
                ->filter(static function (DocumentEntry $entry) use ($asset): bool {
                    return $entry->asset_id === $asset->getKey();
                })
                ->map(static function (DocumentEntry $entry): Product {
                    return $entry->service;
                });
            $warranty->save();

            // Store
            $warranties[] = $warranty;
        }

        return $warranties;
    }

    protected function assetOem(Asset $asset): Oem {
        return $this->oem($asset->vendor, $asset->vendor);
    }

    protected function assetType(Asset $asset): TypeModel {
        return $this->type(new AssetModel(), $asset->assetType);
    }

    protected function assetProduct(Asset $asset): Product {
        $oem     = $this->assetOem($asset);
        $type    = ProductType::asset();
        $product = $this->product(
            $oem,
            $type,
            $asset->sku,
            $asset->productDescription,
            $asset->eolDate,
            $asset->eosDate,
        );

        return $product;
    }

    protected function assetCustomer(Asset $asset): ?Customer {
        $id       = $asset->customerId ?? (isset($asset->customer) ? $asset->customer->id : null);
        $customer = null;

        if ($id) {
            $customer = $this->customerResolver->get($id);
        }

        if ($id && !$customer && $this->customerFactory) {
            $customer = $this->customerFactory->create($asset->customer);
        }

        if ($id && !$customer) {
            throw new CustomerNotFoundException(sprintf(
                'Customer `%s` not found (asset `%s`).',
                $id,
                $asset->id,
            ));
        }

        return $customer;
    }

    protected function assetLocation(Asset $asset, ?Customer $customer, ?Reseller $reseller): ?Location {
        $location = null;
        $required = !$this->locations->isEmpty($asset);

        if ($customer) {
            $location = $this->locations->find($customer, $asset);
        }

        if ($reseller && !$location) {
            $location = $this->locations->find($reseller, $asset);
        }

        if ($required && !$location) {
            $location = $this->locations->create(new AssetModel(), $asset);

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

    protected function assetStatus(Asset $asset): Status {
        return $this->status(new AssetModel(), $asset->status);
    }

    /**
     * @return array<\App\Models\Tag>
     */
    protected function assetTags(Asset $asset): array {
        $name = $this->normalizer->string($asset->assetTag);
        if ($name) {
            return [$this->tag($name)];
        }

        return [];
    }
    // </editor-fold>
}
