<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry;
use App\Models\Enums\ProductType;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\ViewAssetDocumentNoDocument;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithCustomer;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithProduct;
use App\Services\DataLoader\Factories\Concerns\WithReseller;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use Closure;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function array_map;
use function array_merge;
use function array_udiff;
use function array_uintersect;
use function implode;
use function sprintf;

class DocumentFactory extends ModelFactory {
    use WithOem;
    use WithType;
    use WithProduct;
    use WithContacts;
    use WithReseller;
    use WithCustomer;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected OemResolver $oems,
        protected TypeResolver $types,
        protected ResellerResolver $resellers,
        protected CustomerResolver $customers,
        protected ProductResolver $products,
        protected CurrencyFactory $currencies,
        protected DocumentResolver $documents,
        protected LanguageFactory $languages,
        protected DistributorFactory $distributors,
    ) {
        parent::__construct($logger, $normalizer);
    }

    public function find(Type $type): ?DocumentModel {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    protected function getResellerResolver(): ResellerResolver {
        return $this->resellers;
    }

    protected function getCustomerResolver(): CustomerResolver {
        return $this->customers;
    }
    // </editor-fold>

    // <editor-fold desc="Factory">
    // =========================================================================
    public function create(Type $type): ?DocumentModel {
        $model = null;

        if ($type instanceof AssetDocumentObject) {
            $model = $this->createFromAssetDocumentObject($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                implode('`, `', [
                    AssetDocumentObject::class,
                ]),
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
        $keys = (new Collection($assets))
            ->map(static function (ViewAsset $asset): array {
                return array_map(static function (ViewAssetDocument $document): string {
                    return $document->document->id ?? $document->documentNumber;
                }, $asset->assetDocument ?? []);
            })
            ->flatten()
            ->filter()
            ->unique()
            ->all();

        $this->documents->prefetch($keys, $reset, $callback);

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="AssetDocumentObject">
    // =========================================================================
    protected function createFromAssetDocumentObject(AssetDocumentObject $object): ?DocumentModel {
        // Document exists?
        if (!isset($object->document->document->id)) {
            throw new ViewAssetDocumentNoDocument($object->document);
        }

        // Get/Create/Update
        $created = false;
        $factory = $this->factory(function (DocumentModel $model) use (&$created, $object): DocumentModel {
            // Update
            $created            = !$model->exists;
            $model->id          = $this->normalizer->uuid($object->document->document->id);
            $model->oem         = $this->documentOem($object->document->document);
            $model->type        = $this->documentType($object->document->document);
            $model->support     = $this->assetDocumentObjectSupport($object);
            $model->reseller    = $this->reseller($object->document->document);
            $model->customer    = $this->customer($object->document->document);
            $model->currency    = $this->currencies->create($object);
            $model->language    = $this->languages->create($object);
            $model->distributor = $this->distributors->create($object);
            $model->start       = $this->normalizer->datetime($object->document->document->startDate);
            $model->end         = $this->normalizer->datetime($object->document->document->endDate);
            $model->price       = $this->normalizer->number($object->document->document->totalNetPrice);
            $model->number      = $this->normalizer->string($object->document->document->documentNumber);
            $model->contacts    = $this->objectContacts($model, (array) $object->document->document->contactPersons);
            $model->entries     = $this->assetDocumentObjectEntries($model, $object);
            $model->save();

            // Return
            return $model;
        });
        $model   = $this->documents->get(
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

    protected function assetDocumentObjectSupport(AssetDocumentObject $document): ?Product {
        $product = null;
        $package = $document->document->supportPackage ?? null;
        $desc    = $document->document->supportPackageDescription ?? null;
        $oem     = isset($document->document->document)
            ? $this->documentOem($document->document->document)
            : $document->asset->oem;

        if ($oem && $package && $desc) {
            $type    = ProductType::support();
            $product = $this->product($oem, $type, $package, $desc, null, null);
        }

        return $product;
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

        foreach ($model->entries as $entry) {
            /** @var \App\Models\DocumentEntry $entry */
            if ($entry->asset_id === $assetId) {
                $existing[] = $entry;
            } else {
                $all[] = $entry;
            }
        }

        // Update entries:
        $compare = function (DocumentEntry $a, DocumentEntry $b): int {
            return $this->compareDocumentEntries($a, $b);
        };
        $entries = array_map(function (ViewAssetDocument $entry) use ($model, $document) {
            return $this->assetDocumentEntry($document->asset, $model, $entry);
        }, $document->entries);
        $keep    = array_uintersect($existing, $entries, $compare);
        $add     = array_udiff($entries, $existing, $compare);
        $all     = array_merge($all, $keep, $add);

        // Return
        return $all;
    }

    protected function assetDocumentEntry(
        AssetModel $asset,
        DocumentModel $document,
        ViewAssetDocument $assetDocument,
    ): DocumentEntry {
        $entry                = new DocumentEntry();
        $entry->asset         = $asset;
        $entry->product       = $asset->product;
        $entry->serial_number = $asset->serial_number;
        $entry->currency      = $this->currencies->create($assetDocument);
        $entry->net_price     = $this->normalizer->number($assetDocument->netPrice);
        $entry->list_price    = $this->normalizer->number($assetDocument->listPrice);
        $entry->discount      = $this->normalizer->number($assetDocument->discount);
        $entry->renewal       = $this->normalizer->number($assetDocument->estimatedValueRenewal);
        $entry->service       = $this->product(
            $document->oem,
            ProductType::service(),
            $assetDocument->skuNumber,
            $assetDocument->skuDescription,
            null,
            null,
        );

        return $entry;
    }

    protected function compareDocumentEntries(DocumentEntry $a, DocumentEntry $b): int {
        return $a->currency_id <=> $b->currency_id
            ?: $a->net_price <=> $b->net_price
            ?: $a->list_price <=> $b->list_price
            ?: $a->discount <=> $b->discount
            ?: $a->renewal <=> $b->renewal
            ?: $a->service_id <=> $b->service_id
            ?: 0;
    }
    // </editor-fold>

    // <editor-fold desc="Document">
    // =========================================================================
    protected function documentOem(ViewDocument $document): Oem {
        return $this->oem(
            $document->vendorSpecificFields->vendor,
            $document->vendorSpecificFields->vendor,
        );
    }

    protected function documentType(ViewDocument $document): TypeModel {
        return $this->type(new DocumentModel(), $document->type);
    }
    // </editor-fold>
}
