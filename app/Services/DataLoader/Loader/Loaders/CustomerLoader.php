<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Models\Customer;
use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Loader\Concerns\WithAssets;
use App\Services\DataLoader\Loader\Concerns\WithWarrantyCheck;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\LoaderRecalculable;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use App\Utils\Iterators\ObjectIterator;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class CustomerLoader extends Loader implements LoaderRecalculable {
    use WithWarrantyCheck;
    use WithAssets;

    // <editor-fold desc="API">
    // =========================================================================
    protected function process(?Type $object): ?Model {
        // Process
        $company = parent::process($object);

        if ($this->isWithAssets() && $company) {
            $this->loadAssets($company);
        }

        // Return
        return $company;
    }

    /**
     * @inheritDoc
     */
    protected function getObject(array $properties): ?Type {
        return new Company($properties);
    }

    protected function getObjectById(string $id): ?Type {
        if ($this->isWithWarrantyCheck()) {
            $this->runCustomerWarrantyCheck($id);
        }

        return $this->client->getCustomerById($id);
    }

    protected function getObjectFactory(): ModelFactory {
        return $this->getCustomersFactory();
    }

    protected function getModelNotFoundException(string $id): Exception {
        return new CustomerNotFound($id);
    }
    // </editor-fold>

    // <editor-fold desc="WithAssets">
    // =========================================================================
    protected function getCurrentAssets(Model $owner): ObjectIterator {
        return $this->isWithAssetsDocuments()
            ? $this->client->getAssetsByCustomerIdWithDocuments($owner->getKey())
            : $this->client->getAssetsByCustomerId($owner->getKey());
    }

    /**
     * @inheritdoc
     */
    protected function getMissedAssets(Model $owner, array $current): ?Builder {
        return $owner instanceof Customer
            ? $owner->assets()->whereNotIn('id', $current)->getQuery()
            : null;
    }
    // </editor-fold>
}
