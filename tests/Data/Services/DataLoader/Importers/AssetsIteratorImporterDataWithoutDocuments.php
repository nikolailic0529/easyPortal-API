<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Asset;
use App\Services\DataLoader\Importer\Importers\AssetsIteratorImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;

use function array_fill_keys;
use function array_flip;
use function array_rand;
use function count;
use function round;

class AssetsIteratorImporterDataWithoutDocuments extends AssetsData {
    public const LIMIT     = null;
    public const CHUNK     = 10;
    public const DOCUMENTS = false;
    public const ASSETS    = [
        '50670a45-ff6e-4008-baa2-7677d0f59a99',
        '94f3c58a-8609-4adf-ac81-25c50e1b38eb',
        '6b537397-4fe6-4f12-8fd2-4dc2421fd46c',
        '8b77fddd-1e19-42f4-92e7-c0f7f9f33ab2',
        '33d080a9-af7e-47ca-9afc-7de11d7a7cbe',
        'e87c18e7-caf6-4577-b1fe-5ca5ac5b0334',
        'a159098b-cea0-4a97-98f7-3b18fbb36311',
        'dd446309-f8fa-4c5e-952d-d335eaa08678',
        'badee429-473d-4f23-9d1a-ea2b13276596',
        '622403ca-1bcb-4268-86ef-98c559ba3767',
        '00000000-0000-0000-0000-000000000000',
    ];

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(AssetsIteratorImporter::class)
                ->setIterator(static::getIterator())
                ->setWithDocuments(static::DOCUMENTS)
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();

            return true;
        });
    }

    /**
     * @return ObjectIterator<Asset|string>
     */
    public static function getIterator(): ObjectIterator {
        $assets = static::ASSETS;
        $models = array_fill_keys((array) array_rand(array_flip($assets), (int) round(count($assets) / 2)), true);
        $model  = new Asset();

        foreach ($assets as $key => $id) {
            $assets[$key] = isset($models[$id])
                ? (clone $model)->forceFill([$model->getKeyName() => $id])
                : $id;
        }

        return new ObjectsIterator(
            Mockery::mock(ExceptionHandler::class),
            $assets,
        );
    }
}
