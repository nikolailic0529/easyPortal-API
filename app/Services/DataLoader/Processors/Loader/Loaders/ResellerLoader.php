<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Loaders;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Processors\Importer\Importers\Resellers\AssetsImporter;
use App\Services\DataLoader\Processors\Importer\Importers\Resellers\DocumentsImporter;
use App\Services\DataLoader\Processors\Importer\Importers\Resellers\IteratorImporter;
use App\Services\DataLoader\Processors\Loader\CallbackLoader;
use App\Services\DataLoader\Processors\Loader\CompanyLoader;
use App\Services\DataLoader\Processors\Loader\CompanyLoaderState;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends CompanyLoader<CompanyLoaderState>
 */
class ResellerLoader extends CompanyLoader {
    // <editor-fold desc="Loader">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function getOperations(CompositeState $state): array {
        return [
            new CompositeOperation(
                'Update properties',
                function (CompanyLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
                        ->setForce($state->force)
                        ->setIterator(new ObjectsIterator([
                            Reseller::query()->whereKey($state->objectId)->first() ?? $state->objectId,
                        ]));
                },
            ),
            ...$this->getAssetsOperations(),
            ...$this->getDocumentsOperations(),
            new CompositeOperation(
                'Recalculate',
                function (CompanyLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(CallbackLoader::class)
                        ->setObjectId($state->objectId)
                        ->setCallback(static function (Dispatcher $dispatcher, Client $client, string $objectId): void {
                            $dispatcher->dispatch(new DataImported(
                                (new Data())->add(Reseller::class, $objectId),
                            ));
                        });
                },
            ),
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
