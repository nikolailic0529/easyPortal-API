<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Document as DocumentModel;
use App\Models\Enums\ProductType;
use App\Models\Language;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Factories\Concerns\WithContacts;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithProduct;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
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
        protected ResellerResolver $resellers,
        protected CustomerResolver $customers,
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

        if ($type instanceof AssetDocument) {
            $model = $this->createFromAssetDocument($type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                AssetDocument::class,
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

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromAssetDocument(AssetDocument $document): ?DocumentModel {
        return $this->createFromDocument($document->document, $this->documentProduct($document));
    }

    protected function createFromDocument(Document $document, Product $product = null): ?DocumentModel {
        // Get/Create
        $created = false;
        $factory = $this->factory(function (DocumentModel $model) use (&$created, $document, $product): DocumentModel {
            $created         = !$model->exists;
            $model->id       = $this->normalizer->uuid($document->id);
            $model->oem      = $this->documentOem($document);
            $model->type     = $this->documentType($document);
            $model->product  = $product;
            $model->reseller = $this->documentReseller($document);
            $model->customer = $this->documentCustomer($document);
            $model->currency = $this->documentCurrency($document);
            $model->language = $this->documentLanguage($document);
            $model->start    = $this->normalizer->datetime($document->startDate);
            $model->end      = $this->normalizer->datetime($document->endDate);
            $model->price    = $this->normalizer->number($document->totalNetPrice);
            $model->number   = $this->normalizer->string($document->documentNumber);
            $model->contacts = $this->objectContacts($model, $document->contactPersons);
            $model->save();

            return $model;
        });
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

    protected function documentCurrency(Document $document): Currency {
        return $this->currencies->create($document);
    }

    protected function documentType(Document $document): TypeModel {
        return $this->type(new DocumentModel(), $document->type);
    }

    protected function documentProduct(AssetDocument $document): ?Product {
        $product     = null;
        $package     = $document->supportPackage ?? null;
        $description = $document->supportPackageDescription ?? null;

        if ($package && $description) {
            $oem     = $this->documentOem($document->document);
            $type    = ProductType::support();
            $product = $this->product($oem, $type, $package, $description, null, null);
        }

        return $product;
    }

    protected function documentReseller(Document $document): ?Reseller {
        $id       = $document->resellerId;
        $reseller = null;

        if ($id) {
            $reseller = $this->resellers->get($id);
        }

        if ($id && !$reseller) {
            throw new ResellerNotFoundException(sprintf(
                'Reseller `%s` not found (document `%s`).',
                $id,
                $document->id,
            ));
        }

        return $reseller;
    }

    protected function documentCustomer(Document $document): ?Customer {
        $id       = $document->customerId;
        $customer = null;

        if ($id) {
            $customer = $this->customers->get($id);
        }

        if ($id && !$customer) {
            throw new CustomerNotFoundException(sprintf(
                'Customer `%s` not found (document `%s`).',
                $id,
                $document->id,
            ));
        }

        return $customer;
    }

    protected function documentLanguage(Document $document): ?Language {
        return $this->languages->create($document);
    }
    // </editor-fold>
}
