<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\Oem;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Utils\Console\CommandOptions;
use Illuminate\Console\Command;

use function array_sum;

class CustomerLoaderCreateWithoutAssets extends AssetsData {
    use CommandOptions;

    public const CUSTOMER = 'a0df13a5-c42c-4269-ae57-71085acb5319';
    public const ASSETS   = false;
    public const ASSET    = null;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $results = [
                $this->kernel->call('ep:data-loader-update-customer', $this->getOptions([
                    'id'       => [static::CUSTOMER],
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
                'reseller_id' => null,
                'customer_id' => static::CUSTOMER,
                'location_id' => null,
                'status_id'   => null,
                'type_id'     => null,
                'oem_id'      => Oem::query()->first(),
            ]);
        }

        return $result;
    }
}
