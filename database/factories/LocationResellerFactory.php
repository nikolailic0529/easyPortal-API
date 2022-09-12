<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Data\Location;
use App\Models\LocationReseller;
use App\Models\Reseller;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method LocationReseller create($attributes = [], ?Model $parent = null)
 * @method LocationReseller make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<LocationReseller>
 */
class LocationResellerFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = LocationReseller::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid(),
            'location_id' => Location::factory(),
            'reseller_id' => Reseller::factory(),
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
