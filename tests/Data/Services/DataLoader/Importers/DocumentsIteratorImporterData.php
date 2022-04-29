<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Document;
use App\Services\DataLoader\Importer\Importers\DocumentsIteratorImporter;
use App\Services\DataLoader\Testing\Data\DocumentsData;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;

use function array_fill_keys;
use function array_flip;
use function array_rand;
use function count;
use function round;

class DocumentsIteratorImporterData extends DocumentsData {
    public const LIMIT     = null;
    public const CHUNK     = 10;
    public const DOCUMENTS = [
        'd472ed2c-ed56-4aa4-9d0a-eb4d22dc5bf6',
        'e17cfda0-d8df-4007-8dd8-7f327376c9f8',
        '3cae1cdf-5610-4f49-af6b-5a68949f7e02',
        'e476f5e7-5dbc-4346-9acd-12df7278c4d0',
        'bd2f1205-d472-4203-8301-a4b761f7515a',
        '0f14c721-e16b-46bf-a41e-47f8973964fe',
        'b8a8d830-b7df-4c86-b2fa-c1309441f461',
        'ef318f78-9909-45b0-a3dc-f829322378e6',
        '5fc9b61a-8796-46ba-b649-4f8aaf00631f',
        '2dd89a24-ba60-492f-a2e5-322e8b9a89d9',
        '00000000-0000-0000-0000-000000000000',
    ];

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(DocumentsIteratorImporter::class)
                ->setIterator(static::getIterator())
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();

            return true;
        });
    }

    /**
     * @return ObjectIterator<Document|string>
     */
    public static function getIterator(): ObjectIterator {
        $documents = static::DOCUMENTS;
        $models    = array_fill_keys((array) array_rand(array_flip($documents), (int) round(count($documents) / 2)), true);
        $model     = new Document();

        foreach ($documents as $key => $id) {
            $documents[$key] = isset($models[$id])
                ? (clone $model)->forceFill([$model->getKeyName() => $id])
                : $id;
        }

        return new ObjectsIterator(
            Mockery::mock(ExceptionHandler::class),
            $documents,
        );
    }
}
