<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Document;
use App\Services\DataLoader\Commands\UpdateDocument;
use App\Services\DataLoader\Importer\Importers\AssetsIteratorImporter;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Iterators\EloquentIterator;
use App\Utils\Iterators\ObjectsIterator;
use Exception;
use Illuminate\Contracts\Console\Kernel;
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
    public function __invoke(ExceptionHandler $handler, Kernel $kernel, AssetsIteratorImporter $importer): array {
        return GlobalScopes::callWithoutGlobalScope(
            OwnedByOrganizationScope::class,
            function () use ($handler, $kernel, $importer): array {
                $result = $this->syncProperties($handler, $kernel);
                $assets = $result && $this->syncAssets($handler, $importer);

                return [
                    'result' => $result,
                    'assets' => $assets,
                ];
            },
        );
    }

    protected function syncProperties(ExceptionHandler $handler, Kernel $kernel): bool {
        try {
            return $this->isCommandSuccessful($kernel->call(UpdateDocument::class, $this->getOptions([
                'interaction' => false,
                'id'          => $this->getObjectId(),
            ])));
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }

    protected function syncAssets(ExceptionHandler $handler, AssetsIteratorImporter $importer): bool {
        try {
            $document = Document::query()->whereKey($this->getObjectId())->first();
            $iterator = $document
                ? new EloquentIterator($document->assets()->getQuery()->getChunkedIterator())
                : new ObjectsIterator($handler, []);

            return $importer
                ->setIterator($iterator)
                ->setWithDocuments(false)
                ->start();
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }
}
