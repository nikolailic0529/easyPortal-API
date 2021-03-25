<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Document as DocumentModel;
use App\Models\Enums\ProductType;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithProduct;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
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

use function array_map;
use function sprintf;

class DocumentFactory extends ModelFactory {
    use WithOem;
    use WithType;
    use WithProduct;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected OemResolver $oems,
        protected TypeResolver $types,
        protected ResellerResolver $resellers,
        protected CustomerResolver $customers,
        protected ProductResolver $products,
        protected CurrencyResolver $currencies,
        protected DocumentResolver $documents,
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
                    return $document->document->id;
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
        // Get/Create
        $created = false;
        $factory = $this->factory(function (DocumentModel $model) use (&$created, $document): DocumentModel {
            $created         = !$model->exists;
            $model->id       = $this->normalizer->uuid($document->document->id);
            $model->oem      = $this->documentOem($document);
            $model->type     = $this->documentType($document);
            $model->product  = $this->documentProduct($document);
            $model->reseller = $this->documentReseller($document);
            $model->customer = $this->documentCustomer($document);
            $model->currency = $this->documentCurrency($document);
            $model->start    = $this->normalizer->datetime($document->document->startDate);
            $model->end      = $this->normalizer->datetime($document->document->endDate);
            $model->price    = $this->normalizer->price($document->document->vendorSpecificFields->totalNetPrice);
            $model->number   = $this->normalizer->string($document->document->documentId);

            $model->save();

            return $model;
        });
        $model   = $this->documents->get(
            $document->document->id,
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

    protected function documentOem(AssetDocument $document): Oem {
        return $this->oem(
            $document->document->vendorSpecificFields->vendor,
            $document->document->vendorSpecificFields->vendor,
        );
    }

    protected function documentCurrency(AssetDocument $document): Currency {
        $currency = $this->currencies->get('EUR', $this->factory(static function (): Currency {
            $model = new Currency();

            $model->code = 'EUR';
            $model->name = 'EUR';

            $model->save();

            return $model;
        }));

        return $currency;
    }

    protected function documentType(AssetDocument $document): TypeModel {
        return $this->type(new DocumentModel(), $document->document->type);
    }

    protected function documentProduct(AssetDocument $document): Product {
        $oem     = $this->documentOem($document);
        $type    = ProductType::support();
        $product = $this->product(
            $oem,
            $type,
            $document->supportPackage,
            $document->supportPackageDescription,
            null,
            null,
        );

        return $product;
    }

    protected function documentReseller(AssetDocument $document): ?Reseller {
        $id       = $document->document->resellerId;
        $reseller = null;

        if ($id) {
            $reseller = $this->resellers->get($id);
        }

        if ($id && !$reseller) {
            throw new ResellerNotFoundException(sprintf(
                'Reseller `%s` not found (document `%s`).',
                $id,
                $document->document->id,
            ));
        }

        return $reseller;
    }

    protected function documentCustomer(AssetDocument $document): ?Customer {
        $id       = $document->document->customerId;
        $customer = null;

        if ($id) {
            $customer = $this->customers->get($id);
        }

        if ($id && !$customer) {
            throw new CustomerNotFoundException(sprintf(
                'Customer `%s` not found (document `%s`).',
                $id,
                $document->document->id,
            ));
        }

        return $customer;
    }
    // </editor-fold>
}
