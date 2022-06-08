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
    public const LIMIT     = null;
    public const CHUNK     = 10;
    public const CUSTOMERS = [
        '06502d97-fa26-42cc-9478-34fbc1910b71',
        'fd865591-18fc-4278-9c35-000e761d49d9',
        'cc5799fa-e7c3-4a47-b879-e5f731a7ed8f',
        'f6d8ddba-f3bb-4f83-904d-277d43e7e0a0',
        'ca3fd926-367d-4690-bde9-e895916ab4df',
        '8a25f6f7-1e65-43f7-aa10-64e1dc7f3ec8',
        '3125937c-24bf-46ad-97c5-0727cb3b76bd',
        'f67c0662-4715-4807-a5fb-807d75d5c92d',
        '9ed7937c-537c-460f-abc4-d167aab8c28c',
        'ada5cb92-2351-4b09-9502-7f767805b2f3',
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
