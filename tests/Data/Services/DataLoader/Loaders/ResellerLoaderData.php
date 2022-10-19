<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\Data\Oem;
use App\Models\Data\Type;
use App\Models\Document;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Utils\Console\CommandOptions;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Console\Command;

use function array_sum;

class ResellerLoaderData extends AssetsData {
    use CommandOptions;

    public const RESELLER  = 'c0d99925-8b3b-47d8-9db2-9d3a5f5520a2';
    public const ASSETS    = false;
    public const ASSET     = null;
    public const DOCUMENTS = false;
    public const DOCUMENT  = null;

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $results = [
                $this->kernel->call('ep:data-loader-reseller-sync', $this->getOptions([
                    'id'          => static::RESELLER,
                    '--documents' => static::DOCUMENTS,
                    '--assets'    => static::ASSETS,
                ])),
            ];

            if (static::ASSET) {
                $results[] = $this->kernel->call('ep:data-loader-asset-sync', $this->getOptions([
                    'id' => '00000000-0000-0000-0000-000000000000',
                ]));
                $results[] = $this->kernel->call('ep:data-loader-asset-sync', $this->getOptions([
                    'id' => static::ASSET,
                ]));
            }

            if (static::DOCUMENT) {
                $results[] = $this->kernel->call('ep:data-loader-document-sync', $this->getOptions([
                    'id' => '00000000-0000-0000-0000-000000000000',
                ]));
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
                    'reseller_id' => static::RESELLER,
                    'customer_id' => null,
                    'oem_id'      => null,
                    'type_id'     => null,
                    'product_id'  => null,
                    'location_id' => null,
                    'status_id'   => null,
                ]);

                if (!Asset::query()->whereKey(static::ASSET)->exists()) {
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
            }

            if (static::DOCUMENT) {
                Document::factory()->create([
                    'id'          => '00000000-0000-0000-0000-000000000000',
                    'oem_id'      => null,
                    'type_id'     => null,
                    'reseller_id' => static::RESELLER,
                    'customer_id' => null,
                ]);

                if (!Document::query()->withTrashed()->whereKey(static::DOCUMENT)->exists()) {
                    Document::factory()->create([
                        'id'          => static::DOCUMENT,
                        'reseller_id' => static::RESELLER,
                        'customer_id' => null,
                        'type_id'     => Type::query()->first(),
                        'oem_id'      => Oem::query()->first(),
                    ]);
                }
            }
        });

        return $result;
    }
}
