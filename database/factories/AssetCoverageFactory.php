<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetCoverage;
use App\Models\Data\Coverage;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method AssetCoverage create($attributes = [], ?Model $parent = null)
 * @method AssetCoverage make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<AssetCoverage>
 */
class AssetCoverageFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = AssetCoverage::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid(),
            'asset_id'    => Asset::factory(),
            'coverage_id' => Coverage::factory(),
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
