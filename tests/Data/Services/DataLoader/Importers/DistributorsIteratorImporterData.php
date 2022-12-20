<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Distributor;
use App\Services\DataLoader\Processors\Importer\Importers\Distributors\IteratorImporter;
use App\Services\DataLoader\Testing\Data\Context;
use App\Services\DataLoader\Testing\Data\Data;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

class DistributorsIteratorImporterData extends Data {
    public const DISTRIBUTORS = [
        '0920a7cb-3cf1-4a7d-bd84-f255cb753dec',
        '1af1c44e-8112-4e72-9654-b11c705e9372',
        'a985a692-c063-499a-ab2f-0a2adef86a3f',
        'efc39adf-0497-40a3-a6e6-bbd692f87516',
        '00000000-0000-0000-0000-000000000000',
    ];

    protected function generateData(TestData $root, Context $context): bool {
        return $this->app->make(IteratorImporter::class)
            ->setIterator(static::getIterator())
            ->setChunkSize(static::CHUNK)
            ->setLimit(static::LIMIT)
            ->start();
    }

    public function restore(TestData $root, Context $context): bool {
        $result = parent::restore($root, $context);

        Distributor::factory()->create([
            'id'        => '00000000-0000-0000-0000-000000000000',
            'synced_at' => Date::now(),
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
