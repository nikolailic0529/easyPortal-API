<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Document;
use App\Services\DataLoader\Importer\Importers\Documents\IteratorImporter;
use App\Services\DataLoader\Testing\Data\DocumentsData;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;

use function array_fill_keys;
use function array_flip;
use function array_rand;
use function count;
use function round;

class DocumentsIteratorImporterData extends DocumentsData {
    public const DOCUMENTS = [
        '0aa9d168-1ccd-4ffd-a680-02a04d3e4b9a',
        '0c8324bb-600d-488b-9b53-35ee6849ff59',
        '05a153c5-251c-413b-92dd-0cd27d6ed8fd',
        '083145d4-b350-49cd-9844-d3998c411ce5',
        '07a09563-d1fe-4f0f-a286-115dc2742178',
        '0d2169e6-94b7-49ee-8560-80673d7009bf',
        '0c890540-172e-41ea-b1f3-f91a899437b5',
        '0821f5a6-95f1-493f-a527-c62a45ca1e13',
        '0a38d611-f78f-48e4-a861-8d56afaa8154',
        '013db1c0-c4b2-4f3a-830b-58de8b40ccc8',
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
     * @return ObjectIterator<Document|string>
     */
    public static function getIterator(): ObjectIterator {
        $documents = static::DOCUMENTS;
        $models    = array_fill_keys(
            (array) array_rand(array_flip($documents), (int) round(count($documents) / 2)),
            true,
        );
        $model     = new Document();

        foreach ($documents as $key => $id) {
            $documents[$key] = isset($models[$id])
                ? (clone $model)->forceFill([$model->getKeyName() => $id])
                : $id;
        }

        return new ObjectsIterator(
            $documents,
        );
    }
}
