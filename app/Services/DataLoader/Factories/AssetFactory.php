<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Exceptions\LocationNotFoundException;
use App\Services\DataLoader\Factories\Concerns\WithOem;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ProductResolver;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function sprintf;

class AssetFactory extends ModelFactory {
    use WithOem;
    use WithType;

    protected ?CustomerFactory $customerFactory = null;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected AssetResolver $assets,
        protected OemResolver $oems,
        protected TypeResolver $types,
        protected ProductResolver $products,
        protected CustomerResolver $customerResolver,
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

    // <editor-fold desc="Functions">
    // =========================================================================
    protected function createFromAsset(Asset $asset): ?AssetModel {
        $oem      = $this->assetOem($asset);
        $type     = $this->assetType($asset);
        $product  = $this->assetProduct($asset);
        $customer = $this->assetCustomer($asset);
        $location = $this->assetLocation($asset, $customer);
        $model    = $this->asset($asset->id, $oem, $type, $product, $customer, $location, $asset->serialNumber);

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

    protected function assetCustomer(Asset $asset): ?Customer {
        $id       = $asset->customerId ?? (isset($asset->customer) ? $asset->customer->id : null);
        $customer = null;

        if ($id) {
            $customer = $this->customerResolver->get($id);
        }

        if (!$customer && $this->customerFactory) {
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

    protected function assetLocation(Asset $asset, ?Customer $customer): ?Location {
        $location = null;
        $required = null
            || ($asset->zip ?? null)
            || ($asset->city ?? null)
            || ($asset->address ?? null)
            || ($asset->address2 ?? null);

        if ($customer) {
            $location = $this->locations->find($customer, $asset);
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
        $created = false;
        $factory = $this->factory(function (Product $product) use (&$created, $oem, $sku, $name, $eol, $eos): Product {
            $created       = !$product->exists;
            $product->oem  = $oem;
            $product->sku  = $this->normalizer->string($sku);
            $product->name = $this->normalizer->string($name);
            $product->eol  = $this->normalizer->datetime($eol);
            $product->eos  = $this->normalizer->datetime($eos);

            $product->save();

            return $product;
        });
        $product = $this->products->get(
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

    protected function asset(
        string $id,
        Oem $oem,
        TypeModel $type,
        Product $product,
        ?Customer $customer,
        ?Location $location,
        string $serialNumber,
    ): AssetModel {
        // Get/Create
        $created = false;
        $factory = $this->factory(
            function (
                AssetModel $asset,
            ) use (
                &$created,
                $id,
                $oem,
                $type,
                $product,
                $customer,
                $location,
                $serialNumber,
            ): AssetModel {
                $created              = !$asset->exists;
                $asset->id            = $id;
                $asset->oem           = $oem;
                $asset->type          = $type;
                $asset->product       = $product;
                $asset->customer      = $customer;
                $asset->location      = $location;
                $asset->serial_number = $this->normalizer->string($serialNumber);

                $asset->save();

                return $asset;
            },
        );
        $asset   = $this->assets->get(
            $id,
            static function () use ($factory): AssetModel {
                return $factory(new AssetModel());
            },
        );

        // Update
        if (!$created && !$this->isSearchMode()) {
            $factory($asset);
        }

        // Return
        return $asset;
    }
    // </editor-fold>
}
