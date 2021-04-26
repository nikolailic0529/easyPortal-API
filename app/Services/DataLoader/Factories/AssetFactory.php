<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\AssetWarranty;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry;
use App\Models\Enums\ProductType;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\DocumentNotFoundException;
use App\Services\DataLoader\Exceptions\LocationNotFoundException;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithProduct;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Type;
use Closure;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SplObjectStorage;

use function array_map;
use function array_unique;
use function is_null;
use function sprintf;

class AssetFactory extends ModelFactory {
    use WithOem;
    use WithType;
    use WithProduct;

    protected ?CustomerFactory $customerFactory = null;
    protected ?ResellerFactory $resellerFactory = null;
    protected ?DocumentFactory $documentFactory = null;

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
        protected DocumentResolver $documentResolver,
        protected CurrencyResolver $currencies,
    ) {
        parent::__construct($logger, $normalizer);
    }

    // <editor-fold desc="Settings">
    // =========================================================================
    public function getCustomerFactory(): ?CustomerFactory {
        return $this->customerFactory;
    }

    public function setCustomersFactory(?CustomerFactory $factory): static {
        $this->customerFactory = $factory;

        return $this;
    }

    public function getResellerFactory(): ?ResellerFactory {
        return $this->resellerFactory;
    }

    public function setResellerFactory(?ResellerFactory $factory): static {
        $this->resellerFactory = $factory;

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
        $model = $this->assetAsset($asset);

        if (!$this->isSearchMode() && $this->documentFactory && isset($asset->assetDocument)) {
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
            $document = $this->assetDocument($model, $assetDocument);
            $entry    = $this->assetDocumentEntry($document, $assetDocument);

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

    protected function assetDocument(AssetModel $asset, AssetDocument $assetDocument): Document {
        $document = null;

        if ($this->documentFactory) {
            if (isset($assetDocument->document)) {
                $document = $this->documentFactory->create($assetDocument);
            } else {
                // This is temporary fix for https://thefas.atlassian.net/browse/EAP-60
                $created  = false;
                $factory  = $this->factory(
                    function (DocumentModel $model) use (&$created, $asset, $assetDocument): DocumentModel {
                        $created         = !$model->exists;
                        $model->id       = $this->normalizer->string($assetDocument->documentNumber);
                        $model->oem      = $asset->oem;
                        $model->type     = $this->type(new DocumentModel(), '??');
                        $model->product  = $this->product(
                            $asset->oem,
                            ProductType::support(),
                            $assetDocument->supportPackage,
                            $assetDocument->supportPackageDescription,
                            null,
                            null,
                        );
                        $model->reseller = $asset->reseller;
                        $model->customer = $asset->customer;
                        $model->currency = $this->currencies->get(
                            $assetDocument->currencyCode ?: 'EUR',
                            $this->factory(static function () use ($assetDocument): Currency {
                                $model = new Currency();

                                $model->code = $assetDocument->currencyCode ?: 'EUR';
                                $model->name = $assetDocument->currencyCode ?: 'EUR';

                                $model->save();

                                return $model;
                            }),
                        );
                        $model->price    = $this->normalizer->number('0.00');
                        $model->number   = $this->normalizer->string($assetDocument->documentNumber);

                        if ($created) {
                            // These dates are not consistent and create a lot of:
                            // - UPDATE `documents` SET `start` = '2020-10-22 00:00:00', `end` = '2022-12-31 00:00:00'
                            // - UPDATE `documents` SET `start` = '2019-07-01 00:00:00', `end` = '2020-10-21 00:00:00'
                            // - UPDATE `documents` SET `start` = '2020-10-22 00:00:00', `end` = '2022-12-31 00:00:00'
                            //
                            // For this reason we will not update it at all.
                            $model->start = $this->normalizer->datetime($assetDocument->startDate);
                            $model->end   = $this->normalizer->datetime($assetDocument->endDate);
                        }

                        $model->save();

                        return $model;
                    },
                );
                $document = $this->documentResolver->get(
                    $assetDocument->documentNumber,
                    static function () use ($factory): DocumentModel {
                        return $factory(new DocumentModel());
                    },
                );

                // Update
                if (!$created && !$this->isSearchMode()) {
                    $factory($document);
                }
            }
        }

        if (!$document) {
            throw new DocumentNotFoundException(sprintf(
                'Document `%s` not found.',
                $assetDocument->documentNumber,
            ));
        }

        return $document;
    }

    protected function assetDocumentEntry(Document $document, AssetDocument $assetDocument): DocumentEntry {
        $entry          = new DocumentEntry();
        $entry->product = $this->product(
            $document->oem,
            ProductType::service(),
            $assetDocument->skuNumber,
            $assetDocument->skuDescription,
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
            $document = $documents->get($assetDocument->documentNumber);

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
        /** @var \App\Models\Document $document */
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
            $warranty->services = $document->entries
                ->filter(static function (DocumentEntry $entry) use ($asset): bool {
                    return $entry->asset_id === $asset->getKey();
                })
                ->map(static function (DocumentEntry $entry): Product {
                    return $entry->product;
                });
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
