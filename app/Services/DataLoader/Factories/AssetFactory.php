<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Asset as AssetModel;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\DataLoaderException;
use App\Services\DataLoader\Factories\Concerns\WithType;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Providers\AssetProvider;
use App\Services\DataLoader\Providers\CustomerProvider;
use App\Services\DataLoader\Providers\OemProvider;
use App\Services\DataLoader\Providers\ProductProvider;
use App\Services\DataLoader\Providers\TypeProvider;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

use function sprintf;

class AssetFactory extends ModelFactory {
    use WithType;

    protected ?CustomerFactory $customerFactory = null;

    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected AssetProvider $assets,
        protected OemProvider $oems,
        protected TypeProvider $types,
        protected ProductProvider $products,
        protected CustomerProvider $customerProvider,
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
            $customer = $this->customerProvider->get($id, static function (): ?Customer {
                return null;
            });
        }

        if (!$customer && $this->customerFactory) {
            $customer = $this->customerFactory->create($asset->customer);
        }

        if ($id && !$customer) {
            throw new DataLoaderException(sprintf(
                'Customer `%s` not found (asset `%s`).',
                $id,
                $asset->id,
            ));
        }

        return $customer;
    }

    protected function assetLocation(Asset $asset, ?Customer $customer): ?Location {
        $location = null;

        if ($customer) {
            $location = $this->locations->find($customer, $asset);

            if (!$location) {
                throw new DataLoaderException(sprintf(
                    'Customer `%s` location not found (asset `%s`).',
                    $customer->getKey(),
                    $asset->id,
                ));
            }
        }

        return $location;
    }

    protected function oem(string $abbr, string $name): Oem {
        $oem = $this->oems->get($abbr, $this->factory(function () use ($abbr, $name): Oem {
            $model = new Oem();

            $model->abbr = $this->normalizer->string($abbr);
            $model->name = $this->normalizer->string($name);

            $model->save();

            return $model;
        }));

        return $oem;
    }

    protected function product(
        Oem $oem,
        string $sku,
        string $name,
        string $eol,
        string $eos,
    ): Product {
        // Get/Create
        $factory = $this->factory(function (Product $product) use ($oem, $sku, $name, $eol, $eos): Product {
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
        if (!$product->wasRecentlyCreated) {
            $factory(new Product());
        }

        // Return
        return $product;
    }

    protected function asset(
        string $id,
        Oem $oem,
        TypeModel $type,
        Product $product,
        Customer $customer,
        Location $location,
        string $serialNumber,
    ): AssetModel {
        // Get/Create
        $factory = $this->factory(
            function (
                AssetModel $asset,
            ) use (
                $id,
                $oem,
                $type,
                $product,
                $customer,
                $location,
                $serialNumber,
            ): AssetModel {
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
        if (!$asset->wasRecentlyCreated) {
            $factory($asset);
        }

        // Return
        return $asset;
    }
    // </editor-fold>
}
