<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Synchronizers;

use App\Models\Asset;
use App\Services\DataLoader\Exceptions\AssetNotFound;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

use function array_sum;

class AssetsSynchronizerData extends Data {
    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $results = [
                $this->kernel->call('ep:data-loader-assets-sync', [
                    '--chunk'       => static::CHUNK,
                    '--no-outdated' => true,
                ]),
            ];

            try {
                $this->kernel->call('ep:data-loader-asset-sync', [
                    'id' => '00000000-0000-0000-0000-000000000000',
                ]);
            } catch (AssetNotFound) {
                // expected, we just need a dump
            }

            return array_sum($results) === Command::SUCCESS;
        });
    }

    /**
     * @inheritDoc
     */
    public function restore(string $path, array $context): bool {
        $result = parent::restore($path, $context);

        Asset::factory()->create([
            'id' => '00000000-0000-0000-0000-000000000000',
        ]);

        return $result;
    }

}
