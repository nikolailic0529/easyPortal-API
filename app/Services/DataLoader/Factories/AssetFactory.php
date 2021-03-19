<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Customer;
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
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function array_map;
use function sprintf;

class AssetFactory extends ModelFactory {
    use WithOem;
    use WithType;

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

    protected function assetOem(Asset $asset): Oem {
        return $this->oem($asset->vendor, $asset->vendor);
    }

    protected function assetType(Asset $asset): TypeModel {
        return $this->type(new AssetModel(), $asset->assetType);
    }

    protected function assetProduct(Asset $asset): Product {
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

    protected function product(
        Oem $oem,
        string $sku,
        string $name,
        ?string $eol,
        ?string $eos,
    ): Product {
        // Get/Create
        $type    = ProductType::asset();
        $created = false;
        $factory = $this->factory(
            function (Product $product) use (&$created, $type, $oem, $sku, $name, $eol, $eos): Product {
                $created       = !$product->exists;
                $product->type = $type;
                $product->oem  = $oem;
                $product->sku  = $this->normalizer->string($sku);
                $product->name = $this->normalizer->string($name);
                $product->eol  = $this->normalizer->datetime($eol);
                $product->eos  = $this->normalizer->datetime($eos);

                $product->save();

                return $product;
            },
        );
        $product = $this->products->get(
            $type,
            $oem,
            $sku,
            static function () use ($factory): Product {
                return $factory(new Product());
            },
        );

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($product);
        }

        // Return
        return $product;
    }
    // </editor-fold>
}
