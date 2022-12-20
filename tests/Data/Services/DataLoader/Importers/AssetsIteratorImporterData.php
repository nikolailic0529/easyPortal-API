<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Asset;
use App\Services\DataLoader\Processors\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Services\DataLoader\Testing\Data\Context;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

class AssetsIteratorImporterData extends AssetsData {
    public const ASSETS = [
        '2f5dcdae-ed5a-4a87-a9b1-417819b638e4',
        'b9b64396-7d9a-4c4c-bfb3-7c7f0e342ec6',
        'fa66f78b-0580-4ca4-8c81-06d2d2f25cf5',
        '91fe9f38-1137-424e-bde7-90bd367ee016',
        'cf82deb8-e098-4f15-98bd-fe890cbc89cf',
        'f5135d55-81fd-40e7-9bb5-a227f5516b87',
        '16425fd0-0107-4a3f-a294-d27a5802abfa',
        '3a010157-00fa-4110-87d9-cee7a5ba8482',
        '5d62ee0b-ebdf-4a76-bcab-60078f6eba32',
        'b01ad503-d3f7-47d7-96ce-c0d37a9e3307',
        '00000000-0000-0000-0000-000000000000',
    ];

    protected function generateData(TestData $root, Context $context): bool {
        return $this->createAsset()
            && $this->app->make(IteratorImporter::class)
                ->setIterator(static::getIterator())
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();
    }

    public function restore(TestData $root, Context $context): bool {
        return $this->createAsset()
            && parent::restore($root, $context);
    }

    /**
     * @return ObjectIterator<Asset|string>
     */
    public static function getIterator(): ObjectIterator {
        return static::getModelsIterator(Asset::class, static::ASSETS);
    }

    private function createAsset(): bool {
        Asset::factory()->create([
            'id'          => '00000000-0000-0000-0000-000000000000',
            'reseller_id' => null,
            'customer_id' => null,
            'oem_id'      => null,
            'type_id'     => null,
            'product_id'  => null,
            'location_id' => null,
            'status_id'   => null,
            'synced_at'   => Date::now(),
        ]);

        return true;
    }
}
