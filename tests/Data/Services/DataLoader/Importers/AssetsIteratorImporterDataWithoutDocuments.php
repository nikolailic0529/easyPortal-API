<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Asset;
use App\Services\DataLoader\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Utils\Iterators\Contracts\ObjectIterator;

class AssetsIteratorImporterDataWithoutDocuments extends AssetsData {
    public const DOCUMENTS = false;
    public const ASSETS    = [
        'f8c66877-b503-457a-a978-e6d4e1237628',
        '299ba28d-d7f8-4b08-803c-643d7d3d4829',
        '7859879b-5a3e-40f2-bb06-2ec0cf61de76',
        'e3c2178a-2bbb-4cb8-8929-9b6411930ea4',
        '35ccd425-0a25-4178-ad23-b53f5cb391bd',
        '7b549172-dbb5-4065-836a-125a4d1be428',
        '0687f0ee-4729-432b-8422-60917635742d',
        '0260d0b8-30cc-4a12-a123-3b103804d77b',
        '9d48cd6d-1ab4-44ef-974b-24f43d58b18c',
        'acdf826c-6d18-4c99-a5c7-f0c8258e7305',
        '00000000-0000-0000-0000-000000000000',
    ];

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(IteratorImporter::class)
                ->setIterator(static::getIterator())
                ->setWithDocuments(static::DOCUMENTS)
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

        Asset::factory()->create([
            'id'          => '00000000-0000-0000-0000-000000000000',
            'reseller_id' => null,
            'customer_id' => null,
            'oem_id'      => null,
            'type_id'     => null,
            'product_id'  => null,
            'location_id' => null,
            'status_id'   => null,
        ]);

        return $result;
    }

    /**
     * @return ObjectIterator<Asset|string>
     */
    public static function getIterator(): ObjectIterator {
        return static::getModelsIterator(Asset::class, static::ASSETS);
    }
}
