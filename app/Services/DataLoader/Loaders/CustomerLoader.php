<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Customer;
use App\Models\Model;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\LoaderRecalculable;
use App\Services\DataLoader\Loaders\Concerns\WithAssets;
use App\Services\DataLoader\Schema\Type;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class CustomerLoader extends CompanyLoader implements LoaderRecalculable {
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

    protected function getObjectFactory(): ModelFactory {
        return $this->getCustomersFactory();
    }

    protected function getModelNotFoundException(string $id): Exception {
        return new CustomerNotFound($id);
    }
    // </editor-fold>

    // <editor-fold desc="WithAssets">
    // =========================================================================
    protected function getCurrentAssets(Model $owner): QueryIterator {
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
