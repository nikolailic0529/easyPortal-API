<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Synchronizers;

use App\Models\Reseller;
use App\Services\DataLoader\Testing\Data\Context;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

use function array_sum;

class ResellersSynchronizerData extends Data {
    protected function generateData(TestData $root, Context $context): bool {
        $results = [
            $this->kernel->call('ep:data-loader-resellers-sync', [
                '--chunk'       => static::CHUNK,
                '--no-outdated' => true,
            ]),
            $this->kernel->call('ep:data-loader-reseller-sync', [
                'id' => '00000000-0000-0000-0000-000000000000',
            ]),
        ];

        return array_sum($results) === Command::SUCCESS;
    }

    public function restore(TestData $root, Context $context): bool {
        $result = parent::restore($root, $context);

        Reseller::factory()->create([
            'id'        => '00000000-0000-0000-0000-000000000000',
            'synced_at' => Date::now(),
        ]);

        return $result;
    }
}
