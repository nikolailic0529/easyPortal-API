<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Enums\ProductType;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\LocationNotFoundException;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithProduct;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Type;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

use function array_map;
use function is_null;
use function sprintf;

class AssetFactory extends ModelFactory {
    use WithOem;
    use WithType;
    use WithProduct;

    protected ?CustomerFactory $customerFactory = null;
    protected ?ResellerFactory $resellerFactory = null;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected AssetResolver $assets,
        protected OemResolver $oems,
        protected TypeResolver $types,
        protected ProductResolver $products,
        protected CustomerResolver $customerResolver,
        protected ResellerResolver $resellerResolver,
        protected LocationFactory $locations,
        protected DocumentFactory $documents,
    ) {
        parent::__construct($logger, $normalizer);
    }

    // <editor-fold desc="Settings">
    // =========================================================================
    public function setCustomersFactory(?CustomerFactory $factory): static {
        $this->customerFactory = $factory;

        return $this;
    }

    public function setResellerFactory(?ResellerFactory $factory): static {
        $this->resellerFactory = $factory;

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
     */
    public function prefetch(array $assets, bool $reset = false): static {
        $keys = array_map(static function (Asset $asset): string {
            return $asset->id;
        }, $assets);

        $this->assets->prefetch($keys, $reset);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromAsset(Asset $asset): ?AssetModel {
        $model = $this->assetAsset($asset);

        if (isset($asset->assetDocument) && !$this->isSearchMode()) {
            $documents = $this->assetDocuments($model, $asset);

            $this->assetInitialWarranties($model, $asset, $documents);
            $this->assetExtendedWarranties($model, $documents);
        }

        return $model;
    }

    protected function assetAsset(Asset $asset): AssetModel {
        // Get/Create
        $created = false;
        $factory = $this->factory(function (AssetModel $model) use (&$created, $asset): AssetModel {
            $reseller = $this->assetReseller($asset);
            $customer = $this->assetCustomer($asset);
            $location = $this->assetLocation($asset, $customer, $reseller);

            $created              = !$model->exists;
            $model->id            = $this->normalizer->uuid($asset->id);
            $model->oem           = $this->assetOem($asset);
            $model->type          = $this->assetType($asset);
            $model->product       = $this->assetProduct($asset);
            $model->reseller      = $reseller;
            $model->customer      = $customer;
            $model->location      = $location;
            $model->serial_number = $this->normalizer->string($asset->serialNumber);

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
        // Get all Document and Entries
        /** @var \SplObjectStorage<\App\Models\Document,\Illuminate\Support\Collection<\App\Models\DocumentEntry>> $documents */
        $documents = new SplObjectStorage();

        foreach ($asset->assetDocument as $assetDocument) {
            $document = $this->assetDocument($assetDocument);
            $entry    = $this->assetDocumentEntry($assetDocument);

            if ($documents->contains($document)) {
                $documents[$document]->add($entry);
            } else {
                $documents[$document] = new Collection([$entry]);
            }
        }

        // Update Documents entries
        $byProductId = static function (DocumentEntry $entry): string {
            return $entry->product_id;
        };

        /** @var \App\Models\Document $document */
        foreach ($documents as $document) {
            $existing = $document->entries
                ->filter(function (DocumentEntry $entry) use ($model): bool {
                    return $entry->asset_id === $this->normalizer->uuid($model->getKey());
                })
                ->keyBy($byProductId);
            $entries  = $documents[$document]->groupBy($byProductId);

            foreach ($entries as $product => $group) {
                // Update entry
                /** @var \App\Models\DocumentEntry $entry */
                $entry = $existing->get($product) ?? $group->first();

                $entry->asset    = $model;
                $entry->document = $document;
                $entry->quantity = $group->count();
                $entry->save();

                // Mark that exists
                $existing->forget($product);

                // Add entry to entries
                if ($document->entries->search($entry, true) === false) {
                    $document->entries->add($entry);
                }
            }

            // Remove missed entries
            foreach ($existing as $entry) {
                $key = $document->entries->search($entry, true);

                if ($key !== false) {
                    $document->entries->offsetUnset($key);
                }

                $entry->delete();
            }
        }

        // Return
        return new Collection($documents);
    }

    protected function assetDocument(AssetDocument $document): Document {
        return $this->documents->create($document);
    }

    protected function assetDocumentEntry(AssetDocument $document): DocumentEntry {
        $entry          = new DocumentEntry();
        $entry->oem     = $this->oem(
            $document->document->vendorSpecificFields->vendor,
            $document->document->vendorSpecificFields->vendor,
        );
        $entry->product = $this->product(
            $entry->oem,
            ProductType::service(),
            $document->skuNumber,
            $document->skuDescription,
            null,
            null,
        );

        return $entry;
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Document> $documents
     *
     * @return \Illuminate\Support\Collection<\App\Models\AssetWarranty>
     */
    protected function assetInitialWarranties(AssetModel $model, Asset $asset, Collection $documents): Collection {
        // @LastDragon: If I understand correctly, after purchasing the Asset
        // has an initial warranty up to "warrantyEndDate" and then the user
        // can buy additional warranty.

        $warranties = new Collection();
        $documents  = $documents->keyBy(static function (Document $document): string {
            return $document->getKey();
        });

        foreach ($asset->assetDocument as $assetDocument) {
            // Warranty exists?
            $end = $this->normalizer->datetime($assetDocument->warrantyEndDate);

            if (!$end) {
                continue;
            }

            // Document exists?
            /** @var \App\Models\Document $document */
            $document = $documents->get($assetDocument->document->id);

            if (!$document) {
                continue;
            }

            // Add new/Update
            /** @var \App\Models\AssetWarranty|null $warranty */
            $warranty = $model->warranties->first(static function (AssetWarranty $warranty) use ($document): bool {
                return is_null($warranty->document_id)
                    && $warranty->customer_id === $document->customer_id;
            });

            if (!$warranty) {
                $warranty = new AssetWarranty();

                $model->warranties->add($warranty);
            }

            $warranty->start    = null;
            $warranty->end      = $end;
            $warranty->asset    = $model;
            $warranty->customer = $document->customer;
            $warranty->reseller = $document->reseller;
            $warranty->document = null;

            $warranty->save();

            $warranties->add($warranty);
        }

        // Return
        return $warranties;
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Document> $documents
     *
     * @return \Illuminate\Support\Collection<\App\Models\AssetWarranty>
     */
    protected function assetExtendedWarranties(AssetModel $asset, Collection $documents): Collection {
        foreach ($documents as $document) {
            $warranty = $asset->warranties->first(static function (AssetWarranty $warranty) use ($document): bool {
                return $warranty->document_id === $document->getKey();
            });

            if (!$warranty) {
                $warranty = new AssetWarranty();

                $asset->warranties->add($warranty);
            }

            $warranty->start    = $document->start;
            $warranty->end      = $document->end;
            $warranty->asset    = $asset;
            $warranty->customer = $document->customer;
            $warranty->reseller = $document->reseller;
            $warranty->document = $document;
            $warranty->save();
        }

        return $asset->warranties;
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

    protected function assetReseller(Asset $asset): ?Reseller {
        $id       = $asset->resellerId ?? (isset($asset->reseller) ? $asset->reseller->id : null);
        $reseller = null;

        if ($id) {
            $reseller = $this->resellerResolver->get($id);
        }

        if ($id && !$reseller && $this->resellerFactory) {
            $reseller = $this->resellerFactory->create($asset->reseller);
        }

        if ($id && !$reseller) {
            throw new ResellerNotFoundException(sprintf(
                'Reseller `%s` not found (asset `%s`).',
                $id,
                $asset->id,
            ));
        }

        return $reseller;
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
    // </editor-fold>
}
