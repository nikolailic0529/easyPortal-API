<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry;
use App\Models\Enums\ProductType;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithProduct;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
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

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected OemResolver $oems,
        protected TypeResolver $types,
        protected ResellerFactory $resellers,
        protected CustomerFactory $customers,
        protected ProductResolver $products,
        protected CurrencyFactory $currencies,
        protected DocumentResolver $documents,
        protected LanguageFactory $languages,
    ) {
        parent::__construct($logger, $normalizer);
    }

    public function find(Type $type): ?DocumentModel {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($type);
    }

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
                    Document::class,
                ]),
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
        $keys = (new Collection($assets))
            ->map(static function (Asset $asset): array {
                return array_map(static function (AssetDocument $document): string {
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
    protected function createFromAssetDocumentObject(AssetDocumentObject $document): ?DocumentModel {
        // Get/Create/Update
        $model   = null;
        $product = $this->factory(function () use ($document): ?Product {
            return $this->assetDocumentObjectSupport($document);
        });
        $entries = $this->factory(function (DocumentModel $model) use ($document) {
            return $this->assetDocumentObjectEntries($model, $document);
        });

        if (isset($document->document->document)) {
            $model = $this->createFromDocument($document->document->document, $product, $entries);
        } else {
            $created = false;
            $factory = $this->factory(
                function (DocumentModel $model) use (&$created, $document, $product, $entries): DocumentModel {
                    $created         = !$model->exists;
                    $model->id       = $this->normalizer->string($document->document->documentNumber);
                    $model->oem      = $document->asset->oem;
                    $model->type     = $this->type(new DocumentModel(), '??');
                    $model->support  = $product($model);
                    $model->reseller = $this->resellers->create($document);
                    $model->customer = $this->customers->create($document);
                    $model->currency = $this->currencies->create($document);
                    $model->language = $this->languages->create($document);
                    $model->price    = null;
                    $model->number   = $this->normalizer->string($document->document->documentNumber);
                    $model->start    = $this->normalizer->datetime($document->document->startDate);
                    $model->end      = $this->normalizer->datetime($document->document->endDate);
                    $model->contacts = [];
                    $model->entries  = $entries($model);

                    $model->save();

                    return $model;
                },
            );
            $model   = $this->documents->get(
                $document->document->documentNumber,
                static function () use ($factory): DocumentModel {
                    return $factory(new DocumentModel());
                },
            );

            // Update
            if (!$created && !$this->isSearchMode()) {
                $factory($model);
            }
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
        $entries = array_map(function (AssetDocument $entry) use ($model, $document) {
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
        AssetDocument $assetDocument,
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
    protected function createFromDocument(
        Document $document,
        Closure $product = null,
        Closure $entries = null,
    ): ?DocumentModel {
        // WARNING: Document and Document.entries doesn't contains all required
        //      information to create Document.

        // Get/Create
        $created = false;
        $factory = $this->factory(
            function (DocumentModel $model) use (&$created, $document, $product, $entries): DocumentModel {
                // We can have a document that was created with ID = number,
                // now we know its ID and can update or remove it.
                $existing = $this->documents->get($document->documentNumber);
                $created  = !$model->exists;

                if ($existing) {
                    if ($model->exists) {
                        $existing->delete();
                    } else {
                        $model = $existing;
                    }
                }

                // Update
                $model->id       = $this->normalizer->uuid($document->id);
                $model->oem      = $this->documentOem($document);
                $model->type     = $this->documentType($document);
                $model->support  = $product ? $product($model) : null;
                $model->reseller = $this->resellers->create($document);
                $model->customer = $this->customers->create($document);
                $model->currency = $this->currencies->create($document);
                $model->language = $this->languages->create($document);
                $model->start    = $this->normalizer->datetime($document->startDate);
                $model->end      = $this->normalizer->datetime($document->endDate);
                $model->price    = $this->normalizer->number($document->totalNetPrice);
                $model->number   = $this->normalizer->string($document->documentNumber);
                $model->contacts = $this->objectContacts($model, $document->contactPersons);
                $model->entries  = $entries ? $entries($model) : [/** TODO */];
                $model->save();

                // Return
                return $model;
            },
        );
        $model   = $this->documents->get(
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

    protected function documentOem(Document $document): Oem {
        return $this->oem(
            $document->vendorSpecificFields->vendor,
            $document->vendorSpecificFields->vendor,
        );
    }

    protected function documentType(Document $document): TypeModel {
        return $this->type(new DocumentModel(), $document->type);
    }
    // </editor-fold>
}
