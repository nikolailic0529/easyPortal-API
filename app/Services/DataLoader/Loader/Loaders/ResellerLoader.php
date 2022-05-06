<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Models\Asset;
use App\Models\Document;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Importer\Importers\Resellers\AssetsImporter;
use App\Services\DataLoader\Importer\Importers\Resellers\DocumentsImporter;
use App\Services\DataLoader\Importer\Importers\Resellers\IteratorImporter;
use App\Services\DataLoader\Loader\CompanyLoader;
use App\Services\DataLoader\Loader\CompanyLoaderState;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\Contracts\Processor;
use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends CompanyLoader<CompanyLoaderState>
 */
class ResellerLoader extends CompanyLoader {
    // <editor-fold desc="Loader">
    // =========================================================================
    protected function getModelNotFoundException(string $id): Exception {
        return new ResellerNotFound($id);
    }

    /**
     * @inheritDoc
     */
    protected function operations(): array {
        return [
            new CompositeOperation(
                'Reseller update',
                function (CompanyLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
                        ->setIterator(new ObjectsIterator(
                            $this->getExceptionHandler(),
                            [$state->objectId],
                        ));
                },
            ),
            ...$this->getAssetsOperations(),
            ...$this->getDocumentsOperations(),
        ];
    }
    // </editor-fold>

    // <editor-fold desc="WithAssets">
    // =========================================================================
    protected function getAssetsImporter(CompanyLoaderState $state): AssetsImporter {
        return $this->getContainer()->make(AssetsImporter::class);
    }

    protected function getMissedAssets(CompanyLoaderState $state): Builder {
        return Asset::query()
            ->where('reseller_id', '=', $state->objectId)
            ->where('synced_at', '<', $state->started);
    }
    // </editor-fold>

    // <editor-fold desc="WithDocuments">
    // =========================================================================
    protected function getDocumentsImporter(CompanyLoaderState $state): DocumentsImporter {
        return $this->getContainer()->make(DocumentsImporter::class);
    }

    protected function getMissedDocuments(CompanyLoaderState $state): Builder {
        return Document::query()
            ->where('reseller_id', '=', $state->objectId)
            ->where('synced_at', '<', $state->started);
    }
    // </editor-fold>
}
