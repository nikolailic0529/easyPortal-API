<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Importer\Importers\Assets\IteratorImporter;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Iterators\ObjectsIterator;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;

class AssetSync extends Sync {
    public function displayName(): string {
        return 'ep-data-loader-asset-sync';
    }

    public function init(Asset $asset): static {
        return $this
            ->setObjectId($asset->getKey())
            ->initialized();
    }

    /**
     * @return array{result: bool}
     */
    public function __invoke(ExceptionHandler $handler, Client $client, IteratorImporter $importer): array {
        return GlobalScopes::callWithout(
            OwnedByOrganizationScope::class,
            function () use ($handler, $client, $importer): array {
                return [
                    'result' => $this->syncProperties($handler, $client, $importer),
                ];
            },
        );
    }

    protected function syncProperties(
        ExceptionHandler $handler,
        Client $client,
        IteratorImporter $importer,
    ): bool {
        try {
            $iterator = new ObjectsIterator($handler, [$this->getObjectId()]);
            $importer = $importer
                ->setIterator($iterator)
                ->setWithDocuments(true);

            return $client->runAssetWarrantyCheck($this->getObjectId())
                && $importer->start();
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }
}
