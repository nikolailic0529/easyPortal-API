<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Importer\Importers\AssetsImporter;
use App\Services\DataLoader\Importer\Importers\CustomerAssetsImporter;
use App\Services\DataLoader\Importer\Importers\CustomerDocumentsImporter;
use App\Services\DataLoader\Importer\Importers\CustomerDocumentsImporterState;
use App\Services\DataLoader\Importer\Importers\DocumentsImporter;
use App\Services\DataLoader\Loader\CompanyLoader;
use App\Services\DataLoader\Loader\Concerns\WithWarrantyCheck;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Exception;

/**
 * @template TOwner of \App\Models\Customer
 *
 * @extends CompanyLoader<TOwner>
 */
class CustomerLoader extends CompanyLoader {
    use WithWarrantyCheck;

    // <editor-fold desc="API">
    // =========================================================================
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
    // </editor-fold>

    // <editor-fold desc="WithDocuments">
    // =========================================================================
    /**
     * @param TOwner $owner
     *
     * @return DocumentsImporter<CustomerDocumentsImporterState>
     */
    protected function getDocumentsImporter(Model $owner): DocumentsImporter {
        return $this->getContainer()
            ->make(CustomerDocumentsImporter::class)
            ->setCustomerId($owner->getKey());
    }
    // </editor-fold>
}
