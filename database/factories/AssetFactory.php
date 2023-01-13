<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Asset create($attributes = [], ?Model $parent = null)
 * @method Asset make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Asset::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'                        => $this->faker->uuid(),
            'reseller_id'               => null,
            'oem_id'                    => null,
            'type_id'                   => null,
            'product_id'                => null,
            'customer_id'               => null,
            'location_id'               => null,
            'status_id'                 => null,
            'serial_number'             => $this->faker->uuid(),
            'nickname'                  => null,
            'warranty_end'              => null,
            'warranty_changed_at'       => null,
            'warranty_service_group_id' => null,
            'warranty_service_level_id' => null,
            'contacts_count'            => 0,
            'coverages_count'           => 0,
            'data_quality'              => null,
            'eosl'                      => null,
            'contracts_active_quantity' => null,
            'hash'                      => null,
            'changed_at'                => null,
            'synced_at'                 => null,
            'created_at'                => Date::now(),
            'updated_at'                => Date::now(),
            'deleted_at'                => null,
        ];
    }
}
