<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Importer\Importers\AssetsImporter;
use App\Services\DataLoader\Importer\Importers\ResellerAssetsImporter;
use App\Services\DataLoader\Loader\Concerns\WithAssets;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\LoaderRecalculable;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use DateTimeInterface;
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
    protected function getAssetsImporter(Model $owner): AssetsImporter {
        return $this->getContainer()
            ->make(ResellerAssetsImporter::class)
            ->setResellerId($owner->getKey());
    }

    protected function getMissedAssets(Model $owner, DateTimeInterface $datetime): ?Builder {
        return $owner instanceof Reseller
            ? $owner->assets()->where('synced_at', '<', $datetime)->getQuery()
            : null;
    }
    // </editor-fold>
}
