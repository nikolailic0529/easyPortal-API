<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Oem;
use App\Models\Type;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Utils\Console\CommandOptions;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Console\Command;

use function array_sum;

class CustomerLoaderDataWithoutAssets extends AssetsData {
    use CommandOptions;

    public const CUSTOMER  = 'a0df13a5-c42c-4269-ae57-71085acb5319';
    public const ASSETS    = false;
    public const ASSET     = null;
    public const DOCUMENTS = false;
    public const DOCUMENT  = null;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $results = [
                $this->kernel->call('ep:data-loader-customer-update', $this->getOptions([
                    'id'          => static::CUSTOMER,
                    '--documents' => static::DOCUMENTS,
                    '--assets'    => static::ASSETS,
                ])),
            ];

            if (static::ASSET) {
                $results[] = $this->kernel->call('ep:data-loader-asset-update', $this->getOptions([
                    'id'          => static::ASSET,
                    '--documents' => true,
                ]));
            }

            if (static::DOCUMENT) {
                $results[] = $this->kernel->call('ep:data-loader-document-update', $this->getOptions([
                    'id' => static::DOCUMENT,
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

        GlobalScopes::callWithoutAll(static function (): void {
            if (static::ASSET && !Asset::query()->whereKey(static::ASSET)->exists()) {
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

            if (static::DOCUMENT && !Document::query()->whereKey(static::DOCUMENT)->exists()) {
                Document::factory()->create([
                    'id'          => static::DOCUMENT,
                    'reseller_id' => null,
                    'customer_id' => static::CUSTOMER,
                    'type_id'     => Type::query()->first(),
                    'oem_id'      => Oem::query()->first(),
                ]);
            }
        });

        return $result;
    }
}
