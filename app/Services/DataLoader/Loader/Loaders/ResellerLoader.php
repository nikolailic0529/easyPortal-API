<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Models\Document;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Importer\Importers\AssetsImporter;
use App\Services\DataLoader\Importer\Importers\DocumentsImporter;
use App\Services\DataLoader\Importer\Importers\ResellerAssetsImporter;
use App\Services\DataLoader\Importer\Importers\ResellerDocumentsImporter;
use App\Services\DataLoader\Loader\CompanyLoader;
use App\Services\DataLoader\Loader\Concerns\WithDocuments;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TOwner of \App\Models\Reseller
 *
 * @extends CompanyLoader<TOwner>
 */
class ResellerLoader extends CompanyLoader {
    /**
     * @phpstan-use \App\Services\DataLoader\Loader\Concerns\WithDocuments<TOwner>
     */
    use WithDocuments;

    // <editor-fold desc="API">
    // =========================================================================
    protected function process(?Type $object): ?Model {
        // Process
        $company = parent::process($object);

        if ($this->isWithDocuments() && $company) {
            $this->loadDocuments($company);
        }

        // Return
        return $company;
    }

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

    // <editor-fold desc="WithAssets">
    // =========================================================================
    protected function getDocumentsImporter(Model $owner): DocumentsImporter {
        return $this->getContainer()
            ->make(ResellerDocumentsImporter::class)
            ->setResellerId($owner->getKey());
    }

    /**
     * @return Builder<Document>
     */
    protected function getMissedDocuments(Model $owner, DateTimeInterface $datetime): Builder {
        return $owner->documents()->where('synced_at', '<', $datetime)->getQuery();
    }
    // </editor-fold>
}
