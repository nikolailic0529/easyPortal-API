<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\Data\Oem;
use App\Models\Data\Type;
use App\Models\Document;
use App\Services\DataLoader\Exceptions\AssetNotFound;
use App\Services\DataLoader\Exceptions\DocumentNotFound;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Utils\Console\CommandOptions;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Console\Command;

use function array_sum;

class CustomerLoaderData extends AssetsData {
    use CommandOptions;

    public const CUSTOMER  = '04e2ec4e-9bb1-47c7-98f0-6096cd178974';
    public const ASSETS    = false;
    public const ASSET     = null;
    public const DOCUMENTS = false;
    public const DOCUMENT  = null;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $results = [
                $this->kernel->call('ep:data-loader-customer-sync', $this->getOptions([
                    'id'          => static::CUSTOMER,
                    '--documents' => static::DOCUMENTS,
                    '--assets'    => static::ASSETS,
                ])),
            ];

            if (static::ASSET) {
                try {
                    $this->kernel->call('ep:data-loader-asset-sync', $this->getOptions([
                        'id' => '00000000-0000-0000-0000-000000000000',
                    ]));
                } catch (AssetNotFound) {
                    // expected, we just need a dump
                }

                $results[] = $this->kernel->call('ep:data-loader-asset-sync', $this->getOptions([
                    'id' => static::ASSET,
                ]));
            }

            if (static::DOCUMENT) {
                try {
                    $this->kernel->call('ep:data-loader-document-sync', $this->getOptions([
                        'id' => '00000000-0000-0000-0000-000000000000',
                    ]));
                } catch (DocumentNotFound) {
                    // expected, we just need a dump
                }

                $results[] = $this->kernel->call('ep:data-loader-document-sync', $this->getOptions([
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
            if (static::ASSET) {
                Asset::factory()->create([
                    'id'          => '00000000-0000-0000-0000-000000000000',
                    'reseller_id' => null,
                    'customer_id' => static::CUSTOMER,
                    'oem_id'      => null,
                    'type_id'     => null,
                    'product_id'  => null,
                    'location_id' => null,
                    'status_id'   => null,
                ]);

                if (!Asset::query()->whereKey(static::ASSET)->exists()) {
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
            }

            if (static::DOCUMENT) {
                Document::factory()->create([
                    'id'          => '00000000-0000-0000-0000-000000000000',
                    'oem_id'      => null,
                    'type_id'     => null,
                    'reseller_id' => null,
                    'customer_id' => static::CUSTOMER,
                ]);

                if (!Document::query()->whereKey(static::DOCUMENT)->exists()) {
                    Document::factory()->create([
                        'id'          => static::DOCUMENT,
                        'reseller_id' => null,
                        'customer_id' => static::CUSTOMER,
                        'type_id'     => Type::query()->first(),
                        'oem_id'      => Oem::query()->first(),
                    ]);
                }
            }
        });

        return $result;
    }
}
