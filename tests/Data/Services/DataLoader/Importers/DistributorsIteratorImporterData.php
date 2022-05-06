<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Distributor;
use App\Services\DataLoader\Importer\Importers\Distributors\IteratorImporter;
use App\Services\DataLoader\Testing\Data\Data;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;

use function array_fill_keys;
use function array_flip;
use function array_rand;
use function count;
use function round;

class DistributorsIteratorImporterData extends Data {
    public const LIMIT        = null;
    public const CHUNK        = 10;
    public const DISTRIBUTORS = [
        '143c456a-e894-4710-a1c2-745b9582ca47',
        'eb1cb37a-48ab-43da-a01e-220220f8654a',
        '74262ef0-5df5-4479-9f7c-de0625970696',
        '562adf6b-1bc2-49b4-9d42-429e1afe5fc5',
        'f066f65a-6455-4cff-8dfb-0aaaeba544f9',
        '00000000-0000-0000-0000-000000000000',
    ];

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(IteratorImporter::class)
                ->setIterator(static::getIterator())
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();

            return true;
        });
    }

    /**
     * @return ObjectIterator<Distributor|string>
     */
    public static function getIterator(): ObjectIterator {
        $distributors = static::DISTRIBUTORS;
        $models       = array_fill_keys(
            (array) array_rand(array_flip($distributors), (int) round(count($distributors) / 2)),
            true,
        );
        $model        = new Distributor();

        foreach ($distributors as $key => $id) {
            $distributors[$key] = isset($models[$id])
                ? (clone $model)->forceFill([$model->getKeyName() => $id])
                : $id;
        }

        return new ObjectsIterator(
            Mockery::mock(ExceptionHandler::class),
            $distributors,
        );
    }
}
