<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\Oem;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Utils\Console\CommandOptions;
use Illuminate\Console\Command;

use function array_sum;

class ResellerLoaderCreateWithoutAssets extends AssetsData {
    use CommandOptions;

    public const RESELLER = '6bbb0d14-6854-4dbb-9a2c-a1292ccf2e9e';
    public const ASSETS   = false;
    public const ASSET    = null;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $results = [
                $this->kernel->call('ep:data-loader-update-reseller', $this->getOptions([
                    'id'       => [static::RESELLER],
                    '--assets' => static::ASSETS,
                    '--create' => true,
                ])),
            ];

            if (static::ASSET) {
                $results[] = $this->kernel->call('ep:data-loader-update-asset', $this->getOptions([
                    'id'          => static::ASSET,
                    '--create'    => true,
                    '--documents' => true,
                ]));
            }

            return array_sum($results) === Command::SUCCESS;
        });
    }

    /**
     * @inheritDoc
     */
    public function restore(string $path, array $context): bool {
        $result = parent::restore($path, $context);

        if (static::ASSET) {
            Asset::factory()->create([
                'id'          => static::ASSET,
                'reseller_id' => static::RESELLER,
                'customer_id' => null,
                'location_id' => null,
                'status_id'   => null,
                'type_id'     => null,
                'oem_id'      => Oem::query()->first(),
            ]);
        }

        return $result;
    }
}
