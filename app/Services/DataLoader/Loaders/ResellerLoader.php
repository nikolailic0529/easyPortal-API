<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders;

use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\LoaderRecalculable;
use App\Services\DataLoader\Loaders\Concerns\WithAssets;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use App\Utils\Iterators\ObjectIterator;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class ResellerLoader extends Loader implements LoaderRecalculable {
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
        return $this->client->getResellerById($id);
    }

    protected function getObjectFactory(): ModelFactory {
        return $this->getResellersFactory();
    }

    protected function getModelNotFoundException(string $id): Exception {
        return new ResellerNotFound($id);
    }
    // </editor-fold>

    // <editor-fold desc="WithAssets">
    // =========================================================================
    protected function getCurrentAssets(Model $owner): ObjectIterator {
        return $this->isWithAssetsDocuments()
            ? $this->client->getAssetsByResellerIdWithDocuments($owner->getKey())
            : $this->client->getAssetsByResellerId($owner->getKey());
    }

    /**
     * @inheritdoc
     */
    protected function getMissedAssets(Model $owner, array $current): ?Builder {
        return $owner instanceof Reseller
            ? $owner->assets()->whereNotIn('id', $current)->getQuery()
            : null;
    }
    // </editor-fold>
}
