<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Synchronizers;

use App\Models\Distributor;
use App\Services\DataLoader\Testing\Data\Context;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

use function array_sum;

class DistributorsSynchronizerData extends Data {
    protected function generateData(TestData $root, Context $context): bool {
        $results = [
            $this->kernel->call('ep:data-loader-distributors-sync', [
                '--chunk' => static::CHUNK,
            ]),
            $this->kernel->call('ep:data-loader-distributor-sync', [
                'id' => '00000000-0000-0000-0000-000000000000',
            ]),
        ];

        return array_sum($results) === Command::SUCCESS;
    }

    public function restore(TestData $root, Context $context): bool {
        $result = parent::restore($root, $context);

        Distributor::factory()->create([
            'id'        => '00000000-0000-0000-0000-000000000000',
            'synced_at' => Date::now(),
        ]);

        return $result;
    }
}
