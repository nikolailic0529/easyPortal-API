<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Synchronizers;

use App\Models\Distributor;
use App\Services\DataLoader\Exceptions\DistributorNotFound;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

class DistributorsSynchronizerData extends Data {
    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-distributors-import', [
                '--limit' => static::LIMIT,
                '--chunk' => static::CHUNK,
            ]);
            $success = $result === Command::SUCCESS;

            try {
                $this->kernel->call('ep:data-loader-distributor-update', [
                    'id' => '00000000-0000-0000-0000-000000000000',
                ]);
            } catch (DistributorNotFound) {
                // expected, we just need a dump
            }

            return $success;
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
