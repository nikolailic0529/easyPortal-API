<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Document;
use App\Services\DataLoader\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Loader\Loaders\DocumentLoader;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Iterators\Eloquent\EloquentIterator;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;

class DocumentSync extends Sync {
    public function displayName(): string {
        return 'ep-data-loader-document-sync';
    }

    public function init(Document $document): static {
        return $this
            ->setObjectId($document->getKey())
            ->initialized();
    }

    /**
     * @return array{result: bool, assets: bool}
     */
    public function __invoke(ExceptionHandler $handler, Container $container, DocumentLoader $loader): array {
        return GlobalScopes::callWithoutAll(function () use ($container, $handler, $loader): array {
            $result = $this->syncProperties($handler, $loader);
            $assets = $result && $this->syncAssets($handler, $container);

            return [
                'result' => $result,
                'assets' => $assets,
            ];
        });
    }

    protected function syncProperties(ExceptionHandler $handler, DocumentLoader $loader): bool {
        try {
            return $loader
                ->setObjectId($this->getObjectId())
                ->start();
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }

    protected function syncAssets(ExceptionHandler $handler, Container $container): bool {
        // todo(DataLoader): Seems would be good to use Batches?

        try {
            $result   = true;
            $document = Document::query()->whereKey($this->getObjectId())->first();

            if ($document) {
                $iterator = $document->assets()->getQuery()->getChangeSafeIterator();
                $iterator = new EloquentIterator($iterator);
                $result   = $container
                    ->make(IteratorImporter::class)
                    ->setIterator($iterator)
                    ->setWithDocuments(true)
                    ->start();
            }
        } catch (Exception $exception) {
            $handler->report($exception);

            $result = false;
        }

        return $result;
    }
}
