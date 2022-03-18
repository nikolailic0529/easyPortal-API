<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Importer\Importers\AssetsImporter;
use App\Services\DataLoader\Importer\Importers\CustomerAssetsImporter;
use App\Services\DataLoader\Loader\Concerns\WithAssets;
use App\Services\DataLoader\Loader\Concerns\WithWarrantyCheck;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TOwner of \App\Models\Customer
 */
class CustomerLoader extends Loader {
    use WithWarrantyCheck;

    /**
     * @phpstan-use \App\Services\DataLoader\Loader\Concerns\WithAssets<TOwner>
     */
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
    protected function getAssetsImporter(Model $owner): AssetsImporter {
        return $this->getContainer()
            ->make(CustomerAssetsImporter::class)
            ->setCustomerId($owner->getKey());
    }

    /**
     * @param TOwner $owner
     */
    protected function getMissedAssets(Model $owner, DateTimeInterface $datetime): Builder {
        return $owner->assets()->where('synced_at', '<', $datetime)->getQuery();
    }
    // </editor-fold>
}
