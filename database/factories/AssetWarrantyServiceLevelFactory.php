<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\AssetWarranty;
use App\Models\AssetWarrantyServiceLevel;
use App\Models\Data\ServiceLevel;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method AssetWarrantyServiceLevel create($attributes = [], ?Model $parent = null)
 * @method AssetWarrantyServiceLevel make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<AssetWarrantyServiceLevel>
 */
class AssetWarrantyServiceLevelFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = AssetWarrantyServiceLevel::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'                => $this->faker->uuid(),
            'asset_warranty_id' => AssetWarranty::factory(),
            'service_level_id'  => ServiceLevel::factory(),
            'created_at'        => Date::now(),
            'updated_at'        => Date::now(),
            'deleted_at'        => null,
        ];
    }
}
