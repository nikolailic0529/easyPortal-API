<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Customer;
use App\Services\DataLoader\Importer\Importers\Customers\IteratorImporter;
use App\Services\DataLoader\Testing\Data\ClientDumpContext;
use App\Services\DataLoader\Testing\Data\Data;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;

use function array_fill_keys;
use function array_flip;
use function array_rand;
use function count;
use function round;

class CustomersIteratorImporterData extends Data {
    public const CUSTOMERS = [
        '004d6d19-4a7d-4216-8bd5-55dbfb038e09',
        '5b043b1f-c7e6-462a-8090-d8c02a97c2f2',
        'fb368ca1-7aa6-4ec5-8c86-c39e211879e8',
        '909cf52d-f00f-422e-a488-cef47eaa0542',
        'c271b43c-be93-44a9-8595-259d355eb9f0',
        '6f4a5f12-e0fc-4af6-9ee2-e6830737ac41',
        '7fd29a80-1ab7-4bf8-9750-783245a989f3',
        'e8c4a536-312d-42d1-8b1d-86958d6660f5',
        '4eced8db-72a3-4f73-a9c6-30d85763d0ce',
        '792df4fc-ce83-4e94-a169-a4caeb979475',
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
    protected function generateContext(string $path): array {
        return $this->app->make(ClientDumpContext::class)->get($path, [
            ClientDumpContext::RESELLERS,
        ]);
    }

    /**
     * @return ObjectIterator<Customer|string>
     */
    public static function getIterator(): ObjectIterator {
        $customers = static::CUSTOMERS;
        $models    = array_fill_keys(
            (array) array_rand(array_flip($customers), (int) round(count($customers) / 2)),
            true,
        );
        $model     = new Customer();

        foreach ($customers as $key => $id) {
            $customers[$key] = isset($models[$id])
                ? (clone $model)->forceFill([$model->getKeyName() => $id])
                : $id;
        }

        return new ObjectsIterator(
            $customers,
        );
    }
}
