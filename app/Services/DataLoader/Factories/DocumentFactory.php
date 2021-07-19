<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Document as DocumentModel;
use App\Models\DocumentEntry;
use App\Models\Oem;
use App\Models\OemGroup;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\ViewAssetDocumentNoDocument;
use App\Services\DataLoader\Factories\Concerns\WithAssetDocument;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithCustomer;
use App\Services\DataLoader\Factories\Concerns\WithDistributor;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithOemGroup;
use App\Services\DataLoader\Factories\Concerns\WithProduct;
use App\Services\DataLoader\Factories\Concerns\WithReseller;
use App\Services\DataLoader\Factories\Concerns\WithServiceGroup;
use App\Services\DataLoader\Factories\Concerns\WithServiceLevel;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\FactoryPrefetchable;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\DistributorResolver;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemGroupResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolvers\ServiceLevelResolver;
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

class DocumentFactory extends ModelFactory implements FactoryPrefetchable {
    use WithOem;
    use WithOemGroup;
    use WithServiceGroup;
    use WithServiceLevel;
    use WithType;
    use WithProduct;
    use WithContacts;
    use WithReseller;
    use WithCustomer;
    use WithDistributor;
    use WithAssetDocument;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected OemResolver $oems,
        protected TypeResolver $types,
        protected ResellerResolver $resellerResolver,
        protected CustomerResolver $customerResolver,
        protected ProductResolver $products,
        protected CurrencyFactory $currencies,
        protected DocumentResolver $documents,
        protected LanguageFactory $languages,
        protected DistributorResolver $distributorResolver,
        protected ContactFactory $contacts,
        protected OemGroupResolver $oemGroupResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
        protected ?DistributorFinder $distributorFinder = null,
        protected ?ResellerFinder $resellerFinder = null,
        protected ?CustomerFinder $customerFinder = null,
        protected ?ServiceGroupFinder $serviceGroupFinder = null,
        protected ?ServiceLevelFinder $serviceLevelFinder = null,
    ) {
        parent::__construct($logger, $normalizer);
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
        return $this->contacts;
    }

    protected function getOemResolver(): OemResolver {
        return $this->oems;
    }

    protected function getProductResolver(): ProductResolver {
        return $this->products;
    }

    protected function getTypeResolver(): TypeResolver {
        return $this->types;
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
                return array_map(static function (ViewAssetDocument $document): ?string {
                    return $document->document->id ?? null;
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
            $created             = !$model->exists;
            $document            = $object->document->document;
            $model->id           = $this->normalizer->uuid($document->id);
            $model->oem          = $this->documentOem($document);
            $model->oemGroup     = $this->documentOemGroup($document);
            $model->oem_said     = $this->normalizer->string($document->vendorSpecificFields->said ?? null);
            $model->type         = $this->documentType($document);
            $model->serviceGroup = $this->assetDocumentObjectServiceGroup($object);
            $model->reseller     = $this->reseller($document);
            $model->customer     = $this->customer($document);
            $model->currency     = $this->currencies->create($object);
            $model->language     = $this->languages->create($object);
            $model->distributor  = $this->distributor($document);
            $model->start        = $this->normalizer->datetime($document->startDate);
            $model->end          = $this->normalizer->datetime($document->endDate);
            $model->price        = $this->normalizer->number($document->totalNetPrice);
            $model->number       = $this->normalizer->string($document->documentNumber);
            $model->changed_at   = $this->normalizer->datetime($document->updatedAt);
            $model->contacts     = $this->objectContacts($model, (array) $document->contactPersons);
            $model->entries      = $this->assetDocumentObjectEntries($model, $object);
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

    protected function assetDocumentObjectServiceGroup(AssetDocumentObject $document): ?ServiceGroup {
        return $this->assetDocumentServiceGroup($document->asset, $document->document);
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
        $entry->serviceLevel  = $this->assetDocumentServiceLevel($asset, $assetDocument);

        return $entry;
    }

    protected function compareDocumentEntries(DocumentEntry $a, DocumentEntry $b): int {
        return $a->currency_id <=> $b->currency_id
            ?: $a->net_price <=> $b->net_price
            ?: $a->list_price <=> $b->list_price
            ?: $a->discount <=> $b->discount
            ?: $a->renewal <=> $b->renewal
            ?: $a->service_level_id <=> $b->service_level_id
            ?: 0;
    }
    // </editor-fold>

    // <editor-fold desc="Document">
    // =========================================================================
    protected function documentOemGroup(ViewDocument $document): ?OemGroup {
        $key   = $document->vendorSpecificFields->groupId ?? null;
        $desc  = $document->vendorSpecificFields->groupDescription ?? null;
        $group = null;

        if ($key) {
            $oem   = $this->documentOem($document);
            $group = $this->oemGroup($oem, $key, (string) $desc);
        }

        return $group;
    }

    protected function documentType(ViewDocument $document): TypeModel {
        return $this->type(new DocumentModel(), $document->type);
    }
    // </editor-fold>
}
