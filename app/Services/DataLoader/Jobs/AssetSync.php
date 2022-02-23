<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Importer\Importers\AssetsIteratorImporter;
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

    public function __invoke(ExceptionHandler $handler, Client $client, AssetsIteratorImporter $importer): bool {
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
