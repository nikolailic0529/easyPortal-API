<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Loaders;

use App\Models\Asset;
use App\Models\Data\Oem;
use App\Models\Data\Type;
use App\Models\Document;
use App\Services\DataLoader\Testing\Data\AssetsData;
use App\Services\DataLoader\Testing\Data\Context;
use App\Utils\Console\CommandOptions;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

use function array_sum;

class CustomerLoaderData extends AssetsData {
    use CommandOptions;

    public const CUSTOMER  = '04e2ec4e-9bb1-47c7-98f0-6096cd178974';
    public const ASSETS    = false;
    public const ASSET     = null;
    public const DOCUMENTS = false;
    public const DOCUMENT  = null;

    protected function generateData(TestData $root, Context $context): bool {
        $results = [
            $this->kernel->call('ep:data-loader-customer-sync', $this->getOptions([
                'id'          => static::CUSTOMER,
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
    }

    public function restore(TestData $root, Context $context): bool {
        $result = parent::restore($root, $context);

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
                    'synced_at'   => Date::now(),
                ]);

                if (!Asset::query()->whereKey(static::ASSET)->exists()) {
                    Asset::factory()->create([
                        'id'                        => static::ASSET,
                        'reseller_id'               => null,
                        'customer_id'               => static::CUSTOMER,
                        'location_id'               => null,
                        'status_id'                 => null,
                        'type_id'                   => null,
                        'oem_id'                    => Oem::query()->first(),
                        'product_id'                => null,
                        'serial_number'             => null,
                        'nickname'                  => null,
                        'warranty_end'              => null,
                        'warranty_changed_at'       => null,
                        'warranty_service_group_id' => null,
                        'warranty_service_level_id' => null,
                        'contacts_count'            => 0,
                        'coverages_count'           => 0,
                        'data_quality'              => null,
                        'contracts_active_quantity' => null,
                        'changed_at'                => null,
                        'synced_at'                 => Date::now(),
                        'created_at'                => Date::now(),
                        'updated_at'                => Date::now(),
                        'deleted_at'                => null,
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
                    'synced_at'   => Date::now(),
                ]);

                if (!Document::query()->withTrashed()->whereKey(static::DOCUMENT)->exists()) {
                    Document::factory()->create([
                        'id'             => static::DOCUMENT,
                        'reseller_id'    => null,
                        'customer_id'    => static::CUSTOMER,
                        'type_id'        => Type::query()->first(),
                        'oem_id'         => Oem::query()->first(),
                        'oem_said'       => null,
                        'oem_group_id'   => null,
                        'entries_count'  => 0,
                        'contacts_count' => 0,
                        'statuses_count' => 0,
                        'number'         => null,
                        'start'          => null,
                        'end'            => null,
                        'price_origin'   => null,
                        'price'          => null,
                        'currency_id'    => null,
                        'language_id'    => null,
                        'oem_amp_id'     => null,
                        'oem_sar_number' => null,
                        'changed_at'     => null,
                        'synced_at'      => Date::now(),
                        'created_at'     => Date::now(),
                        'updated_at'     => Date::now(),
                        'deleted_at'     => null,
                    ]);
                }
            }
        });

        return $result;
    }
}
