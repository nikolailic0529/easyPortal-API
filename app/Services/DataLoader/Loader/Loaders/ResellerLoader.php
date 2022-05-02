<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Importer\Importers\AssetsImporter;
use App\Services\DataLoader\Importer\Importers\DocumentsImporter;
use App\Services\DataLoader\Importer\Importers\ResellerAssetsImporter;
use App\Services\DataLoader\Importer\Importers\ResellerDocumentsImporter;
use App\Services\DataLoader\Loader\CompanyLoader;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Exception;

/**
 * @template TOwner of \App\Models\Reseller
 *
 * @extends CompanyLoader<TOwner>
 */
class ResellerLoader extends CompanyLoader {
    // <editor-fold desc="API">
    // =========================================================================
    protected function getObjectById(string $id): ?Type {
        return $this->client->getResellerById($id);
    }

    /**
     * @return ModelFactory<TOwner>
     */
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
    // </editor-fold>

    // <editor-fold desc="WithDocuments">
    // =========================================================================
    protected function getDocumentsImporter(Model $owner): DocumentsImporter {
        return $this->getContainer()
            ->make(ResellerDocumentsImporter::class)
            ->setResellerId($owner->getKey());
    }
    // </editor-fold>
}
