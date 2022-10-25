<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Synchronizers;

use App\Models\Asset;
use App\Services\DataLoader\Testing\Data\Context;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

use function array_sum;

class AssetsSynchronizerData extends Data {
    protected function generateData(TestData $root, Context $context): bool {
        $results = [
            $this->kernel->call('ep:data-loader-assets-sync', [
                '--chunk'       => static::CHUNK,
                '--no-outdated' => true,
            ]),
            $this->kernel->call('ep:data-loader-asset-sync', [
                'id' => '00000000-0000-0000-0000-000000000000',
            ]),
        ];

        return array_sum($results) === Command::SUCCESS;
    }

    public function restore(TestData $root, Context $context): bool {
        $result = parent::restore($root, $context);

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
}
