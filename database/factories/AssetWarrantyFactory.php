<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Reseller;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method AssetWarranty create($attributes = [], ?Model $parent = null)
 * @method AssetWarranty make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<AssetWarranty>
 */
class AssetWarrantyFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = AssetWarranty::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'               => $this->faker->uuid(),
            'asset_id'         => static function (): Asset {
                return Asset::factory()->create();
            },
            'type_id'          => null,
            'status_id'        => null,
            'document_id'      => null,
            'service_group_id' => null,
            'service_level_id' => null,
            'reseller_id'      => static function (): Reseller {
                return Reseller::factory()->create();
            },
            'customer_id'      => static function (): Customer {
                return Customer::factory()->create();
            },
            'start'            => $this->faker->randomElement([null, $this->faker->dateTime()]),
            'end'              => $this->faker->randomElement([null, $this->faker->dateTime()]),
            'description'      => null,
            'document_number'  => null,
            'created_at'       => Date::now(),
            'updated_at'       => Date::now(),
            'deleted_at'       => null,
        ];
    }
}
