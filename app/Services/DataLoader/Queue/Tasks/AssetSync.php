<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Tasks;

use App\Models\Asset;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Processors\Importer\Importers\Assets\IteratorImporter;
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
     * @return array{result: bool, warranty: bool}
     */
    public function __invoke(ExceptionHandler $handler, Client $client, IteratorImporter $importer): array {
        return GlobalScopes::callWithoutAll(function () use ($handler, $client, $importer): array {
            $asset    = Asset::query()->whereKey($this->getObjectId())->first();
            $result   = false;
            $warranty = false;

            if ($asset === null || ($asset->serial_number && $asset->product_id)) {
                $warranty = $this->checkWarranty($handler, $client);
                $result   = $warranty
                    && $this->syncProperties($handler, $importer);
            } else {
                $result = $this->syncProperties($handler, $importer);
            }

            return [
                'warranty' => $warranty,
                'result'   => $result,
            ];
        });
    }

    protected function checkWarranty(ExceptionHandler $handler, Client $client): bool {
        try {
            return $client->runAssetWarrantyCheck($this->getObjectId());
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }

    protected function syncProperties(
        ExceptionHandler $handler,
        IteratorImporter $importer,
    ): bool {
        try {
            $iterator = new ObjectsIterator([$this->getObjectId()]);
            $importer = $importer->setIterator($iterator);

            return $importer->start();
        } catch (Exception $exception) {
            $handler->report($exception);
        }

        return false;
    }
}
