<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Distributor;
use App\Services\DataLoader\Importer\Importers\Distributors\IteratorImporter;
use App\Services\DataLoader\Testing\Data\Data;
use App\Utils\Iterators\Contracts\ObjectIterator;

class DistributorsIteratorImporterData extends Data {
    public const DISTRIBUTORS = [
        '0920a7cb-3cf1-4a7d-bd84-f255cb753dec',
        '1af1c44e-8112-4e72-9654-b11c705e9372',
        'a985a692-c063-499a-ab2f-0a2adef86a3f',
        'efc39adf-0497-40a3-a6e6-bbd692f87516',
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
     * @inheritDoc
     */
    public function restore(string $path, array $context): bool {
        $result = parent::restore($path, $context);

        Distributor::factory()->create([
            'id' => '00000000-0000-0000-0000-000000000000',
        ]);

        return $result;
    }

    /**
     * @return ObjectIterator<Distributor|string>
     */
    public static function getIterator(): ObjectIterator {
        return static::getModelsIterator(Distributor::class, static::DISTRIBUTORS);
    }
}
