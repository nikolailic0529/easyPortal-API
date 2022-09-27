<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Synchronizers;

use App\Models\Distributor;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

use function array_sum;

class DistributorsSynchronizerData extends Data {
    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $results = [
                $this->kernel->call('ep:data-loader-distributors-sync', [
                    '--chunk' => static::CHUNK,
                ]),
                $this->kernel->call('ep:data-loader-distributor-sync', [
                    'id' => '00000000-0000-0000-0000-000000000000',
                ]),
            ];

            return array_sum($results) === Command::SUCCESS;
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
}
